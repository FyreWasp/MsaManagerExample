<?php

namespace Example\Traits;

use Illuminate\Support\Collection;

/**
 * Trait that allows deep clones to be produced by using the clone function.
 */
trait Cloneable
{
    /**
     * Magic method that defines what to do when clone is called on the object
     * used here to create a deep clone of any object.
     */
    public function __clone()
    {
        foreach ($this as $property => $value) {
            if (is_a($value, Collection::class)) {
                // need to iterate over collections specifically because they can contain a collection of objects
                $collection = collect([]);
                foreach ($value as $item) {
                    $collection->push(clone $item);
                }
                $this->{$property} = $collection;
            } elseif (is_array($value)) {
                // need to iterate over arrays specifically because they could contain an array of objects
                $array = [];
                foreach ($value as $key => $item) {
                    if (is_array($item)) {
                        $array[$key] = $item;
                    } elseif (is_object($item)) {
                        $array[$key] = clone $item;
                    } else {
                        $array[$key] = $item;
                    }
                }
                $this->{$property} = $array;
            } elseif (is_object($value)) {
                $this->{$property} = clone $this->{$property};
            } else {
                $this->{$property} = $value;
            }
        }
    }
}
