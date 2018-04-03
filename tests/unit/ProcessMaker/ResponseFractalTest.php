<?php

namespace Tests\Unit\ProcessMaker;

use ProcessMaker\Model\ReportTable;
use ProcessMaker\Transformers\ReportTableTransformer;
use Tests\TestCase;

class ResponseFractalTest extends TestCase
{

    /**
     * Test response Fractal item
     */
    public function testResponseItem()
    {
        $this->createDataReportTable();

        $reportTable = ReportTable::first();

        $response = response()->item($reportTable, new ReportTableTransformer());
        $data = json_decode($response->getContent(), true);

        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('rep_tab_name', $data);
        $this->assertArrayHasKey('rep_tab_type', $data);
        $this->assertArrayHasKey('fields', $data);
        $this->assertInternalType('array', $data['fields']);
    }

    /**
     * Test Response Fractal Collection
     */
    public function testResponseCollection()
    {
        $reportTable = ReportTable::where('ADD_TAB_TYPE', 'NORMAL')->get();

        $response = response()->collection($reportTable, new ReportTableTransformer());
        $data = json_decode($response->getContent(), true);
        $dataCount = count($data) - 1;

        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('rep_tab_name', $data[$dataCount]);
        $this->assertArrayHasKey('rep_tab_type', $data[$dataCount]);
        $this->assertArrayHasKey('fields', $data[$dataCount]);
        $this->assertInternalType('array', $data[$dataCount]['fields']);
    }

    /**
     * Test Response Fractal Paged
     */
    public function testResponsePaged()
    {
        $reportTable = ReportTable::where('ADD_TAB_TYPE', 'NORMAL')->paginate(4);

        $response = response()->paged($reportTable, new ReportTableTransformer());
        $data = json_decode($response->getContent(), true);
        $dataCount = count($data['data']) - 1;

        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInternalType('array', $data['data']);
        $this->assertArrayHasKey('start', $data);
        $this->assertArrayHasKey('limit', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('rep_tab_name', $data['data'][$dataCount]);
        $this->assertArrayHasKey('rep_tab_type', $data['data'][$dataCount]);
        $this->assertArrayHasKey('fields', $data['data'][$dataCount]);
        $this->assertInternalType('array', $data['data'][$dataCount]['fields']);
    }

    /**
     * Populate table for test
     */
    protected function createDataReportTable()
    {
        $reportTable = ReportTable::All()->toArray();
        if (count($reportTable) < 3) {
            factory(ReportTable::class, 10)->create();
        }
    }
}