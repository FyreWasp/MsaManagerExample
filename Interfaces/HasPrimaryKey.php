<?php

namespace Example\Interfaces;

/**
 * Contract for primary keys.
 */
interface HasPrimaryKey
{
    /**
     * Retrieve the primary key name.
     *
     * @return string
     */
    public function getKeyName(): string;
}
