<?php

namespace ProcessMaker\WebServices;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Contracts\SoapClientInterface;
use ProcessMaker\WebServices\Contracts\WebServiceCallerInterface;
use ProcessMaker\WebServices\Contracts\WebServiceConfigBuilderInterface;
use ProcessMaker\WebServices\Contracts\WebServiceRequestBuilderInterface;
use ProcessMaker\WebServices\Contracts\WebServiceResponseMapperInterface;

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
        array $dataSource
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->responseMapper = $responseMapper;
        $this->requestCaller = $requestCaller;
        $this->dataSource = $dataSource;
    }

    public function execute(array $data, array $serviceTaskConfig)
    {
        //$dataSourceConfig = $this->dataSource->toArray();
//        $dataSourceConfig['credentials'] = $this->dataSource->credentials;
        //$config = $this->config->build($serviceTaskConfig, $dataSourceConfig, $data);

        $config = $this->config->build($serviceTaskConfig, $this->dataSource, $data);
        $request = $this->request->build($config, $data);
        $response = $this->requestCaller->call($request);
        $result = $this->responseMapper->map($response, $config, $data);
        return $result;
    }
}