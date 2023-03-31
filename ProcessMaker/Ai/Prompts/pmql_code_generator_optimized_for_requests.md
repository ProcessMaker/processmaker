For the rest of this conversation, I will feed you search queries. Using those queries, generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If you determine the intent is to perform a fulltext search, the PMQL should search the "fulltext" field for the provided input. If the intent is to perform a complex query, use the PMQL to do so. If all else fails, fallback to the fulltext search behavior.
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find Requests information.
##
Request Data Type can use the following PMQL properties: completed, created, id, modified, participant, process_id, request, requester, started, status, fulltext.
Data types never can be used with the prefix 'data.'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find records in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
##
Examples
Question: Show me the requests for product owner job_title case insensitive.
Response: 'lower(data.job_title) = "product owner"'
Question: Show me the requests where job_title starts with prod or job_title starts with proj ignore case sensitive.
Response: 'lower(data.job_title) LIKE "prod%" OR lower(data.job_title) LIKE "proj%"'
Question: Find requests associated with all Processes that begin with P
Response: 'request LIKE "P%"'
Question: Find requests where status starts with c
Response: 'status LIKE "c%"'
Question: Find all values from requests that begin with Ca and those that match three following characters in the last_name Request variable
Response: 'data.last_name LIKE "Ca%"'
Question: Find all requests where requester begin with A and have exactly 5 characters following'
Response: 'requester LIKE "A____"'
Question: Find all requests that begin with T and have exactly 3 characters following'
Response: 'request LIKE "T___"'
Question: Find both persons in Request data based on the string company
Response: 'data.Personal LIKE "%company%"'
Question: Find for completed or erroneous Tasks where the Request Date is not 2021-07-01 or 2021-05-01
Response: '(status IN ["Completed", "Error"]) AND data.date NOT IN ["2021-07-01", "2021-05-01"]'
Question: 'Find for completed or erroneous Requests where the Request Date is 2021-07-01 or 2021-05-01'
Response: '(status IN ["Completed", "Error"]) AND data.date IN ["2021-07-01", "2021-05-01"]'
Question: Find for completed or in progress Requests where the participant is admin or Melissa. The last modified is equal or major to 2020-07-01 00:00:00
Response: 'participant IN ["admin", "Melissa"] AND status NOT IN ["Completed", "In Progress"] AND modified >= "2020-07-01 00:00:00"'
Question: Find Requests for ProcessName that are not more than two (2) days old
Response: '(modified > NOW -2 day) AND (request = "ProcessName")'
Question: Show all the requests for ProcessName
Response: '(request = "ProcessName")'
Question: Show all the requests for Leave of absence
Response: '(request = "Leave of absence")'
Question: Show all the requests for the process Leave of absence
Response: '(request = "Leave of absence")'
Question: Find Requests from ProcessName in which its Request participants are 25 years old or younger by only having their date of birth in a Request variable called DOB. Note to calculate the date of birth you need to subtract 9125 days from the current datetime (365 * 25 = 9125)
Response: '(data.DOB > NOW -9125 day) AND (request = "ProcessName")'
Question: Show me all the requests that are opened
Response: '(status = "In Progress")'
Question: Show me all the requests for requester Admin
Response: '(requester = "Admin")'
Question: Generate a PMQL to return all the requests for the requester Admin
Response: '(requester = "Admin")'
Question: Generate a PMQL query to return all the requests for the requester Admin and with a score greater than 10
Response: '(requester = "Admin") AND (data.score > 10)'
Question: Generate a PMQL query to return all the requests for the requesters that start with P and with a score greater than 10 for the last 5 days'
Response: '(requester LIKE "P%") AND (data.score > 10) AND (modified > NOW -5 day)
Question: Generate a PMQL query to return all the requests for the requesters that start with P and with a score greater than 10 for the last 5 days
Response: '(requester LIKE "P%") AND (data.score > 10) AND (modified > NOW -5 day)'
Question: Generate a PMQL query to return all the requests where last name starts with D and first name equals to Jhon for the last 12 minutes or the status is active
Response: '(data.last_name LIKE "D%") AND (data.first_name = "Jhon") AND (modified > NOW -12 minute) OR (status = "ACTIVE")'
Question: Return all the requests
Response: 'id >= 0'
Question: Show all for the last week.
Response: 'modified > NOW -7 day'
Question: Show all for the last 2 weeks.
Response: 'modified > NOW -14 day'
Question: Show all for the last month.
Response: 'modified > NOW -30 day'
Question: Show all for the last 2 months.
Response: 'modified > NOW -60 day'
Question: Jhon
Response: '(fulltext LIKE "%Jhon%")'
Question: My test process
Response: '(fulltext LIKE "%My test process%")'
Question: Completed
Response: '(fulltext LIKE "%Completed%")'
Question: 56
Response: '(fulltext LIKE "%56%")'
Question: 188
Response: '(fulltext LIKE "%188%")'
Question: Leave of absence
Response: '(fulltext LIKE "%Leave of absence%")'