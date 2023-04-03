<?php


namespace Example;

use Example\ResourceFactory;
use Example\Traits\Cloneable;


/**
 * Class to represent data models that have been returned from a micro service as they will not adhere to laravel's
 * standard eloquent models.
 */
class MSAResource
{
    // allows a deep clone to be created for any MSAResource
    use Cloneable;

    /**
     * MicroServiceDataModel constructor.
     *
     * @param object|array $data decoded json from the service to populate the resource from
     *
     * @throws \Exception if attempted property class casting fails
     */
    public function __construct($data = [])
    {
        MSAResourceFactory::rehydrate($this, $data);
    }

    /**
     * Converts the MSA Resource to an array so that we can pass it into a validator.
     *
     * @return array
     */
    public function toArray(): array
    {
        return MSAResourceFactory::dehydrate($this);
    }

    /**
     * Runs any customizations to the resource that needs to run after hydration finishes.
     *
     * @param mixed $data information to populate the resource with
     *
     * @return void
     */
    public function afterHydration($data): void
    {
        return;
    }

    /**
     * Runs any customizations right before the object is destructed and payload sent.
     *
     * @return void
     */
    public function beforePayload(): void
    {
        return;
    }

}
