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

class ExecuteScreenTitleTranslationRequest implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $handler;

    private $type;

    private $screen;

    private $targetLanguage;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
            $screen,
            $languageTranslationHandler,
            $type,
            $targetLanguage
        ) {
        $this->screen = $screen;
        $this->handler = $languageTranslationHandler;
        $this->type = $type;
        $this->targetLanguage = $targetLanguage;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('Calling OpenAI for screen title translation...');
        $this->handler->generatePrompt(
            $this->type,
            $this->screen['title'],
        );

        [$response, $usage, $targetLanguage] = $this->handler->execute();

        $saved = $this->saveTranslationsInScreen($this->screen['id'], $response);
    }

    private function saveTranslationsInScreen($screenId, $screenTitleTranslation)
    {
        if (!$screenTitleTranslation) {
            return;
        }

        $screen = Screen::findOrFail($screenId);
        $screenTranslations = $screen->translations;

        if (!$screenTranslations || !array_key_exists($this->targetLanguage['language'], $screenTranslations)) {
            $screenTranslations[$this->targetLanguage['language']]['screenTitle'] = [];
        }

        $screenTranslations[$this->targetLanguage['language']]['screen_title'] = $screenTitleTranslation;
        $screenTranslations[$this->targetLanguage['language']]['strings'] = [];
        $screenTranslations[$this->targetLanguage['language']]['created_at'] = Carbon::now();
        $screenTranslations[$this->targetLanguage['language']]['updated_at'] = Carbon::now();

        $screen->translations = $screenTranslations;
        $screen->save();

        \Log::info('Translation title saved OK.');
    }
}
