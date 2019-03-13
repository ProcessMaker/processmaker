# Testing the PHP sdk

1. Import php_sdk_process.bmp4
1. Import user_form_process.bpm4
1. Create a new user with no permissions (in addition to the admin user)
1. Create a new request "PHP SDK Test"
1. Verify the logged in user (admin) gets a task to "Pick color"
1. Complete the task
1. In a new incognito window, log in as the other user
1. Verify the user also got the task assigned to them.
1. Complete the task.
1. Back as the admin user, verity that the process gets completed 5 minutes after it originally started.

Note: The 5 minute timer is manually set in the bpm file since the UI doesn't have that option