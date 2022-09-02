<?php
namespace ProcessMaker\ImportExport;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigrationHelper {
    
    public function addUuidsToTables($tables) {
        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->uuid('uuid')->after('id')->unique()->nullable();
                });
            }
        }
    }
    
    public function removeUuidsFromTables($tables) {
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('uuid');
                });
            }
        }
    }

    public function populateUuids($tables)
    {
        foreach($tables as $table) {
            \DB::table($table)
             ->select('id')
             ->where('uuid', '=', null)
             ->orderBy('id')->chunkById(1000, function($rows) use ($table) {
                 $count = count($rows);
                 foreach ($rows as $row) {
                     $uuid = (string) Str::orderedUuid();
                     \DB::statement("update `$table` set `uuid` = ? where `id` = ?", [$uuid, $row->id]);
                 }
             });
         }
    }
}