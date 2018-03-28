<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    use CreatesApplication;

    /**
     * Run additional setUps from traits.
     *
     */
    protected function setUp()
    {
    	parent::setUp();
        foreach (get_class_methods($this) as $method) {
            $imethod = strtolower($method);
            if (strpos($imethod, 'setup') === 0 && $imethod !== 'setup') {
                $this->$method();
            }
        }
    }

    /**
     * Run additional tearDowns from traits.
     *
     */
    protected function tearDown()
    {
    	parent::tearDown();
        foreach (get_class_methods($this) as $method) {
            $imethod = strtolower($method);
            if (strpos($imethod, 'teardown') === 0 && $imethod !== 'teardown') {
                $this->$method();
            }
        }
    }
}
