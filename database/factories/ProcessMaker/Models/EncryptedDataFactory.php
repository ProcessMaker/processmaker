<?php

namespace Database\Factories\ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use ProcessMaker\Models\EncryptedData;
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
        $iv = EncryptedData::generateIv();
        $cipherText = EncryptedData::encryptText($this->faker->sentence(3), $iv);
        return [
            'field_name' => $this->faker->word(),
            'request_id' => ProcessRequest::factory()->create()->id,
            'iv' => base64_encode($iv),
            'data' => $cipherText,
        ];
    }
}
