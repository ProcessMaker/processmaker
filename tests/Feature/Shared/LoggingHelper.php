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
