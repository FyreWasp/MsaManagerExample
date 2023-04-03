<?php

namespace Example\Interfaces;

/**
 * Contract for when services are nested because they contain additional information.
 *
 * Sometimes the microservice wants to relay additional information about services
 * so it will change the structure of the information being returned so that entries
 * aren't the only thing contained in that node.
 *
 * This interface allows us identify these so that they may be parsed properly
 * when constructing resources and payloads.
 *
 */
interface HasServiceInfo
{
    /**
     * Retrieve all necessary fields that contain service info and which service they correspond.
     *
     * example return ['propertyName' => \App\Constants\Services::SERVICE_NAME];
     *
     * @return array
     */
    public function getServiceInfoMaps(): array;
}
