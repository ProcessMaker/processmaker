#!/usr/bin/env php
<?php

/**
 * Stand-in for the docker CLI used by ScriptDockerBindingFilesTrait::runContainer.
 *
 * ProcessMaker builds a `docker run` command with -e assignments and -v binds,
 * then PHP exec() runs it through the shell. This environment has no dockerd.
 * After the shell parses that command, proot applies the same binds and executes
 * processmaker/docker-executor-php 1.4.5 src/bootstrap.php unchanged.
 */

declare(strict_types=1);

$bootstrap = dirname(__DIR__, 2) . '/vendor/processmaker/docker-executor-php/src/bootstrap.php';
if (!is_file($bootstrap)) {
    fwrite(STDERR, "docker-executor-php bootstrap.php is not installed\n");
    exit(1);
}

$args = array_slice($argv, 1);
if (($args[0] ?? null) !== 'run') {
    fwrite(STDERR, "unsupported docker command\n");
    exit(1);
}
array_shift($args);

$environment = [];
$binds = [];
$count = count($args);
$index = 0;
while ($index < $count) {
    $argument = $args[$index];
    if ($argument === '--rm') {
        $index++;
        continue;
    }
    if ($argument === '-e' || $argument === '--env') {
        $pair = $args[++$index] ?? '';
        $split = explode('=', $pair, 2);
        $environment[$split[0]] = $split[1] ?? '';
        $index++;
        continue;
    }
    if (str_starts_with($argument, '--shm-size') || str_starts_with($argument, '--network')) {
        $index++;
        continue;
    }
    if ($argument === '-v' || $argument === '--volume') {
        $spec = $args[++$index] ?? '';
        $position = strrpos($spec, ':');
        if ($position === false) {
            fwrite(STDERR, "invalid volume bind\n");
            exit(1);
        }
        $binds[substr($spec, $position + 1)] = substr($spec, 0, $position);
        $index++;
        continue;
    }
    if (str_starts_with($argument, '-')) {
        fwrite(STDERR, "unsupported docker option\n");
        exit(1);
    }
    break;
}

$command = array_slice($args, $index);
if (!in_array('/opt/executor/bootstrap.php', $command, true)) {
    fwrite(STDERR, "docker command did not run bootstrap.php\n");
    exit(1);
}

$stub = tempnam(sys_get_temp_dir(), 'executor-autoload');
file_put_contents($stub, "<?php\n");

$proot = [
    'proot',
    '-b', $bootstrap . ':/opt/executor/bootstrap.php',
    '-b', $stub . ':/opt/executor/vendor/autoload.php',
];
foreach ($binds as $guest => $host) {
    $proot[] = '-b';
    $proot[] = $host . ':' . $guest;
}
$proot[] = PHP_BINARY;
$proot[] = '/opt/executor/bootstrap.php';

$processEnvironment = $environment;
$processEnvironment['PATH'] = getenv('PATH') ?: '/usr/bin';
$processEnvironment['HOME'] = sys_get_temp_dir();

$pipes = [];
$process = proc_open(
    $proot,
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    null,
    $processEnvironment
);
if (!is_resource($process)) {
    @unlink($stub);
    fwrite(STDERR, "unable to start executor bootstrap\n");
    exit(1);
}
fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);
@unlink($stub);

if ($exitCode !== 0) {
    fwrite(STDERR, $stderr !== '' ? $stderr : $stdout);
}

exit($exitCode);
