<?php

namespace ProcessMaker\Contracts;

interface SecurityLogEventInterface
{
    public function getData(): array;

    public function getChanges(): array;

    public function getEventName(): string;
}
