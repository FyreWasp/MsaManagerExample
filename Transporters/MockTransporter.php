<?php

namespace Example\Transporters;


/**
 * Class to be used for simulating a user experience by faking what would be returned from the micro service architecture.
 */
class MockTransporter implements TransporterInterface
{
    /**
     * constants to represent the various endpoints or sample input values.
     */
    public const FIND_MOCK_SERVICE_ENTRY = 'mock.find.service';
    public const GUID_001                = '3d9385cc-d26b-4af6-9e9c-440a45d8eca1';

    /**
     * the main mocked method to simulate.
     *
     * @param string $url     the endpoint to be triggered
     * @param mixed  $payload will vary depending on the endpoing being simulated
     *
     * @return mixed could return a wide array of expected values depending on the endpoint called
     *
     * @throws \Exception when it cannot locate the url for then mock endpoint
     */
    public function execute($url, $payload = null): mixed
    {
        if (null === $payload) {
            $payload = (object) [];
        }

        switch ($url) {
            case self::FIND_MOCK_SERVICE_ENTRY:
                $return = $this->findMockService($payload);
                break;
            default:
                throw new \Exception('invalid mock service url');
        }

        return $return;
    }

    /**
     * simulates the response from microservice when searching for a specific service record.
     *
     * @param string $id the id of the service
     *
     * @return object|null returns the found invitation as a json object or null if not found
     */
    protected function findMockService(string $id): ?object
    {
        switch ($id) {
            case self::GUID_001:
                $mockService = json_encode([
                    'resourceData'  => 'Test Data, Test Data',
                ]);
                break;
            default:
                $mockService = null;
        }

        return $mockService;
    }
}
