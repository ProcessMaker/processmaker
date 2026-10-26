<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Jobs\InstallPlugin;
use ProcessMaker\Jobs\TogglePlugin;
use ProcessMaker\Jobs\UninstallPlugin;
use ProcessMaker\Managers\PluginManager;

class PluginController extends Controller
{
    public function __construct(private PluginManager $manager)
    {
    }

    public function index(): JsonResponse
    {
        $plugins = $this->manager->list();

        return response()->json(['data' => $plugins]);
    }

    public function install(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required_without:zip|nullable|string',
            'zip' => 'required_without:url|nullable|file|mimes:zip',
            'branch' => 'prohibits:tag|nullable|string',
            'tag' => 'prohibits:branch|nullable|string',
        ]);

        $userId = $request->user()->id;

        if ($request->hasFile('zip')) {
            $tmpPath = $request->file('zip')->store('plugin-uploads', 'local');
            $fullPath = storage_path('app/' . $tmpPath);

            InstallPlugin::dispatch(
                userId: $userId,
                zipPath: $fullPath,
            );
        } else {
            InstallPlugin::dispatch(
                userId: $userId,
                url: $request->input('url'),
                branch: $request->input('branch'),
                tag: $request->input('tag'),
            );
        }

        return response()->json([
            'queued' => true,
            'message' => 'Plugin installation queued',
        ]);
    }

    public function destroy(string $name, Request $request): JsonResponse
    {
        UninstallPlugin::dispatch($name, $request->user()->id);

        return response()->json([
            'queued' => true,
            'message' => 'Plugin uninstallation queued',
        ]);
    }

    public function toggle(string $name, Request $request): JsonResponse
    {
        TogglePlugin::dispatch($name, $request->user()->id);

        return response()->json([
            'queued' => true,
            'message' => 'Plugin toggle queued',
        ]);
    }
}
