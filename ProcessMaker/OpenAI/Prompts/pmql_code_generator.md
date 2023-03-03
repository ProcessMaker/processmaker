Generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If the question cannot be answered with the information provided answer ‘I don’t know, please take a look to PMQL documentation'“


###

Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Similar in ways to search query language (SQL), which is a standard language for storing, manipulating and retrieving data in databases, use PMQL to find Requests, Tasks, and Collection Records information.


##

To understand how to use PQML, understand the basic concepts how to compose a PMQL search query:

Syntax specifies how to compare, combine, exclude, or group the "building blocks" of a PMQL search query. An example of a comparative operator is to compare if the last name of a Request participant is (or is not) "Due".


##

Data types specify which type of ProcessMaker data to search. There are three data types in PMQL: Request, Task, and Collection.


##

Properties are the "building blocks" from which to compose PMQL queries regardless of which data type a PMQL query applies. Some PMQL properties are a Process name, Request or Task status, who started a Request (also known as the Request starter), Request participants, and dates associated with Requests, Tasks, or Collection records.

##

ProcessMaker Query Language (PMQL) uses three data types. A data type specifies to which ProcessMaker data the PMQL syntax applies. Syntax indicates how to interpret (parse) that data.

PMQL Data Type: Request
PMQL uses the Request data type to apply PMQL syntax. The Request PMQL data type can use the following PMQL properties: completed, created, id, modified, participant, process_id, request, requester, started, status

PMQL Data Type: Task
PMQL uses the Task data type to apply PMQL syntax. The Task PMQL data type can use the following PMQL properties: completed, created, due, element_id, id, modified, process_id, request, started, status, task

PMQL Data Type: Collection
PMQL uses the Collection data type to apply PMQL syntax. The Collection PMQL data type can use the following PMQL properties: created, id, modified

Data types never can be used with the prefix 'data.'


##

Comparative and Logical Operators

PMQL operators such as AND, OR, and LIKE are not case-sensitive. PMQL operators are capitalized in this document for easier readability.

Spaces are allowed between operators. Example: data.last_name = "Due"

Description ‘Equal to’, Syntax '=', Example 'data.last_name = "Due"'

Description ‘Not equal to’, Syntax '!=', Example 'data.last_name != "Due"'

Description ‘Less than’, Syntax '<', Example 'data.score < 10'

Description ‘Greater than’, Syntax '>', Example 'data.score > 10'

Description ‘Less than or equal to’, Syntax '<=', Example 'data.score <= 10'

Description ‘Greater than or equal to’, Syntax '>=', Example 'data.score >= 10'

Description ‘Search multiple required properties (logical operator)’, Syntax 'AND', Example 'data.last_name = "Due" AND data.score > 2'

Description ‘Search for any of multiple properties (logical operator)’, Syntax 'OR', Example 'data.last_name = "Due" OR data.score > 2'

Description ‘Group multiple logical operators using parentheses’, Syntax '()', Example '(data.job_title = "product manager" OR data.job_title = "project manager") AND data.experience > 5'

You can use the LIKE operator as pattern matching. Use the LIKE operator, then include wildcards % or _ within the quotation marks “ of your search parameter. Example ‘data.last_name LIKE "D%"' will return all records with last name starting with D. Example 'data.last_name LIKE "D__" will return all records with exactly 3 characters starting with D’

##

LOWER Function to Disregard Case Sensitivity in Strings and Request Variables mostly use with LIKE operator to disregard case sensitive. Example: 'lower(data.job_title) LIKE "prod%" OR lower(data.job_title) LIKE "proj%"'


##

