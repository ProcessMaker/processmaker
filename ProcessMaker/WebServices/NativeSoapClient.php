<?php

namespace ProcessMaker\WebServices;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Helpers\StringHelper;
use ProcessMaker\WebServices\Contracts\SoapClientInterface;
use SimpleXMLElement;
use SoapClient;
use SoapHeader;
use SoapVar;

class NativeSoapClient implements SoapClientInterface
{
    private $soapClient;

    private $services = [];

    private $debug = false;

    private $options;

    public function __construct(string $wsdl, array $options)
    {
        $this->soapClient = new SoapClient($wsdl, $options);

        // Parse WSDL
        $dom = new DOMDocument();
        $this->loadWsdl($dom, $wsdl, $options);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/wsdl/soap/');
        $xpath->registerNamespace('wsdl', 'http://schemas.xmlsoap.org/wsdl/');
        // Get Service Ports
        $xpathQuery = 'wsdl:service/wsdl:port[@name]/soap:address';
        $servicePorts = $xpath->query($xpathQuery);
        foreach ($servicePorts as $servicePort) {
            $serviceName = $servicePort->parentNode->getAttribute('name');
            $servicePortLocation = $servicePort->getAttribute('location');
            $this->services[$serviceName] = [
                'name' => $serviceName,
                'location' => $servicePortLocation,
            ];
        }
        $this->debug = $options['debug_mode'] ?? false;
        $this->options = $options;
        // Add Soap Auth Headers
        $this->addSoapAuthHeaders($options);
    }

    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Execute a Soap Method
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function callMethod(string $method, array $parameters)
    {
        try {
            $response = $this->soapClient->__soapCall($method, $parameters);
        } catch (\Throwable $e) {
            $lastMsg = $e->getMessage();
            $lastResponse = $this->soapClient->__getLastResponse();
            $response = ['response' => $lastMsg . ': ' . $lastResponse, 'status' => 401];
        }

        if ($this->debug) {
            $this->logSoap('Request: ', $this->soapClient->__getLastRequest());
            $this->logSoap('Request Headers: ', $this->soapClient->__getLastRequestHeaders());
            $this->logSoap('Response: ', $this->soapClient->__getLastResponse());
            $this->logSoap('Response headers: ', $this->soapClient->__getLastResponseHeaders());
        }

        return $response;
    }

    private function logSoap($label, $log)
    {
        if (!$log) {
            return;
        }
        try {
            $doc = new DOMDocument();
            $doc->formatOutput = true;
            $doc->loadXML($log);

            $credentials = $doc->getElementsByTagName('UsernameToken');
            if ($credentials->length) {
                $doc->getElementsByTagName('Username')->item(0)->nodeValue = '************';
                $doc->getElementsByTagName('Password')->item(0)->nodeValue = '************';

                if ($doc->getElementsByTagName('Nonce')->item(0) && $doc->getElementsByTagName('Nonce')->item(0)->nodeValue) {
                    $doc->getElementsByTagName('Nonce')->item(0)->nodeValue = '************';
                }
            }

            //Log::channel('data-source')->info($label . $doc->saveXML());
            $connectorName = StringHelper::friendlyFileName($this->options['datasource_name']) . '_(' . $this->options['datasource_id'] . ')';
            Log::build([
                'driver' => 'daily',
                'path' => storage_path("logs/data-connectors/$connectorName.log"),
            ])->info($label . $doc->saveXML());
        } catch (\Throwable $th) {
            if ($label === 'Request Headers: ') {
                $newLog = '';
                foreach (preg_split("/((\r?\n)|(\r\n?))/", $log) as $line) {
                    if (str_contains($line, 'Authorization:')) {
                        $line = 'Authorization: ************';
                    }
                    $newLog .= $line . PHP_EOL;
                }
                $log = $newLog;
            }
            Log::channel('data-source')->info($label . $log);
        }
    }

    public function selectServicePort(string $portName)
    {
        $this->soapClient->__setLocation($this->services[$portName]['location']);
    }

    private function addSoapAuthHeaders(array $options)
    {
        switch ($options['authentication_method']) {
            case 'PASSWORD':
                $this->soapClient->__setSoapHeaders(
                    $this->soapClientWSSecurityHeader($options)
                );
                break;
            case 'WSDL_FILE':
                $this->soapClient->__setSoapHeaders([
                    new SoapHeader(
                        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
                        'Security',
                        new SoapVar(
                            '<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
                                <wsse:UsernameToken>
                                    <wsse:Username>' . $options['login'] . '</wsse:Username>
                                    <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $options['password'] . '</wsse:Password>
                                </wsse:UsernameToken>
                            </wsse:Security>',
                            XSD_ANYXML
                        )
                    ),
                ]);
                break;
        }
    }

