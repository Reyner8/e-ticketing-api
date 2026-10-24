<?php

namespace Tests\Feature;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use App\Enums\SecurityAuditStatus;
use App\Enums\SecurityAuditType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
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

    private function auditPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Audit Hak Akses SIMRS Triwulan IV',
            'type' => SecurityAuditType::AccessRights->value,
            'audit_date' => '2026-10-24',
            'period' => 'Triwulan IV 2026',
            'scope' => 'SIMRS, RME',
            'summary' => 'Dari 47 akun aktif, ditemukan 3 akun mantan pegawai masih aktif.',
        ], $overrides);
    }

    public function test_it_staff_can_create_and_list_security_audits(): void
    {
        Sanctum::actingAs($this->staff());

        $response = $this->postJson('/api/v1/security-audits', $this->auditPayload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type.value', SecurityAuditType::AccessRights->value)
            ->assertJsonPath('data.status.value', SecurityAuditStatus::Planned->value)
            ->assertJsonPath('data.period', 'Triwulan IV 2026');

        $id = $response->json('data.id');
        $this->assertMatchesRegularExpression('/^AUD-\d{4}-\d{3}$/', $id);

        $this->getJson('/api/v1/security-audits')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.findings_count', 0)
            ->assertJsonPath('data.0.open_findings_count', 0);
    }

    public function test_reporter_can_read_but_cannot_write(): void
    {
        Sanctum::actingAs($this->staff());
        $id = $this->postJson('/api/v1/security-audits', $this->auditPayload())->json('data.id');

        Sanctum::actingAs($this->reporter());

        $this->getJson('/api/v1/security-audits')->assertOk();
        $this->getJson("/api/v1/security-audits/{$id}")->assertOk();

        $this->postJson('/api/v1/security-audits', $this->auditPayload())->assertForbidden();
        $this->putJson("/api/v1/security-audits/{$id}", ['title' => 'Ubah judul'])->assertForbidden();
        $this->deleteJson("/api/v1/security-audits/{$id}")->assertForbidden();
    }

    public function test_findings_lifecycle_open_to_completed(): void
    {
        Sanctum::actingAs($this->staff());
        $auditId = $this->postJson('/api/v1/security-audits', $this->auditPayload())->json('data.id');

        $finding = $this->postJson("/api/v1/security-audits/{$auditId}/findings", [
            'description' => 'Akun mantan pegawai masih aktif di SIMRS',
            'severity' => FindingSeverity::High->value,
            'recommendation' => 'Nonaktifkan akun dan tinjau SOP offboarding',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status.value', FindingStatus::Open->value)
            ->assertJsonPath('data.severity.value', FindingSeverity::High->value)
            ->json('data');

        $this->assertNull($finding['completed_at']);

        $updated = $this->putJson("/api/v1/security-audit-findings/{$finding['id']}", [
            'status' => FindingStatus::Completed->value,
            'corrective_action' => 'Akun dinonaktifkan pada 25-10-2026',
        ])->assertOk()
            ->assertJsonPath('data.status.value', FindingStatus::Completed->value);

        $this->assertNotNull($updated->json('data.completed_at'));

        // Membuka kembali temuan harus menghapus tanggal selesai.
        $this->putJson("/api/v1/security-audit-findings/{$finding['id']}", [
            'status' => FindingStatus::InProgress->value,
        ])->assertOk()
            ->assertJsonPath('data.status.value', FindingStatus::InProgress->value)
            ->assertJsonPath('data.completed_at', null);

        $this->getJson("/api/v1/security-audits/{$auditId}")
            ->assertOk()
            ->assertJsonCount(1, 'data.findings');
    }

    public function test_validation_rejects_invalid_type_and_missing_fields(): void
    {
        Sanctum::actingAs($this->staff());

        $this->postJson('/api/v1/security-audits', $this->auditPayload(['type' => 'bukan-audit']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        $this->postJson('/api/v1/security-audits', ['type' => SecurityAuditType::AccessRights->value])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'audit_date']);
    }
}
