<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Models\DataSource as dataSourceModel;
use ProcessMaker\Exception\DataSourceResponseException;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\DatasourceResponseNotification;
use Throwable;

class DataSource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
    private $user;

    /**
     * @var dataSourceModel
     */
    private $datasource;

    /**
     * @var array
     */
    private $data;

    private $index;
    /**
     * @var bool
     */
    private $immediate;


    /**
     * Create a new job instance.
     *
     * @param dataSourceModel $dataSource
     * @param User $user
     * @param array $data
     * @param $index
     * @param bool $immediate
     */
    public function __construct(dataSourceModel $dataSource, User $user, array $data, $index, $immediate = false)
    {
        $this->user = $user;
        $this->datasource = $dataSource;
        $this->data = $data;
        $this->index = $index;
        $this->immediate = $immediate;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags()
    {
        return ['data source', $this->datasource->name];
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     */
    public function handle()
    {
        try {
            $this->datasource->endpoints = [
                $this->data['purpose'] => $this->data
            ];
            if ($this->immediate) {
                return $this->datasource->request($this->data['data'], $this->data['config']);
            }
            return $this->sendResponse(200,
                $this->datasource->request($this->data['data'], $this->data['config'])
            );
        } catch (Throwable $exception) {
            $this->sendResponse(500, [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Send a response to the user interface
     *
     * @param int $status
     * @param array $response
     */
    private function sendResponse($status, array $response)
    {
        $this->user->notify(new DatasourceResponseNotification($response['status'], $response['response'], $this->index));
    }
}
