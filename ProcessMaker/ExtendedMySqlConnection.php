<?php 

namespace ProcessMaker;

class ExtendedMySqlConnection extends \Illuminate\Database\MySqlConnection
{

    /**
     * @return ExtendedMySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new ExtendedMySqlGrammar());
    }

}