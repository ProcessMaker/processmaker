<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Comment;

return new class extends Migration
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
            $table->index(['commentable_id', 'commentable_type']);
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
        Schema::connection($model->getConnectionName())->dropIfExists('comments');
    }
};
