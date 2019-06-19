# Testing the PHP sdk

1. Import php_sdk_process.processmaker
1. Import user_form_process.processmaker
1. Create a new user with no permissions (in addition to the admin user)
1. Edit both scripts that got created in the import and set the Run As user to the admin user
1. Create a new request "PHP SDK Test"
1. Verify the logged in user (admin) gets a task to "Pick color"
1. Complete the task
1. In a new incognito window, log in as the other user
1. Verify the user also got the task assigned to them.
1. Complete the task.
1. Back as the admin user, verity that the process gets completed 5 minutes after it originally started.

Note: The 5 minute timer is manually set in the bpm file since the UI doesn't have that option

# Testing the LUA sdk

1. Import lua_sdk_process.processmaker
1. The script that got created in the import and set the Run As user to the admin user
1. Create a new request "Test LUA SDK"
1. The request should complete automatically in a few seconds
1. View the completed requests and verify the data has an email array containing all the users' email addresses
1. Add additional users and start the request again to verity the data gets populated with more items
