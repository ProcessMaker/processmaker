<?php

namespace ProcessMaker\Traits;

trait SupportsNonInteraction
{    
    private function pretending()
    {
        return $this->option('pretend');
    }
    
    private function interactive()
    {
        return ! $this->option('no-interaction');
    }

    private function confirmOptional($param, $question)
    {
        $option = $this->option($param);
        
        if ($option) {
            return $option;
        } else {
            if ($this->interactive()) {
                return $this->confirm($question);
            } else {
                return $option;
            }
        }
    }
    
    private function choiceOptional($param, $question, $choices, $default = null)
    {
        $option = $this->option($param);
        
        if ($option) {
            return $option;
        } else {
            if ($this->interactive()) {
                return $this->choice($question, $choices, $default);
            } else {
                return $option;
            }
        }
    }

    private function askOptional($param, $question)
    {
        $option = $this->option($param);
        
        if ($option) {
            return $option;
        } else {
            if ($this->interactive()) {
                return $this->ask($question);
            } else {
                return $option;
            }
        }
    }
    
    private function anticipateOptional($param, $question, $choices, $default = null)
    {
        $option = $this->option($param);
        
        if ($option) {
            return $option;
        } else {
            if ($this->interactive()) {
                return $this->anticipate($question, $choices, $default);
            } else {
                return $option;
            }
        }
    }

    private function secretOptional($param, $question)
    {
        $option = $this->option($param);
        
        if ($option) {
            return $option;
        } else {
            if ($this->interactive()) {
                return $this->secret($question);
            } else {
                return $option;
            }
        }
    }
    
    private function infoIfInteractive($string)
    {
        if ($this->interactive()) {
            $this->info($string);
        }
    }

    private function errorOrExit($string)
    {
        $this->error($string);
        
        if (! $this->interactive()) {
            exit(255);
        }
    }
}
