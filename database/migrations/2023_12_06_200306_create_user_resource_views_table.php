<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserResourceViewsTable extends Migration
{
    public function up()
    {
        Schema::create('user_resource_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->morphs('viewable');
            $table->timestamps();

            $table->index(['viewable_id', 'viewable_type']);

            // Foreign keys
            $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade')
            ->constrained('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_resource_views');
    }
}
