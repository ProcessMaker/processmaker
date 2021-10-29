<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Arr;
use ProcessMaker\Nayra\Bpmn\Models\SignalEventDefinition as ModelsSignalEventDefinition;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

/**
 * SignalEventDefinition
 *
 * @package ProcessMaker\Model
 */
class SignalEventDefinition extends ModelsSignalEventDefinition
{
    /**
     * Get data contained in the event payload
     *
     * @param TokenInterface|null $token
     *
     * @return mixed
     */
    public function getPayloadData(TokenInterface $token = null, CatchEventInterface $startEvent = null)
    {
        $sourceEventDefinition = $token->getOwnerElement()->getEventDefinitions()->item(0);
        $requestData = $token ? $token->getInstance()->getDataStore()->getData() : [];
        $eventConfig = json_decode($sourceEventDefinition->getProperty('config') ?? null);
        $payload = $eventConfig && $eventConfig->payload ? $eventConfig->payload[0] : null;
        $payloadId = $payload && $payload->id ? $payload->id : null;

        $targetVariable = $startEvent->getProperty('config', false);

        $data = [];

        switch ($payloadId) {
            case "REQUEST_VARIABLE":
                if ($payload->variable) {
                    $extractedData = Arr::get($requestData, $payload->variable);
                    Arr::set($data, $payload->variable, $extractedData);
                }
                break;
            case "EXPRESSION":
                $expression = $payload->expression;
                $formalExp = new FormalExpression();
                $formalExp->setLanguage('FEEL');
                $formalExp->setBody($expression);
                $expressionResult = $formalExp($requestData);
                Arr::set($data, $payload->variable, $expressionResult);
                break;
            case "NONE":
                $data = [];
                break;
            default:
                $data = $requestData;
                break;
        }
        if ($targetVariable) {
            $data = [
                $targetVariable => $data
            ];
        }

        return $data;
    }
}
