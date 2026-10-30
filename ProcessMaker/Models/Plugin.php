<?php

namespace ProcessMaker\Models;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Plugin model for managing ProcessMaker plugins.
 * This model does not have a database table - it works with file system data.
 */
class Plugin extends ProcessMakerModel
{
    /**
     * The plugin directory path.
     *
     * @var string
     */
    protected $path;

    /**
     * The plugin name (directory name).
     *
     * @var string
     */
    protected $name;

    /**
     * Cached composer.json data.
     *
     * @var array|null
     */
    protected $composerJson;

    /**
     * Create a Plugin instance from a directory path.
     *
     * @param string $path
     * @return self
     */
    public static function fromPath(string $path): self
    {
        if (!is_dir($path)) {
            throw new RuntimeException("Plugin directory does not exist: {$path}");
        }

        $plugin = new self();
        $plugin->path = $path;
        $plugin->name = basename($path);

        return $plugin;
    }

    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the composer.json data.
     *
     * @return array
     * @throws RuntimeException
     */
    public function getComposerJson(): array
    {
        if ($this->composerJson !== null) {
            return $this->composerJson;
        }

        $composerJsonPath = $this->path . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            throw new RuntimeException("composer.json not found in plugin: {$this->path}");
        }

        $content = file_get_contents($composerJsonPath);
        $composerJson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                "Invalid JSON in composer.json at {$composerJsonPath}: " . json_last_error_msg()
            );
        }

        $this->composerJson = $composerJson;

        return $this->composerJson;
    }

    /**
     * Get the plugin description from composer.json.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        try {
            $composerJson = $this->getComposerJson();

            return $composerJson['description'] ?? null;
        } catch (RuntimeException $e) {
            return null;
        }
    }

    /**
     * Get the current git branch.
     *
     * @return string|null
     */
    public function getBranch(): ?string
    {
        if (!is_dir($this->path . '/.git')) {
            return null;
        }

        try {
            $process = Process::path($this->path)
                ->run('git rev-parse --abbrev-ref HEAD');

            if ($process->successful()) {
                return trim($process->output());
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return null;
    }

    /**
     * Get the current git tag if on a tag.
     *
     * @return string|null
     */
    public function getTag(): ?string
    {
        if (!is_dir($this->path . '/.git')) {
            return null;
        }

        try {
            $process = Process::path($this->path)
                ->run('git describe --exact-match --tags HEAD 2>/dev/null');

            if ($process->successful()) {
                return trim($process->output());
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return null;
    }

    /**
     * Get the current git reference (branch or tag).
     *
     * @return string|null
     */
    public function getReference(): ?string
    {
        $tag = $this->getTag();
        if ($tag !== null) {
            return $tag;
        }

        return $this->getBranch();
    }

    /**
     * Get the plugin URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return 'https://github.com/ProcessMaker/' . $this->name;
    }

    /**
     * Override getTable to return null since this model doesn't use a database table.
     *
     * @return string|null
     */
    public function getTable()
    {
        return null;
    }
}
