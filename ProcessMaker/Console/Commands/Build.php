<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;

class Build extends Command
{
    private $package, $version, $previousVersion, $pre, $composer;
    
    private $path = '/Users/ryancooley/Sites/processmaker';
    
    private $packages = [
        [ 
            'slug' => 'core',
            'name' => 'Core',
            'branch' => 'develop',
        ],
        [ 
            'slug' => 'package-actions-by-email',
            'name' => 'Actions By Email',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-advancedforms',
            'name' => 'Advanced Forms',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-auth',
            'name' => 'Auth',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-auth-auth0',
            'name' => 'Auth: Auth 0',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-auth-saml',
            'name' => 'Auth: SAML',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-collections',
            'name' => 'Collections',
            'branch' => 'develop',
        ],
        [ 
            'slug' => 'package-comments',
            'name' => 'Comments',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'connector-docusign',
            'name' => 'Connector DocuSign',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'connector-pdf-print',
            'name' => 'Connector PDF Print',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'connector-send-email',
            'name' => 'Connector Send Email',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-conversational-forms',
            'name' => 'Conversational Forms',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-data-sources',
            'name' => 'Data Sources',
            'branch' => 'develop',
        ],
        [ 
            'slug' => 'package-dynamic-ui',
            'name' => 'Dynamic UI',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-files',
            'name' => 'Files',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-googleplaces',
            'name' => 'Google Places',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'nayra',
            'name' => 'Nayra',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-process-documenter',
            'name' => 'Process Documenter',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-process-optimization',
            'name' => 'Process Optimization',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-savedsearch',
            'name' => 'Saved Search',
            'branch' => 'develop',
        ],
        [ 
            'slug' => 'package-sentry',
            'name' => 'Sentry',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-signature',
            'name' => 'Signature',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'connector-slack',
            'name' => 'Slack Connector',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-translations',
            'name' => 'Translations',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-versions',
            'name' => 'Versions',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-vocabularies',
            'name' => 'Vocabularies',
            'branch' => 'master',
        ],
        [ 
            'slug' => 'package-webentry',
            'name' => 'Web Entry',
            'branch' => 'master',
        ],
    ];
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build
                            {--package=        : The name of the package}
                            {--version-number= : The next version number}
                            {--pre             : Whether this should be a pre-release version}
                            {--branch=         : The branch on which to base this release}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build ProcessMaker and packages';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
            
        $this->packages = collect($this->packages);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($package = $this->option('package')) {
            $this->package = $this->packages->where('slug', $package)->first();
            if (! $this->package) {
                return $this->error('The specified package does not exist.');
            }
        } else {
            $package = $this->choice('What do you wish to build?', $this->packages->pluck('name')->toArray());
            $this->package = $this->packages->where('name', $package)->first();
        }
        
        $this->switchBranches();
        
        $this->composer = json_decode(file_get_contents($this->getPath('composer.json')));
        $this->previousVersion = $this->composer->version;
        if ($version = $this->option('version-number')) {
            $this->version = $version;
        } else {
            $this->version = $this->ask("What version number would you like to use (current version is {$this->previousVersion})?");
        }
        
        $this->verifyVersionNumber();
        
        if ($pre = $this->option('pre')) {
            $this->pre = $pre;
        } else {
            if (! $this->option('version-number')) {
                    $this->pre = $this->confirm("Will this be a pre-release version?", true);
            }
        }
        
        $type = $this->pre ? 'pre-release' : 'full release';
        $confirm = $this->confirm("Create a new {$type} of {$this->package['name']} with version number {$this->getVersionNumber()}?");
        
        if (! $confirm) exit;
        
        if ($this->package['slug'] !== 'core') {
            $this->createBranch();
            $this->updateVersion();
            $this->gitCommitAndPush();
            $this->createRelease();
        }
    }
    
    private function switchBranches()
    {
        if (! $branch = $this->option('branch')) {
            $branch = $this->package['branch'];
        }
        
        $this->info("Switching to {$branch} branch and pulling any changes...");
        
        system("
            cd {$this->getPath()} &&
            git stash &&
            git checkout $branch &&
            git pull
        ");
    }
    
    private function verifyVersionNumber()
    {
        exec("
            cd {$this->getPath()} &&
            git branch -l
        ", $branches);
        
        exec("
            cd {$this->getPath()} &&
            git tag -l
        ", $tags);
        
        $versions = [
            $this->getVersionNumber(false),
            $this->getVersionNumber(true),
        ];
        
        foreach ($versions as $version) {
            if (in_array($version, $branches) || in_array($version, $tags)) {
                $this->error("This version already exists in the repo. Please try a different version number.");
                exit;
            }
        }
    }
    
    private function createBranch()
    {
        $this->info('Creating branch...');
        system("
            cd {$this->getPath()} &&
            git branch {$this->getVersionNumber(true)} &&
            git checkout {$this->getVersionNumber(true)}
        ");
    }
    
    private function updateVersion()
    {
        $this->info('Updating version number in composer.json...');
        
        $file = file_get_contents($this->getPath('composer.json'));
        $toReplace = '"version": "' . $this->previousVersion . '"';
        $replacement = '"version": "' . $this->getVersionNumber() . '"';
        $file = str_replace($toReplace, $replacement, $file);
        
        file_put_contents($this->getPath('composer.json'), $file);
    }
    
    private function gitCommitAndPush()
    {
        $this->info('Committing changes and pushing to GitHub repo...');
        system("
            cd {$this->getPath()} &&
            git add composer.json &&
            git commit -m \"Version {$this->getVersionNumber()}\" &&
            git tag {$this->getVersionNumber(true)} &&
            git push origin refs/heads/{$this->getVersionNumber(true)}:refs/heads/{$this->getVersionNumber(true)} &&
            git push origin refs/tags/{$this->getVersionNumber(true)}:refs/tags/{$this->getVersionNumber(true)}
        ");
    }
    
    private function createRelease()
    {
        $this->info('Creating release on GitHub...');
        system("
            cd {$this->getPath()} &&
            gh release create \"{$this->getVersionNumber(true)}\" -p --target \"{$this->getVersionNumber(true)}\" --title \"{$this->getVersionNumber(false)}\"
        ");
    }
    
    private function getPath($file = null)
    {
        $path = '';
        
        if ($this->package['slug'] == 'core') {
            $path = $this->path . '/core'; 
        } else {
            $path = $this->path . '/packages/' . $this->package['slug'];
        }
        
        if ($file) {
            return "$path/$file";
        } else {
            return $path;
        }
    }
    
    private function getVersionNumber($v = false)
    {
        $version = '';
        
        if ($v) {
            $version .= 'v';
        }
        
        $version .= mb_strtoupper($this->version);
        
        return $version;
    }
}
