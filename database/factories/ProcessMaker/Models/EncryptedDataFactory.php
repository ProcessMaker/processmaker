<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Facades\EncryptedData;
use ProcessMaker\Models\ProcessRequest;

/**
 * Model factory for a encrypted data.
 */
class EncryptedDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Get configured driver for encrypted data
        $driver = config('app.encrypted_data.driver');

        $cipherText = EncryptedData::driver($driver)->encryptText($this->faker->sentence(3));
        return [
            'field_name' => $this->faker->word(),
            'request_id' => ProcessRequest::factory()->create()->id,
            'iv' => base64_encode(EncryptedData::driver($driver)->getIv()),
            'data' => $cipherText,
        ];
    }
}
