<?php

namespace ProcessMaker\Console\Commands;

use App;
use Log;
use Storage;
use Illuminate\Console\Command;
use ProcessMaker\Managers\IndexManager;
use ProcessMaker\Models\Setting;
use ProcessMaker\Traits\SupportsNonInteraction;

class IndexedSearchEnable extends Command
{
    use SupportsNonInteraction;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "
        indexed-search:enable
            {--driver= : Choose a search driver (either Elasticsearch or SQLite)}
            {--url=    : If Elasticsearch, the URL to the server (including protocol and port number)}
            {--prefix= : The prefix of your search indices (needed if using one Elasticache instance for multiple ProcessMaker 4 instances)}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable faster searches';

    private $manager;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->manager = App::make(IndexManager::class);
        $this->setCommandDescription();
        parent::__construct();
    }

    private function setCommandDescription()
    {
        $items = $this->manager->list()->pluck('name')->implode(', ');
        return $this->description = "{$this->description} of {$items}";
    }

    private function addToIndex($items)
    {
        try {
            $items->searchable();
        } catch (\PDOException $e) {
            Log::error('Encountered database lock when indexing records, trying again');
            sleep(1);
            $this->addToIndex($items);
        }
    }

    private function indexRecords()
    {
        foreach ($this->manager->list() as $index) {
            $this->line("\nIndexing {$index->name}...");
            if ($index->callback && is_callable($index->callback)) {
                call_user_func($index->callback);
                $this->info("All {$index->name} records have been imported.");
            } else {
                if ($index->model == 'ProcessMaker\Models\ProcessRequestToken') {
                    $query = $index->model::whereIn('element_type', ['task', 'userTask']);
                } else {
                    $query = $index->model::query();
                }

                $bar = $this->output->createProgressBar($query->count());
                $bar->start();

                $query->chunk(5, function($items) use(&$bar, &$count) {
                    $this->addToIndex($items);
                    foreach ($items as $item) {
                        $bar->advance();
                    }
                });
                
                $bar->finish();
                $this->info("\nAll {$index->name} records have been imported.");
            }
        }
    }

    private function setConfig($driver, $url = null, $prefix = null)
    {
        config(['filesystems.disks.install' => [
            'driver' => 'local',
            'root' => base_path()
        ]]);

        if (Storage::disk('install')->exists('.env')) {
            $env = Storage::disk('install')->get('.env');
        } else {
            $env = '';
        }

        $driver = mb_strtolower($driver);
        $prefix = mb_strtolower(preg_replace("/[^A-Za-z0-9]/", '', $prefix));

        if (empty($prefix)) {
            $prefix = null;
        } else {
            $prefix .=  '_';
        }

        switch ($driver) {
            case "elasticsearch":
                $driver = 'elastic';
                $env .= "\n\nSCOUT_DRIVER={$driver}";
                $env .= "\nELASTIC_HOST={$url}";
                break;
            case 'sqlite':
                $driver = 'tntsearch';
                $env .= "\n\nSCOUT_DRIVER={$driver}";
                break;
        }

        if ($prefix) {
            $env .= "\nSCOUT_PREFIX={$prefix}";
        }

        Storage::disk('install')->put('.env', $env);

        $this->callSilent('config:cache');

        config([
            'scout.driver' => $driver,
            'scout.prefix' => $prefix,
            'scout.queue' => false,
            'elastic.client.hosts' => [$url],
        ]);
    }

    private function retrieveOptions()
    {
        $driver = mb_strtolower($this->option('driver'));
        $url = $this->option('url');
        $prefix = $this->option('prefix');

        if (in_array($driver, ['elasticsearch', 'sqlite'])) {
            if ($driver === 'elasticsearch') {
                if (! $url) {
                    exit($this->error('No URL provided for Elasticsearch.'));
                }
            }
            $this->setConfig($driver, $url, $prefix);
        } else {
            exit($this->error('Invalid driver specified. Must be one of Elasticsearch or SQLite.'));
        }
    }

    private function promptForInput()
    {
        $url = null;
        $prefix = null;
        $driver = $this->choice('Which search driver would you like to use?', ['Elasticsearch', 'SQLite'], 0);
        if ($driver === 'Elasticsearch') {
            $url = $this->ask('What is the full URL to your Elasticsearch server (including protocol and port number)?');
            $prefix = $this->ask(
                "What unique prefix would you like to apply to your indices?\n " .
                "This is needed if using one Elasticache server for multiple ProcessMaker 4 instances.\n " .
                "Leave empty for none"
            );
        }
        $this->setConfig($driver, $url, $prefix);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->interactive()) {
            $confirmed = $this->confirm(
                "Running this command will index data from Requests, Tasks, and packages that support indexed search.\n " .
                "This may take a very long time and consume system resources on large datasets.\n\n " .
                "Are you sure you wish to proceed?"
            );
        } else {
            $confirmed = true;
        }

        if ($confirmed) {
            if ($this->option('driver')) {
                $this->retrieveOptions();
            } else {
                $this->promptForInput();
            }

            $setting = Setting::updateOrCreate(
                [ 'key' => 'indexed-search' ],
                [ 'config' => [ 'enabled' => true ], ]
            );

            $this->indexRecords();

            if ($setting->config['enabled'] === true) {
                $this->line("\nIndexed search has been enabled.");
            }
        }
    }
}
