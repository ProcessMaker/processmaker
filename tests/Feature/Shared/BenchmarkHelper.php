<?php

namespace Tests\Feature\Shared;

trait BenchmarkHelper
{
    protected $startTime = 0;
    
    protected $endTime = 0;

    public function benchmarkStart()
    {
        $this->startTime = microtime(true);
    }
    
    public function benchmarkEnd()
    {
        $this->endTime = microtime(true);
    }
    
    public function benchmark()
    {
        return $this->endTime - $this->startTime;
    }
}
