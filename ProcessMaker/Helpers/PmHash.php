<?php

namespace ProcessMaker\Helpers;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class PmHash implements HasherContract
{
    /**
     * @var mixed|string
     */
    protected $algo;

    /**
     * @var string
     */
    protected $defaultAlgo = 'sha1';

    public function __construct()
    {
        $priority = [
            'sha3-512',
            'sha3-384',
            'sha3-256',
            'sha512',
            'sha384',
            'sha256',
            'sha224',
            'sha1',
        ];
        $algos = hash_algos();
        foreach ($priority as $algo) {
            if (in_array($algo, $algos)) {
                $this->algo = $algo;
                break;
            }
        }
        $this->algo = $this->algo ?: $this->defaultAlgo;
    }

    /**
     * @param $hashedValue
     * @return array|string[]
     */
    public function info($hashedValue)
    {
        return [
            'algo' => $this->algo,
        ];
    }

    /**
     * @param string $value
     * @param array $options
     * @return string
     */
    public function make($value, array $options = [])
    {
        return hash_hmac(
            $this->algo,
            $value,
            config('app.key')
        );
    }

    /**
     * @param string $value
     * @param string $hashedValue
     * @param array $options
     * @return bool
     */
    public function check($value, $hashedValue, array $options = [])
    {
        $fresh = $this->make($value, $options);
        if (strlen($fresh) !== strlen($hashedValue)) {
            return false;
        }
        $match = true;
        for ($i = 0; isset($fresh[$i]); $i++) {
            $match = $match && $fresh[$i] === $hashedValue[$i];
        }

        return $match;
    }

    /**
     * @param string $hashedValue
     * @param array $options
     * @return false
     */
    public function needsRehash($hashedValue, array $options = [])
    {
        return false;
    }
}
