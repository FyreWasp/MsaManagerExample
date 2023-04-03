<?php

namespace Example;

use Example\MSAResourceFactory;
use Example\MSAResource;
use Example\TransporterInterface;
use Example\Transporters\MockTransporter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * class to handle any actions related to an service.
 */
class MockService extends AbstractService implements MSAResourceServiceInterface
{
    /**
     * endpoint key to be used when performing a query call.
     */
    public const QUERY_SERVICE_URI = 'dom.mock-service.entry';

    /**
     * Endpoint key to be used when creating new services.
     */
    public const CREATE_SERVICE_URI = 'app.mock-service.entry.create';

    /**
     * Endpoint to use when saving full service entries.
     */
    public const SAVE_SERVICE_URI = 'app.mock-service.entry.update';

    /**
     * Endpoint to use when submitting full services.
     */
    public const SUBMIT_SERVICE_URI = 'app.mock-service.entry.submit';

    /**
     * @var array store some services in this request cycle to limit transporter interactions
     */
    protected $store = [];

    /**
     * Instantiate the service.
     *
     * @param MockTransporter $transporter
     */
    public function __construct(MockTransporter $transporter)
    {
        //In Laravel would hav use Service Providers to manage dependencies
        parent::__construct($transporter);
    }

    /**
     * Retrieves an service entry from the provided micro service if the service entry exists.
     *
     * @param string $id the service uuid to retrieve
     *
     * @return |MSAResource|null
     */
    public function find($id)
    {
        if (!array_key_exists($id, $this->store)) {
            $criteria = ['resourceId' => $id];

            $this->store[$id] = $this->query($criteria)->first();
        }

        return $this->store[$id];
    }


    /**
     * Creates a new service.
     *
     * @param MSAResource $model
     *
     * @return string
     *
     * @throws \Exception if property is instantiated as an object for a class that does not exist or invalid json
     */
    public function create(MSAResource $model): string
    {
        $payload = [
            'resourceId'   => $model->resourceId,
            'consumerId' => $model->consumerId,
            'resourceData'  => ['candidate' => null],
        ];

        try {
            $json    = $this->transporter->execute(self::CREATE_SERVICE_URI, $payload);
            $object  = $this->getObjectFromJson($json);
            $service   = new MockMSAResource($object);
            $serviceId = $service->serviceId;
        } catch (\Exception $e) {
            $errorMessage = 'Unknown error occurred while attempting to parse the json after creating an service.';
            $errorContext = [
                'exception' => $e->getMessage(),
                'payload'   => $payload,
                'trace'     => $e->getTrace(),
            ];
            Log::error($errorMessage, $errorContext);

            $serviceId = '';
        }

        return $serviceId;
    }

    /**
     * Updates an existing service using the micro service architecture.
     *
     * @param MSAResource $data the model to be updated
     *
     * @return bool true on successful update
     */
    public function update(MSAResource $data): bool
    {
        $status  = false;
        $payload = MSAResourceFactory::payload($data);

        try {
            $json   = $this->transporter->execute(self::SAVE_SERVICE_URI, $payload);
            $object = $this->getObjectFromJson($json);
            $status = true;
        } catch (\Exception $e) {
            $errorMessage = 'Unknown error occurred while saving the service.';
            $errorContext = [
                'exception' => $e->getMessage(),
                'payload'   => $payload,
                'trace'     => $e->getTrace(),
            ];
            Log::error($errorMessage, $errorContext);
        }

        return $status;
    }

    /**
     * Submit an service to the microservice for processing.
     * An service should only be submitted once.
     *
     * @param MockMSAResource $service object to submit
     *
     * @return string[] array repsenting the result of the attempt to submit the service
     */
    public function submit(MockMSAResource $service): array
    {
        $criteria = ['serviceId' => $service->serviceId];
        $response = ['status' => 'error'];
        try {
            $json = $this->transporter->execute(self::SUBMIT_SERVICE_URI, $criteria);

            $response = json_decode($json, true);
        } catch (\Exception $e) {
            $errorMessage = 'Unknown error occurred while attempting to submit the service.';
            $errorContext = [
                'exception' => $e->getMessage(),
                'criteria'  => $criteria,
                'trace'     => $e->getTrace(),
            ];
            Log::error($errorMessage, $errorContext);
            $response['errorData'] = $errorContext;
        }

        return $response;
    }

    /**
     * Deletes an service using the micro services architecture.
     *
     * @param MSAResource $data the model to be deleted
     *
     * @return bool
     */
    public function delete(MSAResource $data): bool
    {
        // TODO: Implement delete() method.
    }

    /**
     * Searches for one or more services based on criteria.
     *
     * @param array $criteria an array of criteria parameters
     *
     * @return Collection a collection of services that match the supplied criteria
     *
     * @throws \Exception if property is instantiated as an object for a class that does not exist or invalid json
     */
    public function query(array $criteria): Collection
    {
        try {
            $json   = $this->transporter->execute(self::QUERY_SERVICE_URI, $criteria);
            $object = $this->getObjectFromJson($json);

            $servicesCollection = collect([]);

            foreach ($object as $data) {
                $service  = new MockMSAResource($data);
                $servicesCollection->push($service);
            }
        } catch (\Exception $e) {
            $errorMessage = 'Unknown error occurred while attempting to parse the json of an service.';
            $errorContext = [
                'exception' => $e->getMessage(),
                'criteria'  => $criteria,
                'trace'     => $e->getTrace(),
            ];
            Log::error($errorMessage, $errorContext);

            $servicesCollection = collect([]);
        }

        return $servicesCollection;
    }
}
