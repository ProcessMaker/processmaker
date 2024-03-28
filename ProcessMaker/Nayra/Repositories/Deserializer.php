<?php

namespace ProcessMaker\Nayra\Repositories;

use Exception;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Nayra\Bpmn\Collection;
use ProcessMaker\Nayra\Bpmn\Models\Error;
use ProcessMaker\Nayra\Contracts\Bpmn\CollectionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Storage\BpmnDocumentInterface;
use ProcessMaker\Repositories\BpmnDocument;
use ProcessMaker\Repositories\DefinitionsRepository;
use ProcessMaker\Repositories\ExecutionInstanceRepository;
use ProcessMaker\Repositories\TokenRepository;

class Deserializer
{
    private $definitions = [];

    private $requests = [];

    private $tokens = [];

    private ExecutionInstanceRepository $instanceRepository;

    private TokenRepository $tokenRepository;

    private DefinitionsRepository $factory;

    /**
     * Deserializer constructor.
     */
    public function __construct()
    {
        $this->instanceRepository = new ExecutionInstanceRepository();
        $this->instanceRepository->setAbortIfInstanceNotFound(false);
        // Do not load all the active tokens to improve persistence performance
        $this->instanceRepository->setLoadTokens(false);
        $this->tokenRepository = new TokenRepository($this->instanceRepository);
        $this->factory = new DefinitionsRepository();
    }

    /**
     * Find process definition
     *
     * @param int $modelId
     * @return BpmnDocument
     */
    private function findProcessDefinition($modelId): BpmnDocument
    {
        if (!isset($this->definitions[$modelId])) {
            $version = ProcessVersion::find($modelId);
            $model = $version->process;

            $definition = app(BpmnDocumentInterface::class, ['process' => $model, 'process_version' => $version]);
            $definition->setFactory($this->factory);
            $definition->loadXML($version->bpmn);

            $this->definitions[$modelId] = $definition;
        }

        return $this->definitions[$modelId];
    }

    /**
     * Find process request
     *
     * @param int|string $instanceId
     * @param BpmnDocument $definition
     * @param string $processId
     * @return ProcessRequest
     *
     * @throws Exception
     */
    private function findRequest($instanceId, BpmnDocument $definition, $processId): ProcessRequest
    {
        // Return process request if already stored
        if (isset($this->requests[$instanceId])) {
            return $this->requests[$instanceId];
        }

        // Load process request
        $instance = $this->instanceRepository->loadExecutionInstanceByUid($instanceId, $definition);

        // If not exists, create a new one
        if (!$instance && !is_numeric($instanceId)) {
            $instance = $this->instanceRepository->createExecutionInstance();
            $instance->setId($instanceId);
            $instance->uuid = $instanceId;
            $instance->setProcess($definition->getProcess($processId));
            $dataStore = $this->factory->createDataStore();
            $instance->setDataStore($dataStore);
        } elseif (!$instance) {
            throw new Exception("ProcessRequest {$instanceId} not found.");
        }

        // Store process request finded
        $this->requests[$instance->getId()] = $instance;
        $this->requests[$instance->uuid] = $instance;

        return $instance;
    }

    /**
     * Find process request token
     *
     * @param int|string $tokenId
     * @param int|string $instanceId
     * @param string $elementId
     * @param BpmnDocument $definition
     * @return ProcessRequestToken
     *
     * @throws Exception
     */
    private function findRequestToken($tokenId, $instanceId, $elementId, BpmnDocument $definition): ProcessRequestToken
    {
        // Return process request token if already stored
        if (isset($this->tokens[$tokenId])) {
            return $this->tokens[$tokenId];
        }

        // Load process request
        $element = $definition->getElementInstanceById($elementId);
        $process = $element->getProcess();
        $instance = $this->findRequest($instanceId, $definition, $process->getId());

        // Load tokens of process request
        $tokens = $instance->getTokens();
        $token = $tokens->findFirst(function ($token) use ($tokenId) {
            return $token->getId() === $tokenId;
        });

        // Store and return process request token if finded
        if ($token) {
            $this->tokens[$token->getId()] = $token;
            $this->tokens[$token->uuid] = $token;

            return $token;
        }

        // Load process request token
        $token = $this->tokenRepository->loadTokenByUid($tokenId);

        // If not exists, create a new one
        if (!$token && !is_numeric($tokenId)) {
            $token = $this->tokenRepository->createTokenInstance();
            $token->setId($tokenId);
            $token->uuid = $tokenId;
        } elseif ($token) {
            $tokenInfo = [
                'id' => $token->getKey(),
                'status' => $token->status,
                'index' => $token->element_index,
                'element_ref' => $token->element_id,
            ];
            $properties = array_merge($token->token_properties ?: [], $tokenInfo);
            $token->setProperties($properties);
        } else {
            throw new Exception("ProcessRequestToken {$tokenId} not found.");
        }

        // Set process request to the token
        $token->setInstance($instance);

        // Store process request token finded
        $this->tokens[$token->getId()] = $token;
        $this->tokens[$token->uuid] = $token;

        return $token;
    }

