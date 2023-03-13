Generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If the question cannot be answered with the information provided answer "I don't know, please take a look to PMQL documentation"
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find tasks information.
##
Task Data Type can use the following PMQL properties: completed, created, due, element_id, id, modified, process_id, request, started, status, task.
Data types never can be used with the prefix 'data.'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find records in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
##
Examples
Question: Find all tasks that begin with T
Response: 'task LIKE "T%"'
Question: Find all tasks where the process begin with P and have exactly 5 characters following
Response: 'request LIKE "P____"'
Question: Find all tasks that begin with T and have exactly 3 characters following
Response: 'task LIKE "T___"'
Question: Show me the tasks for product owner job_title case insensitive.
Response: 'lower(data.job_title) = "product owner"'
Question: Show me the list where job_title starts with prod or job_title starts with proj ignore case sensitive.
Response: 'lower(data.job_title) LIKE "prod%" OR lower(data.job_title) LIKE "proj%"'
Question: Find tasks associated with all Processes that begin with P
Response: 'request LIKE "P%"'
Question: Find tasks where status starts with c
Response: 'status LIKE "c%"'
Question: Find for completed or erroneous Tasks where the Request Date is not 2021-07-01 or 2021-05-01
Response: '(status IN ["Completed", "Error"]) AND data.date NOT IN ["2021-07-01", "2021-05-01"]'
Question: Find for completed or erroneous Requests where the Request Date is 2021-07-01 or 2021-05-01
Response: '(status IN ["Completed", "Error"]) AND data.date IN ["2021-07-01", "2021-05-01"]'
Question: Find for completed or in progress Requests where the element is 1 or 3. The last modified is equal or major to 2020-07-01 00:00:00
Response: 'element_id IN ["1", "3"] AND status NOT IN ["Completed", "In Progress"] AND modified >= "2020-07-01 00:00:00"'
Question: Find tasks for ProcessName that are not more than two (2) days old
Response: '(modified < NOW -2 day) AND (request = "ProcessName")'
Question: Show me all the tasks for the task "Fill user data"
Response: '(task = "Fill user data")'
Question: Generate a PMQL to return all the tasks for the request "Leave of absence"
Response: '(request = "Leave of absence")'
Question: Show me all the tasks for the process that starts with Leave of absence
Response: '(request LIKE "Leave of absence%")'
Question: Show me all the tasks for the process that contains absence
Response: '(request LIKE "%absence%")'
Question: Show me the task "Fill user data" for the process "Leave of absence"
Response: '(task = "Fill user data") AND (request = "Leave of absence")'
Question: Show me the tasks "Fill user data" that are in progress or completed
Response: '(task = "Fill user data") AND (status IN ["In Progress", "Completed"])'
Question: Show me all the tasks that are opened
Response: '(status = "In Progress")'
Question: Generate a PMQL query to return all the tasks for the process "Leave of absence" and "Manage customer"
Response: '(request IN ["Leave of absence", "Manage customer"])'
Question: Return all the tasks
Response: 'id >= 0'