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

        //verify the response is not null
        $this->assertNotNull($response);
        //verify the response status is 200 Ok
        $this->assertEquals(200, $response->getStatusCode());

        //verify if the fields exist in the data response
        $this->verifyStructure($data);
    }

    /**
     * Test Response Fractal Collection
     */
    public function testResponseCollection()
    {
        $reportTable = ReportTable::where('ADD_TAB_TYPE', 'NORMAL')->get();

        $response = response()->collection($reportTable, new ReportTableTransformer());
        $data = json_decode($response->getContent(), true);

        //verify the response is not null
        $this->assertNotNull($response);
        //verify the response status is 200 Ok
        $this->assertEquals(200, $response->getStatusCode());

        //verify if the fields exist in the data response
        foreach ($data as $reportTableData) {
            $this->verifyStructure($reportTableData);
        }
    }

    /**
     * Test Response Fractal Paged
     */
    public function testResponsePaged()
    {
        $reportTable = ReportTable::where('ADD_TAB_TYPE', 'NORMAL')->paginate(4);

        $response = response()->paged($reportTable, new ReportTableTransformer());
        $data = json_decode($response->getContent(), true);

        //verify the response is not null
        $this->assertNotNull($response);
        //verify the response status is 200 Ok
        $this->assertEquals(200, $response->getStatusCode());

        //verify if the fields exist in the data response
        $this->assertInternalType('array', $data['data']);
        $this->assertArrayHasKey('start', $data);
        $this->assertArrayHasKey('limit', $data);
        $this->assertArrayHasKey('total', $data);
        foreach ($data['data'] as $reportTableData) {
            $this->verifyStructure($reportTableData);
        }
    }

    /**
     * Verify structure of response
     *
     * @param array $data
     */
    private function verifyStructure($data)
    {
        //verify if the fields exist in the data response
        $this->assertArrayHasKey('rep_tab_uid', $data);
        $this->assertArrayHasKey('rep_tab_name', $data);
        $this->assertArrayHasKey('rep_tab_description', $data);
        $this->assertArrayHasKey('rep_tab_plg_uid', $data);
        $this->assertArrayHasKey('rep_tab_connection', $data);
        $this->assertArrayHasKey('pro_uid', $data);
        $this->assertArrayHasKey('rep_tab_type', $data);
        $this->assertArrayHasKey('rep_tab_grid', $data);
        $this->assertArrayHasKey('rep_tab_tag', $data);

        $this->assertInternalType('array', $data['fields']);
    }

    /**
     * Populate table for test
     */
    private function createDataReportTable()
    {
        $reportTable = ReportTable::All()->toArray();
        if (count($reportTable) < 3) {
            factory(ReportTable::class, 10)->create();
        }
    }
}
