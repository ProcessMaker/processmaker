<?php

namespace Tests\Unit\ProcessMaker\Transformers;

use ProcessMaker\Model\Process;
use League\Fractal\TransformerAbstract;

/**
 * Description of TestTransformer
 *
 */
class TestTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'user'
    ];

    /**
     * Transform a process object.
     *
     * @param Process $process
     *
     * @return array
     */
    function transform(Process $process)
    {
        return [
            'pro_uid' => $process->PRO_UID,
        ];
    }

    /**
     * Transform the user of process.
     *
     * @param Process $process
     *
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(Process $process)
    {
        return $this->item($process->USR_UID, function ($user) {
           return [
               'usr_uid' => $user,
           ];
        });
    }
}
