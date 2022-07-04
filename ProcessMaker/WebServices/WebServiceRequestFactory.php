<?php

namespace ProcessMaker\WebServices;

class WebServiceRequestFactory
{
    public function create($type, $dataSource)
    {
        $result = null;
        switch($type)
        {
            case 'soap':
                $result = $this->createSoapWebService($dataSource);
                break;
            case 'rest':
                $result = $this->createRestWebService($dataSource);
                break;
            default:
                throw new \Exception("Can't create WebServiceRequest for type '$type'.");
        }
        return $result;
    }

    private function createSoapWebService($dataSource)
    {
        return new WebServiceRequest(
            new SoapConfigBuilder(),
            new SoapRequestBuilder(),
            new SoapResponseMapper(),
            new SoapServiceCaller(),
            $dataSource
        );
    }

    private function createRestWebService($dataSource)
    {
        return new WebServiceRequest(
            new SoapConfigBuilder(),
            new SoapRequestBuilder(),
            new SoapResponseMapper(),
            new SoapServiceCaller(),
            $dataSource
        );
    }
}