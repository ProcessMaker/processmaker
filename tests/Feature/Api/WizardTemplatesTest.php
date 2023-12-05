<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\WizardTemplate;
use Tests\Feature\Shared\RequestHelper;
use Tests\TestCase;

class WizardTemplatesTest extends TestCase
{
    use RequestHelper;

    public function testGetWizardTemplates()
    {
        $total = 20;
        WizardTemplate::factory()->count($total)->create();

        $params = [
            'order_by' => 'id',
            'order_direction' => 'asc',
            'per_page' => 10,
        ];
        $route = route('api.wizard-templates.index', $params);
        $response = $this->apiCall('GET', $route);

        $response->assertStatus(200);
        $response->assertJsonCount(10, 'data');
        $response->assertJson([
            'meta' => [
                'per_page' => $params['per_page'],
                'total' => $total,
            ],
        ]);
    }
}
