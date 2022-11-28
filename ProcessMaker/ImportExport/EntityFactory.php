<?php

namespace ProcessMaker\ImportExport;

use ProcessMaker\ImportExport\Contracts\Entity;
use Symfony\Component\Yaml\Yaml;

class EntityFactory
{
    public function __construct(
        private EntityStore $store,
    ) {
    }

    public function build()
    {
        foreach ($this->relationships() as $entityName => $definition) {
            $entityClass = '\\ProcessMaker\\ImportExport\\Entities\\' . $definition['type'];
            $params = $definition['params'] ?? [];
            $entity = app($entityClass, [
                'name' => $entityName,
                'params' => $params,
                'definition' => $definition
            ]);
            $this->store->add($entity);
        }

        foreach ($this->store->all() as $entity) {
            $this->buildReferences($entity);
        }
    }

    private function buildReferences($entity)
    {
        $references = [];
        $referenceDefintions = $entity->definition['references'] ?? [];
        foreach ($referenceDefintions as $reference) {
            $destinationEntity = $this->store->get($reference['entity']);
            $strategyClass = '\\ProcessMaker\\ImportExport\\Strategies\\' . $reference['strategy'];
            $params = $reference['params'] ?? [];
            $strategy = app($strategyClass, [
                'sourceEntity' => $entity,
                'destinationEntity' => $destinationEntity,
                'params' => $params
            ]);
            $references[] = $strategy;
        }

        $entity->setReferences($references);
    }

    public function relationships()
    {
        return Yaml::parseFile(__DIR__ . '/relationships.yaml');
    }
}
