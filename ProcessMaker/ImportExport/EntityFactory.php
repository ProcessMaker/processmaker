<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\ImportExport\Contracts\Entity;
use Symfony\Component\Yaml\Yaml;

class EntityFactory
{
    public $relationships;

    public function __construct(
        private EntityStore $store,
    ) {
    }

    public function make($name) : Entity
    {
        if (!$this->store->get($name)) {
            $definition = $this->entityDefinition($name);
            $class = '\\ProcessMaker\\ImportExport\\Entities\\' . $definition['type'];
            $params = $definition['params'];
            $entity = new $class($params);
            $this->store->set($name, $entity);

            if (isset($definition['references'])) {
                $references = $this->references($definition['references']);
                $entity->setReferences($references);
            }
        }

        return $this->store->get($name);
    }

    private function references($referencesDefinition)
    {
        $references = [];
        foreach ($referencesDefinition as $reference) {
            $name = $reference['entity'];
            $entity = $this->make($name);
            $strategyClass = '\\ProcessMaker\\ImportExport\\Strategies\\' . $reference['strategy'];
            $strategy = new $strategyClass($entity, $reference['params'] ?? []);
            $reference = new Reference($entity, $strategy);
            $strategy->setReference($reference);
            $references[] = $reference;
        }

        return $references;
    }

    public function entityDefinition($name)
    {
        if (!$this->relationships) {
            $this->relationships = Yaml::parseFile(__DIR__ . '/relationships.yaml');
        }

        return $this->relationships[$name];
    }
}
