<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Managers\PluginManager;
use RuntimeException;

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
            'url' => 'required_without:zip|nullable|string|url',
            'zip' => 'required_without:url|nullable|file|mimes:zip',
        ]);

        try {
            if ($request->hasFile('zip')) {
                $zipFile = $request->file('zip');
                $tmpPath = $zipFile->store('plugin-uploads', 'local');
                $fullPath = storage_path('app/' . $tmpPath);
                $this->manager->installFromZip($fullPath);
            } else {
                $this->manager->install($request->input('url'));
            }

            return response()->json(['message' => 'Plugin installed successfully']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(string $name): JsonResponse
    {
        try {
            $this->manager->uninstall($name);

            return response()->json(['message' => 'Plugin uninstalled successfully']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function toggle(string $name): JsonResponse
    {
        try {
            $enabled = $this->manager->toggle($name);

            return response()->json([
                'enabled' => $enabled,
                'message' => $enabled ? 'Plugin enabled' : 'Plugin disabled',
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
