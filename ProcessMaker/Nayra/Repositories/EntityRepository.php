<?php

namespace ProcessMaker\Nayra\Repositories;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Models\ProcessRequest;

abstract class EntityRepository
{
    private static $uid2id = [];

    abstract public function create(array $transaction): ? Model;

    abstract public function update(array $transaction): ? Model;

    abstract public function save(array $transaction): ? Model;
}
