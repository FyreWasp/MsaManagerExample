<?php

namespace Example\Interfaces;

/**
 * Contract for when resources are returned with their data nested under another key.
 *
 * Some items coming back from the MSA will contain data nodes to contain data being
 * set by the user, this key represents when to apply its data as properties.
 */
interface HasNestedData
{
    /**
     * Retrieve the name of the key that the data for this resource is nested under.
     *
     * @return string
     */
    public function getNestedDataKey(): string;
}
