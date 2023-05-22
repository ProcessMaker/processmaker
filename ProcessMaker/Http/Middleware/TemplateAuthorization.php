<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TemplateAuthorization
{
    public function handle($request, Closure $next)
    {
        $templateType = $this->getTemplateType($request);
        if ($templateType) {
            $middlewares = $this->getMiddlewareForTemplateType($templateType);
            $request->route()->middleware($middlewares);

            if ($this->isImportRoute($request)) {
                return $this->handleImportRoute($request, $next, $templateType);
            }

            if ($this->isExportRoute($request)) {
                return $this->handleExportRoute($request, $next, $templateType);
            }
        }

        return $next($request);
    }

    protected function getTemplateType(Request $request)
    {
        $templateType = $request->route()->parameter('type');
        if ($templateType === 'process_templates') {
            return 'process';
        }

        return $templateType;
    }

    protected function getMiddlewareForTemplateType($templateType)
    {
        return [
            "can:view-{$templateType}-templates",
            "can:edit-{$templateType}-templates",
            "can:archive-{$templateType}-templates",
            "can:create-{$templateType}-templates",
            "can:import-{$templateType}-templates",
            "can:export-{$templateType}-templates",
        ];
    }

    protected function isImportRoute(Request $request)
    {
        return $request->route()->named('import.do_import') || $request->route()->named('processes.preimportValidation');
    }

    protected function isExportRoute(Request $request)
    {
        return $request->route()->named('export.download');
    }

    protected function handleImportRoute(Request $request, Closure $next, $templateType)
    {
        if ($request->user()->can("import-{$templateType}-templates")) {
            return $next($request);
        }

        if ($request->user()->can('import-processes')) {
            return $next($request);
        }

        // abort(403);
    }

    protected function handleExportRoute(Request $request, Closure $next, $templateType)
    {
        if ($request->user()->can("export-{$templateType}-templates")) {
            return $next($request);
        }

        if ($request->user()->can('export-processes')) {
            return $next($request);
        }

        // abort(403);
    }
}
