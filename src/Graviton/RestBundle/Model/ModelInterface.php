<?php

namespace Graviton\RestBundle\Model;

use Doctrine\Common\Persistence\ObjectRepository;

/**
 * ModelInterface
 *
 * @category GravitonRestBundle
 * @package  Graviton
 * @author   Manuel Kipfer <manuel.kipfer@swisscom.com>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.com
 */
interface ModelInterface
{
    /**
     * Set document repository
     *
     * @param ObjectRepository $repository document repo
     *
     * @return void
     */
    public function setRepository(ObjectRepository $repository);

    /**
     * get repository instance
     *
     * @return ObjectRepository
     */
    public function getRepository();

    /**
     * Find a single record by id
     *
     * @param String $id Record-Id
     *
     * @return Object
     */
    public function find($id);

    /**
     * Find all records
     *
     * @return Array
     */
    public function findAll();

    /**
     * Insert a new Record
     *
     * @param Object $entity Entity
     *
     * @return Object
     */
    public function insertRecord($entity);

    /**
     * Update an existing entity
     *
     * @param String $id     id of entity to update
     * @param Object $entity entity with new data
     *
     * @return Object
     */
    public function updateRecord($id, $entity);

    /**
     * Delete a record by id
     *
     * @param Number $id Record-Id
     *
     * @return Boolean
     */
    public function deleteRecord($id);

    /**
     * Get the name of entity class
     *
     * @return String
     */
    public function getEntityClass();

    /**
     * Get the connection name
     *
     * @return String
     */
    public function getConnectionName();
}
