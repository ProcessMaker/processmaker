<?php

namespace ProcessMaker\Exception;

use Exception;

class ExportEmptyProcessException extends Exception
{
    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json([
            'message' => __('The process to export is empty.'),
            'exception' => 'ProcessMaker\\Exception\\ExportEmptyProcessException',
        ], 500);
    }
}
