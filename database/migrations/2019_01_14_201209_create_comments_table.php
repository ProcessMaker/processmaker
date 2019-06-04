<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ProcessMaker\Models\Comment;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $model = new Comment();
        Schema::connection($model->getConnectionName())->create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable(); // could be a system-generated comment
            $table->morphs('commentable');
            $table->text('subject');
            $table->text('body');
            $table->boolean('hidden')->default(false);
            $table->enum('type', ['LOG', 'MESSAGE']);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['commentable_id','commentable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('data')->dropIfExists('comments');
    }
}
