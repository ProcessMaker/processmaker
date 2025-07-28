<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ProcessMaker\Models\Setting;
use ProcessMaker\Models\User;

class CompileUI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    public function __construct(public ?int $user_id = null)
    {
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping())->releaseAfter(5)];
    }

    public function handle()
    {
        $setting = Setting::byKey('css-override');
        \Log::debug('*** Got setting: ' . json_encode($setting));
        if ($setting) {
            $this->writeColors(json_decode($setting->attributesToArray()['config']['variables'], true));
            $this->writeFonts(json_decode($setting->attributesToArray()['config']['sansSerifFont']));
        }
        $this->backupVariablesScss();
        try {
            $this->compileSass($this->user_id);
        } finally {
            $this->restoreVariablesScss();
        }
    }

    /**
     * Write variables in file
     *
     * @param $data
     */
    private function writeColors($data)
    {
        $tenant = app('currentTenant');
        $tenantId = $tenant ? $tenant->id : null;

        // Now generate the _colors.scss file
        $contents = "// Changed theme colors\n";
        foreach ($data as $key => $value) {
            $contents .= $value['id'] . ': ' . $value['value'] . ";\n";
        }
        if ($tenantId) {
            // Create tenant-specific colors file
            $tenantSassPath = app()->resourcePath('sass/tenant_' . $tenantId);

            if (!file_exists($tenantSassPath)) {
                mkdir($tenantSassPath, 0755, true);
            }

            File::put(app()->resourcePath('sass/tenant_' . $tenantId) . '/_colors.scss', $contents);
        } else {
            File::put(app()->resourcePath('sass') . '/_colors.scss', $contents);
        }
    }

    /**
     * Write variables font in file
     *
     * @param $sansSerif
     */
    private function writeFonts($sansSerif)
    {
        $tenant = app('currentTenant');
        $tenantId = $tenant ? $tenant->id : null;

        $sansSerif = $sansSerif ? $sansSerif : $this->sansSerifFontDefault();
        // Generate the _fonts.scss file
        $contents = "// Changed theme fonts\n";
        $contents .= '$font-family-sans-serif: ' . $sansSerif->id . " !default;\n";

        if ($tenantId) {
            // Create tenant-specific fonts file
            $tenantSassPath = app()->resourcePath('sass/tenant_' . $tenantId);

            if (!file_exists($tenantSassPath)) {
                mkdir($tenantSassPath, 0755, true);
            }

            File::put($tenantSassPath . '/_fonts.scss', $contents);
        } else {
            File::put(app()->resourcePath('sass') . '/_fonts.scss', $contents);
        }
    }

    private function backupVariablesScss()
    {
        $variablesScss = File::get(app()->resourcePath('sass/_variables.scss'));
        File::put(app()->resourcePath('sass/_variables.scss.backup'), $variablesScss);
    }

    private function restoreVariablesScss()
    {
        $variablesScss = File::get(app()->resourcePath('sass/_variables.scss.backup'));
        File::put(app()->resourcePath('sass/_variables.scss'), $variablesScss);
        File::delete(app()->resourcePath('sass/_variables.scss.backup'));
    }

    /**
     * run jobs compile
     */
    private function compileSass($userId)
    {
        $tenant = app('currentTenant');
        $tenantId = $tenant ? $tenant->id : null;

        if ($tenantId) {
            $variablesScss = File::get(app()->resourcePath('sass/_variables.scss'));
            foreach (['fonts', 'colors'] as $file) {
                if (File::exists(app()->resourcePath('sass/tenant_' . $tenantId . '/_' . $file . '.scss'))) {
                    $modifiedVariablesScss = str_replace('@import \'' . $file . '\';', '@import \'tenant_' . $tenantId . '/' . $file . '\';', $variablesScss);
                    if ($modifiedVariablesScss === $variablesScss) {
                        throw new \Exception('Could not find parts to modify. Did the _variables.scss file get corrupted?');
                    }
                    $variablesScss = $modifiedVariablesScss;
                }
            }
            File::put(app()->resourcePath('sass/_variables.scss'), $variablesScss);

            // Compile tenant-specific Sass files
            $this->run(new CompileSass([
                'tag' => 'sidebar',
                'origin' => 'resources/sass/sidebar/sidebar.scss',
                'target' => 'public/css/sidebar_tenant_' . $tenantId . '.css',
                'user' => $userId,
            ]));
            $this->updateTenantMixManifest($tenantId, 'sidebar');

            $this->run(new CompileSass([
                'tag' => 'app',
                'origin' => 'resources/sass/app.scss',
                'target' => 'public/css/app_tenant_' . $tenantId . '.css',
                'user' => $userId,
            ]));
            $this->updateTenantMixManifest($tenantId, 'app');

            $this->run(new CompileSass([
                'tag' => 'queues',
                'origin' => 'resources/sass/admin/queues.scss',
                'target' => 'public/css/admin/queues.css',
                'user' => $userId,
            ]));
        } else {
            $this->run(new CompileSass([
                'tag' => 'sidebar',
                'origin' => 'resources/sass/sidebar/sidebar.scss',
                'target' => 'public/css/sidebar.css',
                'user' => $userId,
            ]));
            $this->run(new CompileSass([
                'tag' => 'app',
                'origin' => 'resources/sass/app.scss',
                'target' => 'public/css/app.css',
                'user' => $userId,
            ]));
            $this->run(new CompileSass([
                'tag' => 'queues',
                'origin' => 'resources/sass/admin/queues.scss',
                'target' => 'public/css/admin/queues.css',
                'user' => $userId,
            ]));
        }
    }

    /**
     * Default Sans Serif Font
     *
     * @return \stdClass
     */
    private function sansSerifFontDefault()
    {
        $data = new \stdClass();
        $data->id = "'Open Sans'";
        $data->title = "'Open Sans'";

        return $data;
    }

    /**
     * Update tenant mix manifest
     *
     * @param $tenantId
     */
    private function updateTenantMixManifest($tenantId, $tag)
    {
        $manifestFile = public_path('mix-manifest.json');
        if (!file_exists($manifestFile)) {
            return;
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        if ($manifest === null) {
            return;
        }

        // Add tenant-specific app.css entry
        $guid = bin2hex(random_bytes(16));
        $tenantAppKey = "/css/{$tag}_tenant_{$tenantId}.css";
        $tenantAppValue = "/css/{$tag}_tenant_{$tenantId}.css?id={$guid}";

        $manifest[$tenantAppKey] = $tenantAppValue;

        $encodedManifest = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($manifestFile, $encodedManifest);

        // Cache the updated manifest
        Cache::put('mix-manifest', $manifest, now()->addHours(24));
    }

    private function run(CompileSass $compileSass)
    {
        return $compileSass->handle();
    }
}
