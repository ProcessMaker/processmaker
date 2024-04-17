<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ProcessMaker\Models\ScreenTemplates;

class TemplateAuthorization
{
    public function handle($request, Closure $next)
    {
        $templateType = $this->getTemplateType($request);
        if ($templateType) {
            $middlewares = $this->getMiddlewareForTemplateType($templateType);
            $request->route()->middleware($middlewares);

            if ($this->isRoute($request, ['import.do_import', 'processes.preimportValidation'])) {
                return $this->handleImportRoute($request, $next, $templateType);
            }

            if ($this->isRoute($request, 'export.download')) {
                return $this->handleExportRoute($request, $next, $templateType);
            }

            if ($templateType === 'screen' && $this->isRoute($request, [
                'templates.configure',
                'api.template.delete',
            ])) {
                $this->authorizeScreenTemplateAccess($request);
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

    protected function isRoute(Request $request, string|array $routeNames): bool
    {
        $routeNames = (array) $routeNames;
        foreach ($routeNames as $routeName) {
            if ($request->route()->named($routeName)) {
                return true;
            }
        }

        return false;
    }

    protected function handleImportRoute(Request $request, Closure $next, $templateType)
    {
        if ($request->user()->can("import-{$templateType}-templates")) {
            return $next($request);
        }

        if ($request->user()->can('import-processes')) {
            return $next($request);
        }
    }

    protected function handleExportRoute(Request $request, Closure $next, $templateType)
    {
        if ($request->user()->can("export-{$templateType}-templates")) {
            return $next($request);
        }

        if ($request->user()->can('export-processes')) {
            return $next($request);
        }
    }

    /**
     * Authorize user access to the screen template based on ownership or admin status.
     *
     * @throws HttpException If the user is unauthorized.
     */
    protected function authorizeScreenTemplateAccess(Request $request): void
    {
        $templateParam = $request->route()->parameter('template');
        $idParam = $request->route()->parameter('id');
        $id = $templateParam ?? $idParam;
        $template = ScreenTemplates::findOrFail($id);

        abort_unless(
            $template->is_owner,
            403,
            'Unauthorized access to the template.'
        );
    }
}
