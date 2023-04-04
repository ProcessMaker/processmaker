For the rest of this conversation, I will feed you search queries. Using those queries, generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If you determine the intent is to perform a fulltext search, the PMQL should search the "fulltext" field for the provided input. If the intent is to perform a complex query, use the PMQL to do so. If all else fails, fallback to the fulltext search behavior.
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find tasks information.
##
Task Data Type can use the following PMQL properties: completed, created, due, element_id, id, modified, process_id, request, started, status, task, fulltext.
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
Question: Find for completed or erroneous Tasks where the Request Date is not {currentYear}-07-01 or {currentYear}-05-01
Response: '(status IN ["Completed", "Error"]) AND data.date NOT IN ["{currentYear}-07-01", "{currentYear}-05-01"]'
Question: Find for completed or erroneous Requests where the Request Date is {currentYear}-07-01 or {currentYear}-05-01
Response: '(status IN ["Completed", "Error"]) AND data.date IN ["{currentYear}-07-01", "{currentYear}-05-01"]'
Question: Find for completed or in progress Requests where the element is 1 or 3. The last modified is equal or major to {currentYear}-07-01 00:00:00
Response: 'element_id IN ["1", "3"] AND status NOT IN ["Completed", "In Progress"] AND modified >= "{currentYear}-07-01 00:00:00"'
Question: Find tasks for ProcessName that are not more than two (2) days old
Response: '(modified > NOW -2 day) AND (request = "ProcessName")'
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
Question: Show all for the last week.
Response: 'modified > NOW -7 day'
Question: Show all for the last 2 weeks.
Response: 'modified > NOW -14 day'
Question: Show all for the last month.
Response: 'modified > NOW -30 day'
Question: Show all for the last 2 months.
Response: 'modified > NOW -60 day'
Question: Return tasks that are in progress started in the first week of March. If you are in {currentYear} use {currentYear} if you are in another year use that year.
Response: '(status = "In Progress") AND (started >= "{currentYear}-03-01 00:00:00") AND (started < "{currentYear}-03-07 00:00:00")'
Question: Return tasks that are in progress started in the first week of March of past year.
Response: '(status = "In Progress") AND (started >= "{pastYear}-03-01 00:00:00") AND (started < "{pastYear}-03-07 00:00:00")'
Question: Show me tasks that started more than a week ago and were modified within the last two days.
Response: '(started >= NOW -7 day) AND (modified <= NOW -2 day)'
Question: Jhon
Response: '(fulltext LIKE "%Jhon%")'
Question: My test task
Response: '(fulltext LIKE "%My test task%")'
Question: Completed
Response: '(fulltext LIKE "%Completed%")'
Question: 56
Response: '(fulltext LIKE "%56%")'
Question: 188
Response: '(fulltext LIKE "%188%")'
Question: Leave of absence form
Response: '(fulltext LIKE "%Leave of absence form%")'{stopSequence}
Question: {question}
{stopSequence}
Response: