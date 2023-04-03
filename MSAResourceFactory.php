<?php

namespace Example;

use Example\Interfaces\HasCheckboxProperties;
use Example\Interfaces\HasDateProperties;
use Example\Interfaces\HasNestedData;
use Example\Interfaces\HasPrimaryKey;
use Example\Interfaces\HasResourceCollections;
use Example\Interfaces\HasResourceProperties;
use Example\Interfaces\HasServiceInfo;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;

/**
 * Handles the complexities of resource construction and destruction.
 *
 * This handles resource rehydration, dehydration, and payloads.
 *
 * A factory is preferential in this scenario since resource objects are
 * converted to and from payloads, handle variable data, sub classes, child
 * objects, interfaces, and expanding construction/destruction complexity.
 */
final class MSAResourceFactory
{
    /**
     * Hydrate a resource object with the supplied data.
     *
     * @param MSAResource $resource the resource to hydrate
     * @param mixed       $data     optional data to populate the resource with
     *
     * @return MSAResource
     *
     * @throws Exception on invalid data, not type cast since data is externally sourced
     * @throws Exception when a property maps to a resource that does not exist
     */
    public static function rehydrate(MSAResource $resource, mixed $data = []): MSAResource
    {
        if (!is_object($data) && !is_array($data)) {
            $type  = gettype($data);
            $class = $resource::class;
            throw new Exception("\$data of $type for $class is unsupported for hydration");
        }

        // Since we have data coming in either objects (payloads) or arrays (hydrations)
        // just make all data an associative array so that it is more predictable.
        $data = self::destructArray((array) $data);

        return self::constructResource($resource, $data);
    }

    /**
     * Dehydrate an object to a compatible data state.
     *
     * @param MSAResource $resource the resource to dehydrate
     *
     * @return array
     */
    public static function dehydrate(MSAResource $resource): array
    {
        return self::destructObject($resource);
    }

    /**
     * Create a payload based on the information contained in a resource.
     *
     * @param MSAResource $resource the resource to generate a payload of
     *
     * @return stdClass
     */
    public static function payload(MSAResource $resource): stdClass
    {
        return self::constructPayload($resource);
    }

    /**
     * Execute all operations required to fulfill a newly constructed resource.
     *
     * @param MSAResource $resource the resource to hydrate
     * @param array       $data     optional data to populate the resource with
     *
     * @return MSAResource
     *
     * @throws Exception when a property maps to a resource that does not exist
     */
    private static function constructResource(MSAResource $resource, array $data): MSAResource
    {
        self::initializeResourceData($resource);
        self::populateServiceInfo($resource, $data);
        self::populateResourceData($resource, $data);
        self::executeResourceInterfaces($resource);

        $resource->afterHydration($data);

        return $resource;
    }

    /**
     * Initialize empty properties such as empty objects and collections.
     *
     * This is useful for states where no data was passed in but we need empty
     * objects constructed so that they exist.
     *
     * @param MSAResource $resource the resource to initialize
     *
     * @return void
     */
    private static function initializeResourceData(MSAResource $resource): void
    {
        $contracts = class_implements($resource);

        if (in_array(HasServiceInfo::class, $contracts)) {
            if (!property_exists($resource, 'serviceInfo')) {
                $resource->serviceInfo = new stdClass();
            }
        }

        if (in_array(HasResourceProperties::class, $contracts)) {
            foreach ($resource->getResourcePropertyMaps() as $property => $class) {
                $resource->$property = new $class();
            }
        }

        if (in_array(HasResourceCollections::class, $contracts)) {
            foreach ($resource->getResourceCollectionMaps() as $property => $class) {
                $resource->$property = collect([]);
            }
        }
    }

    /**
     * Populate the overall service information for the resource.
     *
     * Service information is unique in that it comes from the transporter
     * combined with the service entries, which we use a collection for instead
     * of a standard object or resource, therefore we need to break it out into
     * its own property so we can access it while keeping our service entry
     * collections intact.
     *
     * @param MSAResource $resource the resource to hydrate
     * @param mixed       $data     optional data to populate the resource with
     *
     * @return void
     */
    private static function populateServiceInfo(MSAResource $resource, array $data): void
    {
        $contracts = class_implements($resource);

        if (in_array(HasServiceInfo::class, $contracts)) {
            if (in_array(HasNestedData::class, $contracts)) {
                $key = $resource->getNestedDataKey();
                if (!empty($data[$key])) {
                    $data = $data[$key];
                }
            }

            foreach ($resource->getServiceInfoMaps() as $property => $class) {
                if (array_key_exists($property, $data)) {
                    if (is_array($data[$property]) && array_key_exists('entries', $data[$property])) {
                        // populate service info as it comes from the microservice
                        $serviceInfo = array_merge((array) $resource->serviceInfo->$property, $data[$property]);

                        $resource->serviceInfo->$property = (object) $serviceInfo;
                        unset($resource->serviceInfo->$property->entries);
                    } elseif (!empty($data['serviceInfo'][$property])) {
                        // populate service info as it comes from livewire hydration
                        $resource->serviceInfo->$property = (object) $data['serviceInfo'][$property];
                    }
                }
            }
        }
    }

