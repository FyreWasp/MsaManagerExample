<?php

namespace Example\Interfaces;

/**
 * Contract for properties that should be populated with resources.
 */
interface HasResourceProperties
{
    /**
     * Retrieve a list of all properties and the resource they should be populated with.
     *
     * example return ['propertyName' => \App\MSAResources\MSAResource::class];
     *
     * @return array
     */
    public function getResourcePropertyMaps(): array;
}
