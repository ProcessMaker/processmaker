For the rest of this conversation, I will feed you search queries. Using those queries, generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If you determine the intent is to perform a fulltext search, the PMQL should search the "fulltext" field for the provided input. If the intent is to perform a complex query, use the PMQL to do so. If all else fails, fallback to the fulltext search behavior.
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find collection records information.
##
Collection records Data Type can use the following PMQL properties: created, id, modified, fulltext.
Data types never can be used with the prefix 'data.'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find records in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
##
Examples
Question: Find records associated with customer with firstname that begin with P
Response: 'data.firstname LIKE "P%"'
Question: Find records with statuses that start with c
Response: 'data.status LIKE "c%"'
Question: Find all rows that name begin with T
Response: 'data.name LIKE "T%"'
Question: Find all rows where the last name begin with P and have exactly 5 characters following
Response: 'data.lastname LIKE "P____"'
Question: Find all records that job_title begins with T and have exactly 3 characters following
Response: 'data.job_title LIKE "T___"'
Question: Find all records that job_title begins with T and have exactly 3 characters following case not sensitive.
Response: 'lower(data.job_title) LIKE "t___"'
Question: Find both persons in collection data based on the string company
Response: 'data.Personal LIKE "%company%"'
Question: Show me the records where job_title begins with T case insensitive.
Response: 'lower(data.job_title) LIKE "t%"'
Question: Show me the list where job_title starts with Prod or job_title starts with Proj ignore case sensitive.
Response: 'lower(data.job_title) LIKE "prod%" OR lower(data.job_title) LIKE "proj%"'
Question: Find for completed or erroneous statuses where the day of birthday is not {currentYear}-07-01 or {currentYear}-05-01
Response: '(data.status IN ["Completed", "Error"]) AND data.day_of_birthday NOT IN ["{currentYear}-07-01", "{currentYear}-05-01"]'
Question: Find for Jhon or Agustin firstname where the age is 23 or 33 years
Response: '(data.firstname IN ["Jhon", "Agustin"]) AND (data.date IN ["23", "33"])'
Question: Find for Software Engineer or Human Resource Specialist job titles in the collection where the participant is admin or Melissa. The last modified is equal or major to {currentYear}-07-01 00:00:00
Response: '(data.participant IN ["admin", "Melissa"]) AND (data.job_title NOT IN ["Software Engineer", "Human Resource Specialist"]) AND (modified >= "{currentYear}-07-01 00:00:00")'
Question: Find all the records with name ProcessName that day of birthday is not more than two (2) days old
Response: '(data.day_of_birthday > NOW -2 day) AND (data.name = "ProcessName")'
Question: Find all the records where day of birthday is between 2002-01-01 and {currentYear}-03-07
Response: '(data.day_of_birthday >= 2002-01-01) AND (data.day_of_birthday <= {currentYear}-03-07)'
Question: Show me all the records for the task "Fill user data"
Response: '(data.task = "Fill user data")'
Question: Generate a PMQL to return all the records for credits upper to 1200
Response: '(data.credits > 1200)'
Question: Show me all the invoices where the total is over than 4000 usd for the product item hammer
Response: '(data.total > 4000) AND (data.item.product = "hammer")'
Question: Show me all the records where item name is hammer or screwdriver and was payed'
Response: '((data.item.name = "hammer") OR (data.item.name = "screwdriver")) AND (data.status = "Payed")'
Question: Return all records
Response: 'id >= 0'
Question: Show all for the last week.
Response: 'modified > NOW -7 day'
Question: Show all for the last 2 weeks.
Response: 'modified > NOW -14 day'
Question: Show all for the last month.
Response: 'modified > NOW -30 day'
Question: Show all for the last 2 months.
Response: 'modified > NOW -60 day'
Question: Return all records where total greater than 120 created in the first week of March. If you are in {currentYear} use {currentYear} if you are in another year use that year.
Response: '(data.total > 120) AND (created >= "{currentYear}-03-01 00:00:00") AND (created < "{currentYear}-03-07 00:00:00")'
Question: Return all records where total greater than 120 created in the first week of March of past year.
Response: '(data.total > 120) AND (created >= "{pastYear}-03-01 00:00:00") AND (created < "{pastYear}-03-07 00:00:00")'
Question: Show me records that created more than a week ago and were modified within the last two days.
Response: '(created >= NOW -7 day) AND (modified <= NOW -2 day)'
Question: Jhon
Response: '(fulltext LIKE "%Jhon%")'
Question: Mc Callister
Response: '(fulltext LIKE "%Mc Callister%")'
Question: 3
Response: '(fulltext LIKE "%3%")'
Question: 51
Response: '(fulltext LIKE "%51%")'
Question: 31 years
Response: '(fulltext LIKE "%31 years%")'
Question: Employee
Response: '(fulltext LIKE "%Employee%")'
Question: show me all where firstname is Jhon
Response: 'data.firstname = "Jhon"'
Question: Show me all where firstname starts with Jhon for the collection persons
Response: '
{
  "pmql": "(data.firstname LIKE \"Jhon%\")",
  "collectionName": "persons"
}
'
Question: {currentYear}-09-03
Response: '(fulltext LIKE "%{currentYear}-09-03%")'{stopSequence}
Question: {question}
{stopSequence}
Response: