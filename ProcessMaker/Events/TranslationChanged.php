<?php

namespace ProcessMaker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TranslationChanged
{
    use Dispatchable;

    public string $locale;

    public array $changes;

    public ?string $screenId;

    /**
     * Create a new event instance.
     *
     * @param string $locale
     * @param array $changes Key-value pairs of changed translations
     * @param string|null $screenId Optional screen ID if change is specific to a screen
     */
    public function __construct(int $screenId, string $language, array $changes)
    {
        $this->language = $language;
        $this->changes = $changes;
        $this->screenId = $screenId;
    }
}
