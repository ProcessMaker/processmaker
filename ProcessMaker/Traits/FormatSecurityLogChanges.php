<?php

namespace ProcessMaker\Traits;

trait FormatSecurityLogChanges
{
    public function formatChanges(array $changes, array $original)
    {
        //return $changes;
        $formatted = [];
        foreach ($changes as $key => $newValue) {
            $translated = trans('validation.attributes.' . $key);
            $translated = stripos($translated, 'validation.') === false ? $translated : $key;
            $formatted['+ ' . $translated] = is_array($newValue) || is_object($newValue) ? json_encode($newValue) : $newValue;
            if (isset($original[$key])) {
                $oldValue = $original[$key];
                $formatted['- ' . $translated] = is_array($oldValue) || is_object($oldValue) ? json_encode($oldValue) : $oldValue;;
            }
        }
        return $formatted;
    }
}