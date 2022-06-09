<?php

namespace ProcessMaker\Managers;

use Exception;
use Mustache_Engine;
use ProcessMaker\Contracts\WebServiceConfigBuilderInterface;
use ProcessMaker\Models\FormalExpression;

class WebServiceSoapConfigBuilder implements WebServiceConfigBuilderInterface
{
    private $mustache = null;

    public function build(array $serviceTaskConfig, array $dataSourceConfig, array $data): array
    {
        $config = $serviceTaskConfig;
        $credentials = $dataSourceConfig['credentials'];
        if (!$credentials) {
            throw new Exception('Credentials are required');
        }
        $config['wsdl'] = $credentials['wsdl'] ?? $dataSourceConfig['wsdlFile']['path'];
        $config['username'] = $credentials['user'];
        $config['password'] = $credentials['password'];
        // @todo add the authentication_method in datasource settings
        $config['authentication_method'] = $credentials['authentication_method'] ?? 'password';
        $config['debug_mode'] = $dataSourceConfig['debug_mode'];
        $config['location'] = $credentials['location'];
        // Prepare endpoint params and dataMapping
        if (!empty($serviceTaskConfig['endpoint'])) {
            $endpoint = $serviceTaskConfig['endpoint'];
            $endpointDefinition = $dataSourceConfig['endpoints'][$endpoint];
            $config['operation'] = $endpointDefinition['operation'];
            $outboundConfig = $serviceTaskConfig['outboundConfig'];
            $parameters = [];
            foreach ($outboundConfig as $map) {
                if ($map['type'] !== 'PARAM') {
                    continue;
                }
                $format = $map['format'];
                if ($format === 'mustache') {
                    $value = $this->evalMustache($map['value'], $data);
                } elseif ($format === 'feel') {
                    $value = $this->evalExpression($map['value'], $data);
                } else {
                    throw new Exception('Invalid format: ' . $format . ' for ' . $map['key']);
                }
                $parameters[$map['key']] = $value;
            }
            $config['parameters'] = $parameters;
        } else {
            $config['operation'] = '';
            $config['parameters'] = [];
        }
        return $config;
    }

    /**
     * Evaluate a BPMN FEEL expression
     * ex.
     *      foo             => bar
     *      _request.id     => 1001
     *      _user.id        => 101
     *      10              => 10
     *      {{ form.age }}  => 21
     *      "{{ form.lastname }} {{ form.firstname }}" => John Doe
     *
     * @return mixed
     */
    private function evalExpression($expression, array $data)
    {
        try {
            $formal = new FormalExpression();
            $formal->setBody($expression);
            return $formal($data);
        } catch (Exception $exception) {
            return "{$expression}: " . $exception->getMessage();
        }
    }

    /**
     * Evaluate a mustache expression
     * ex.
     *      foo             => "foo"
     *      10              => "10"
     *      {{ user.id }}  => "101"
     *      {{ form.age }}  => "21"
     *      {{ form.lastname }} {{ form.firstname }} => "John Doe"
     * @return string
     */
    private function evalMustache($expression, array $data)
    {
        try {
            return $this->getMustache()->render($expression, $data);
        } catch (Exception $exception) {
            return "{$expression}: " . $exception->getMessage();
        }
    }

    /**
     * Get mustache engine
     *
     * @return \Mustache_Engine
     */
    private function getMustache()
    {
        if ($this->mustache === null) {
            $this->mustache = app(Mustache_Engine::class);
        }
        return $this->mustache;
    }
}
