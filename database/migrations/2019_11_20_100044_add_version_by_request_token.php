<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Models\ProcessRequestToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\Script;
use Illuminate\Database\QueryException;

class AddVersionByRequestToken extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->table('process_requests', function (Blueprint $table) {
            $table->dropColumn('versions');
        });
        Schema::connection($model->getConnectionName())->table('process_request_tokens', function (Blueprint $table) {
            $table->integer('version_id')->nullable();
            $table->string('version_type')->nullable();
        });
        try {
            Schema::table('script_versions', function (Blueprint $table) {
                $table->dropUnique('script_versions_key_unique');
            });
        } catch(QueryException $e) {
            // Skip drop script_versions_key_unique in script_versions table if it does not exists
        }
        foreach(Screen::all() as $screen) {
            $screen->saveVersion();
        }
        foreach(Script::all() as $script) {
            $script->saveVersion();
        }
        foreach(ProcessRequestToken::all() as $token) {
            $token->saveVersion();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $model = new ProcessRequest();
        Schema::connection($model->getConnectionName())->table('process_request_tokens', function (Blueprint $table) {
            $table->dropColumn(['version_id', 'version_type']);
        });
        Schema::connection($model->getConnectionName())->table('process_requests', function (Blueprint $table) {
            $table->json('versions')->nullable();
        });
        try {
            Schema::table('script_versions', function (Blueprint $table) {
                $table->unique(['key']);
            });
        } catch(QueryException $e) {
            // Skip unique for key column in script_versions table if it cause problems
        }
    }
}