    /**
     * Return a process request from serialized data
     *
     * @param array $serialized
     * @return ExecutionInstanceInterface
     */
    public function unserializeInstance(array $serialized): ExecutionInstanceInterface
    {
        // Extract properties
        $definition = $this->findProcessDefinition($serialized['model_id']);
        $instance = $this->findRequest($serialized['id'], $definition, $serialized['process_id']);
        $properties = $serialized['properties'] ?? [];

        // Remove the id
        unset($properties['id']);

        // Set process request properties
        $properties = array_merge($instance->getProperties(), $properties);
        $instance->setProperties($properties);
        if (isset($properties['parent_request_id'])) {
            $instance->parent_request_id = $properties['parent_request_id'];
        }

        // Set request data
        if (!empty($serialized['data']) && is_array($serialized['data'])) {
            // Preserve the parent request id
            if (isset($serialized['data']['_parent'])) {
                $serialized['data']['_parent']['request_id'] = $instance->parent_request_id;
            }
            $dataStore = $instance->getDataStore();
            foreach ($serialized['data'] as $key => $value) {
                $dataStore->putData($key, $value);
            }
        }

        return $instance;
    }

    /**
     * Return a process request token from serialized data
     *
     * @param array $serialized
     * @return TokenInterface|ProcessRequestToken
     */
    public function unserializeToken(array $serialized): TokenInterface
    {
        // Extract properties
        $definition = $this->findProcessDefinition($serialized['model_id']);
        $token = $this->findRequestToken($serialized['token_id'], $serialized['instance_id'], $serialized['element_id'], $definition);
        $properties = $serialized['properties'] ?? [];

        // Remove the id
        unset($properties['id']);

        // Set process request token properties
        $properties = array_merge($token->getProperties(), $properties);

        // Convert string error into Error
        if (isset($properties['error']) && is_string($properties['error'])) {
            $error = new Error();
            $error->setName($properties['error']);
            $properties['error'] = $error;
        }

        $token->setProperties($properties);

        return $token;
    }

    /**
     * Return entity from serialized data
     *
     * @param array $serialized
     * @return EntityInterface
     */
    public function unserializeEntity(array $serialized): EntityInterface
    {
        $definition = $this->findProcessDefinition($serialized['model_id']);

        return $definition->getElementInstanceById($serialized['id']);
    }

    /**
     * Return tokens collection from serialized data
     *
     * @param array $serialized
     * @return CollectionInterface
     */
    public function unserializeTokensCollection(array $serialized): CollectionInterface
    {
        $collection = new Collection();
        foreach ($serialized as $item) {
            $collection->push($this->unserializeToken($item));
        }

        return $collection;
    }

    /**
     * Return event definition from serialized data
     *
     * @param array $serialized
     * @return EventDefinitionInterface
     */
    public function unserializeEventDefinition(array $serialized): EventDefinitionInterface
    {
        $definition = $this->findProcessDefinition($serialized['model_id']);
        $element = $definition->getElementInstanceById($serialized['element_id']);
        $node = $element->getBpmnElement();
        $childNode = $node->childNodes->item($serialized['index']);
        $eventDefinition = $childNode->getBpmnElementInstance();

        return $eventDefinition;
    }
}
