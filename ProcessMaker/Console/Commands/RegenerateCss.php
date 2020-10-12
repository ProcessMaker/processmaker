<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Jobs\CompileSass;
use ProcessMaker\Models\Setting;
use ProcessMaker\PackageChecker;

class RegenerateCss extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:regenerate-css';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        $this->info("\nStarting CSS compiling...");

        $setting = Setting::byKey('css-override');

        if ($setting) {
            $this->writeColors(json_decode($setting->attributesToArray()['config']['variables'], true));
            $this->writeFonts(json_decode($setting->attributesToArray()['config']['sansSerifFont']));
        }

        // Compile the Sass files
        CompileSass::dispatch([
            'tag' => 'sidebar',
            'origin' => 'resources/sass/sidebar/sidebar.scss',
            'target' => 'public/css/sidebar.css',
            'user' => null
        ]);

        CompileSass::dispatch([
            'tag' => 'app',
            'origin' => 'resources/sass/app.scss',
            'target' => 'public/css/app.css',
            'user' => null
        ]);

        CompileSass::dispatch([
            'tag' => 'queues',
            'origin' => 'resources/sass/admin/queues.scss',
            'target' => 'public/css/admin/queues.css',
            'user' => null
        ]);

        $this->info("\nCSS files have been generated.");
    }


    /**
     * Write variables in file
     *
     * @param $request
     */
    private function writeColors($data)
    {
        // Now generate the _colors.scss file
        $contents = "// Changed theme colors\n";
        foreach ($data as $key => $value) {
            $contents .= $value['id'] . ': ' . $value['value'] . ";\n";
        }
        File::put(app()->resourcePath('sass') . '/_colors.scss', $contents);

    }

    /**
     * Write variables font in file
     *
     * @param $sansSerif
     * @param $serif
     */
    private function writeFonts($sansSerif)
    {
        $sansSerif = $sansSerif ? $sansSerif : $this->sansSerifFontDefault();
        // Generate the _fonts.scss file
        $contents = "// Changed theme fonts\n";
        $contents .= '$font-family-sans-serif: ' . $sansSerif->id . " !default;\n";
        File::put(app()->resourcePath('sass') . '/_fonts.scss', $contents);
    }


    private function sansSerifFontDefault()
    {
        $data = new \stdClass();
        $data->id = "'Open Sans'";
        $data->title = "'Open Sans'";
        return $data;
    }

}
