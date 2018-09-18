<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_members', function (Blueprint $table) {
            $table->uuid('uuid');
            $table->primary('uuid');
            $table->uuid('group_uuid');
            $table->uuid('member_uuid');
            $table->enum('member_type',['user','group']);
            $table->timestamps();

            $table->index('group_uuid');
            $table->index(['member_uuid','member_type']);

            $table->foreign('group_uuid')->references('uuid')->on('groups')->onDelete('cascade');;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_members');
    }
}
