<?php

/*
 * This file is part of Psy Shell.
 *
 * (c) 2012-2020 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Psy\Readline;

use Psy\Util\Str;

/**
 * A Libedit-based Readline implementation.
 *
 * This is largely the same as the Readline implementation, but it emulates
 * support for `readline_list_history` since PHP decided it was a good idea to
 * ship a fake Readline implementation that is missing history support.
 */
class Libedit extends GNUReadline
{
    private $hasWarnedOwnership = false;

    /**
     * Let's emulate GNU Readline by manually reading and parsing the history file!
     *
     * @return bool
     */
    public static function isSupported()
    {
        return \function_exists('readline') && !\function_exists('readline_list_history');
    }

    /**
     * {@inheritdoc}
     */
    public function listHistory()
    {
        $history = \file_get_contents($this->historyFile);
        if (!$history) {
            return [];
        }

        // libedit doesn't seem to support non-unix line separators.
        $history = \explode("\n", $history);

        // shift the history signature, ensure it's valid
        if (\array_shift($history) !== '_HiStOrY_V2_') {
            return [];
        }

        // decode the line
        $history = \array_map([$this, 'parseHistoryLine'], $history);
        // filter empty lines & comments
        return \array_values(\array_filter($history));
    }

    /**
     * {@inheritdoc}
     */
    public function writeHistory()
    {
        $res = parent::writeHistory();

        // Libedit apparently refuses to save history if the history file is not
        // owned by the user, even if it is writable. Warn when this happens.
        //
        // See https://github.com/bobthecow/psysh/issues/552
        if ($res === false && !$this->hasWarnedOwnership) {
            if (\is_file($this->historyFile) && \is_writable($this->historyFile)) {
                $this->hasWarnedOwnership = true;
                $msg = \sprintf('Error writing history file, check file ownership: %s', $this->historyFile);
                \trigger_error($msg, E_USER_NOTICE);
            }
        }

        return $res;
    }

    /**
     * From GNUReadline (readline/histfile.c & readline/histexpand.c):
     * lines starting with "\0" are comments or timestamps;
     * if "\0" is found in an entry,
     * everything from it until the next line is a comment.
     *
     * @param string $line The history line to parse
     *
     * @return string | null
     */
    protected function parseHistoryLine($line)
    {
        // empty line, comment or timestamp
        if (!$line || $line[0] === "\0") {
            return;
        }
        // if "\0" is found in an entry, then
        // everything from it until the end of line is a comment.
        if (($pos = \strpos($line, "\0")) !== false) {
            $line = \substr($line, 0, $pos);
        }

        return ($line !== '') ? Str::unvis($line) : null;
    }
}
