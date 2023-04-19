<?php

namespace ProcessMaker;

use Illuminate\Database\Query\Grammars\MySqlGrammar as IlluminateMySqlGrammar;

class ExtendedMySqlGrammar extends IlluminateMySqlGrammar
{
    protected function wrapJsonSelector($value)
    {
        [$field, $path] = $this->wrapJsonFieldAndPath($value);

        return $field . '->>"$."' . $path;
    }
}
