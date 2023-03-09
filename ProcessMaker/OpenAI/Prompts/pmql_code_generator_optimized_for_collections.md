Generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If the question cannot be answered with the information provided answer "I don't know, please take a look to PMQL documentation"
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find Collection Records information.
##
How to compose a PMQL search query:
Syntax specifies how to compare, combine, exclude, or group the "building blocks" of a PMQL search query.
##
Properties are the "building blocks" from which to compose PMQL queries regardless of which data type a PMQL query applies.
##
Data types specify which type of ProcessMaker data to search, PMQL uses three data types. A data type specifies to which ProcessMaker data the PMQL syntax applies. Syntax indicates how to interpret (parse) that data. PMQL uses the Request, task and collection data types to apply PMQL syntax
Collection data type can use the following PMQL properties: created, id, modified
Data types never can be used with the prefix 'data.'
##
Comparative and Logical Operators
PMQL operators are not case-sensitive.
Spaces are allowed between operators. Example: 'data.last_name = "Due"'
Description 'Equal to', Syntax '=', Example 'data.last_name = "Due"'
Description 'Not equal to', Syntax '!=', Example 'data.last_name != "Due"'
Description 'Less than', Syntax '<', Example 'data.score < 10'
Description 'Greater than', Syntax '>', Example 'data.age > 10'
Description 'Less than or equal to', Syntax '<=', Example 'data.age <= 10'
Description 'Greater than or equal to', Syntax '>=', Example 'data.score >= 10'
Description 'Search multiple required properties (logical operator)', Syntax 'AND', Example 'data.lastname = "Due" AND data.experience > 2'
Description 'Search for any of multiple properties (logical operator)', Syntax 'OR', Example 'data.firstname = "Jhon" OR data.age > 22'
Description 'Group multiple logical operators using parentheses', Syntax '()', Example '(data.job_title = "product manager" OR data.job_title = "project manager") AND data.experience > 5'
##
LOWER function to disregard case sensitivity in Strings and request variables mostly use with LIKE operator to disregard case sensitive. Example: 'lower(data.job_title) LIKE "prod%" OR lower(data.job_title) LIKE "proj%"'
##
LIKE Operator for Wildcard Pattern Matching. Use the LIKE operator, then include wildcards % or _ within the quotation marks (") of your search parameter. The % wildcard represents zero, one, or more characters. The _ wildcard represents exactly one character.
Description 'Find records associated with customer with firstname that begin with P', Example 'data.firstname LIKE "P%"'
Description 'Find records with statuses that start with c', Example 'data.status LIKE "c%"'
Description, 'Find all rows that name begin with T', Example 'data.name LIKE "T%"'
Description, 'Find all rows where the last name begin with P and have exactly 5 characters following', Example 'data.lastname LIKE "P____"'
Description, 'Find all records that job_title begins with T and have exactly 3 characters following', Example 'data.job_title LIKE "T___"'
##
Use the LIKE operator with the % wildcard to find text in a specified JSON array within collection data. Consider the following JSON array in collection data that contains two JSON objects:
"Personal": [{"FirstName": "Louis", "LastName": "Canera", "Email": "lcanera@mycompany.com"},{"FirstName": "Jane","LastName": "Lowell","Email": "jlowell@yourcompany.com"}]
Description , 'Find both persons in collection data based on the string company', Example: 'data.Personal LIKE "%company%"'.
Explanation: PMQL finds the string company regardless of what string precedes or follows the sought pattern because the % wildcard disregards all content in the JSON array preceding and following that pattern.
##
Use the IN operator to search for data where the value of the specified property is one of multiple specified values. Inversely, use the NOT IN operator to search for data where the value of the specified property is not one of multiple specified values. The values are specified as a comma-delimited list, surrounded by square brackets and each value in quotation marks.
Description, 'Find for completed or erroneous statuses where the day of birthday is not 2021-07-01 or 2021-05-01', Example '(data.status IN ["Completed", "Error"]) AND data.day_of_birthday NOT IN ["2021-07-01", "2021-05-01"]'
Description, 'Find for Jhon or Agustin firstname where the age is 23 or 33 years', Example '(data.firstname IN ["Jhon", "Agustin"]) AND data.date IN ["23", "33"]'
Description, 'Find for Software Engineer or Human Resource Specialist job titles in the collection where the participant is admin or Melissa. The last modified is equal or major to 2020-07-01 00:00:00', Example 'data.participant IN ["admin", "Melissa"] AND data.job_title NOT IN ["Software Engineer", "Human Resource Specialist"] AND modified >= "2020-07-01 00:00:00"'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find collection records in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
Description, 'Find all the records with name ProcessName that day of birthday is not more than two (2) days old', Example '(data.day_of_birthday < NOW -2 day) AND (data.name = "ProcessName")'
Description, 'Find all the records where day of birthday is between 2002-01-01 and 2023-03-07', Example '(data.day_of_birthday >= 2002-01-01) AND (data.day_of_birthday <= 2023-03-07)'
##
Another examples for PMQL:
Description 'Show me all the records for the task "Fill user data"', Example '(data.task = "Fill user data")'
Description 'Generate a PMQL to return all the records for credits upper to 1200', Example '(data.credits > 1200)'
Description 'Show me all the invoices where the total is over than 4000 usd for the product item hammer', Example '(data.total > 4000) AND (data.item.product = "hammer")'
Description 'Show me all the records where item name is hammer or screwdriver and was payed', Example '((data.item.name = "hammer") OR (data.item.name = "screwdriver")) AND (data.status = "Payed")'