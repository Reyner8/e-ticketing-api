<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Faker\Factory as FakerFactory;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create('id_ID');

        // Observer ticket mencatat activity log dengan Auth::id();
        // saat seeding tidak ada sesi, jadi login sebagai user pertama.
        $firstUserId = User::query()->min('id');
        if ($firstUserId) {
            Auth::loginUsingId((int) $firstUserId);
        }

        $ticketIds = [];
        for ($i = 0; $i < 10; $i++) {
            // tickets.id string (format sama dengan TicketService::generateTicketId);
            // MySQL menolak insert tanpa id, dan firstOrCreate membuat
            // seeder aman dijalankan ulang.
            $id = sprintf('TKT-%s-%03d', now()->format('Y'), $i + 1);
            Ticket::firstOrCreate(['id' => $id], [
                'title' => $faker->sentence,
                'description' => $faker->paragraph,
                'category' => $faker->randomElement([
                    'general_report',
                    'software_bug',
                    'feature_request',
                    'network_issue',
                    'hardware_problem',
                    'system_error',
                    'performance_issue'
                ]),
                'priority' => $faker->randomElement(['low', 'medium', 'high', 'critical']),
                'status' => $faker->randomElement([
                    'draft',
                    'pending_approval',
                    'assigned',
                    'in_progress',
                    'waiting_for_user',
                    'resolved',
                    'closed',
                    'converted',
                ]),
                'reporter_id' => $faker->numberBetween(1, 10),
                'assigned_to_id' => $faker->numberBetween(1, 10),
                'assigned_team' => $faker->randomElement(['programmer', 'network', 'hardware']),
                'date_reported' => $faker->dateTimeBetween('-1 month', 'now'),
                'due_date' => $faker->dateTimeBetween('now', '+1 month'),
                'resolved_date' => $faker->dateTimeBetween('now', '+2 months'),
                'closed_date' => $faker->dateTimeBetween('now', '+3 months'),
                'sla_breached' => $faker->boolean,
                'response_time' => $faker->numberBetween(1, 100),
                'resolution_time' => $faker->numberBetween(1, 100),
                'estimated_effort' => $faker->numberBetween(1, 100),
                'actual_effort' => $faker->numberBetween(1, 100),
                'parent_ticket_id' => $ticketIds === [] ? null : $faker->optional()->randomElement($ticketIds),
                'converted_to_type' => $faker->optional()->randomElement(['error_report', 'feature_request']),
                'converted_to_id' => $faker->optional()->numberBetween(1, 10),
                'converted_at' => $faker->optional()->dateTimeBetween('-15 days', 'now'),
                'conversion_reason' => $faker->optional()->sentence,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ticketIds[] = $id;
        }

        Auth::logout();
    }
}
