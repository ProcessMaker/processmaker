<?php


namespace ProcessMaker\Http\Middleware;


use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Str;

class ProcessMakerAuthenticate extends Authenticate
{

    protected function authenticate($request, array $guards)
    {
        $this->addAcceptJsonHeaderIfApiCall($request, $guards);

        return parent::authenticate($request, $guards);
    }

    /**
     * @param array $guards
     * @param \Illuminate\Http\Request $request
     */
    private function addAcceptJsonHeaderIfApiCall(\Illuminate\Http\Request $request, array $guards): void
    {
        if (in_array('api', $guards) && !$this->requestHasAcceptJsonHeader($request)) {
            $request->headers->set('accept', 'application/json,' . $request->header('accept'));
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return bool
     */

    private function requestHasAcceptJsonHeader(\Illuminate\Http\Request $request): bool
    {
        return Str::contains($request->header('accept'), ['/json', '+json']);
    }

}