<?php

namespace Example\Interfaces;

/**
 * Contract for when properties will be dates that need formatting.
 */
interface HasDateProperties
{
    /**
     * Retrieve the names of all properties that are formatted dates.
     *
     * @return array
     */
    public function getDatePropertyNames(): array;
}
