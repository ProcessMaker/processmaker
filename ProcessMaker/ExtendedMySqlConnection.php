<?php

namespace ProcessMaker;

use ProcessMaker\Grammars\MySqlGrammar;

class ExtendedMySqlConnection extends \Illuminate\Database\MySqlConnection
{
    /**
     * @return MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new MySqlGrammar());
    }
}
