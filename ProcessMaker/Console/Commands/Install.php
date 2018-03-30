<?php
namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Exception;

use Illuminate\Support\Facades\DB;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bpm:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure ProcessMaker BPM';

    private $files;

    private $env;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("<fg=cyan;bold>" . __("ProcessMaker Installer") . "</>");
        // Determine if .env file exists or not
        // if exists, bail out with an error
        // If file does not exist, begin to generate it
        if($this->files->exists('.env')) {
            $this->error(__("A .env file already exists"));
            $this->error(__("Remove the .env file to perform a new installation"));
            return 255;
        }
        $this->info(__("This application installs a new version of ProcessMaker."));
        $this->info(__("You must have your database credentials available in order to continue."));
        $this->confirm(__("Are you ready to begin?"));
        $this->checkDependencies();
        do {
        $this->fetchDatabaseCredentials();
        } while(!$this->testDatabaseConnection());
        // Create database
        // Now install migrations
        $this->call('migrate:fresh', ['--seed']);
    }


    /**
     * The following checks for required extensions needed by ProcessMaker
     */
    private function checkDependencies()
    {
        $this->info(__("Dependencies Check"));
        $table = new Table($this->output);
        $table->setRows([
            [__('PHP Version'), phpversion()],
            [__('OpenSSL Extension'), phpversion('openssl')],
            [__('PDO Extension'), phpversion('pdo')],
            [__('PDO MySQL Extension'), phpversion('pdo_mysql')],
            [__('mbstring Extension'), phpversion('mbstring')],
            [__('Tokenizer Extension'), phpversion('tokenizer')],
            [__('XML Extension'), phpversion('xml')],
            [__('CType Extension'), phpversion('ctype')],
            [__('JSON Extension'), phpversion('json')],
            [__('GD Extension'), phpversion('gd')],
            [__('SOAP Extension'), phpversion('soap')]
        ]);
        $table->render();
        return true;
    }

    private function fetchDatabaseCredentials()
    {
        $this->env['DB_HOSTNAME'] = $this->ask(__("Enter your MySQL host (Default 'localhost')"));
        $this->env['DB_PORT'] = $this->anticipate(__("Enter your MySQL port (Default '3306')"), [3306]);
        $this->env['DB_DATABASE'] = $this->ask(__("Enter your MySQL Database name (Default 'workflow')"));
        $this->env['DB_USERNAME'] = $this->ask(__("Enter your MySQL Username"));
        $this->env['DB_PASSWORD'] = $this->secret(__("Enter your MySQL Password (Input hidden)"));
    }

    private function testDatabaseConnection()
    {
        // Setup Laravel Database Configuration
        config(['database.connections.workflow' => [
            'driver' => 'mysql',
            'host' => $this->env['DB_HOSTNAME'],
            'port' => $this->env['DB_PORT'],
            'database' => $this->env['DB_DATABASE'],
            'username' => $this->env['DB_USERNAME'],
            'password' => $this->env['DB_PASSWORD']
        ]]);
        //DB::connection('workflow')->reconnect();
        // Attempt to connect
        try {
            $pdo = DB::connection('workflow')->getPdo();
        } catch(Exception $e) {
            $this->error(__("Failed to connect to MySQL database. Check your credentails and try again."));
            $this->error($e);
            return false;
        }
        return true;
    }
}
