<?php

namespace Example;

use Illuminate\Support\Collection;

/**
 * Interface that establishes the contract for all MSAResource services.
 */
interface MSAResourceServiceInterface
{
    /**
     * create a new model instance through the micro service architecture.
     *
     * @param MSAResource $model
     *
     * @return string the id of the newly created element
     */
    public function create(MSAResource $model): string;

    /**
     * update an existing model instance through the micro service architecture.
     *
     * @param MSAResource $model
     *
     * @return bool true on successful update
     */
    public function update(MSAResource $model): bool;

    /**
     * deletes an existing model instance through the micro service architecture.
     *
     * @param MSAResource $model
     *
     * @return bool true on successful delete
     */
    public function delete(MSAResource $model): bool;

    /**
     * gets a model instance through the micro service architecture.
     *
     * @param int|string $id the identifier for the MSAResource
     *
     * @return MSAResource|null a data instance of the expected element
     */
    public function find($id);

    /**
     * searches for a collection of the model instances through the micro service architecture.
     *
     * @param array $criteria an array of criteria to search by
     *
     * @return Collection a collection of results matching the provided criteria
     */
    public function query(array $criteria): Collection;
}
