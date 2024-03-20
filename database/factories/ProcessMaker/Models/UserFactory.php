<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Model factory for a User
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        if (!isset($GLOBALS['testPassword'])) {
            $GLOBALS['testPassword'] = Hash::make('oneOnlyPassword');
        }

        return [
            'username' => $this->faker->unique()->userName() . '_' . $this->faker->unique()->randomNumber(3),
            'email' => $this->faker->unique()->email(),
            'password' => $GLOBALS['testPassword'],

            'status' => $this->faker->randomElement(['ACTIVE', 'INACTIVE']),

            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'postal' => $this->faker->postcode(),
            'country' => 'US',

            'phone' => $this->faker->phoneNumber(),
            'fax' => $this->faker->phoneNumber(),
            'cell' => $this->faker->phoneNumber(),

            'title' => $this->faker->jobTitle(),
            'birthdate' => $this->faker->dateTimeThisCentury(),
            'timezone' => $this->faker->timezone(),
            'datetime_format' => $this->faker->randomElement(['Y-m-d H:i', 'm/d/Y', 'm/d/Y h:i A', 'm/d/Y H:i']),
            'language' => 'en',
            'loggedin_at' => null,

            'is_administrator' => false,
            'force_change_password' => 0,

        ];
    }

    public function admin()
    {
        return $this->state(function () {
            return [
                'is_administrator' => true,
            ];
        });
    }
}
