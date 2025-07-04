<?php

return [
    // Interval in minutes for clearing metrics. Can be set via METRICS_CLEAR_INTERVAL env variable.
    'clear_interval' => env('METRICS_CLEAR_INTERVAL', 10),
];
