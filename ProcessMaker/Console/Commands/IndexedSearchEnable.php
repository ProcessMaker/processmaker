<?php

namespace ProcessMaker\Console\Commands;

use App;
use Log;
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
    protected $signature = 'indexed-search:enable';

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->interactive()) {
            $confirmed = $this->confirm(
                "Running this command will index data from Requests, Tasks, and packages that support indexed search. " .
                "This may take a very long time and consume system resources on large datasets.\n\n " .
                "Are you sure you wish to proceed?"
            );
        } else {
            $confirmed = true;
        }

        if ($confirmed) {
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

                    $query->chunk(50, function($items) use(&$bar, &$count) {
                        $this->addToIndex($items);
                        foreach ($items as $item) {
                            $bar->advance();
                        }
                    });
                    
                    $bar->finish();
                    $this->info("\nAll {$index->name} records have been imported.");
                }
            }

            $setting = Setting::updateOrCreate(
                [ 'key' => 'indexed-search' ],
                [ 'config' => [ 'enabled' => true ], ]
            );

            if ($setting->config['enabled'] === true) {
                $this->line("\nIndexed search has been enabled.");
            }
        }
    }
}
