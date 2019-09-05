<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\Script;

class DataSourceSeeder extends Seeder
{
    const IMPLEMENTATION_ID = 'package-data-sources/datasource-task-service';

    /**
     * Creates or updates the script implementation.
     *
     * @return void
     */
    public function run()
    {
        //Definition script send an email
        $definition = [
            'key' => self::IMPLEMENTATION_ID,
            'title' => 'DataSourceService',
            'description' => 'Data source service',
            'language' => 'PHP',
            'run_as_user_id' => Script::defaultRunAsUser()->id,
            'code' => $this->getCode(),
        ];
        $exists = Script::where('key', self::IMPLEMENTATION_ID)->first();
        if ($exists) {
            $exists->fill($definition);
            $exists->saveOrFail();
        } else {
            $script = factory(Script::class)->make($definition);
            $script->saveOrFail();
        }
    }

    private function getCode()
    {
        clearstatcache(false, __DIR__ . '/code/DataSourceService.php');
        return file_get_contents(__DIR__ . '/code/DataSourceService.php');
    }

    public function update()
    {
        $this->run();
    }

    public function delete()
    {
        Script::where('key', self::IMPLEMENTATION_ID)->delete();
    }
}