    /**
     * Populate the resource based on the supplied data.
     *
     * @param MSAResource $resource the resource to populate
     * @param array       $data     the data to populate the resource with
     *
     * @return void
     *
     * @throws Exception when a property maps to a resource that does not exist
     */
    private static function populateResourceData(MSAResource $resource, array $data): void
    {
        if (!empty($data)) {
            $key = '';
            if (in_array(HasNestedData::class, class_implements($resource))) {
                $key = $resource->getNestedDataKey();
            }

            foreach ($data as $property => $value) {
                if ((strtolower($property) === strtolower($key)) && !empty($value)) {
                    foreach ($value as $dataProperty => $dataValue) {
                        self::populateResourceProperty($resource, $dataProperty, $dataValue);
                    }
                } else {
                    self::populateResourceProperty($resource, $property, $value);
                }
            }
        }
    }

    /**
     * Populate a specific property on the resource with the supplied value.
     *
     * This can support populating normal values, but also instantiating new
     * child resources or even collections of child resources.
     *
     * @param MSAResource $resource the resource to populate the property on
     * @param string      $property name of the property to populate
     * @param mixed       $value    what to populate the property with
     *
     * @return void
     *
     * @throws Exception when a property maps to a resource that does not exist
     */
    private static function populateResourceProperty(MSAResource $resource, string $property, mixed $value): void
    {
        $propertyMap = self::getCombinedResourcePropertyMap($resource);

        if (array_key_exists($property, $propertyMap)) {
            $class = $propertyMap[$property];

            if (!class_exists($class)) {
                throw new Exception("Error attempting to hydrate property: $property as $class. Class does not exist.");
            }

            if (self::hasCollectionProperty($resource, $property)) {
                if (self::hasServiceInfoProperty($resource, $property)) {
                    if (is_object($value) && property_exists($value, 'entries')) {
                        $value = $value->entries;
                    } elseif (is_array($value) && array_key_exists('entries', $value)) {
                        $value = $value['entries'];
                    }
                }

                $resources = collect([]);
                foreach ($value as $data) {
                    $resources->push(new $class($data));
                }
                $value = $resources;
            } else {
                $value = new $class($value);
            }
        }

        // service information is handled by its own dedicated method
        if ('serviceInfo' !== $property && !is_null($value)) {
            $resource->$property = $value;
        }
    }

    /**
     * Execute all interface contracts on a resource.
     *
     * @param MSAResource $resource the resource to execute interfaces on
     *
     * @return void
     */
    private static function executeResourceInterfaces(MSAResource $resource): void
    {
        $contracts = class_implements($resource);

        if (in_array(HasCheckboxProperties::class, $contracts)) {
            foreach ($resource->getCheckboxPropertyNames() as $property) {
                if (!is_null($resource->$property)) {
                    $resource->$property = (int) $resource->$property;
                }
            }
        }

        if (in_array(HasDateProperties::class, $contracts)) {
            foreach ($resource->getDatePropertyNames() as $property) {
                if (!empty($resource->$property)) {
                    $resource->$property = Carbon::parseForDisplay($resource->$property);
                }
            }
        }

        if (in_array(HasPrimaryKey::class, $contracts)) {
            $key = $resource->getKeyName();
            if (empty($resource->$key)) {
                $resource->$key = (string) Str::uuid();
            }
        }
    }

