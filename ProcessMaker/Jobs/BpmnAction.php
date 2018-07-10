<?php
namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use Illuminate\Support\Facades\App;
use ProcessMaker\Model\Process as Definitions;

abstract class BpmnAction implements ShouldQueue
{

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;
}
