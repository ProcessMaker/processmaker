<?php

namespace ProcessMaker\Model;

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
        $dbSource->PRO_UID = "AnyProcessID";
        $dbSource->DBS_TYPE = env('DB_ADAPTER');
        $dbSource->DBS_SERVER = env('DB_HOST');
        $dbSource->DBS_DATABASE_NAME = env('DB_DATABASE');
        $dbSource->DBS_USERNAME = env('DB_USERNAME');
        $dbSource->DBS_PASSWORD = env('DB_PASSWORD');
        $dbSource->DBS_PORT = env('DB_PORT');
        $dbSource->DBS_ENCODE = 'utf8';
        $dbSource->DBS_DESCRIPTION = 'Connection for testing purposes';

        $dbSource->DBS_TYPE = null;
        $this->assertFalse($dbSource->isValid());
        $dbSource->DBS_TYPE = env('DB_ADAPTER');

        // wrong type should be invalid
        $dbSource->DBS_TYPE = 'XYZWrongType';
        $this->assertFalse($dbSource->isValid());
        $dbSource->DBS_TYPE = env('DB_ADAPTER');

        // an empty db server should be invalid
        $dbSource->DBS_SERVER = '';
        $this->assertFalse($dbSource->isValid());
        $dbSource->DBS_SERVER = env('DB_HOST');

        // an empty db name should be invalid
        $dbSource->DBS_DATABASE_NAME = '';
        $this->assertFalse($dbSource->isValid());
        $dbSource->DBS_DATABASE_NAME = env('DB_DATABASE');

        // an empty db name should be invalid
        $dbSource->DBS_PORT = '';
        $this->assertFalse($dbSource->isValid());
        $dbSource->DBS_PORT = env('DB_PORT');

        // an oracle connection with empty tns should be invalid
        $dbSource->DBS_TYPE = 'oracle';
        $dbSource->DBS_TNS = '';
        $dbSource->DBS_CONNECTION_TYPE = 'TNS';
        $this->assertFalse($dbSource->isValid());
        $dbSource->DBS_TYPE = env('DB_ADAPTER');

        // a wrong dbs_encode should be invalid
        $dbSource->DBS_ENCODE = 'WrongEncode';
        $this->assertFalse($dbSource->isValid());
        $dbSource->DBS_ENCODE = 'utf8';

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
        $dbSource->PRO_UID = "AnyProcessID";
        $dbSource->DBS_TYPE = env('DB_ADAPTER');
        $dbSource->DBS_SERVER = env('DB_HOST');
        $dbSource->DBS_DATABASE_NAME = env('DB_DATABASE');
        $dbSource->DBS_USERNAME = env('DB_USERNAME');
        $dbSource->DBS_PASSWORD = env('DB_PASSWORD');
        $dbSource->DBS_PORT = 3306;
        $dbSource->DBS_ENCODE = 'utf8';
        $dbSource->DBS_DESCRIPTION = 'Connection for testing purposes';

        //the original DbSource isn't an Oracle tns
        $this->assertFalse($dbSource->isTns());

        //we change parameter so the connection is an Oracle tns
        $dbSource->DBS_TYPE = 'oracle';
        $dbSource->DBS_CONNECTION_TYPE = 'TNS';
        $this->assertTrue($dbSource->isTns());
    }

    /**
     * Tests if the server attribute is set correctly when using a Tns connection
     */
    public function testGetDbsServerAttribute()
    {
        $dbSource = new DbSource();
        $this->assertEquals($dbSource->getDbsServerAttribute('FakeServer'), 'FakeServer');

        $dbSource->DBS_TYPE = 'oracle';
        $dbSource->DBS_TNS = 'test:tns';
        $dbSource->DBS_CONNECTION_TYPE = 'TNS';
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
        $dbSource->DBS_DESCRIPTION = $aDescription;
        $this->assertEquals($dbSource->getDbsDatabaseDescriptionAttribute(), $aDescription);

        $dbSource->DBS_TYPE = 'oracle';
        $dbSource->DBS_TNS = 'test:tns';
        $dbSource->DBS_CONNECTION_TYPE = 'TNS';
        $this->assertEquals($dbSource->getDbsDatabaseDescriptionAttribute(), '[test:tns]'.$aDescription);
    }
}