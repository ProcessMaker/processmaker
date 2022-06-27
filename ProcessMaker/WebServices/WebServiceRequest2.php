<?php

namespace ProcessMaker\WebServices;

use ProcessMaker\Contracts\WebServiceCallerInterface;
use ProcessMaker\Contracts\WebServiceConfigBuilderInterface;
use ProcessMaker\Contracts\WebServiceRequestBuilderInterface;
use ProcessMaker\Contracts\WebServiceResponseMapperInterface;

class WebServiceRequest2
{
    private $configBuilder;
    private $requestBuilder;
    private $responseMapper;
    private $serviceCaller;

    public function __construct(
        WebServiceConfigBuilderInterface $configBuilder,
        WebServiceRequestBuilderInterface $requestBuilder,
        WebServiceResponseMapperInterface $responseMapper,
        WebServiceCallerInterface $serviceCaller)
    {
        $this->configBuilder = $configBuilder;
        $this->requestBuilder = $requestBuilder;
        $this->responseMapper = $responseMapper;
        $this->serviceCaller = $serviceCaller;
    }

    public function callWebService(array $config, array $data)
    {
        $dataSourceConfig = $this->dataSource->toArray();
        $dataSourceConfig['credentials'] = $this->dataSource->credentials;
        $config = $this->config->build($serviceTaskConfig, $dataSourceConfig, $data);
        $request = $this->request->build($config, $data);
        $response = $this->requestCaller->call($request, $config);
        $result = $this->responseMapper->map($response, $config, $data);
        return $result;
    }
}