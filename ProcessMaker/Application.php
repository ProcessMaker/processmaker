<?php

namespace ProcessMaker;

class Application extends \Illuminate\Foundation\Application
{
    /**
     * Sets the timezone for the application and for php with the specified timezone.
     *
     * @param $timezone string
     */
    public function setTimezone(string $timezone): void
    {
        if (!$this->configurationIsCached()) {
            config(['app.timezone' => $timezone]);
        }

        date_default_timezone_set(config('app.timezone'));
    }

    /**
     * Retrieves the currently set timezone.
     */
    public function getTimezone(): string
    {
        return config('app.timezone');
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @note This extends the base Application to specify ProcessMaker instead of app as the main directory
     *
     * @param string $path Optionally, a path to append to the app path
     *
     * @return string
     */
    public function path($path = '')
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'ProcessMaker' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}
