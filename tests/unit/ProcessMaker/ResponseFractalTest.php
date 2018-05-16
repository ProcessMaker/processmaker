<?php

namespace Tests\Unit\ProcessMaker;

use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Serializer\ArraySerializer;
use ProcessMaker\Model\ReportTable;
use ProcessMaker\Transformers\ReportTableTransformer;
use Tests\TestCase;

class ResponseFractalTest extends TestCase
{

    /**
     * Test response Fractal item
     */
    public function testResponseItem(): void
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

        //Custom serializer
        $response = response()->item($reportTable, new ReportTableTransformer(), 200, [], new ArraySerializer());
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
    public function testResponseCollection(): void
    {
        $reportTable = ReportTable::where('type', 'NORMAL')->get();

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

        //Custom Serializer
        $response = response()->collection($reportTable, new ReportTableTransformer(), 200, [], new ArraySerializer());
        $data = json_decode($response->getContent(), true);

        //verify the response is not null
        $this->assertNotNull($response);
        //verify the response status is 200 Ok
        $this->assertEquals(200, $response->getStatusCode());

        //verify if the fields exist in the data response
        foreach ($data['data'] as $reportTableData) {
            $this->verifyStructure($reportTableData);
        }
    }

    /**
     * Test Response Fractal Paged
     */
    public function testResponsePaged(): void
    {
        $reportTable = ReportTable::where('type', 'NORMAL')->paginate(4);

        $reportTable->appends([
            'filter' => 'column1',
            'sort_by' => 'columna2',
            'sort_order' => 'column3'
        ]);

        $response = response()->paged($reportTable, new ReportTableTransformer());
        $data = json_decode($response->getContent(), true);

        //verify the response is not null
        $this->assertNotNull($response);
        //verify the response status is 200 Ok
        $this->assertEquals(200, $response->getStatusCode());

        //verify if the fields exist in the data response
        $this->assertInternalType('array', $data['data']);
        $this->assertInternalType('array', $data['meta']);
        $this->assertArrayHasKey('total', $data['meta']);
        $this->assertArrayHasKey('per_page', $data['meta']);
        $this->assertArrayHasKey('current_page', $data['meta']);
        $this->assertArrayHasKey('total_pages', $data['meta']);
        $this->assertArrayHasKey('filter', $data['meta']);
        $this->assertArrayHasKey('sort_by', $data['meta']);
        $this->assertArrayHasKey('sort_order', $data['meta']);
        foreach ($data['data'] as $reportTableData) {
            $this->verifyStructure($reportTableData);
        }

        //custom Serializer and Paginator
        $paginator = new IlluminatePaginatorAdapter(
            new LengthAwarePaginator($reportTable, 4, 2)
        );
        $response = response()->paged($reportTable, new ReportTableTransformer(), 200, [], new ArraySerializer(), $paginator);
        $data = json_decode($response->getContent(), true);

        //verify the response is not null
        $this->assertNotNull($response);
        //verify the response status is 200 Ok
        $this->assertEquals(200, $response->getStatusCode());

        //verify if the fields exist in the data response
        $this->assertInternalType('array', $data['data']);
        $this->assertInternalType('array', $data['meta']);
        $this->assertInternalType('array', $data['meta']['pagination']);
        $this->assertArrayHasKey('total', $data['meta']['pagination']);
        $this->assertArrayHasKey('count', $data['meta']['pagination']);
        $this->assertArrayHasKey('per_page', $data['meta']['pagination']);
        $this->assertArrayHasKey('current_page', $data['meta']['pagination']);
        $this->assertArrayHasKey('total_pages', $data['meta']['pagination']);
        $this->assertArrayHasKey('links', $data['meta']['pagination']);
        foreach ($data['data'] as $reportTableData) {
            $this->verifyStructure($reportTableData);
        }
    }

    /**
     * Verify structure of response
     *
     * @param array $data
     */
    private function verifyStructure($data): void
    {
        //verify if the fields exist in the data response
        $this->assertArrayHasKey('uid', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('process', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('grid', $data);
        $this->assertArrayHasKey('tag', $data);

        $this->assertInternalType('array', $data['fields']);
    }

    /**
     * Populate table for test
     */
    private function createDataReportTable(): void
    {
        $reportTable = ReportTable::All()->toArray();
        if (count($reportTable) < 3) {
            factory(ReportTable::class, 10)->create();
        }
    }
}
