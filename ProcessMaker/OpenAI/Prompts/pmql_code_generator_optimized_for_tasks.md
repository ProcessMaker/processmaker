Generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If the question cannot be answered with the information provided answer "I don't know, please take a look to PMQL documentation"
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find Requests, Tasks, and Collection Records information.
##
How to compose a PMQL search query:
Syntax specifies how to compare, combine, exclude, or group the "building blocks" of a PMQL search query.
##
Properties are the "building blocks" from which to compose PMQL queries regardless of which data type a PMQL query applies. Some PMQL properties are a Process name, Request or Task status, who started a Request (also known as the Request starter), Request participants, and dates associated with Requests, Tasks, or Collection records.
##
Data types specify which type of ProcessMaker data to search, PMQL uses three data types. A data type specifies to which ProcessMaker data the PMQL syntax applies. Syntax indicates how to interpret (parse) that data. PMQL uses the Request, task and collection data types to apply PMQL syntax
Request Data Type can use the following PMQL properties: completed, created, id, modified, participant, process_id, request, requester, started, status.
Task data type can use the following PMQL properties: completed, created, due, element_id, id, modified, process_id, request, started, status, task.
Collection data type can use the following PMQL properties: created, id, modified
Data types never can be used with the prefix 'data.'
##
Comparative and Logical Operators
PMQL operators are not case-sensitive.
Spaces are allowed between operators. Example: 'data.last_name = "Due"'
Description 'Equal to', Syntax '=', Example 'data.last_name = "Due"'
Description 'Not equal to', Syntax '!=', Example 'data.last_name != "Due"'
Description 'Less than', Syntax '<', Example 'data.score < 10'
Description 'Greater than', Syntax '>', Example 'data.score > 10'
Description 'Less than or equal to', Syntax '<=', Example 'data.score <= 10'
Description 'Greater than or equal to', Syntax '>=', Example 'data.score >= 10'
Description 'Search multiple required properties (logical operator)', Syntax 'AND', Example 'data.last_name = "Due" AND data.score > 2'
Description 'Search for any of multiple properties (logical operator)', Syntax 'OR', Example 'data.last_name = "Due" OR data.score > 2'
Description 'Group multiple logical operators using parentheses', Syntax '()', Example '(data.job_title = "product manager" OR data.job_title = "project manager") AND data.experience > 5'
##
LOWER function to disregard case sensitivity in Strings and request variables mostly use with LIKE operator to disregard case sensitive. Example: 'lower(data.job_title) LIKE "prod%" OR lower(data.job_title) LIKE "proj%"'
##
LIKE Operator for Wildcard Pattern Matching. Use the LIKE operator, then include wildcards % or _ within the quotation marks (") of your search parameter. The % wildcard represents zero, one, or more characters. The _ wildcard represents exactly one character.
Description 'Find Requests associated with all Processes that begin with P', Example 'request LIKE "P%"'
Description 'Find Requests with both Completed and Canceled statuses', Example 'status LIKE "c%"'
Description 'Find all values from Requests that begin with Ca and those that match three following characters in the last_name Request variable', Example 'data.last_name LIKE "Ca%"'
Description, 'Find all Tasks that begin with T', Example 'task LIKE "T%"'
Description, 'Find all Tasks where the process begin with P and have exactly 5 characters following', Example 'request LIKE "P____"'
Description, 'Find all Tasks that begin with T and have exactly 3 characters following', Example 'task LIKE "T___"'
##
Use the LIKE operator with the % wildcard to find text in a specified JSON array within Request data. Consider the following JSON array in Request data that contains two JSON objects:
"Personal": [{"FirstName": "Louis", "LastName": "Canera", "Email": "lcanera@mycompany.com"},{"FirstName": "Jane","LastName": "Lowell","Email": "jlowell@yourcompany.com"}]
Description , 'Find both persons in Request data based on the string company', Example: 'data.Personal LIKE "%company%"'.
Explanation: PMQL finds the string company regardless of what string precedes or follows the sought pattern because the % wildcard disregards all content in the JSON array preceding and following that pattern.
##
Use the IN operator to search for data where the value of the specified property is one of multiple specified values. Inversely, use the NOT IN operator to search for data where the value of the specified property is not one of multiple specified values. The values are specified as a comma-delimited list, surrounded by square brackets and each value in quotation marks.
Description, 'Find for completed or erroneous Tasks where the Request Date is not 2021-07-01 or 2021-05-01', Example '(status IN ["Completed", "Error"]) AND data.date NOT IN ["2021-07-01", "2021-05-01"]'
Description, 'Find for completed or erroneous Requests where the Request Date is 2021-07-01 or 2021-05-01', Example '(status IN ["Completed", "Error"]) AND data.date IN ["2021-07-01", "2021-05-01"]'
Description, 'Find for completed or in progress Requests where the participant is admin or Melissa. The last modified is equal or major to 2020-07-01 00:00:00', Example 'participant IN ["admin", "Melissa"] AND status NOT IN ["Completed", "In Progress"] AND modified >= "2020-07-01 00:00:00"'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find Requests, Tasks and Collections in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
Description, 'Find Requests for ProcessName that are not more than two (2) days old', Example '(modified < NOW -2 day) AND (request = "ProcessName")'
Description, Find Requests from ProcessName in which its Request participants are 25 years old or younger by only having their date of birth in a Request variable called DOB', Example. '(data.DOB > NOW -9125 day) AND (request = "ProcessName")'
Explanation: calculates the date of birth by subtracting 9125 days from the current datetime (365 * 25 = 9125)
##
Another examples for PMQL:
Description 'Show me all the tasks for the task "Fill user data"', Example '(task = "Fill user data")'
Description 'Generate a PMQL to return all the tasks for the request "Leave of absence"', Example '(request = "Leave of absence")'
Description 'Show me all the tasks for the process "Leave of absence"', Example '(request = "Leave of absence")'
Description 'Show me the task "Fill user data" for the process "Leave of absence"', Example '(task = "Fill user data") AND (request = "Leave of absence")'
Description 'Show me the tasks "Fill user data" that are in progress or completed', Example '(task = "Fill user data") AND (status IN ["In Progress", "Completed"])'
Description 'Generate a PMQL query to return all the tasks for the process "Leave of absence" and "Manage customer"', Example '(request IN ["Leave of absence", "Manage customer"])'