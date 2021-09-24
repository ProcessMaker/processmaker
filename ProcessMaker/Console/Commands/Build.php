<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;

class Build extends Command
{
    private $package, $version, $previousVersion, $pre, $meta;
    
    private $path = '/Users/ryancooley/Sites/processmaker';
    
    private $nodeBin = '/Users/ryancooley/.nvm/versions/node/v14.17.6/bin/node';
    
    private $npmBin = '/Users/ryancooley/.nvm/versions/node/v14.17.6/bin/npm';
    
    private $packages = [
        [ 
            'slug' => 'core',
            'name' => 'Core',
            'branch' => 'develop',
            'type' => 'php',
        ],
        [
            'slug' => 'bpmnlint-plugin',
            'name' => 'BMPN Lint Plugin',
            'branch' => 'master',
            'type' => 'js',
        ],
        [
            'slug' => 'modeler',
            'name' => 'Modeler',
            'branch' => 'develop',
            'type' => 'js',
        ],
        [
            'slug' => 'screen-builder',
            'name' => 'Screen Builder',
            'branch' => 'develop',
            'type' => 'js',
        ],
        [
            'slug' => 'vue-form-elements',
            'name' => 'Vue Form Elements',
            'branch' => 'master',
            'type' => 'js',
        ],
        [ 
            'slug' => 'package-actions-by-email',
            'name' => 'Actions By Email',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-advancedforms',
            'name' => 'Advanced Forms',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-auth',
            'name' => 'Auth',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-auth-auth0',
            'name' => 'Auth: Auth 0',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-auth-saml',
            'name' => 'Auth: SAML',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-collections',
            'name' => 'Collections',
            'branch' => 'develop',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-comments',
            'name' => 'Comments',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'connector-docusign',
            'name' => 'Connector DocuSign',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'connector-pdf-print',
            'name' => 'Connector PDF Print',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'connector-send-email',
            'name' => 'Connector Send Email',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-conversational-forms',
            'name' => 'Conversational Forms',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-data-sources',
            'name' => 'Data Sources',
            'branch' => 'develop',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-dynamic-ui',
            'name' => 'Dynamic UI',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-files',
            'name' => 'Files',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-googleplaces',
            'name' => 'Google Places',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'nayra',
            'name' => 'Nayra',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-process-documenter',
            'name' => 'Process Documenter',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-process-optimization',
            'name' => 'Process Optimization',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-savedsearch',
            'name' => 'Saved Search',
            'branch' => 'develop',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-sentry',
            'name' => 'Sentry',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-signature',
            'name' => 'Signature',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'connector-slack',
            'name' => 'Slack Connector',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-translations',
            'name' => 'Translations',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-versions',
            'name' => 'Versions',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-vocabularies',
            'name' => 'Vocabularies',
            'branch' => 'master',
            'type' => 'php',
        ],
        [ 
            'slug' => 'package-webentry',
            'name' => 'Web Entry',
            'branch' => 'master',
            'type' => 'php',
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
        
        switch ($this->package['type']) {
            case 'php':
                return $this->handlePhp();
            case 'js':
                return $this->handleJs();
        }
    }
    
    private function askVersion()
    {
        $this->previousVersion = $this->meta->version;
        if ($version = $this->option('version-number')) {
            $this->version = $version;
        } else {
            $this->version = $this->ask("What version number would you like to use (current version is {$this->previousVersion})?");
        }
    }
    
    private function askPre()
    {
        if ($pre = $this->option('pre')) {
            $this->pre = $pre;
        } else {
            if (! $this->option('version-number')) {
                    $this->pre = $this->confirm("Will this be a pre-release version?", true);
            }
        }
    }
    
    private function confirmBuild()
    {
        $type = $this->pre ? 'pre-release' : 'full release';
        $confirm = $this->confirm("Create a new {$type} of {$this->package['name']} with version number {$this->getVersionNumber()}?");
        
        if (! $confirm) exit;
    }
    
    private function handleJs()
    {
        $this->meta = json_decode(file_get_contents($this->getPath('package.json')));
        
        $this->askVersion();
        
        $this->verifyVersionNumber();
        
        $this->pre = false;
        
        $this->confirmBuild();
        
        $this->clearNodeModules();
        
        $this->createJsBuild();
    }
    
    private function handlePhp()
    {
        $this->meta = json_decode(file_get_contents($this->getPath('composer.json')));
        
        $this->askVersion();
        
        $this->verifyVersionNumber();
        
        $this->askPre();
        
        $this->confirmBuild();
        
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
            git pull --ff-only
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

    private function clearNodeModules()
    {
        $this->info('Clearing Node modules directory...');
        system("
            cd {$this->getPath()} &&
            rm -rf node_modules
        ", $code);
        if ($code !== 0) {
            $this->clearNodeModules();
        }
    }

    private function createJsBuild()
    {
        $this->info('Creating new NPM release...');
        system("
            cd {$this->getPath()} &&
            {$this->npmBin} install &&
            {$this->npmBin} version {$this->getVersionNumber(false)} &&
            git push &&
            git push origin refs/tags/{$this->getVersionNumber(true)}:refs/tags/{$this->getVersionNumber(true)}
        ");
        
        if ($this->package['slug'] !== 'bpmnlint-plugin') {
            system("
                cd {$this->getPath()} &&
                {$this->npmBin} run build-bundle &&
                {$this->npmBin} publish
            ");
        } else {
            system("
                cd {$this->getPath()} &&
                {$this->npmBin} publish
            ");
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
