<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use ProcessMaker\Enums\ExporterMap;
use ProcessMaker\ImportExport\Exporter;
use ProcessMaker\ImportExport\Exporters\GroupExporter;
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
            case 'translations':
                if (empty($this->config)) {
                    $translations = Translatable::all();
                } else {
                    $translations = Translatable::whereIn('key', $ids)->get();
                }

                return $translations->map(function ($translation) {
                    return $this->exportHelper($translation, TranslatableExporter::class);
                });
            case 'auth_clients':
                if (empty($this->config)) {
                    $authClients = \Laravel\Passport\Client::where('revoked', false)->get();
                } else {
                    $authClients = \Laravel\Passport\Client::where('revoked', false)->whereIn('id', $ids)->get();
                }

                return $authClients->map(function ($authClient) {
                    $authClient->setting_type = $this->setting;

                    return $authClient;
                });
            case 'Log-In & Auth':
            case 'User Settings':
            case 'Email':
            case 'Integrations':
                $bundleSettings = Setting::whereIn('key', $ids)->get();

                return $bundleSettings->map(function ($bundleSetting) {
                    $bundleSetting->setting_type = $this->setting;

                    return $bundleSetting;
                });
        }

        return [];
    }

    public function exportHelper($model, $exporterClass, $options = null, $ignoreExplicitExport = true)
    {
        $exporter = new Exporter(false, $ignoreExplicitExport);
        $exporter->export($model, $exporterClass, $options);

        return $exporter->payload();
    }
}
