<?php

namespace Example\Interfaces;

/**
 * Contract for properties that are mapped as multiple resources.
 */
interface HasResourceCollections
{
    /**
     * Retrieve a list of all properties that are collections and the resource they should be populated with.
     *
     * example return ['propertyName' => \App\MSAResources\MSAResource::class];
     *
     * @return array
     */
    public function getResourceCollectionMaps(): array;
}
