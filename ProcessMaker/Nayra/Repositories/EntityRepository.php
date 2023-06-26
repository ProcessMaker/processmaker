<?php

namespace ProcessMaker\Nayra\Repositories;

use Exception;
use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Nayra\Repositories\ProcessRequestRepository;
use ProcessMaker\Nayra\Repositories\ProcessRequestTokenRepository;

abstract class EntityRepository
{
    private static $uid2id = ['requests' =>[], 'tokens' =>[]];

    abstract public function create(array $transaction): ? Model;

    abstract public function update(array $transaction): ? Model;

    abstract public function save(array $transaction): ? Model;

    /**
     * Get the corresponding id related to uid
     *
     * @param string $uid
     * @return int $id
     * @throws Exception
     */
    public function resolveId(string $uid): int
    {
        // Set some variables according to the class
        switch (get_called_class()) {
            case ProcessRequestRepository::class:
                $instance = new ProcessRequest();
                $type = 'requests';
                break;
            case ProcessRequestTokenRepository::class:
                $instance = new ProcessRequestToken();
                $type = 'tokens';
                break;
        }

        // Get record if is not stored previously
        if (!isset(self::$uid2id[$type][$uid])) {
            $record = $instance->select('id')->where('uuid', $uid)->first();
            if ($record) {
                self::$uid2id[$type][$uid] = $record->getKey();
            } else {
                throw new Exception("The uid {$uid} does not exist in the database");
            }
        }

        return self::$uid2id[$type][$uid] ?? 0;
    }

    /**
     * Store temporally the uid
     *
     * @param string $uid
     * @param int $id
     */
    public function storeUid(string $uid, int $id): void
    {
        // Set some variables according to the class
        switch (get_called_class()) {
            case ProcessRequestRepository::class:
                $type = 'requests';
                break;
            case ProcessRequestTokenRepository::class:
                $type = 'tokens';
                break;
        }

        self::$uid2id[$type][$uid] = $id;
    }
}
