<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use ProcessMaker\Exception\PluginGitException;
use ProcessMaker\Exception\PluginInstallException;

class PluginGitService
{
    private const HTTPS_PREFIX = 'https://';

    public function repositoryName(string $url): string
    {
        $url = preg_replace('/(\.git|\/)$/', '', $url);
        if (preg_match('#/([^/]+)$#', $url, $matches)) {
            return $matches[1];
        }

        throw PluginInstallException::cannotExtractRepositoryNameFromUrl($url);
    }

    public function prepareUrl(string $url, ?string $username = null, ?string $token = null): string
    {
        if (!str_starts_with($url, self::HTTPS_PREFIX)) {
            return $url;
        }

        $explicitCredentialsUrl = $this->withExplicitCredentials($url, $username, $token);
        if ($explicitCredentialsUrl !== null) {
            return $explicitCredentialsUrl;
        }

        return $this->withGithubToken($url);
    }

    public function stripCredentials(string $url): string
    {
        if (!str_starts_with($url, self::HTTPS_PREFIX)) {
            return $url;
        }
        $parts = parse_url($url);

        return $parts === false || !isset($parts['scheme'], $parts['host'])
            ? $url
            : $this->formatUrl($parts);
    }

    public function clone(string $url, string $destination, string $pluginName, ?int $userId, ?string $canonical, PluginLogService $log): void
    {
        try {
            $process = Process::timeout(3600)->run(['git', 'clone', $url, $destination]);
        } catch (ProcessTimedOutException) {
            throw PluginGitException::gitCloneTimedOut($pluginName);
        }
        if (!$process->successful()) {
            $error = PluginLogService::redactHttpUserInfoFromString($process->errorOutput());
            $log->error('Failed to clone repository: ' . $error, $pluginName, $userId);
            throw PluginGitException::failedToCloneRepository($error);
        }
        Process::path($destination)->timeout(60)->run([
            'git', 'remote', 'set-url', 'origin', $this->stripCredentials($canonical ?? $url),
        ]);
    }

    public function currentBranch(string $path): ?string
    {
        $process = Process::path($path)->run(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
        $branch = trim($process->output());

        return $process->successful() && $branch !== '' && $branch !== 'HEAD' ? $branch : null;
    }

    public function update(
        string $path,
        string $name,
        ?int $userId,
        PluginLogService $log,
        PluginGitUpdateOptions $options,
    ): void
    {
        $log->running('Plugin directory exists, fetching from remote...', $name, $userId);
        $restore = $options->canonicalUrl !== null ? $this->stripCredentials($options->canonicalUrl) : null;
        $previous = Process::path($path)->timeout(60)->run(['git', 'remote', 'get-url', 'origin']);
        $previousUrl = $previous->successful() ? trim($previous->output()) : '';
        $temporaryAuth = $options->authenticatedUrl !== null
            && $restore !== null
            && str_starts_with($options->authenticatedUrl, self::HTTPS_PREFIX);
        if ($temporaryAuth) {
            Process::path($path)->timeout(60)->run([
                'git',
                'remote',
                'set-url',
                'origin',
                $options->authenticatedUrl,
            ]);
        }
        try {
            $this->fetch($path, $name, $userId, $log);
            if ($options->tag !== null) {
                $log->running("Checking out tag: {$options->tag}", $name, $userId);
                $this->checkoutTag($path, $options->tag, $name, $userId, $log);
                return;
            }
            $branch = $options->branch ?? $this->defaultBranch($path, $name, $userId, $log);
            $log->running("Checking out branch: {$branch}", $name, $userId);
            $this->checkoutBranch($path, $branch, $name, $userId, $log);
            $log->running("Pulling latest changes for branch {$branch}...", $name, $userId);
            $this->pull($path, $name, $userId, $branch, $log);
        } finally {
            $clean = $restore ?: '';
            if ($clean === '' && $previousUrl !== '') {
                $clean = $this->stripCredentials($previousUrl);
            }
            if ($clean !== '' && is_dir($path . '/.git')) {
                Process::path($path)->timeout(60)->run(['git', 'remote', 'set-url', 'origin', $clean]);
            }
        }
    }

    public function fetch(string $path, string $name, ?int $userId, PluginLogService $log): void
    {
        $this->runGit(['git', 'fetch', '--all', '--tags'], $path, $name, $userId, fn (string $error) => PluginGitException::failedToFetchRepository($error), $log);
    }

    public function checkoutBranch(string $path, string $branch, string $name, ?int $userId, PluginLogService $log): void
    {
        $this->runGit(['git', 'checkout', $branch], $path, $name, $userId, fn (string $error) => PluginGitException::failedToCheckoutBranch($branch, $error), $log);
    }

    public function checkoutTag(string $path, string $tag, string $name, ?int $userId, PluginLogService $log): void
    {
        $this->runGit(['git', 'checkout', "tags/{$tag}"], $path, $name, $userId, fn (string $error) => PluginGitException::failedToCheckoutTag($tag, $error), $log);
    }

    public function pull(string $path, string $name, ?int $userId, ?string $branch, PluginLogService $log): void
    {
        $command = $branch === null ? ['git', 'pull'] : ['git', 'pull', 'origin', $branch];
        $this->runGit($command, $path, $name, $userId, fn (string $error) => PluginGitException::failedToPullRepository($error), $log);
    }

    public function defaultBranch(string $path, string $name, ?int $userId, PluginLogService $log): string
    {
        $process = Process::path($path)->timeout(60)->run(['git', 'rev-parse', '--abbrev-ref', 'origin/HEAD']);
        $ref = trim($process->output());
        if ($process->successful() && str_starts_with($ref, 'origin/')) {
            return substr($ref, 7);
        }
        foreach (['main', 'master', 'develop'] as $branch) {
            if (Process::path($path)->run(['git', 'show-ref', '--verify', '--quiet', "refs/remotes/origin/{$branch}"])->successful()) {
                return $branch;
            }
        }
        $log->error('Could not determine default branch for plugin repository', $name, $userId);
        throw PluginGitException::cannotDetermineDefaultBranch();
    }

    private function runGit(array $command, string $path, string $name, ?int $userId, callable $exception, PluginLogService $log): void
    {
        $process = Process::path($path)->timeout(300)->run($command);
        if (!$process->successful()) {
            $error = PluginLogService::redactHttpUserInfoFromString($process->errorOutput());
            $log->error('Failed to ' . $command[1] . ' repository: ' . $error, $name, $userId);
            throw $exception($error);
        }
    }

    private function formatUrl(array $parts, string $userInfo = ''): string
    {
        return self::HTTPS_PREFIX . $userInfo . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '')
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }

    private function withExplicitCredentials(string $url, ?string $username, ?string $token): ?string
    {
        $hasCredentials = ($username !== null && trim($username) !== '')
            || ($token !== null && $token !== '');
        if (!$hasCredentials) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $user = rawurlencode((string) $username);
        $pass = $token !== null && $token !== '' ? rawurlencode($token) : '';

        return $this->formatUrl($parts, $pass !== '' ? "{$user}:{$pass}@" : "{$user}@");
    }

    private function withGithubToken(string $url): string
    {
        $token = env('GITHUB_TOKEN');
        if (!$token || !str_starts_with($url, 'https://github.com/')) {
            return $url;
        }

        return str_replace('https://github.com/', self::HTTPS_PREFIX . "{$token}@github.com/", $url);
    }
}
