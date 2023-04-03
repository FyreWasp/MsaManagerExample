<?php

namespace Example;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Base functionality for all services.
 */
abstract class AbstractService
{
    /**
     * @var TransporterInterface data broker to handle data requests
     */
    protected TransporterInterface $transporter;

    /**
     * Instantiate the service.
     *
     * @param TransporterInterface $transporter
     */
    public function __construct(TransporterInterface $transporter)
    {
        $this->transporter = $transporter;
    }

    /**
     * Convert json to an object so that it can be used to populate a resource.
     *
     * @throws \Exception if property is instantiated as an object for a class that does not exist or invalid json
     *
     * @returns object
     */
    protected function getObjectFromJson(string $json)
    {
        $object = new \stdClass();

        if (!empty($json)) {
            if (!Str::isValidJson($json)) {
                $message = 'Error attempting to set properties for '.__CLASS__.', invalid json provided.';

                Log::critical($message, [
                    'class'  => __CLASS__,
                    'method' => __METHOD__,
                    'json'   => $json,
                ]);

                throw new \Exception($message);
            }

            $object = json_decode($json);
        }

        return $object;
    }
}
