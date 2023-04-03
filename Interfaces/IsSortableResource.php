<?php

namespace Example\Interfaces;

use App\Constants\Sorting;

/**
 * Contract for MSAResources that must be fulfilled for sorting capabilities.
 */
interface IsSortableResource
{
    /**
     * Retrieve an array with all applicable sorting parameters.
     *
     * example return ['sortKey', Sorting::TYPE, Sorting::DIR];
     *
     * @return array
     */
    public static function getSortParameters(): array;
}
