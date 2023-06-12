<?php

namespace ProcessMaker\Nayra\Repositories;

use Exception;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\ProcessVersion;
use ProcessMaker\Nayra\Bpmn\Collection;
use ProcessMaker\Nayra\Contracts\Bpmn\CollectionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EntityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowNodeInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
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

    public function __construct()
    {
        $this->instanceRepository = new ExecutionInstanceRepository();
        $this->instanceRepository->setAbortIfInstanceNotFound(false);
        // do not load all the active tokens to improve persistance performance
        $this->instanceRepository->setLoadTokens(false);
        $this->tokenRepository = new TokenRepository($this->instanceRepository);
        $this->factory = new DefinitionsRepository();
    }

    private function findProcessDefinition($modelId): BpmnDocument
    {
        if (!isset($this->definitions[$modelId])) {
            $version = ProcessVersion::find($modelId);
            $model = $version->process;
            $definition = new BpmnDocument($model);
            $definition->setFactory($this->factory);
            $definition->loadXML($version->bpmn);
            $this->definitions[$modelId] = $definition;
        }

        return $this->definitions[$modelId];
    }

    private function findRequest($instanceId, BpmnDocument $definition, $callableId): ProcessRequest
    {
        if (isset($this->requests[$instanceId])) {
            return $this->requests[$instanceId];
        }
        $instance = $this->instanceRepository->loadExecutionInstanceByUid($instanceId, $definition);
        if (!$instance && !is_numeric($instanceId)) {
            $instance = $this->instanceRepository->createExecutionInstance();
            $instance->setId($instanceId);
            $instance->uuid = $instanceId;
            $instance->setProcess($definition->getProcess($callableId));
            $dataStore = $this->factory->createDataStore();
            $instance->setDataStore($dataStore);
        } elseif (!$instance) {
            throw new Exception("ProcessRequest {$instanceId} not found");
        }
        $this->requests[$instance->getId()] = $instance;
        $this->requests[$instance->uuid] = $instance;

        return $instance;
    }

    private function findRequestToken($tokenId, $instanceId, $elementId, BpmnDocument $definition): ProcessRequestToken
    {
        if (isset($this->tokens[$tokenId])) {
            return $this->tokens[$tokenId];
        }
        $element = $definition->getElementInstanceById($elementId);
        $process = $element->getProcess();
        $instance = $this->findRequest($instanceId, $definition, $process->getId());
        // find token
        $tokens = $instance->getTokens();
        $token = $tokens->findFirst(function ($token) use ($tokenId) {
            return $token->getId() === $tokenId;
        });
        if ($token) {
            $this->tokens[$token->getId()] = $token;
            $this->tokens[$token->uuid] = $token;

            return $token;
        }
        // find token in the database
        $token = $this->tokenRepository->loadTokenByUid($tokenId);
        if (!$token && !is_numeric($tokenId)) {
            // create token if not found
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
            throw new Exception("ProcessRequestToken {$tokenId} not found");
        }
        // Add token to element and instance
        if ($element instanceof FlowNodeInterface) {
            $element->addToken($instance, $token);
        } else {
            throw new Exception('Invalid element type ' . $elementId . ' for token ' . $tokenId);
        }
        $token->setInstance($instance);
        // Add token to the index array
        $this->tokens[$token->getId()] = $token;
        $this->tokens[$token->uuid] = $token;

        return $token;
    }

    public function unserializeEntity($serialized):EntityInterface
    {
        $definition = $this->findProcessDefinition($serialized['model_id']);
        return $definition->getElementInstanceById($serialized['id']);
    }

    public function unserializeToken(array $serialized):TokenInterface
    {
        $definition = $this->findProcessDefinition($serialized['model_id']);
        $token = $this->findRequestToken($serialized['token_id'], $serialized['instance_id'], $serialized['element_id'], $definition);
        $properties = $serialized['properties'] ?? [];
        unset($properties['id']);
        $properties = array_merge($token->getProperties(), $properties);
        $token->setProperties($properties);

        return $token;
    }

    public function unserializeTokensCollection(array $serialized):CollectionInterface
    {
        $collection = new Collection();
        foreach ($serialized as $item) {
            $collection->push($this->unserializeToken($item));
        }

        return $collection;
    }

    public function unserializeInstance(array $serialized):ExecutionInstanceInterface
    {
        $definition = $this->findProcessDefinition($serialized['model_id']);
        $instance = $this->findRequest($serialized['id'], $definition, $serialized['process_id']);
        $properties = $serialized['properties'] ?? [];
        unset($properties['id']);
        $properties = array_merge($instance->getProperties(), $properties);
        $instance->setProperties($properties);

        return $instance;
    }
}
