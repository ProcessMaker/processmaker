<?php

namespace ProcessMaker;

use ProcessMaker\Models\User;
use Illuminate\Database\Eloquent\Model;

class PolicyExtension {
    private $extensions;

    public function __construct()
    {
        $this->extensions = [];
    }

    private function key(string $action, string $class)
    {
        return $action . '-' . $class;
    }

    public function has(string $action, string $class) {
        return array_key_exists(
            $this->key($action, $class),
            $this->extensions
        );
    }

    public function add(string $action, string $class, Callable $policy)
    {
        $key = $this->key($action, $class);
        if (!$this->has($action, $class)) {
            $this->extensions[$key] = [];
        }

        $this->extensions[$key][] = $policy;
    }

    public function authorize(string $action, User $user, Model $model)
    {
        $class = get_class($model);
        if (!$this->has($action, $class)) {
            return false;
        }

        $ok = false;
        foreach($this->extensions[$this->key($action, $class)] as $extension) {
            $ok = $extension($user, $model);
            if ($ok) {
                break;
            }
        }

        return $ok;
    }

    public function getExtensions() {
        return array_keys($this->extensions);
    }
}