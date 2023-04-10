<?php

namespace ProcessMaker\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TemplateAuthorization
{
    public function handle($request, Closure $next)
    {
        $templateType = $request->route()->parameter('type');

        if ($templateType) {
            $middlewares = [
                "can:view-{$templateType}-templates",
                "can:edit-{$templateType}-templates",
                "can:archive-{$templateType}-templates",
                "can:create-{$templateType}-templates",
                "can:import-{$templateType}-templates",
                "can:export-{$templateType}-templates",
            ];
            $request->route()->middleware($middlewares);
        }

        return $next($request);
    }
}
