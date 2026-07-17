<?php

namespace Database\Seeders;

use App\Models\FeatureRequest;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;

class FeatureRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create('id_ID');
        for ($i=0; $i < 10; $i++) {
            FeatureRequest::create([
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
                'source_ticket_id' => $faker->numberBetween(1, 10),
                'is_direct_input' => $faker->boolean,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
