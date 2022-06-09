<?php

namespace ProcessMaker\WebServices;

use ProcessMaker\Contracts\SoapClientInterface;
use ProcessMaker\Contracts\WebServiceCallerInterface;
use ProcessMaker\Contracts\WebServiceConfigBuilderInterface;
use ProcessMaker\Contracts\WebServiceRequestBuilderInterface;
use ProcessMaker\Contracts\WebServiceResponseMapperInterface;

class WebServiceRequest
{
    private $config;
    private $request;
    private $responseMapper;
    private $requestCaller;
    private $dataSource;

    public function __construct(
        WebServiceConfigBuilderInterface $config,
        WebServiceRequestBuilderInterface $request,
        WebServiceResponseMapperInterface $responseMapper,
        WebServiceCallerInterface $requestCaller,
        $dataSource
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->responseMapper = $responseMapper;
        $this->requestCaller = $requestCaller;
        $this->dataSource = $dataSource;
    }

    public function execute(array $data, array $serviceTaskConfig)
    {
        $dataSourceConfig = $this->dataSource->toArray();
        $dataSourceConfig['credentials'] = $this->dataSource->credentials;
        $config = $this->config->build($serviceTaskConfig, $dataSourceConfig, $data);
        $request = $this->request->build($config, $data);
        $response = $this->requestCaller->call($request, $config);
        $result = $this->responseMapper->map($response, $config, $data);
        return $result;
    }

    public function getOperations()
    {
        if (!$this->dataSource->credentials) {
            return [];
        }

        if (!isset($this->dataSource->wsdlFile)) {
            return [];
        }

        if (!isset($this->dataSource->credentials['location'])) {
            return [];
        }

        $dataSourceConfig = [
            'id' => $this->dataSource->id,
            'name' => $this->dataSource->name,
            'description' => $this->dataSource->description,
            'endpoints' => $this->dataSource->endpoints,
            'mappings' => $this->dataSource->mappings,
            'type' => $this->dataSource->type,
            'authtype' => $this->dataSource->authtype,
            'debug_mode' => $this->dataSource->debug_mode,
            'credentials' => $this->dataSource->credentials,
            'status' => $this->dataSource->status,
            'data_source_category_id' => $this->dataSource->data_source_category_id,
            'wsdlFile' => $this->dataSource->wsdlFile,
        ];
        $config = $this->config->build([], $dataSourceConfig, []);
        $request = $this->request->build($config, []);
        $client = app(SoapClientInterface::class, $request);
        return $client->getOperations();
    }

    public function getTypes()
    {
        if (!$this->dataSource->wsdlFile) {
            return [];
        }
        $dataSourceConfig = [
            'id' => $this->dataSource->id,
            'name' => $this->dataSource->name,
            'description' => $this->dataSource->description,
            'endpoints' => $this->dataSource->endpoints,
            'mappings' => $this->dataSource->mappings,
            'type' => $this->dataSource->type,
            'authtype' => $this->dataSource->authtype,
            'debug_mode' => $this->dataSource->debug_mode,
            'credentials' => $this->dataSource->credentials,
            'status' => $this->dataSource->status,
            'data_source_category_id' => $this->dataSource->data_source_category_id,
            'wsdlFile' => $this->dataSource->wsdlFile,
        ];
        $config = $this->config->build([], $dataSourceConfig, []);
        $request = $this->request->build($config, []);
        $client = app(SoapClientInterface::class, $request);
        return $client->getTypes();
    }
}
