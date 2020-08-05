<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Traits\MakeHttpRequests;

class MultipleRequest extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'multiple:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $process = 2;
        $startEvent = 'node_1';
        $api = new CallEndpoint('admin', 'admin', $process, $startEvent);
        $requestCount = 10;
        $first = ProcessRequest::max('id') + 1;
        for ($i=0; $i < $requestCount; $i++) {
            $instance = $api->request([], ['endpoint' => 'start']);
        }
        $res = $api->request([], ['endpoint' => 'requests']);
        dump(['count' => ProcessRequest::count()]);
    }
}

class CallEndpoint
{
    use MakeHttpRequests;

    protected $authtype = 'OAUTH2_PASSWORD';
    protected $credentials = ['username' => 'admin', 'password' => 'admin'];
    protected $endpoints = [];

    public function __construct($username, $password, $processId, $eventId)
    {
        $this->endpoints['requests'] = [
            'method' => 'GET',
            'url' => url('/api/1.0/requests'),
            'body' => '',
            'body_type' => 'raw',
        ];
        $this->endpoints['start'] = [
            'method' => 'POST',
            'url' => url("/api/1.0/process_events/{$processId}?event={$eventId}"),
            'body' => '',
            'body_type' => 'raw',
        ];
        $this->credentials['username'] = $username;
        $this->credentials['password'] = $password;
        $this->credentials['url'] = url('/oauth/token');
        $this->credentials['client_id'] = 3;
        $this->credentials['client_secret'] = \DB::table('oauth_clients')
            ->where('id', $this->credentials['client_id'])->get()[0]->secret;
    }
}
