<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ProcessMaker\Models\EnvironmentVariable;

class MetricsApiEnvironmentVariableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EnvironmentVariable::firstOrCreate(
            [
                'name' => 'METRICS_API_ENDPOINT',
            ],
            [
                'description' => 'API endpoint for retrieving process metrics example: /api/1.0/package-plg/processes/{process}/metrics',
                'value' => '/api/1.0/processes/{process}/metrics',
            ]
        );
    }
}
