<?php

namespace Database\Seeders;

use App\Models\ErrorReport;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;

class ErrorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create('id_ID');
        for ($i=0; $i < 10; $i++) {
            ErrorReport::create([
                'title' => $faker->sentence,
                'description' => $faker->paragraph,
                'category' => $faker->randomElement(['hardware', 'software', 'network']),
                'priority' => $faker->randomElement(['low', 'medium', 'high', 'critical']),
                'status' => $faker->randomElement(['pending_approval', 'in_progress', 'completed', 'overdue']),
                'reporter_id' => $faker->numberBetween(1, 10),
                'assigned_to_id' => $faker->numberBetween(1, 10),
                'assigned_team' => $faker->randomElement(['programmer', 'network', 'hardware']),
                'date_reported' => $faker->dateTimeBetween('-1 month', 'now'),
                'start_date' => $faker->dateTimeBetween('-15 days', 'now'),
                'due_date' => $faker->dateTimeBetween('now', '+1 month'),
                'completion_date' => $faker->dateTimeBetween('now', '+2 months'),
                'sla_breached' => $faker->boolean,
                'source_ticket_id' => $faker->numberBetween(1, 10),
                'is_direct_input' => $faker->boolean,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }
    }
}
