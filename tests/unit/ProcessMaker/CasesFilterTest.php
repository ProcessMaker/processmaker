<?php

namespace Tests;

use ProcessMaker\Filters\CasesFilter;
use ProcessMaker\Models\CaseStarted;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Shared\RequestHelper;

class CasesFilterTest extends TestCase
{
    use RequestHelper;

    public function testRejectsSqlInjectionInJsonFieldName()
    {
        // CasesFilter inherits manuallyAddJsonWhere from BaseFilter, so the same
        // injection vector must be closed on this entry point too.
        $payload = 'data.x"\')) OR SLEEP(5) OR ((\'a';

        try {
            $query = CaseStarted::query();
            CasesFilter::filter($query, json_encode([
                [
                    'subject' => ['type' => 'Field', 'value' => $payload],
                    'operator' => '=',
                    'value' => '1',
                ],
            ]));
            $this->fail('Expected a 422 HttpException for an invalid filter field.');
        } catch (HttpException $e) {
            $this->assertEquals(422, $e->getStatusCode());
        }
    }

    public function testJsonFieldNameIsBoundNotInterpolated()
    {
        $query = CaseStarted::query();
        CasesFilter::filter($query, json_encode([
            [
                'subject' => ['type' => 'Field', 'value' => 'data.form_input_1'],
                'operator' => '=',
                'value' => 'abc',
            ],
        ]));

        // The JSON path must be a bound parameter, never interpolated into the SQL string.
        $this->assertStringContainsString('json_extract(`data`, ?)', $query->toSql());
        $this->assertContains('$."form_input_1"', $query->getBindings());
    }
}
