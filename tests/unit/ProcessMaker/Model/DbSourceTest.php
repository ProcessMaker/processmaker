<?php

namespace ProcessMaker\Model;

use ProcessMaker\Model\Process;
use Tests\TestCase;

class DbSourceTest extends TestCase
{
    /**
     * Test the validation logic of the model
     */
    public function testIsValid()
    {
        // we need a DbSource
        $dbSource = new DbSource();
        $dbSource->process_id = factory(Process::class)->create()->uid;
        $dbSource->type = env('DB_ADAPTER');
        $dbSource->server = env('DB_HOST');
        $dbSource->database_name = env('DB_DATABASE');
        $dbSource->username = env('DB_USERNAME');
        $dbSource->password = env('DB_PASSWORD');
        $dbSource->port = env('DB_PORT');
        $dbSource->encode = 'utf8';
        $dbSource->description = 'Connection for testing purposes';

        $dbSource->type = null;
        $this->assertFalse($dbSource->isValid());
        $dbSource->type = env('DB_ADAPTER');

        // wrong type should be invalid
        $dbSource->type = 'XYZWrongType';
        $this->assertFalse($dbSource->isValid());
        $dbSource->type = env('DB_ADAPTER');

        // an empty db server should be invalid
        $dbSource->server = '';
        $this->assertFalse($dbSource->isValid());
        $dbSource->server = env('DB_HOST');

        // an empty db name should be invalid
        $dbSource->database_name = '';
        $this->assertFalse($dbSource->isValid());
        $dbSource->database_name = env('DB_DATABASE');

        // an empty db name should be invalid
        $dbSource->port = '';
        $this->assertFalse($dbSource->isValid());
        $dbSource->port = env('DB_PORT');

        // an oracle connection with empty tns should be invalid
        $dbSource->type = 'oracle';
        $dbSource->tns = '';
        $dbSource->connection_type = 'TNS';
        $this->assertFalse($dbSource->isValid());
        $dbSource->type = env('DB_ADAPTER');

        // a wrong dbs_encode should be invalid
        $dbSource->encode = 'WrongEncode';
        $this->assertFalse($dbSource->isValid());
        $dbSource->encode = 'utf8';

        // using correct arguments should return valid
        $this->assertTrue($dbSource->isValid());
    }

    /**
     * Tests the logic to determine if a dbSource is a Tns connection
     */
    public function testIsTns()
    {
        // we need a DbSource
        $dbSource = new DbSource();
        $dbSource->process_id = factory(Process::class)->create()->uid;
        $dbSource->type = env('DB_ADAPTER');
        $dbSource->server = env('DB_HOST');
        $dbSource->database_name = env('DB_DATABASE');
        $dbSource->username = env('DB_USERNAME');
        $dbSource->password = env('DB_PASSWORD');
        $dbSource->port = 3306;
        $dbSource->encode = 'utf8';
        $dbSource->description = 'Connection for testing purposes';

        //the original DbSource isn't an Oracle tns
        $this->assertFalse($dbSource->isTns());

        //we change parameter so the connection is an Oracle tns
        $dbSource->type = 'oracle';
        $dbSource->connection_type = 'TNS';
        $this->assertTrue($dbSource->isTns());
    }

    /**
     * Tests if the server attribute is set correctly when using a Tns connection
     */
    public function testGetDbsServerAttribute()
    {
        $dbSource = new DbSource();
        $this->assertEquals($dbSource->getDbsServerAttribute('FakeServer'), 'FakeServer');

        $dbSource->type = 'oracle';
        $dbSource->tns = 'test:tns';
        $dbSource->connection_type = 'TNS';
        $this->assertEquals($dbSource->getDbsServerAttribute('FakeServer'), '[test:tns]');
        $this->assertEquals($dbSource->getDbsDatabaseNameAttribute('FakeServer'), '[test:tns]');
    }

    /**
     * Tests if the database description attribute is set correctly when using a Tns connection
     */
    public function testGetDbsDatabaseDescriptionAttribute()
    {
        $dbSource = new DbSource();
        $aDescription = 'Test Description';
        $dbSource->description = $aDescription;
        $this->assertEquals($dbSource->getDbsDatabaseDescriptionAttribute(), $aDescription);

        $dbSource->type = 'oracle';
        $dbSource->tns = 'test:tns';
        $dbSource->connection_type = 'TNS';
        $this->assertEquals($dbSource->getDbsDatabaseDescriptionAttribute(), '[test:tns]'.$aDescription);
    }
}