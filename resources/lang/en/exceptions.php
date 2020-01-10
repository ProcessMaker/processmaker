<?php
return [
    'TaskDoesNotHaveUsersException' => 'The task ":task" has an incomplete assignment. You should select one user or group.',
    'InvalidUserAssignmentException' => 'The variable, :variable, which equals ":value", is not a valid User ID in the system',
    'ScriptLanguageNotSupported' => 'The ":language" language is not supported',
    'SyntaxErrorException' => 'Syntax error. :error',
    'ExpressionFailedException' => 'Failed to evaluate expression. :error',
    'TaskDoesNotHaveRequesterException' => 'This process was started by an anonymous user so this task can not be assigned to the requester',
    'ThereIsNoPreviousUserAssignedException' => 'Can not assign this task because there is no previous user assigned before this task',
];
