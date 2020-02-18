<?php

namespace Tests\Feature\Shared;

trait LoggingHelper
{

    /**
     * Assert that a log entry exists. Data should be passed as an array and
     * can include message, context, level, level_name, and other items.
     *
     * @param array $data
     *
     * @return mixed
     */        
    public function assertLogEntryExists($data)
    {
        $records = app('log')->getHandlers()[0]->getRecords();
        $count = count($data);
        $matches = 0;

        foreach ($records as $record) {
            $matches = 0;
            
            foreach ($data as $key => $value) {
                if (array_key_exists($key, $record)) {
                    if ($record[$key] == $value) {
                        $matches++;
                    }
                }
            }
            
            if ($matches === $count) {
                break;
            }
        }
        
        return $this->assertEquals($count, $matches, 'Failed asserting that a log entry exists.');
    }

    /**
     * Assert that an event broadcast to the log has a payload smaller than the
     * specified size in bytes.
     *
     * @param string $name
     * @param integer $size
     *
     * @return mixed
     */
    public function assertBroadcastEventSizeLessThan($name, $size)
    {
        $length = 0;
        $records = app('log')->getHandlers()[0]->getRecords();
        
        foreach ($records as $record) {
            if (array_key_exists('message', $record)) {
                $doesMatch = preg_match('/Broadcasting \[(?<name>.+)\].+ with payload:(?<payload>.+)/s', $record['message'], $matches);
                if ($doesMatch) {
                    if ($matches['name'] == $name) {
                        $length = strlen($matches['payload']);
                        break;
                    }
                }
            }
        }
        
        return $this->assertLessThan($size, $length);
    }
    
    /**
     * Assert that a log entry exists that contains specific text
     *
     * @param array $data
     *
     * @return mixed
     */        
    public function assertLogContainsText($data)
    {
        $records = app('log')->getHandlers()[0]->getRecords();
        $count = 1;
        $matches = 0;

        foreach ($records as $record) {
            $matches = 0;
            
            if (array_key_exists('message', $record)) {
                $message = $record['message'];
                if (strpos($message, $data) !== false) {
                    $matches = 1;
                }
            }
            
            if ($matches === $count) {
                break;
            }
        }
        
        return $this->assertEquals($count, $matches, 'Failed asserting that the log contains text.');
    }

    /**
     * Assert that a log message exists. This exclusively tests only the actual
     * log message string and not the context, level, or other items.
     *
     * @param string $message
     *
     * @return mixed
     */        
    public function assertLogMessageExists($message)
    {
        return $this->assertLogEntryExists(['message' => $message]);
    }
    
    /**
     * Assert that the test log is empty.
     *
     * @return mixed
     */        
    public function assertLogIsEmpty()
    {
        $records = app('log')->getHandlers()[0]->getRecords();
        return $this->assertEquals(0, count($records), 'Failed asserting that the log is empty.');
    }
    
    /**
     * Assert that the test log is not empty.
     *
     * @return mixed
     */        
    public function assertLogNotEmpty()
    {
        $records = app('log')->getHandlers()[0]->getRecords();
        return $this->assertGreaterThan(0, count($records), 'Failed asserting that the log is not empty.');
    }
}
