<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use ProcessMaker\Models\EnvironmentVariable;
use Tests\Unit\Shared\BinaryUuidTest;
use Tests\TestCase;

class EnvironmentVariableTest extends TestCase
{
    private $class = EnvironmentVariable::class;
    use BinaryUuidTest;

    /**
     * Tests specific to this model
     */
}