<?php

return [
    'messages' => [
        'TASK_CREATED' => '{{- user }} has been assigned to the task {{- subject }} in the process {{- processName }}',
        'TASK_COMPLETED' => 'Task {{- subject }} completed by {{- user }}',
        'TASK_REASSIGNED' => 'Task {{- subject }} reassigned to {{- user }}',
        'TASK_OVERDUE' => 'Task {{- subject }} is overdue. Originally due on {{- due }}',
        'PROCESS_CREATED' => '{{- user}} started the process {{- subject }}',
        'PROCESS_COMPLETED' => '{{- subject }} completed',
        'BUNDLE_UPDATED' => 'The bundle {{- subject }} has a new version. Click to check it',
        'ERROR_EXECUTION' => '{{- subject }} caused an error',
        'COMMENT' => '{{- user}} commented on {{- subject}}',
        'ProcessMaker\\Notifications\\ImportReady' => 'Imported {{- subject }}',
    ],
];
