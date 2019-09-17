<?php

use Illuminate\Database\Seeder;
use ProcessMaker\Models\Permission;

class DataSourcePermissionSeeder extends Seeder
{
    const group = 'DataSources';

    /**
     * Permissions handled by the package
     *
     * @var string
     */
    protected $permissions = [
        'create-datasources' => 'Create DataSources',
        'view-datasources' => 'View DataSources',
        'edit-datasources' => 'Edit DataSources',
        'delete-datasources' => 'Delete DataSources',
    ];

    /**
     * Seed Permissions
     */
    public function run()
    {
        $this->update();
    }

    /**
     * update the permissions of Data Source.
     *
     * @return void
     */
    public function update()
    {
        foreach ($this->permissions as $name => $title) {
            Permission::updateOrCreate([
                'title' => $title,
                'name' => $name,
                'group' => self::group

            ]);
        }
    }

    /**
     * Update the permissions of Data Source.
     */
    public function delete()
    {
        Permission::where('group', self::group)
            ->delete();
    }
}
