<?php

namespace ProcessMaker\Contracts;

interface PrometheusMetricInterface
{
    /**
     * Returns a legible or friendly name for Prometheus metrics.
     *
     * @return string
     */
    public function getPrometheusMetricLabel(): string;
}
