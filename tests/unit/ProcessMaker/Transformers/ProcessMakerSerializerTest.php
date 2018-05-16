<?php

namespace Tests\Unit\ProcessMaker\Transformers;

use ProcessMaker\Transformers\ProcessMakerSerializer;
use League\Fractal\Manager;
use League\Fractal\Pagination\Cursor;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use ProcessMaker\Model\Process;
use Tests\TestCase;
use Tests\Unit\ProcessMaker\Transformers\TestTransformer;

class ProcessMakerSerializerTest extends TestCase
{
    /**
     * Fractal manager.
     *
     * @var Manager
     */
    private $manager;

    /**
     * Serializer.
     *
     * @var \League\Fractal\Serializer\SerializerAbstract
     */
    private $serializer;

    /**
     * Setup the unit test.
     *
     */
    protected function setUp()
    {
        parent::setUp();
        $this->setManager(new Manager());
        $this->serializer = new ProcessMakerSerializer();
        $this->getManager()->setSerializer($this->serializer);
    }

    /**
     * Test serialize one item
     *
     */
    public function testSerializeItem()
    {
        $process = factory(Process::class)->make();
        $expected = [
            'pro_uid' => $process->PRO_UID,
        ];
        $resource = new Item($process, function (Process $process) {
            return [
                'pro_uid' => $process->PRO_UID,
            ];
        }, 'item');
        $this->assertEquals($expected, $this->getManager()->createData($resource)->toArray());
    }

    /**
     * Test serialize a collection of items.
     *
     */
    public function testCollection()
    {
        $processes = [];
        $processes[] = factory(Process::class)->make();
        $processes[] = factory(Process::class)->make();
        $processes[] = factory(Process::class)->make();
        $expected = [];
        foreach ($processes as $process) {
            $expected[] = [
                'pro_uid' => $process->PRO_UID,
            ];
        }
        $resource = new Collection($processes, function (Process $process) {
            return [
                'pro_uid' => $process->PRO_UID,
            ];
        }, 'item');
        $this->assertEquals($expected, $this->getManager()->createData($resource)->toArray());
    }

    /**
     * Test serialize a collection of items with pagination:
     *  {
     *      meta: {
     *          total: (integer, total results)
     *          count: (integer, total items in current response)
     *          per_page: (integer, the number of items per page that this pagination is for)
     *          current_page: (integer, the current page, 1 indexed)
     *          total_pages: (integer, the total number of pages available, based on per_page and total)
     *          filter: (string, optional, if a filter was provided to search by, the value of the filter)
     *          sort_by: (string, optional, if a sort_by was requested, this provides the property names that was sorted by)
     *          sort_order: (string, optional, enum, if a sort was requested, this provides the sort order that was used)
     *      },
     *      data: [
     *          {...(specific object definition for the data type requested)...}
     *      ]
     *  }
     */
    public function testPaginator()
    {
        //Required to initialize serializer with parameter $paged=true
        $this->serializer = new ProcessMakerSerializer(true);
        $this->getManager()->setSerializer($this->serializer);
        
        factory(Process::class, 3)->create();
        $processPaginator = Process::paginate(2);
        $processes = $processPaginator->getCollection();
        $paginator = new IlluminatePaginatorAdapter($processPaginator);
        $resource = new Collection($processes, function (Process $process) {
            return [
                'pro_uid' => $process->PRO_UID,
            ];
        }, 'item');
        $resource->setPaginator($paginator);
        $response = $this->getManager()->createData($resource)->toArray();

        //validating format response
        //$this->assertGreaterThan(2, $response['meta']->total);
        $this->assertInternalType('array', $response['data']);
        $this->assertInstanceOf(\stdClass::class, $response['meta']);
        $this->assertObjectHasAttribute('total', $response['meta']);
        $this->assertObjectHasAttribute('per_page', $response['meta']);
        $this->assertObjectHasAttribute('current_page', $response['meta']);
        $this->assertObjectHasAttribute('total_pages', $response['meta']);
        $this->assertObjectHasAttribute('filter', $response['meta']);
        $this->assertObjectHasAttribute('sort_by', $response['meta']);
        $this->assertObjectHasAttribute('sort_order', $response['meta']);
    }

    /**
     * Test a null resource.
     *
     */
    public function testNullResource()
    {
        $resource = new NullResource();
        $expected = [];
        $this->assertEquals($expected, $this->getManager()->createData($resource)->toArray());
    }

    /**
     * Test include data.
     *
     */
    public function testIncludeData()
    {
        $process = factory(Process::class)->make();
        $expected = [
            'pro_uid' => $process->PRO_UID,
            'user' => [
                'usr_uid' => $process->USR_UID,
            ]
        ];
        $resource = new Item($process, new TestTransformer(), 'item');
        $this->getManager()->parseIncludes('user');
        $this->assertEquals($expected, $this->getManager()->createData($resource)->toArray());
    }

    /**
     * Test cursor.
     *
     */
    public function testCursor()
    {
        //Required to initialize serializer with parameter $paged=true
        $this->serializer = new ProcessMakerSerializer(true);
        $this->getManager()->setSerializer($this->serializer);

        factory(Process::class, 3)->create();

        $start = 1;
        $limit = 2;
        $processes = Process::offset($start)->limit($limit)->get();
        $cursor = new Cursor($start, null, null, $limit);
        $resource = new Collection($processes, function (Process $process) {
            return [
                'pro_uid' => $process->PRO_UID,
            ];
        });
        $resource->setCursor($cursor);
        $response = $this->getManager()->createData($resource)->toArray();
        $this->assertEquals($start, $response['start']);
        $this->assertEquals(count($response['data']), $response['limit']);
        $this->assertArrayNotHasKey('total', $response);
    }

    /**
     * Set the test manager.
     *
     * @param \League\Fractal\Manager $manager
     */
    private function setManager(Manager $manager)
    {
        $this->manager =$manager;
    }

    /**
     * Get the test manager.
     *
     * @return \League\Fractal\Manager
     */
    private function getManager()
    {
        return $this->manager;
    }
}
