<?php

namespace Tests;

use ProcessMaker\Filters\Filter;
use ProcessMaker\Models\ProcessRequest;

class FilterTest extends TestCase
{
    private function filter($filterDefinition)
    {
        $query = ProcessRequest::query();
        Filter::filter($query, json_encode($filterDefinition));

        return $query->toRawSql();
    }

    public function testFormData()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Field', 'value' => 'data.form_input_1'],
                'operator' => '=',
                'value' => 'abc',
            ],
        ]);

        $this->assertEquals(
            "select * from `process_requests` where (json_unquote(json_extract(`data`, '\$.\"form_input_1\"')) = 'abc')",
            $sql
        );
    }

    public function testNestedOr()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Field', 'value' => 'name'],
                'operator' => '=',
                'value' => 'val1',
                'or' => [
                    [
                        'subject' => ['type' => 'Field', 'value' => 'name'],
                        'operator' => '=',
                        'value' => 'val2',
                        'or' => [
                            [
                                'subject' => ['type' => 'Field', 'value' => 'name'],
                                'operator' => '=',
                                'value' => 'val3',
                            ],
                        ],
                    ], [
                        'subject' => ['type' => 'Field', 'value' => 'name'],
                        'operator' => '=',
                        'value' => 'val4',
                    ],
                ],
            ],
            ['subject' => ['type' => 'Field', 'value' => 'name'],
                'operator' => '=',
                'value' => 'val5',
            ],
        ]);

        $this->assertEquals(
            "select * from `process_requests` where ((`name` = 'val1' or ((`name` = 'val2' or (`name` = 'val3')) and `name` = 'val4')) and `name` = 'val5')",
            $sql
        );
    }

    public function testAdditionalOperators()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Field', 'value' => 'foo'],
                'operator' => 'contains',
                'value' => 'abc',
                'or' => [
                    [
                        'subject' => ['type' => 'Field', 'value' => 'bar'],
                        'operator' => 'starts_with',
                        'value' => 'def',
                    ],
                ],
            ],
            [
                'subject' => ['type' => 'Field', 'value' => 'bat'],
                'operator' => 'between',
                'value' => [1, 10],
            ],
            [
                'subject' => ['type' => 'Field', 'value' => 'baz'],
                'operator' => 'in',
                'value' => [1, 2, 3],
            ],
        ]);

        $this->assertEquals(
            "select * from `process_requests` where ((`foo` like '%abc%' or (`bar` like 'def%')) and `bat` between 1 and 10 and `baz` in (1, 2, 3))",
            $sql
        );
    }

    public function testParticipants()
    {
        $sql = $this->filter([
            [
                'subject' => ['type' => 'Participants'],
                'operator' => 'in',
                'value' => [1, 2, 3],
                'or' => [
                    [
                        'subject' => ['type' => 'Participants'],
                        'operator' => '=',
                        'value' => 5,
                    ],
                ],
            ],
        ]);

        $this->assertEquals(
            "select * from `process_requests` where ((`id` in (select `process_request_id` from `process_request_tokens` where `element_type` in ('task', 'userTask', 'startEvent') and `user_id` in (1, 2, 3)) or (`id` in (select `process_request_id` from `process_request_tokens` where `element_type` in ('task', 'userTask', 'startEvent') and `user_id` = 5))))",
            $sql
        );
    }
}