    /**
     * This function implements a WS-Security digest authentification for PHP.
     *
     * @param array $options
     * @return SoapHeader
     */
    private function soapClientWSSecurityHeader($options)
    {
        if ($options['password_type'] === 'None') {
            return;
        }

        // Creating date using yyyy-mm-ddThh:mm:ssZ format
        $tmCreated = gmdate('Y-m-d\TH:i:s\Z');
        $tmExpires = gmdate('Y-m-d\TH:i:s\Z', gmdate('U') + 180); //only necessary if using the timestamp element

        // Generating and encoding a random number
        $simpleNonce = mt_rand();
        $encodedNonce = base64_encode($simpleNonce);

        // Compiling WSS string
        $password = $options['password'];
        $passwordDigest = base64_encode(sha1($simpleNonce . $tmCreated . $password, true));

        // Initializing namespaces
        $nsWsse = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
        $nsWsu = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';

        $passwordType = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText';

        if ($options['password_type'] === 'PasswordDigest') {
            $passwordType = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest';
            $password = $passwordDigest;
        }

        $encodingType = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';

        // Creating WSS identification header using SimpleXML
        $root = new SimpleXMLElement('<root/>');

        $security = $root->addChild('wsse:Security', null, $nsWsse);

        //the timestamp element is not required by all servers
        $timestamp = $security->addChild('wsu:Timestamp', null, $nsWsu);
        $timestamp->addAttribute('wsu:Id', 'Timestamp-28');
        $timestamp->addChild('wsu:Created', $tmCreated, $nsWsu);
        $timestamp->addChild('wsu:Expires', $tmExpires, $nsWsu);

        $usernameToken = $security->addChild('wsse:UsernameToken', null, $nsWsse);
        $usernameToken->addChild('wsse:Username', $options['login'], $nsWsse);
        $usernameToken->addChild('wsse:Password', $password, $nsWsse)->addAttribute('Type', $passwordType);
        $usernameToken->addChild('wsse:Nonce', $encodedNonce, $nsWsse)->addAttribute('EncodingType', $encodingType);
        $usernameToken->addChild('wsu:Created', $tmCreated, $nsWsu);

        // Recovering XML value from that object
        $root->registerXPathNamespace('wsse', $nsWsse);
        $full = $root->xpath('/root/wsse:Security');
        $auth = $full[0]->asXML();

        return new SoapHeader($nsWsse, 'Security', new SoapVar($auth, XSD_ANYXML), true);
    }

    public function getOperations(string $serviceName = ''): array
    {
        $functions = $this->soapClient->__getFunctions();
        $types = $this->soapClient->__getTypes();
        $operations = [];
        foreach ($functions as $definition) {
            preg_match('/([\w\d_]+)\s([\w\d_]+)\((.+)\)/', $definition, $matches);
            $type = $matches[1];
            $operation = $matches[2];
            $parameters = $this->explodeParameters($matches[3], $types);
            $operations[$operation] = [
                'type' => $type,
                'name' => $operation,
                'parameters' => $parameters,
            ];
        }

        return $operations;
    }

    public function getTypes(): array
    {
        $types = $this->soapClient->__getTypes();
        $response = [];
        foreach ($types as $struct) {
            $struct = explode("\n", $struct);
            $struct[0] = trim($struct[0]);
            $type = substr($struct[0], 7, -2);
            $fields = \array_slice($struct, 1, -1);
            $params = [];
            $rand = ['not_defined', 'non_required'];
            foreach ($fields as $i => $field) {
                list($type, $name) = explode(' ', trim($field, ' ;'));
                $params[] = [
                    'type' => $type,
                    'name' => $name,
                    // @todo implement required
                    'required' => substr($name, -2) === 'Rq' ? 'required' : $rand[$i % 2],
                ];
            }
            $response[$type] = $params;
        }

        return $response;
    }

    private function explodeParameters($parameters, array $types)
    {
        $parameters = explode(',', $parameters);
        $params = [];
        $rand = ['not_defined', 'non_required'];
        foreach ($parameters as $parameter) {
            list($type, $name) = explode(' ', trim($parameter));
            foreach ($types as $struct) {
                $struct = explode("\n", $struct);
                $struct[0] = trim($struct[0]);
                if ($struct[0] === "struct {$type} {") {
                    $fields = \array_slice($struct, 1, -1);
                    foreach ($fields as $i => $field) {
                        list($type, $name) = explode(' ', trim($field, ' ;'));
                        $params[] = [
                            'type' => $type,
                            'name' => $name,
                            // @todo implement required
                            'required' => substr($name, -2) === 'Rq' ? 'required' : $rand[$i % 2],
                        ];
                    }
                }
            }

            return $params;
        }

        return $parameters;
    }

    private function loadWsdl(DOMDocument $dom, $wsdl, array $options)
    {
        if ($options['authentication_method'] === 'LOCAL_CERTIFICATE') {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $wsdl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_cert');
            curl_setopt($ch, CURLOPT_SSLCERT, $options['local_cert']);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $options['passphrase']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

            $wsdlContent = curl_exec($ch);
            if (curl_errno($ch)) {
                return false;
            }
            curl_close($ch);
            $dom->loadXML($wsdlContent);
        } else {
            $dom->load($wsdl);
        }
    }
}
