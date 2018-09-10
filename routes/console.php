<?php

use Illuminate\Foundation\Inspiring;

/*
  |--------------------------------------------------------------------------
  | Console Routes
  |--------------------------------------------------------------------------
  |
  | This file is where you may define all of your Closure based console
  | commands. Each Closure is bound to a command instance allowing a
  | simple approach to interacting with each command's IO methods.
  |
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('david', function () {
    Illuminate\Support\Facades\Artisan::call('migrate:fresh');
    foreach (glob(app_path('Models') . '/*') as $file) {
        $class = 'ProcessMaker\Models\\' . basename($file, '.php');
        try {
            echo $class, "\n", str_repeat('=', strlen($class)), "\n";
            $model = factory($class)->create();
            $array = $model->toArray();
            dump($array);
            $model2 = new $class($array);
            dump($model2->toArray());
        } catch (Throwable $e) {
            dump($e->getMessage());
        }
    }
})->describe('Display an inspiring quote');
