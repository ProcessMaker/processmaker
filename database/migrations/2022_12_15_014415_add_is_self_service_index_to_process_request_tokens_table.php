<?php

use Doctrine\DBAL\Types\Types;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public $indexName = 'process_request_tokens_is_self_service_index';

    public $table = 'process_request_tokens';

    public $column = 'is_self_service';

    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', Types::STRING);
    }

    public function up()
    {
        Schema::table($this->table, function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails($this->table);

            if (!$doctrineTable->hasIndex($this->indexName)) {
                $table->index($this->column, $this->indexName);
            } else {
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('process_request_tokens', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails($this->table);

            if ($doctrineTable->hasIndex($this->indexName)) {
                $table->dropIndex($this->indexName);
            }
        });
    }
};
