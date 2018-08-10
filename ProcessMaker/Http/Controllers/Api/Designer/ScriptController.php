<?php

namespace ProcessMaker\Http\Controllers\Api\Designer;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Facades\ScriptManager;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\Model\Process;
use ProcessMaker\Model\Script;
use ProcessMaker\Transformers\ScriptTransformer;
use Symfony\Component\HttpFoundation\Response;

class ScriptController extends Controller
{
    /**
     * Get a list of scripts in a process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function index(Process $process, Request $request)
    {
        $options = [
            'filter' => $request->input('filter', ''),
            'current_page' => $request->input('page', 1),
            'per_page' => $request->input('per_page', 10),
            'sort_by' => $request->input('order_by', 'title'),
            'sort_order' => $request->input('order_direction', 'ASC'),
        ];
        $response = ScriptManager::index($process, $options);
        return fractal($response, new ScriptTransformer())->respond();
    }

    /**
     * Previews executing a script, with sample data/config data
     */
    public function preview(Request $request)
    {
        $data = $request->get('data');
        $config = $request->get('config');
        $code = $request->get('code');
        $language = $request->get('language');
        // Create the temporary files to feed into our docker container
        $datafname = tempnam("/home/vagrant", "data.json");
        chmod($datafname, 0660);
        $tempData = fopen($datafname, 'w');
        fwrite($tempData, $data);

        fclose($tempData);
        $configfname = tempnam("/home/vagrant", "config.json");
        chmod($configfname, 0660);

        $tempData = fopen($configfname, 'w');
        fwrite($tempData, $config);
        fclose($tempData);
        $scriptfname = tempnam("/home/vagrant", "script");
        chmod($scriptfname, 0660);

        $tempData = fopen($scriptfname, 'w');
        fwrite($tempData, $code);
        fclose($tempData);
        $outputfname = tempnam("/home/vagrant", "output.json");
        chmod($outputfname, 0660);

        // So we have the files, let's execute the docker container
        switch($language) {
            case 'php':
                $cmd = "/usr/bin/docker run -v " . $datafname . ":/opt/executor/data.json -v " . $configfname . ":/opt/executor/config.json -v " . $scriptfname . ":/opt/executor/script.php -v " . $outputfname . ":/opt/executor/output.json processmaker/executor:php php /opt/executor/bootstrap.php 2>&1";
                break;
            case 'lua':
                $cmd = "/usr/bin/docker run -v " . $datafname . ":/opt/executor/data.json -v " . $configfname . ":/opt/executor/config.json -v " . $scriptfname . ":/opt/executor/script.lua -v " . $outputfname . ":/opt/executor/output.json processmaker/executor:lua lua5.3 /opt/executor/bootstrap.lua 2>&1";
                break;
        }

        $response = exec($cmd, $output, $returnCode);
        if($returnCode) {
            // Non-zero, there is an error!
            unlink($datafname);
            unlink($configfname);
            unlink($scriptfname);
            unlink($outputfname);
            return [
                'output' => implode($output, "\n")
            ];
        } else {
            // Success, let's format and output
            // Grab output
            $output = json_decode(file_get_contents($outputfname), true);
            unlink($datafname);
            unlink($configfname);
            unlink($scriptfname);
            unlink($outputfname);
            return [
                'output' => json_encode($output, JSON_PRETTY_PRINT)
            ];
        }

    }

    /**
     * Get a single script in a process.
     *
     * @param Process $process
     * @param Script $script
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function show(Process $process, Script $script)
    {
        $this->belongsToProcess($process, $script);
        return fractal($script, new ScriptTransformer())->respond(200);
    }

    /**
     * Create a new script in a process.
     *
     * @param Process $process
     * @param Request $request
     *
     * @return ResponseFactory|Response
     */
    public function store(Process $process, Request $request)
    {
        $response = ScriptManager::save($process, $request->all());
        return fractal($response, new ScriptTransformer())->respond(201);
    }

    /**
     * Update a script in a process.
     *
     * @param Process $process
     * @param Script $script
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function update(Process $process, Script $script, Request $request)
    {
        $this->belongsToProcess($process, $script);
        $data = [];
        if ($request->has('title')) {
            $data['title'] = $request->input('title');
        }
        if ($request->has('description')) {
            $data['description'] = $request->input('description');
        }
        if ($request->has('code')) {
            $data['code'] = $request->input('code');
        }
        if($data) {
            ScriptManager::update($process, $script, $data);
        }
        return response([], 204);
    }

    /**
     * Delete a script in a process.
     *
     * @param Process $process
     * @param Script $script
     *
     * @return ResponseFactory|Response
     * @throws DoesNotBelongToProcessException
     */
    public function remove(Process $process, Script $script)
    {
        $this->belongsToProcess($process, $script);
        ScriptManager::remove($script);
        return response([], 204);
    }

    /**
     * Validate if script belong to process.
     *
     * @param Process $process
     * @param Script $script
     *
     * @throws DoesNotBelongToProcessException|void
     */
    private function belongsToProcess(Process $process, Script $script)
    {
        if($process->id !== $script->process_id) {
            Throw new DoesNotBelongToProcessException(__('The script does not belong to this process.'));
        }
    }

}
