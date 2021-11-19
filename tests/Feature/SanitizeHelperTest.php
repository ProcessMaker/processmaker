<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use ProcessMaker\Models\Screen;
use ProcessMaker\SanitizeHelper;
use Tests\TestCase;

class SanitizeHelperTest extends TestCase
{
    /**
     * Test to ensure not sanitizing rich text data
     */
    public function testScreenWillNotSanitizeRichTextData()
    {
        $screen = factory(Screen::class)->create([
            'config' => json_decode(file_get_contents(__DIR__ . '/Api/screens/FOUR-4408 B.json'))
        ]);

        $taskData = json_decode($this->taskData(), true)['data'];

        $data = SanitizeHelper::sanitizeData($taskData, $screen);

        $this->assertEquals('<p><strong>Strong text formatted</strong></p>', $data['form_text_area_1']);
    }

    /**
     * Test to ensure IS sanitizing rich text data
     */
    public function testScreenWillSanitizeRichTextDataIfIsEmptyDoNotSanitizeVariable()
    {
        $screen = factory(Screen::class)->create([
            'config' => json_decode(file_get_contents(__DIR__ . '/Api/screens/FOUR-4408 B.json'))
        ]);

        $taskDataDoNotSanitizeEmpty = json_decode($this->taskDataDoNotSanitizeEmpty(), true)['data'];

        $data = SanitizeHelper::sanitizeData($taskDataDoNotSanitizeEmpty, $screen);

        $this->assertEquals('Strong text formatted', $data['form_text_area_1']);
    }

    private function taskData() {
        return "{
            \"status\": \"COMPLETED\",
            \"data\": {
                \"_user\": {
                    \"id\": 2
                },
                \"_request\": {
                    \"id\": 1
                },
                \"_DO_NOT_SANITIZE\": \"[\\\"form_text_area_1\\\"]\",
                \"form_text_area_1\": \"<p><strong>Strong text formatted<\/strong><\/p>\"
            }
        }";
    }

    private function taskDataDoNotSanitizeEmpty() {
        return "{
            \"status\": \"COMPLETED\",
            \"data\": {
                \"_user\": {
                    \"id\": 2
                },
                \"_request\": {
                    \"id\": 1
                },
                \"_DO_NOT_SANITIZE\": \"[\\\"\\\"]\",
                \"form_text_area_1\": \"<p><strong>Strong text formatted<\/strong><\/p>\"
            }
        }";
    }
}
