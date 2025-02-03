<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Enums\ExporterMap;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\GroupExporter;
use ProcessMaker\ImportExport\Exporters\MediaExporter;
use ProcessMaker\ImportExport\Exporters\ScriptExecutorExporter;
use ProcessMaker\ImportExport\Exporters\UserExporter;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Package\PackageDynamicUI\ImportExport\DashboardExporter;
use ProcessMaker\Package\PackageDynamicUI\ImportExport\MenuExporter;
use ProcessMaker\Package\PackageDynamicUI\Models\Dashboard;
use ProcessMaker\Package\PackageDynamicUI\Models\Menu;
use ProcessMaker\Package\Translations\ImportExport\TranslatableExporter;
use ProcessMaker\Package\Translations\Models\Translatable;

class BundleSetting extends ProcessMakerModel
{
    use HasFactory;

    protected $fillable = [
        'bundle_id',
        'setting',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    public function export()
    {
        $configData = json_decode($this->config, true);
        $ids = $configData['id'] ?? [];

        switch ($this->setting) {
            case 'users':
                if (empty($this->config)) {
                    $users = User::all();
                } else {
                    $users = User::whereIn('id', $ids)->get();
                }

                return $users->map(function ($user) {
                    return $this->exportHelper($user, UserExporter::class);
                });
            case 'groups':
                if (empty($this->config)) {
                    $groups = Group::all();
                } else {
                    $groups = Group::whereIn('id', $ids)->get();
                }

                return $groups->map(function ($group) {
                    return $this->exportHelper($group, GroupExporter::class);
                });
            case 'script_executors':
                if (empty($this->config)) {
                    $scriptExecutors = ScriptExecutor::all();
                } else {
                    $scriptExecutors = ScriptExecutor::whereIn('id', $ids)->get();
                }

                return $scriptExecutors->map(function ($scriptExecutor) {
                    return $this->exportHelper($scriptExecutor, ScriptExecutorExporter::class);
                });
            case 'ui_dashboards':
                if (empty($this->config)) {
                    $uiDashboards = Dashboard::all();
                } else {
                    $uiDashboards = Dashboard::whereIn('id', $ids)->get();
                }

                return $uiDashboards->map(function ($uiDashboard) {
                    return $this->exportHelper($uiDashboard, DashboardExporter::class);
                });
            case 'ui_menus':
                if (empty($this->config)) {
                    $uiMenus = Menu::all();
                } else {
                    $uiMenus = Menu::whereIn('id', $ids)->get();
                }

                return $uiMenus->map(function ($uiMenu) {
                    return $this->exportHelper($uiMenu, MenuExporter::class);
                });
            case 'public_files':
                if (empty($this->config)) {
                    $publicFiles = Media::all()->filter(function ($media) {
                        return $media->isPublicFile();
                    });
                } else {
                    $publicFiles = Media::whereIn('id', $ids)->get();
                }

                return $publicFiles->map(function ($publicFile) {
                    return $this->exportHelper($publicFile, MediaExporter::class);
                });
            case 'Log-In & Auth':
            case 'User Settings':
            case 'Email':
            case 'ui_settings':
            case 'Integrations':
                $bundleSettings = Setting::whereIn('key', $ids)->get();

                return $bundleSettings->map(function ($bundleSetting) {
                    $bundleSetting->setting_type = $this->setting;

                    return $bundleSetting;
                });
        }
    }

    public function exportHelper($model, $exporterClass, $options = null, $ignoreExplicitExport = true)
    {
        $exporter = new Exporter(false, $ignoreExplicitExport);
        $exporter->export($model, $exporterClass, $options);

        return $exporter->payload();
    }
}
