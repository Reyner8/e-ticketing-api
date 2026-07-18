<?php

namespace Tests\Feature;

use App\Enums\DowntimeComponentCategory;
use App\Enums\DowntimeImpact;
use App\Enums\DowntimeStatus;
use App\Enums\DowntimeType;
use App\Enums\UserRole;
use App\Models\DowntimeComponent;
use App\Models\DowntimeLocation;
use App\Models\DowntimeRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DowntimeMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create([
            'username' => 'staff_'.uniqid(),
            'role' => UserRole::ItStaff->value,
            'is_active' => true,
        ]);
    }

    private function reporter(): User
    {
        return User::factory()->create([
            'username' => 'reporter_'.uniqid(),
            'role' => UserRole::Reporter->value,
            'is_active' => true,
        ]);
    }

    public function test_it_staff_can_manage_locations_and_components(): void
    {
        Sanctum::actingAs($this->staff());

        $locationResponse = $this->postJson('/api/v1/downtime-locations', [
            'name' => 'ICU',
            'description' => 'Intensive care',
        ]);
        $locationResponse->assertCreated()
            ->assertJsonPath('data.name', 'ICU');

        $componentResponse = $this->postJson('/api/v1/downtime-components', [
            'name' => 'Internet Link',
            'category' => DowntimeComponentCategory::Network->value,
        ]);
        $componentResponse->assertCreated()
            ->assertJsonPath('data.category.value', DowntimeComponentCategory::Network->value);

        $this->getJson('/api/v1/downtime-locations')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/downtime-components')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_reporter_cannot_create_master_data(): void
    {
        Sanctum::actingAs($this->reporter());

        $this->postJson('/api/v1/downtime-locations', ['name' => 'Ward A'])
            ->assertForbidden();

        $this->postJson('/api/v1/downtime-components', [
            'name' => 'SIMRS',
            'category' => DowntimeComponentCategory::Application->value,
        ])->assertForbidden();
    }

    public function test_dependency_suggestions_and_multi_source_event_lifecycle(): void
    {
        Sanctum::actingAs($this->staff());

        $internet = DowntimeComponent::create([
            'code' => 'internet-test',
            'name' => 'Internet',
            'category' => DowntimeComponentCategory::Network,
            'is_active' => true,
        ]);
        $simrs = DowntimeComponent::create([
            'code' => 'simrs-test',
            'name' => 'SIMRS',
            'category' => DowntimeComponentCategory::Application,
            'is_active' => true,
        ]);
        $antrean = DowntimeComponent::create([
            'code' => 'antrean-test',
            'name' => 'Antrean',
            'category' => DowntimeComponentCategory::Application,
            'is_active' => true,
        ]);
        $internet->defaultAffectedComponents()->sync([$simrs->id, $antrean->id]);

        $location = DowntimeLocation::create([
            'code' => 'main-test',
            'name' => 'Gedung Utama',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/downtime-components/suggest-affected?source_component_ids='.$internet->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $create = $this->postJson('/api/v1/downtime-records', [
            'title' => 'Internet Outage',
            'type' => DowntimeType::Unplanned->value,
            'reason' => 'ISP down',
            'start_time' => '2026-07-18 08:00:00',
            'impact' => DowntimeImpact::High->value,
            'location_id' => $location->id,
            'source_component_ids' => [$internet->id],
            'affected_component_ids' => [$simrs->id, $antrean->id],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.status.value', DowntimeStatus::Ongoing->value)
            ->assertJsonPath('data.location.name', 'Gedung Utama')
            ->assertJsonCount(1, 'data.source_components')
            ->assertJsonCount(2, 'data.affected_components');

        $id = $create->json('data.id');

        $this->putJson("/api/v1/downtime-records/{$id}", [
            'title' => 'Internet Outage Updated',
            'source_component_ids' => [$internet->id],
            'affected_component_ids' => [$simrs->id],
        ])->assertOk()
            ->assertJsonCount(1, 'data.affected_components');

        $this->patchJson("/api/v1/downtime-records/{$id}/resolve", [
            'end_time' => '2026-07-18 07:00:00',
            'root_cause' => 'Too early',
            'preventive_measures' => 'N/A',
        ])->assertStatus(422);

        $this->patchJson("/api/v1/downtime-records/{$id}/resolve", [
            'end_time' => '2026-07-18 10:00:00',
            'root_cause' => 'Fiber cut',
            'preventive_measures' => 'Add backup link',
            'affected_users' => 120,
            'estimated_cost' => 500000,
        ])->assertOk()
            ->assertJsonPath('data.status.value', DowntimeStatus::Resolved->value)
            ->assertJsonPath('data.duration.minutes', 120);

        $this->assertDatabaseHas('downtime_records', [
            'id' => $id,
            'duration' => 120,
        ]);
    }

    public function test_analytics_and_export_include_structured_fields(): void
    {
        Sanctum::actingAs($this->staff());

        $source = DowntimeComponent::create([
            'code' => 'electricity-test',
            'name' => 'Electricity',
            'category' => DowntimeComponentCategory::Utility,
            'is_active' => true,
        ]);
        $affected = DowntimeComponent::create([
            'code' => 'server-test',
            'name' => 'Server Infra',
            'category' => DowntimeComponentCategory::Infrastructure,
            'is_active' => true,
        ]);
        $location = DowntimeLocation::create([
            'code' => 'server-room-test',
            'name' => 'Ruang Server',
            'is_active' => true,
        ]);

        $create = $this->postJson('/api/v1/downtime-records', [
            'title' => 'Power Loss',
            'type' => DowntimeType::Unplanned->value,
            'reason' => 'Blackout',
            'start_time' => now()->subHours(2)->format('Y-m-d H:i:s'),
            'end_time' => now()->subHour()->format('Y-m-d H:i:s'),
            'impact' => DowntimeImpact::Critical->value,
            'location_id' => $location->id,
            'source_component_ids' => [$source->id],
            'affected_component_ids' => [$affected->id],
            'estimated_cost' => 1000,
            'affected_users' => 50,
        ]);
        $create->assertCreated();

        $this->getJson('/api/v1/downtime-records/analytics?from_date='.now()->startOfMonth()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.summary.incident_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'summary',
                    'most_frequent_sources',
                    'most_affected_components',
                    'location_frequency',
                    'component_uptime',
                    'category_uptime',
                ],
            ]);

        $export = $this->get('/api/v1/exports/downtimes?format=csv');
        $export->assertOk();
        $body = $export->streamedContent();
        $this->assertStringContainsString('direct_sources', $body);
        $this->assertStringContainsString('Electricity', $body);
        $this->assertStringContainsString('Server Infra', $body);
        $this->assertStringContainsString('Ruang Server', $body);
    }

    public function test_referenced_component_cannot_be_hard_deleted(): void
    {
        Sanctum::actingAs($this->staff());

        $component = DowntimeComponent::create([
            'code' => 'ops-test',
            'name' => 'Ops Service',
            'category' => DowntimeComponentCategory::OperationalService,
            'is_active' => true,
        ]);

        $record = DowntimeRecord::create([
            'title' => 'Ops Down',
            'type' => DowntimeType::Unplanned,
            'reason' => 'Staff shortage',
            'start_time' => now()->subHour(),
            'impact' => DowntimeImpact::Medium,
            'reported_by' => User::factory()->create([
                'username' => 'reporter_ref_'.uniqid(),
                'role' => UserRole::ItStaff->value,
            ])->id,
            'status' => DowntimeStatus::Ongoing,
        ]);

        $record->recordComponents()->create([
            'component_id' => $component->id,
            'role' => 'source',
        ]);

        $this->deleteJson("/api/v1/downtime-components/{$component->id}")
            ->assertStatus(422);

        $this->patchJson("/api/v1/downtime-components/{$component->id}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
