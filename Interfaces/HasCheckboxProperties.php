<?php

namespace Example\Interfaces;

/**
 * Contract for when properties will be mapped to checkboxes in the UI.
 */
interface HasCheckboxProperties
{
    /**
     * Retrieve the names of all properties that map to checkboxes.
     *
     * @return array
     */
    public function getCheckboxPropertyNames(): array;
}
