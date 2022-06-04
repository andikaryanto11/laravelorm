<?php

namespace LaravelOrm\Entities;

use LaravelOrm\Interfaces\IEntity;
use Exception;

class EntityUnit
{
    /**
     * Prepare entity that will be persisted. Will persisted after entity unit flush
     *
     * @param IEntity $entity
     * @return EntityUnit
     */
    public function preparePersistence(IEntity $entity)
    {
        $entityUnit = EntityScope::getInstance();
        $entityUnit->addEntity(EntityScope::PERFORM_ADD_UPDATE, $entity);
        return $this;
    }

    /**
     * Prepare entity that will be removed. Will removed after entity unit flush
     *
     * @param IEntity $entity
     * @return EntityUnit
     */
    public function prepareRemove(IEntity $entity)
    {
        $entityUnit = EntityScope::getInstance();
        $entityUnit->addEntity(EntityScope::PERFORM_DELETE, $entity);
        return $this;
    }

    /**
     * Persist all entities to table
     *
     * @return void
     */
    public function flush()
    {
        $entityScope = EntityScope::getInstance();

        $entityManager = new EntityManager();
        $entityManager->beginTransaction();

        try {
            $entityScope->sort();
            foreach ($entityScope->getEntities() as $value) {
                if ($value['perform'] == EntityScope::PERFORM_ADD_UPDATE) {
                    $entityManager->persist($value['entity']);
                } elseif ($value['perform'] == EntityScope::PERFORM_DELETE) {
                    $entityManager->remove($value['entity']);
                }
            }
            $entityManager->commit();
            $entityScope->clean();
        } catch (Exception $e) {
            $entityManager->rollback();
            $entityScope->clean();
            throw $e;
        }
    }
}
