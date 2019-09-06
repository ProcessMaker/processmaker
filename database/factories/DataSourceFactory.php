<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use ProcessMaker\Models\DataSource;
use ProcessMaker\Models\DataSourceCategory;
use Faker\Generator as Faker;

$factory->define(DataSource::class, function (Faker $faker) {

    $auth = $faker->randomElement(['NONE', 'BASIC', 'BEARER']);

    $creds = '[]';

    if ($auth == 'BASIC') {
        $creds = '[{"username":"' . $faker->userName . '"},{"password":"' . $faker->password . '"}]';
    }

    if ($auth == 'BEARER') {
        $creds = '[{"bearer_token":"' . $faker->text . '"}]';
    }

    return [
        'name' => $faker->word,
        'description' => $faker->paragraph(),
        'endpoints' => '[
                    {"create": "' . $faker->url . '"},
                    {"read": "' . $faker->url . '"},
                    {"update": "' . $faker->url . '"},
                    {"delete": "' . $faker->url . '"},
                    {"list": "' . $faker->url . '"}
                    ]',
        'credentials' => $creds,
        'status' => $faker->randomElement(['ACTIVE', 'INACTIVE']),
        'authtype' => $auth,
        'data_source_category_id' => function () {
            return factory(DataSourceCategory::class)->create(['is_system' => false])->getKey();
        },
        'mappings' =>'[
                    {"name_test": "' . $faker->word . '"}
                    ]'

    ];
});
