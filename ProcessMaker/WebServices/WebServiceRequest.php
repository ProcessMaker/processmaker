<?php

namespace ProcessMaker\WebServices;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\WebServices\Contracts\SoapClientInterface;
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

    public function getValidOperations($files = []): array
    {
        $allOperations = $this->getOperations();
        $operations = [];

        foreach ($allOperations as $operation) {
            $operation['missed_reference'] = true;
            foreach ($files as $file) {
                if (!$operation['missed_reference']) {
                    continue;
                }

                $fileContent = Storage::disk($file['disk'])->get($file['path']);
                $dom = new DOMDocument();
                $dom->loadXML($fileContent);
                $xpath = new DOMXPath($dom);

                // Search for operation reference
                $query = "//*[local-name()='element'][@ref='".$operation['name']."']";
                $elements = $xpath->query($query);
                if ($elements->length) {
                    $operation['missed_reference'] = false;
                }
                $operations[$operation['name']] = $operation;
            }
        }

        return $operations;
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