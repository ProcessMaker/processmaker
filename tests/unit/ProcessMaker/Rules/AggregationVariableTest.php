<?php

namespace Tests\Unit\ProcessMaker\Rules;

use Illuminate\Support\Facades\Validator;
use ProcessMaker\Rules\AggregationVariable;
use Tests\TestCase;

class AggregationVariableTest extends TestCase
{
    public function testItAcceptsValidVariableNames(): void
    {
        foreach (['amount', 'var_amount', 'total_amount', 'grant.total', 'a1.b_2'] as $value) {
            $validator = Validator::make(['aggregation' => $value], ['aggregation' => [new AggregationVariable()]]);

            $this->assertFalse($validator->fails(), $value);
        }
    }

    public function testItRejectsInvalidVariableNames(): void
    {
        $payloads = [
            "amount')) AS x, (SELECT 1) AS y, SUM(CAST(JSON_EXTRACT(data, '\$.amount",
            "amount'",
            'amount"',
            'amount)',
            'my amount',
            '1amount',
            'a..b',
            "amount\n",
        ];

        foreach ($payloads as $payload) {
            $validator = Validator::make(['aggregation' => $payload], ['aggregation' => [new AggregationVariable()]]);

            $this->assertTrue($validator->fails(), $payload);
        }
    }

    public function testItRejectsNonStringValues(): void
    {
        foreach ([123, ['amount'], true] as $value) {
            $validator = Validator::make(['aggregation' => $value], ['aggregation' => [new AggregationVariable()]]);

            $this->assertTrue($validator->fails());
        }
    }
}
