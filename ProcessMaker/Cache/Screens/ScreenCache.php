<?php

namespace ProcessMaker\Cache\Screens;

use Illuminate\Support\Collection;
use ProcessMaker\Contracts\PrometheusMetricInterface;

class ScreenCache extends Collection implements PrometheusMetricInterface
{
    public string $label;

    public static function makeFrom(PrometheusMetricInterface $screen, $items): self
    {
        $self = new static($items);
        $self->label = $screen->getPrometheusMetricLabel();

        return $self;
    }

    /**
     * Returns a legible or friendly name for Prometheus metrics.
     *
     * @return string
     */
    public function getPrometheusMetricLabel(): string
    {
        return $this->label;
    }
}
