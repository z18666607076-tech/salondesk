<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * PHPUnit's force="true" writes $_ENV. Laravel reads $_SERVER first,
     * so a CI variable such as CACHE_STORE=redis would ignore phpunit.xml.
     */
    public function createApplication()
    {
        foreach ($_ENV as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $_SERVER[$key] = $value;
            }
        }

        return parent::createApplication();
    }
}
