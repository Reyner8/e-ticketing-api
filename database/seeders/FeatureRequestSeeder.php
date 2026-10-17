<?php

namespace Database\Seeders;

use App\Models\FeatureRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Faker\Factory as FakerFactory;

class FeatureRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create('id_ID');

        // Observer feature request mencatat activity log dengan Auth::id();
        // saat seeding tidak ada sesi, jadi login sebagai user pertama.
        $firstUserId = User::query()->min('id');
        if ($firstUserId) {
            Auth::loginUsingId((int) $firstUserId);
        }

        // feature_requests.id string (format sama dengan FeatureRequestService);
        // MySQL menolak insert tanpa id, dan firstOrCreate membuat
        // seeder aman dijalankan ulang. source_ticket_id harus id
        // ticket yang benar-benar ada (FK).
        $ticketIds = Ticket::query()->pluck('id')->all();
        for ($i=0; $i < 10; $i++) {
            $id = sprintf('FR-%s-%03d', now()->format('Y'), $i + 1);
            FeatureRequest::firstOrCreate(['id' => $id], [
                'title' => $faker->sentence,
                'description' => $faker->paragraph,
                'request_type' => $faker->randomElement(['feature_request', 'bug_fix']),
                'priority' => $faker->randomElement(['low', 'medium', 'high', 'critical']),
                'status' => $faker->randomElement([
                    'submission',
                    'pending_approval',
                    'approved',
                    'assigned',
                    'development',
                    'testing',
                    'validation',
                    'completed',
                    'post_implementation_review',
                    'rejected',
                    'cancelled',
                ]),
                'progress' => $faker->numberBetween(0, 100),
                'reporter_id' => $faker->numberBetween(1, 10),
                'assigned_to_id' => $faker->numberBetween(1, 10),
                'assigned_team' => $faker->randomElement(['programmer', 'network', 'hardware']),
                'due_date' => $faker->dateTimeBetween('now', '+1 month'),
                'sla_breached' => $faker->boolean,
                'approved_by' => $faker->numberBetween(1, 10),
                'rejection_reason' => $faker->optional()->sentence,
                'post_implementation_notes' => $faker->optional()->paragraph,
                'source_ticket_id' => $ticketIds === [] ? null : $faker->randomElement($ticketIds),
                'is_direct_input' => $faker->boolean,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Auth::logout();
    }
}
