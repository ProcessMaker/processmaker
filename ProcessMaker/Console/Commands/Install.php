<?php

namespace ProcessMaker\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Helper\Table;
use ProcessMaker\Traits\SupportsNonInteraction;
use UserSeeder;
use Validator;

/**
 * Install command handles installing a fresh copy of ProcessMaker.
 * If a .env file is found in the base_path(), then we will refuse to install.
 * Note: This is destructive to your database if you point to an existing database with tables.
 */
class Install extends Command
{
    use SupportsNonInteraction;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "
        processmaker:install
            {--app-debug                        : Enable system-wide debugging}
            {--url=                             : The URL of the application, without a trailing slash}
            {--username=                        : The default admin username}
            {--password=                        : The default admin password}
            {--email=                           : The default admin user's email address}
            {--first-name=                      : The default admin user's first name}
            {--last-name=                       : The default admin user's last name}
            {--telescope                        : Enable Telescope debugging}
            {--db-host=                         : The primary database host}
            {--db-port=                         : The primary database port}
            {--db-name=                         : The primary database name}
            {--db-username=                     : The primary database username}
            {--db-password=                     : The primary database password}
            {--data-driver=                     : The driver of the data database (none, mysql, pgsql, or sqlsrv)}
            {--data-host=                       : The data database host}
            {--data-port=                       : The data database port}
            {--data-name=                       : The data database name}
            {--data-username=                   : The data database username}
            {--data-password=                   : The data database password}
            {--data-schema=                     : The data database schema (if pgsql)}
            {--redis-client=predis              : The Redis client (predis or phpredis)}
            {--redis-prefix=                    : The prefix to be appended to Redis entries}
            {--horizon-prefix=horizon:          : The prefix to be appended to Horizon queue entries}
            {--broadcast-debug                  : Enable broadcast debugging}
            {--broadcast-driver=redis           : The broadcast driver (redis or pusher)}
            {--broadcast-host=                  : The Redis broadcast host}
            {--broadcast-key=                   : The Redis broadcast key}
            {--echo-host=                       : The Laravel Echo server host}
            {--echo-port=6001                   : The Laravel Echo server port}
            {--pusher-app-id=                   : The Pusher app ID}
            {--pusher-app-key=                  : The Pusher app key}
            {--pusher-app-secret=               : The Pusher app secret}
            {--pusher-cluster=                  : The Pusher cluster}
            {--pusher-tls                       : Enable TLS for pusher}
            {--p|pretend                        : Dump the env file variables that would be saved}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure ProcessMaker';

    /**
     * The values for our .env to populate
     *
     * $var array
     */
    private $env;

    /**
     * The encryption key we will use for for fresh install and any encryption during install
     */
    private $key;
    
    /**
     * Installs a fresh copy of ProcessMaker
     *
     * @return mixed If the command succeeds, true
     */
    public function handle()
    {        
        // Setup our initial encryption key and set our running laravel app key to it
        $this->key = 'base64:' . base64_encode(Encrypter::generateKey($this->laravel['config']['app.cipher']));
        config(['app.key' => $this->key]);

        // Our initial .env values
        $this->env = [
            'APP_DEBUG' => $this->option('app-debug') ? 'TRUE' : 'FALSE',
            'APP_NAME' => '"ProcessMaker"',
            'APP_ENV' => 'production',
            'APP_KEY' => $this->key,
            'APP_URL' => $this->option('url'),
            'BROADCAST_DRIVER' => $this->option('broadcast-driver'),
            'BROADCASTER_HOST' => null,
            'BROADCASTER_KEY' => $this->option('broadcast-key') ? $this->option('broadcast-key') : '21a795019957dde6bcd96142e05d4b10',
            'LARAVEL_ECHO_SERVER_AUTH_HOST' => null,
            'LARAVEL_ECHO_SERVER_PORT' => $this->option('echo-port'),
            'LARAVEL_ECHO_SERVER_DEBUG' => $this->option('broadcast-debug') ? 'TRUE' : 'FALSE',
            'PUSHER_APP_ID' => $this->option('pusher-app-id'),
            'PUSHER_APP_KEY' => $this->option('pusher-app-key'),
            'PUSHER_APP_SECRET' => $this->option('pusher-app-secret'),
            'PUSHER_CLUSTER' => $this->option('pusher-cluster'),
            'PUSHER_TLS' => $this->option('pusher-tls') ? 'TRUE' : 'FALSE',
            'PUSHER_DEBUG' => $this->option('broadcast-debug') ? 'TRUE' : 'FALSE',
            'APP_TIMEZONE' => 'UTC',
            'DATE_FORMAT' => '"m/d/Y H:i"',
            'MAIN_LOGO_PATH' => '"/img/processmaker_logo.png"',
            'ICON_PATH_PATH' => '"/img/processmaker_icon.png"',
            'LOGIN_LOGO_PATH' => '"img/processmaker_login.png"',
            'REDIS_CLIENT' => $this->option('redis-client'),
            'REDIS_PREFIX' => $this->option('redis-prefix'),
            'HORIZON_PREFIX' => $this->option('horizon-prefix'),
        ];

        // Configure the filesystem to be local
        config(['filesystems.disks.install' => [
            'driver' => 'local',
            'root' => base_path()
        ]]);

        $this->info("<fg=cyan;bold>" . __("ProcessMaker Installer") . "</>");

        // Determine if .env file exists or not
        // if exists, bail out with an error
        // If file does not exist, begin to generate it
        if (! $this->pretending() && Storage::disk('install')->exists('.env')) {
            $this->error(__('A .env file already exists. Stop the installation procedure, delete the existing .env file, and then restart the installation.'));
            $this->error(__('Remove the .env file to perform a new installation.'));
            return 255;
        }

        $this->info(__("This application installs a new version of ProcessMaker."));
        $this->info(__("You must have your database credentials available in order to continue."));
        if ($this->interactive() && !$this->confirm(__("Are you ready to begin?"))) {
            return;
        }
        $this->checkDependencies();
        do {
            $this->fetchDatabaseCredentials();
        } while (!$this->testLocalConnection());
        // Configure the DATA connection
        $this->infoIfInteractive(__('ProcessMaker requires a DATA database.'));
        
        if ($this->interactive()) {
            $dataConnection = $this->choice(__('Would you like to setup different credentials or use the same ProcessMaker 
            connection?'), ['different', 'same']);
        } else {
            if ($this->option('data-driver') !== 'none') {
                $dataConnection = 'different';
            } else {
                $dataConnection = 'same';
            }
        }
        if ($dataConnection === 'same') {
            $this->env['DATA_DB_DRIVER'] = 'mysql';
            $this->env['DATA_DB_HOST'] = $this->env['DB_HOSTNAME'];
            $this->env['DATA_DB_PORT'] = $this->env['DB_PORT'];
            $this->env['DATA_DB_DATABASE'] = $this->env['DB_DATABASE'];
            $this->env['DATA_DB_USERNAME'] = $this->env['DB_USERNAME'];
            $this->env['DATA_DB_PASSWORD'] = $this->env['DB_PASSWORD'];
            $this->env['DATA_DB_ENGINE'] = 'InnoDB';
        }
        do {
            $dataConnection !== 'different' ?: $this->fetchDataConnectionCredentials();
        } while (!$this->testDataConnection());
        
        if (! $this->pretending()) {
            $this->env['DATA_DB_DRIVER'] === 'sqlsrv' ? $this->checkDateFormatSqlServer() : null;            
        }
        
        // Ask for URL and validate
        $invalid = false;
        do {
            if ($invalid) {
                $this->errorOrExit(__('The URL you provided is invalid. Please provide the scheme, host and path without trailing slashes.'));
            }
            $this->env['APP_URL'] = $this->askOptional('url', __('What is the URL of this ProcessMaker installation? (Ex: https://processmaker.example.com, with no trailing slash)'));
        } while ($invalid = (!filter_var(
            $this->env['APP_URL'],
            FILTER_VALIDATE_URL
        )
            || ($this->env['APP_URL'][strlen($this->env['APP_URL']) - 1] == '/')));

        // Set telescope enabled
        $this->env['TELESCOPE_ENABLED'] = $this->confirmOptional('telescope', 'Would you like to enable Telescope debugging?') ? 'TRUE' : 'FALSE';

        // Set broadcaster url
        $this->env['BROADCASTER_HOST'] = $this->option('broadcast-host') ? $this->option('broadcast-host') : $this->env['APP_URL'] . ':6001';

        // Set laravel echo server settings
        $this->env['LARAVEL_ECHO_SERVER_AUTH_HOST'] = $this->option('echo-host') ? $this->option('echo-host') : $this->env['APP_URL'];
        
        // Set path and Docker settings
        Storage::disk('local')->makeDirectory('scripts');
        $this->env['PROCESSMAKER_SCRIPTS_HOME'] = storage_path('app/scripts');
        $this->env['DOCKER_HOST_URL'] = $this->env['APP_URL'];
        $this->env['HOME'] = base_path();
        
        if ($this->pretending()) {
            $headers = ['Key', 'Value'];
            $rows = [];
            foreach ($this->env as $key => $value) {
                $rows[] = [$key, $value];
            }
            $this->table($headers, $rows);
        } else {
            // Set it as our url in our config
            config(['app.url' => $this->env['APP_URL']]);

            $this->info(__("Installing ProcessMaker database, OAuth SSL keys and configuration file."));

            // The database should already exist and is tested by the fetchDatabaseCredentials call
            // Set the database default connection to install
            DB::reconnect();

            // Now generate the .env file
            $contents = '';
            // Build out the file contents for our .env file
            foreach ($this->env as $key => $value) {
                $contents .= $key . '=' . $value . "\n";
            }

            // Now store the env file
            Storage::disk('install')->put('.env', $contents);

            $this->call('config:cache');
            $this->call('config:clear');
            $this->call('cache:clear');

            // Set username, email, password
            $this->fetchUserInformation();

            $this->info(__("Installing database..."));

            // Install migrations
            $this->callSilent('migrate:fresh', [
                '--seed' => true,
            ]);

            $this->info(__("ProcessMaker database installed successfully."));

            // Generate passport secure keys and personal token oauth client
            $this->call('passport:install', [
                '--force' => true
            ]);

            //Create a symbolic link from "public/storage" to "storage/app/public"
            $this->call('storage:link');

            $this->call('vendor:publish', ['--tag'=>'telescope-assets', '--force' =>true]);            

            $this->info(__("ProcessMaker installation is complete. Please visit the URL in your browser to continue."));
            $this->info(__("Installer completed. Consult ProcessMaker documentation on how to configure email, jobs and notifications."));
        }

        return true;
    }

    /**
     * Configure the username, email, password of the admin user
     *
     */
    private function fetchUserInformation()
    {
        do {
            $username = $this->anticipateOptional('username', 'Enter the desired admin username', ['admin'], 'admin');
            $validator = $this->validateField('username', $username, ['required', 'alpha_dash', 'min:4', 'max:255']);
        } while($validator->fails());
        do {
            $email = $this->askOptional('email', 'Enter the email address of the admin user');
            $validator = $this->validateField('email', $email, ['required', 'email']);
        } while($validator->fails());
        do {
            $password = $this->secretOptional('password', 'Enter the desired admin password');
            $validator = $this->validateField('password', $password, ['required', 'sometimes', 'min:6']);
        } while($validator->fails());
        do {
            $confirmPassword = $this->secretOptional('password', 'Confirm the admin password');
            $match = $password === $confirmPassword;
            if (!$match) {
                $this->errorOrExit('Your password/confirm password fields do not match');
            }
        } while(!$match);
        do {
            $firstname = $this->anticipateOptional('first-name', 'Enter the first name of the admin user', ['Admin'], 'Admin');
            if (!$firstname) {
                $firstname = 'Admin';
            }
            $validator = $this->validateField('firstname', $firstname, ['max:50']);
        } while($validator->fails());
        do {
            $lastname = $this->anticipateOptional('last-name', 'Enter the last name of the admin user', ['User'], 'User');
            if (!$lastname) {
                $lastname = 'User';
            }
            $validator = $this->validateField('lastname', $lastname, ['max:50']);
        } while($validator->fails());
        // Set the default admin properties for UserSeeder
        UserSeeder::$INSTALLER_ADMIN_USERNAME = $username;
        UserSeeder::$INSTALLER_ADMIN_EMAIL = $email;
        UserSeeder::$INSTALLER_ADMIN_PASSWORD = $password;
        UserSeeder::$INSTALLER_ADMIN_FIRSTNAME = $firstname;
        UserSeeder::$INSTALLER_ADMIN_LASTNAME = $lastname;
    }

    /**
     * Validate a field value
     *
     * @param string $name
     * @param mixed $value
     * @param array $rules
     *
     * @return Validator
     */
    private function validateField($name, $value, $rules)
    {
        $validator = Validator::make([$name => $value], [
            $name => $rules,
        ]);
        foreach($validator->errors()->get($name) as $error) {
            $this->errorOrExit($error);
        }
        return $validator;
    }

    /**
     * The following checks for required extensions needed by ProcessMaker
     */
    private function checkDependencies()
    {
        $this->info(__('Dependencies Check'));
        $table = new Table($this->output);
        $table->setRows([
            [__('CType Extension'), phpversion('ctype')],
            [__('GD Extension'), phpversion('gd')],
            [__('JSON Extension'), phpversion('json')],
            [__('mbstring Extension'), phpversion('mbstring')],
            [__('OpenSSL Extension'), phpversion('openssl')],
            [__('PDO Extension'), phpversion('pdo')],
            [__('PDO MySQL Extension'), phpversion('pdo_mysql')],
            [__('PHP Version'), phpversion()],
            [__('Tokenizer Extension'), phpversion('tokenizer')],
            [__('XML Extension'), phpversion('xml')],
            [__('ZIP Extension'), phpversion('zip')],
        ]);
        $table->render();
        return true;
    }

    /**
     * Setup PROCESSMAKER connection
     *
     * @return void
     */
    private function fetchDatabaseCredentials()
    {
        $this->infoIfInteractive(__('ProcessMaker requires a MySQL database.'));
        $this->env['DB_HOSTNAME'] = $this->anticipateOptional('db-host', __('Enter your MySQL host'), ['127.0.0.1'], '127.0.0.1');
        $this->env['DB_PORT'] = $this->anticipateOptional('db-port', __('Enter your MySQL port (usually 3306)'), [3306], 3306);
        $this->env['DB_DATABASE'] = $this->anticipateOptional('db-name', __('Enter your MySQL database name'), ['processmaker'], 'processmaker');
        $this->env['DB_USERNAME'] = $this->askOptional('db-username', __('Enter your MySQL username'));
        $this->env['DB_PASSWORD'] = $this->secretOptional('db-password', __('Enter your MySQL password (input hidden)'));
    }

    /**
     * Setup DATA connection
     *
     * @return void
     */
    private function fetchDataConnectionCredentials()
    {
        $this->infoIfInteractive(__('Configure the DATA connection.'));
        $this->env['DATA_DB_DRIVER'] = $this->choiceOptional('data-driver', __('Enter the DB driver'), ['mysql', 'pgsql', 'sqlsrv']);
        
        switch($this->env['DATA_DB_DRIVER']) {
            case 'pgsql':
                $this->fetchPostgreCredentials();
                break;
            case 'sqlsrv':
                $this->fetchSqlServerCredentials();
                break;
            case 'mysql':
            default:
                $this->fetchMysqlCredentials();
                break;
        }
    }

    /**
     * Configure POSTGRE DATA connection
     *
     * @return void
     */
    private function fetchPostgreCredentials()
    {
        $this->env['DATA_DB_HOST'] = $this->anticipateOptional('data-host', __('Enter your DB host'), ['127.0.0.1'], '127.0.0.1');
        $this->env['DATA_DB_PORT'] = $this->anticipateOptional('data-port', __('Enter your DB port (usually 5432)'), [5432], 5432);
        $this->env['DATA_DB_DATABASE'] = $this->anticipateOptional('data-name', __('Enter your DB database name'), ['data'], 'data');
        $this->env['DATA_DB_USERNAME'] = $this->askOptional('data-username', __('Enter your DB username'));
        $this->env['DATA_DB_PASSWORD'] = $this->secretOptional('data-password', __('Enter your DB password (input hidden)'));
        $this->env['DATA_DB_SCHEMA'] = $this->anticipateOptional('data-schema', __('Enter your DB Schema'), ['public'], 'public');
    }

    /**
     * Setup MYSQL DATA connection
     *
     * @return void
     */
    private function fetchMysqlCredentials()
    {
        $this->env['DATA_DB_HOST'] = $this->anticipateOptional('data-host', __('Enter your DB host'), ['127.0.0.1']);
        $this->env['DATA_DB_PORT'] = $this->anticipateOptional('data-port', __('Enter your DB port (usually 3306)'), [3306], 3306);
        $this->env['DATA_DB_DATABASE'] = $this->anticipateOptional('data-name', __('Enter your DB database name'), ['data'], 'data');
        $this->env['DATA_DB_USERNAME'] = $this->askOptional('data-username', __('Enter your DB username'));
        $this->env['DATA_DB_PASSWORD'] = $this->secretOptional('data-password', __('Enter your DB password (input hidden)'));
        $this->env['DATA_DB_ENGINE'] = 'InnoDB';
    }

    /**
     * Setup SQLSRV DATA connection
     *
     * @return void
     */
    private function fetchSqlServerCredentials()
    {
        $this->env['DATA_DB_HOST'] = $this->anticipateOptional('data-host', __('Enter your DB host'), ['127.0.0.1']);
        $this->env['DATA_DB_PORT'] = $this->anticipateOptional('data-port', __('Enter your DB port (usually 1433)'), [1433], 1433);
        $this->env['DATA_DB_DATABASE'] = $this->anticipateOptional('data-name', __('Enter your DB database name'), ['data'], 'data');
        $this->env['DATA_DB_USERNAME'] = $this->askOptional('data-username', __('Enter your DB username'));
        $this->env['DATA_DB_PASSWORD'] = $this->secretOptional('data-password', __('Enter your DB password (input hidden)'));
    }

    /**
     * Check SQLSRV date format
     *
     * @return void
     */
    private function checkDateFormatSqlServer()
    {
        $sqlServerGrammar = new SqlServerGrammar;
        $format = $sqlServerGrammar->getDateFormat();
        $date = DB::connection('data')->select('select getdate() as date')[0]->date;
        if (substr($date, 19, 1) === '.') {
            substr($format, 11, 1) !== '.' ? $this->env['DATA_DB_DATE_FORMAT'] = '"Y-m-d H:i:s.v"' : '';
        } else {
            substr($format, 11, 1) === '.' ? $this->env['DATA_DB_DATE_FORMAT'] = '"Y-m-d H:i:s"' : '';
        }
    }

    /**
     * Test PROCESSMAKER connection
     *
     * @return void
     */
    private function testLocalConnection()
    {
        if (!isset($this->env['DB_DRIVER'])) {
            $this->env['DB_DRIVER'] = 'mysql';
        }
        // Setup Laravel Database Configuration
        config(['database.connections.processmaker' => [
            'driver' => $this->env['DB_DRIVER'],
            'host' => $this->env['DB_HOSTNAME'],
            'port' => $this->env['DB_PORT'],
            'database' => $this->env['DB_DATABASE'],
            'username' => $this->env['DB_USERNAME'],
            'password' => $this->env['DB_PASSWORD']
        ]]);
        // Attempt to connect
        if (! $this->pretending()) {
            try {
                DB::reconnect();
                $pdo = DB::connection('processmaker')->getPdo();
            } catch (Exception $e) {
                $this->errorOrExit(__('Failed to connect to MySQL database. Ensure the database exists. Check your credentials and try again.' . json_encode(config('database.connections.processmaker'))));
                return false;
            }
        }
        return true;
    }

    /**
     * Test DATA connection
     *
     * @return void
     */
    private function testDataConnection()
    {
        // Setup Laravel Database Configuration
        config(['database.connections.data' => [
            'driver' => $this->env['DATA_DB_DRIVER'],
            'host' => $this->env['DATA_DB_HOST'],
            'port' => $this->env['DATA_DB_PORT'],
            'database' => $this->env['DATA_DB_DATABASE'],
            'username' => $this->env['DATA_DB_USERNAME'],
            'password' => $this->env['DATA_DB_PASSWORD'],
            'schema' => isset($this->env['DATA_DB_SCHEMA']) ? $this->env['DATA_DB_SCHEMA'] : '',
            'engine' => isset($this->env['DATA_DB_ENGINE']) ? $this->env['DATA_DB_ENGINE'] : '',
        ]]);
        if (! $this->pretending()) {
            // Attempt to connect
            try {
                DB::connection('data')->reconnect();
                $pdo = DB::connection('data')->getPdo();
            } catch (Exception $e) {
                $this->errorOrExit(__('Failed to connect to DATA connection. Ensure the database exists. Check your credentials and try again. ' . json_encode(config('database.connections.data'))));
                return false;
            }
        }
        return true;
    }
}
