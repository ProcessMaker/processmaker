<?php

namespace ProcessMaker\WebServices;

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
        $dataSourceConfig['credentials'] = json_decode($this->dataSource->credentials);
        $config = $this->config->build($serviceTaskConfig, $dataSourceConfig, $data);
        $request = $this->request->build($config, $data);
        $response = $this->requestCaller->call($request, $config);
        $result = $this->responseMapper->map($response, $config, $data);
        return $result;
    }
}