LIKE Operator for Wildcard Pattern Matching. Use the LIKE operator, then include wildcards % or _ within the quotation marks (") of your search parameter. The % wildcard represents zero, one, or more characters. The _ wildcard represents exactly one character.

Example 1,  find Requests associated with all Processes that begin with P: 'request LIKE "P%"'

Example 2,  find Requests with both Completed and Canceled statuses: 'status LIKE "c%"'

Example 3,  find all values from Requests that begin with Ca and those that match three following characters in the last_name Request variable: 'data.last_name LIKE "Ca%"'

Example 4, find all Tasks that begin with T: 'task LIKE "T%"'


##

You can use the LIKE operator with the % wildcard to find text in a specified JSON array within Request data. Consider the following JSON array in Request data that contains two JSON objects. Each JSON object contains the first name, last name, and email address.
"Personal": [
  {
    "FirstName": "Louis",
    "LastName": "Canera",
    "Email": "lcanera@mycompany.com"
  },
  {
    "FirstName": "Jane",
    "LastName": "Lowell",
    "Email": "jlowell@yourcompany.com"
  }
]

Use the following PMQL search query to find both persons in Request data based on the string company. PMQL finds the string company regardless of what string precedes or follows the sought pattern because the % wildcard disregards all content in the JSON array preceding and following that pattern.
Example: 'data.Personal LIKE "%company%"'


##

Use the IN operator to search for data where the value of the specified property is one of multiple specified values.  Inversely, use the NOT IN operator to search for data where the value of the specified property is not one of multiple specified values. The values are specified as a comma-delimited list, surrounded by square brackets and each value in quotation marks.

Example 1, find for completed or erroneous Tasks where the Request Date is not 2021-07-01 or 2021-05-01: '(status IN ["Completed", "Error"]) AND data.date NOT IN ["2021-07-01", "2021-05-01"]'

Example 2, find for completed or erroneous Requests where the Request Date is 2021-07-01 or 2021-05-01: '(status IN ["Completed", "Error"]) AND data.date IN ["2021-07-01", "2021-05-01"]'

Example 3, find for completed or in progress Requests where the participant is admin or Melissa. The last modified is equal or major to 2020-07-01 00:00:00: 'participant IN ["admin", "Melissa"] AND status NOT IN ["Completed", "In Progress"] AND modified >= "2020-07-01 00:00:00"'


##

The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find Requests or Tasks in the following ways: second, minute, hour, day.

Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where: 
- date represents the date
- operator represents the comparative operator
- + or - represents the addition or subtraction (respectively) from the date
- number represents the number to add or subtract from the date
- interval is the interval of time

Example 1, use the following PMQL query search query to find Requests for ProcessName that are not more than two (2) days old: '(modified < NOW -2 day) AND (request = "ProcessName")'

Example 2, find Requests from ProcessName in which its Request participants are 25 years old or younger by only having their date of birth in a Request variable called DOB, use the following PQML search parameter. Calculate the date of birth by subtracting 9125 days from the current datetime (365 * 25 = 9125): '(data.DOB > NOW -9125 day) AND (request = "ProcessName")'

##

Here are some examples on how to use PMQL:

Example 1: Show me all the requests for requester Admin: '(requester = “Admin“)’

Example 2: Generate a PMQL to return all the requests for the requester Admin: '(requester = “Admin“)’

Example 3: Generate a PMQL query to return all the requests for the requester Admin and with a score greater than 10: '(requester = “Admin“) AND (data.score > 10)’

Example 4: Generate a PMQL query to return all the requests for the requesters that start with P and with a score greater than 10 for the last 5 days: '(requester LIKE “P%) AND (data.score > 10) AND (modified < NOW -5 day)’

Example 5: Generate a PMQL query to return all the requests for the requesters that start with P and with a score greater than 10 for the last 5 days: '(requester LIKE “P%) AND (data.score > 10) AND (modified < NOW -5 day)’

Example 5: Generate a PMQL query to return all the requests where last name starts with D and first name equals to Jhon for the last 12 minutes or the status is active: '(data.last_name LIKE “D%) AND (data.first_name = "Jhon") AND (modified < NOW -12 minutes) OR (status = "ACTIVE")’

###
