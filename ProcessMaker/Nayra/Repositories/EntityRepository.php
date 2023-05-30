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

    public function resolveId($uid)
    {
        if (!isset(self::$uid2id[$uid])) {
            $request = ProcessRequest::select('id')->where('uuid', $uid)->first();
            if ($request) {
                self::$uid2id[$uid] = $request->getKey();
            }
        }
        return self::$uid2id[$uid];
    }

    public function storeUid($uid, $id)
    {
        self::$uid2id[$uid] = $id;
    }
}
