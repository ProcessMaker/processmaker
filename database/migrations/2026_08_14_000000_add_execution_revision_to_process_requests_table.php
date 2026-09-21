<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\ProcessRequest;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $connection = (new ProcessRequest())->getConnectionName();
        Schema::connection($connection)->table('process_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('execution_revision')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = (new ProcessRequest())->getConnectionName();
        Schema::connection($connection)->table('process_requests', function (Blueprint $table) {
            $table->dropColumn('execution_revision');
        });
    }
};
