<?php

namespace ProcessMaker\Models;

use ProcessMaker\Nayra\Bpmn\Models\Message as MessageBase;

/**
 * Implementation of the message element.
 *
 */
class Message extends MessageBase
{

    const PAYLOAD_EXPRESSION = '/^\{([\w.,\s]+)\}$/';
    const MAP_EXPRESSION = '/^\s*(\w+)\s*:\s*([\w.]+)\s*$/';
    const ITEM_EXPRESSION = '/^\s*(\w+)\s*$/';

    /**
     * @var mixed $payload
     */
    private $payload;

    /**
     * Get the payload of the message.
     *
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Get the payload of the message.
     *
     * @param mixed $payload
     *
     * @return $this
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Get the serializable content of message
     *
     */
    public function getData(ProcessRequest $instance)
    {
        $source = $instance->data;
        $data = [];
        if (preg_match(self::PAYLOAD_EXPRESSION, $this->getPayload(), $match)) {
            $elements = explode(',', $match[1]);
            foreach ($elements as $element) {
                if (preg_match(self::MAP_EXPRESSION, $element, $map)) {
                    $name = $map[1];
                    $reference = $map[2];
                } elseif (preg_match(self::ITEM_EXPRESSION, $element, $map)) {
                    $name = $map[1];
                    $reference = $map[1];
                }
                $data[$name] = array_get($source, $reference);
            }
        }
        return $data;
    }
}
