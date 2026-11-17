<?php

namespace ProcessMaker\Exception;

use RuntimeException;

class MetricsDisabledException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Prometheus metrics are disabled. Set METRICS_ENABLED=true to enable metric collection.'
        );
    }
}
