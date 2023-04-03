<?php

namespace Example\Interfaces;

/**
 * Contract for when an object has resource properties that need sorting.
 */
interface HasSortableResources
{
    /**
     * Retrieve a list of all collection properties and the resource that should be sorted.
     *
     * example return ['propertyName' => \App\MSAResources\MSAResource::class];
     *
     * @return array
     */
    public function getSortableResourceMaps(): array;
}
