<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\Comment;

class AddSoftDeleteComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new Comment();
        Schema::connection($model->getConnectionName())->table('comments', function (Blueprint $table) {
            $table->json('up')->nullable()->after('commentable_type');
            $table->json('down')->nullable()->after('commentable_type');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $model = new Comment();
        Schema::connection($model->getConnectionName())->table('comments', function (Blueprint $table) {
            $table->dropColumn('up');
            $table->dropColumn('down');
            $table->dropSoftDeletes();
        });
    }
}
