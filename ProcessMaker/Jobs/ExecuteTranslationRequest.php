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
use ProcessMaker\Events\ProcessTranslationChunkProgressEvent;
use ProcessMaker\Models\ProcessTranslationToken;
use ProcessMaker\Models\Screen;

class ExecuteTranslationRequest implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $screenId;

    private $handler;

    private $type;

    private $chunk;

    private $targetLanguage;

    private $processId;

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
            $targetLanguage,
            $processId
        ) {
        $this->screenId = $screenId;
        $this->handler = $languageTranslationHandler;
        $this->type = $type;
        $this->chunk = $chunk;
        $this->targetLanguage = $targetLanguage;
        $this->processId = $processId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $processTranslationToken = ProcessTranslationToken::where('process_id', $this->processId)->where('language', $this->targetLanguage['language'])->get();

        if (!$this->chunk || !count($this->chunk)) {
            return;
        }

        if (!$processTranslationToken) {
            return $this->batch()->cancel();
        }

        if ($this->batch()->cancelled()) {
            return;
        }

        \Log::info('Calling OpenAI ...');

        $batch = $this->batch();
        event(new ProcessTranslationChunkProgressEvent($this->processId, $this->targetLanguage['language'], $batch));

        $this->handler->generatePrompt(
            $this->type,
            json_encode($this->chunk, JSON_HEX_APOS | JSON_HEX_AMP)
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

        \Log::info('Translation chunk saved OK.');
    }
}
