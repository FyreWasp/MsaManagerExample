<?php


namespace Example;


/**
 * interface to enforce the contract of what should be expected from any micro service.
 */
interface TransporterInterface
{
    public function execute(string $url, $payload = null);
}