    /**
     * Parse an object into an array.
     *
     * This will include all child properties of the object, collections, and
     * other resources.
     *
     * @param mixed $object the object to parse
     *
     * @return array
     */
    private static function destructObject(mixed $object): array
    {
        $data = [];

        $reflection = new \ReflectionObject($object);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name  = $property->getName();
            $value = $object->$name;

            if ($value instanceof Collection) {
                $value = $value->toArray();
            }

            if (is_object($value)) {
                $data[$name] = self::destructObject($value);
            } elseif (is_array($value)) {
                $data[$name] = self::destructArray($value);
            } else {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    /**
     * Parse an array into another array.
     *
     * This will include all child keys of the array, collections, and resources.
     *
     * @param array $array the array to parse
     *
     * @return array
     */
    private static function destructArray(array $array): array
    {
        $data = [];

        foreach ($array as $name => $value) {
            if (is_object($value)) {
                $data[$name] = self::destructObject($value);
            } elseif (is_array($value)) {
                $data[$name] = self::destructArray($value);
            } else {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    /**
     * Generate a payload based on the resource including all child resources
     * and collections therein.
     *
     * @param MSAResource $resource the resource to generate a payload of
     *
     * @return stdClass
     */
    private static function constructPayload(MSAResource $resource): stdClass
    {
        $resource = clone $resource;

        self::executePayloadInterfaces($resource);
        $resource->beforePayload();

        return self::constructStandardObject($resource);
    }

    /**
     * Execute all interface contracts on a resource destined for a payload.
     *
     * @param MSAResource $resource the resource to execute interfaces on
     *
     * @return void
     */
    private static function executePayloadInterfaces(MSAResource $resource): void
    {
        $contracts = class_implements($resource);

        if (in_array(HasCheckboxProperties::class, $contracts)) {
            foreach ($resource->getCheckboxPropertyNames() as $property) {
                if (!is_null($resource->$property)) {
                    $resource->$property = (bool) $resource->$property;
                }
            }
        }

        if (in_array(HasDateProperties::class, $contracts)) {
            foreach ($resource->getDatePropertyNames() as $property) {
                if (!empty($resource->$property)) {
                    $resource->$property = Carbon::parseForPayload($resource->$property);
                }
            }
        }

        if (in_array(HasResourceCollections::class, $contracts)) {
            foreach ($resource->getResourceCollectionMaps() as $property => $class) {
                $resources = [];
                foreach ($resource->$property as $subResource) {
                    $resources[] = self::constructPayload($subResource);
                }

                if (self::hasServiceInfoProperty($resource, $property)) {
                    $resource->$property          = $resource->serviceInfo->$property;
                    $resource->$property->entries = $resources;
                } else {
                    $resource->$property = $resources;
                }
            }
        }

        if (in_array(HasResourceProperties::class, $contracts)) {
            foreach ($resource->getResourcePropertyMaps() as $property => $class) {
                $resource->$property = self::constructPayload($resource->$property);
            }
        }
    }

    /**
     * Convert a resource into a standard object.
     *
     * @param MSAResource $resource the resource to convert
     *
     * @return stdClass
     */
    private static function constructStandardObject(MSAResource $resource): stdClass
    {
        $contracts = class_implements($resource);

        $stub = new stdClass();

        if (in_array(HasNestedData::class, $contracts)) {
            if (in_array(HasPrimaryKey::class, $contracts)) {
                $primaryKey        = $resource->getKeyName();
                $stub->$primaryKey = $resource->$primaryKey;
            }

            $nestedKey        = $resource->getNestedDataKey();
            $stub->$nestedKey = new stdClass();

            foreach (self::getCombinedResourcePropertyMap($resource) as $property => $class) {
                if (isset($resource->$property)) {
                    $stub->$nestedKey->$property = $resource->$property;
                }
            }
        } else {
            foreach ($resource as $property => $value) {
                $stub->$property = $value;
            }
        }

        return $stub;
    }

    /**
     * Retrieve a list of all properties that contain either a resource or a collection
     * of resources mapped to the name of the resource that is stored under them.
     *
     * @param MSAResource $resource resource to retireve the property names from
     *
     * @return array
     */
    private static function getCombinedResourcePropertyMap(MSAResource $resource): array
    {
        $contracts = class_implements($resource);

        $resourceCollections = [];
        if (in_array(HasResourceCollections::class, $contracts)) {
            $resourceCollections = $resource->getResourceCollectionMaps();
        }

        $resourceProperties = [];
        if (in_array(HasResourceProperties::class, $contracts)) {
            $resourceProperties = $resource->getResourcePropertyMaps();
        }

        return array_merge($resourceCollections, $resourceProperties);
    }

    /**
     * Check if a property on the provided resource should contain a collection of resources.
     *
     * @param MSAResource $resource the resource to check the properties of
     * @param string      $property the name of the property to check
     *
     * @return bool
     */
    private static function hasCollectionProperty(MSAResource $resource, string $property): bool
    {
        $hasMapping = false;

        if (in_array(HasResourceCollections::class, class_implements($resource))) {
            if (array_key_exists($property, $resource->getResourceCollectionMaps())) {
                $hasMapping = true;
            }
        }

        return $hasMapping;
    }

    /**
     * Check if a property on the provided resource should contain additional service information.
     *
     * @param MSAResource $resource the resource to check the property on
     * @param string      $property the name of the property to check
     *
     * @return bool
     */
    private static function hasServiceInfoProperty(MSAResource $resource, string $property): bool
    {
        $hasMapping = false;

        if (in_array(HasServiceInfo::class, class_implements($resource))) {
            if (array_key_exists($property, $resource->getServiceInfoMaps())) {
                $hasMapping = true;
            }
        }

        return $hasMapping;
    }
}
