<?php

namespace ProcessMaker\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class ActiveType extends Enum implements LocalizedEnum
{
    const ACTIVE = "ACTIVE";
    const INACTIVE = "INACTIVE";
}
