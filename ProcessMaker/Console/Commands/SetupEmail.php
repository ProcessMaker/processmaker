<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SetupEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bpm:setup-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup your email options';

    /**
     * The values for our .env to populate
     *
     * $var array
     */
    private $env = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
        //Configure the filesystem to be local
        config(['filesystems.disks.install' => [
            'driver' => 'local',
            'root' => base_path()
        ]]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Return error if no existing env file
        if (! Storage::disk('install')->exists('.env')) {
            $this->error(__("A .env file does not yet exist."));
            $this->error(__("Please run bpm:install prior to running this command."));
            return 255;
        }

        //Read our existing env file
        $this->readEnvFile();

        //Fetch from name & email from the user
        $this->fetchEmailFromInfo();
        
        //Explain and then fetch email credentials
        $this->info(__('ProcessMaker works with any SMTP server as well as several email APIs.'));
        $this->fetchEmailCredentials();

        //Update the env file with the new options
        $this->updateEnvFile();
        
        //Send completion message
        $this->info(__("Email setup is complete."));
        return true;
    }
    
    private function readEnvFile()
    {
        //Read the file
        $file = Storage::disk('install')->get('.env');
        
        //Match all dotenv lines
        preg_match_all("/(.+)=(.+)/", $file, $matches, PREG_SET_ORDER);
        
        //Add each match to our env array
        foreach ($matches as $match) {
            $this->env[$match[1]] = $match[2];
        }
    }
    
    private function updateEnvFile()
    {
        //Generate the .env file
        $contents = '';
        
        //Build out the file contents for our .env file
        foreach($this->env as $key => $value) {
            $contents .= $key . "=" . $value . "\n";
        }

        //Update the .env file
        Storage::disk('install')->put('.env', $contents);
    }
    
    private function fetchEmailCredentials()
    {
        //Present multiple choice list of email drivers
        $type = $this->choice(__('Which email driver would you like to use?'), ['SMTP', 'Mailgun', 'Sparkpost', 'Amazon SES', 'Mailtrap'], 0);
        $method = camel_case('setup' . $type);
        $this->{$method}();
    }
    
    private function fetchEmailFromInfo()
    {
        //Obtain from address and name from user
        $this->env['MAIL_FROM_ADDRESS'] = $this->ask(__("Enter the email address you'd like emails to come from"), 'admin@example.com');
        $this->env['MAIL_FROM_NAME'] = '"' . $this->ask(__("Enter the name you'd like emails to come from"), 'ProcessMaker') . '"';  
    }
    
    private function setupSMTP()
    {
        //Ask for SMTP credentials
        $this->env['MAIL_DRIVER'] = "smtp";
        $this->env['MAIL_HOST'] = $this->ask(__("Enter your SMTP host"));
        $this->env['MAIL_PORT'] = $this->anticipate(__("Enter your SMTP port"), [25, 465, 587, 2525]);
        $this->env['MAIL_USERNAME'] = $this->ask(__("Enter your SMTP username"));
        $this->env['MAIL_PASSWORD'] = $this->secret(__("Enter your SMTP password (input hidden)"));
    }

    private function setupMailgun()
    {
        //Ask for Mailgun credentials
        $this->env['MAIL_DRIVER'] = "mailgun";
        $this->env['MAILGUN_DOMAIN'] = $this->ask(__("Enter your Mailgun domain"));
        $this->env['MAILGUN_SECRET'] = $this->secret(__("Enter your Mailgun secret (input hidden)"));
        $this->env['MAILGUN_ENDPOINT'] = $this->ask(__("Enter your Mailgun endpoint"), 'api.mailgun.net');
    }

    private function setupAmazonSES()
    {
        //Warn about dependency
        $this->info(__("<comment>Please note that aws/aws-sdk-php must be installed for this driver to function.</comment>"));

        //Ask for SES credentials
        $this->env['MAIL_DRIVER'] = "ses";
        $this->env['SES_KEY'] = $this->ask(__("Enter your Amazon SES key"));
        $this->env['SES_SECRET'] = $this->secret(__("Enter your Amazon SES secret (input hidden)"));
        $this->env['SES_REGION'] = $this->ask(__("Enter your Amazon SES region"), 'us-east-1');
    }

    private function setupSparkpost()
    {
        //Ask for Sparkpost credentials
        $this->env['MAIL_DRIVER'] = "sparkpost";
        $this->env['SPARKPOST_SECRET'] = $this->secret(__("Enter your Sparkpost secret (input hidden)"));
    }
    
    private function setupMailtrap()
    {
        //Ask for Mailtrap credentials
        $this->env['MAIL_DRIVER'] = "smtp";
        $this->env['MAIL_HOST'] = 'smtp.mailtrap.io';
        $this->env['MAIL_PORT'] = 2525;
        $this->env['MAIL_USERNAME'] = $this->ask(__("Enter your Mailtrap inbox username"));
        $this->env['MAIL_PASSWORD'] = $this->secret(__("Enter your Mailtrap inbox password (input hidden)"));    
    }
}
