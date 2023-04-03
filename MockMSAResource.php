<?php

namespace Example;

use Example\Interfaces\HasPrimaryKey;

/**
 * Class that represents the mock resource portion of the service model being returned from the micro service architecture (MSA).
 */
class MockMSAResource extends MSAResource implements HasPrimaryKey
{
    /**
     * @var string value to identify this employment entry by
     */
    public string $resourceId = '';

    /**
     * @var string value to identify this employment entry by
     */
    public string $resourceData = '';

    /**
     * Retrieve the name of the primary key.
     *
     * @return string
     */
    public function getKeyName(): string
    {
        return 'resourceId';
    }

}
