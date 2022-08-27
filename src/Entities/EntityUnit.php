<?php

namespace LaravelOrm\Entities;

use LaravelOrm\Interfaces\IEntity;
use Exception;
use LaravelOrm\Exception\DatabaseException;
use PDOException;

class EntityUnit
{
    /**
     * Prepare entity that will be validated and persisted.
     * Will persisted after entity unit flush
     *
     * @see entity IEntity->validate()
     *
     * @param IEntity $entity
     * @param bool $needValidate - validate entity that will be persisted
     * @return EntityUnit
     */
    public function preparePersistence(IEntity $entity, bool $needValidate = true)
    {

        $entityScope = EntityScope::getInstance();
        $entityScope->addEntity(EntityScope::PERFORM_ADD_UPDATE, $entity, $needValidate);
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
        $entityScope = EntityScope::getInstance();
        $entityScope->addEntity(EntityScope::PERFORM_DELETE, $entity);
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
                    $entityManager->persist($value['entity'], $value['needValidate']);
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
