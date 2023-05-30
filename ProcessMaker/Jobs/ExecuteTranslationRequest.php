<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ProcessTranslationProgressNotification;

class ExecuteTranslationRequest implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $screenId;

    private $handler;

    private $type;

    private $chunk;

    private $targetLanguage;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
            $screenId,
            $languageTranslationHandler,
            $type,
            $chunk,
            $targetLanguage
        ) {
        $this->screenId = $screenId;
        $this->handler = $languageTranslationHandler;
        $this->type = $type;
        $this->chunk = $chunk;
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('Calling OpenAI ...');
        $this->handler->generatePrompt(
            $this->type,
            json_encode($this->chunk, JSON_UNESCAPED_SLASHES)
        );

        [$response, $usage, $targetLanguage] = $this->handler->execute();

        $saved = $this->saveTranslationsInScreen($this->screenId, json_decode($response, true));
    }

    private function saveTranslationsInScreen($screenId, $chunkResponse)
    {
        if (!$chunkResponse) {
            return;
        }

        // For each the screens
        $screen = Screen::findOrFail($screenId);
        $screenTranslations = $screen->translations;

        if (!$screenTranslations || !array_key_exists($this->targetLanguage['language'], $screenTranslations)) {
            $screenTranslations[$this->targetLanguage['language']]['strings'] = [];
        }

        $strings = $screenTranslations[$this->targetLanguage['language']]['strings'];
        // For each of the result chunkResponse translations of the screen
        foreach ($chunkResponse as $item) {
            $stringFound = false;

            foreach ($strings as &$stringItem) {
                if ($stringItem['key'] === $item['key']) {
                    $stringItem['string'] = $item['value'];
                    $stringFound = true;
                }
            }

            if (!$stringFound) {
                $newItem = ['key' => $item['key'], 'string' => $item['value']];
                $strings[] = $newItem;
            }
        }

        $screenTranslations[$this->targetLanguage['language']]['strings'] = $strings;
        $screenTranslations[$this->targetLanguage['language']]['created_at'] = Carbon::now();
        $screenTranslations[$this->targetLanguage['language']]['updated_at'] = Carbon::now();

        $screen->translations = $screenTranslations;
        $screen->save();

        \Log::info('Chunk translations saved OK.');
    }
}
