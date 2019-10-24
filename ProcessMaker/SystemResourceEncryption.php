<?php
namespace ProcessMaker;

use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use Illuminate\Contracts\Encryption\DecryptException;

class SystemResourceEncryption
{
    const FIELDS_TO_ENCRYPT = [
        Process::class => ['bpmn'],
        ProcessRequest::class => ['data'],
        ProcessRequestToken::class => ['data'],
        Screen::class => ['config'],
        Script::class => ['code'],
    ];

    public static function addRetrievedObservers()
    {
        foreach(self::FIELDS_TO_ENCRYPT as $class => $fields) {
            $class::retrieved(function ($model) use ($fields) {
                if (!$model->isSystemResource()) {
                    return;
                }
                foreach ($fields as $field) {
                    try {
                        $model->$field = decrypt($model->$field);
                    } catch (DecryptException $_err) {
                        // Ignore. It's existing and will get encrypted on next save
                    }
                }
            });
        }
    }
            
    public static function addSavingObservers()
    {
        foreach(self::FIELDS_TO_ENCRYPT as $class => $fields) {
            $class::saving(function ($model) use ($fields) {
                if (!$model->isSystemResource()) {
                    return;
                }
                foreach ($fields as $field) {
                    $model->$field = encrypt($model->$field);
                }
            });
        }
    }
}