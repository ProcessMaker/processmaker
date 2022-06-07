<?php

namespace ProcessMaker\WebServices;

use Illuminate\Support\Arr;
use ProcessMaker\WebServices\Contracts\WebServiceResponseMapperInterface;

class JsonResponseMapper implements WebServiceResponseMapperInterface
{

    //TODO remove headers, status and dsConfig
    public function map($content, $status, $headers, $config, $dsConfig, $data): array
    {
        $mapped = [];

        if (!isset($config['dataMapping'])) {
            return $mapped;
        }

        $merged = array_merge($data, $content, $headers);

        foreach ($config['dataMapping'] as $map) {
            $processVar = ExpressionEvaluator::evaluate('mustache', $map['key'], $data);
            $value = $map['value'];
            $url = $dsConfig['endpoints'][$config['endpoint']]['url'];

            // if value is empty all the response is mapped
            if (trim($value) === '') {
                $mapped[$processVar] = $content;
                continue;
            }
            if (trim($value) === '$status') {
                $mapped[$processVar] = $status;
                continue;
            }

            // if is a collection connector, by default it is not necessary to send data.data and we add it by default
            if (preg_match('/\/api\/[0-9\.]+\/collections/m', $url) === 1) {
                $value = $this->addCollectionsRootObject($value);
            }

            $format = $map['format'] ?? 'dotNotation';
            if ($format === 'mustache') {
                //$evaluatedApiVar = $this->evalMustache($map['value'], $merged);
                $evaluatedApiVar = ExpressionEvaluator::evaluate('mustache', $map['value'], $merged);
            } elseif ($format === 'feel') {
                //$evaluatedApiVar = $this->evalExpression($map['value'], $merged);
                $evaluatedApiVar = ExpressionEvaluator::evaluate('feel', $map['value'], $merged);
            } else { // dot notation + mustache. eg `data.users{{index}}.attributes.firstname`
                if ($map['value']) {
                    $evaluatedApiVar = Arr::get($merged, ExpressionEvaluator::evaluate('mustache', $map['value'], $merged), '');
                } else {
                    $evaluatedApiVar = $content;
                }
            }
            $mapped[$processVar] = $evaluatedApiVar;
        }

        return $mapped;
    }

    private function addCollectionsRootObject($value)
    {
        preg_match_all('/\{\{(.*?)\}\}/m', $value, $matches, PREG_SET_ORDER, 0);
        if (count($matches) > 0) {
            $matchesWithNewVal = [];
            foreach ($matches as $match) {
                $val = $match[1];
                if (strpos($val, 'data.data') === false && strpos($val, 'data') === false) {
                    $match[] = 'data.data.' . trim($val);
                } else {
                    $match[] = trim($val);
                }
                $matchesWithNewVal[] = $match;
            }

            foreach ($matchesWithNewVal as $match) {
                $value = str_replace($match[0], '{{' . $match[2] . '}}', $value);
            }
        } else {
            if (strpos($value, 'data.data') === false && strpos($value, 'data') === false) {
                $value = 'data.data.' . trim($value);
            } else {
                $value = trim($value);
            }
        }
        return $value;
    }

}