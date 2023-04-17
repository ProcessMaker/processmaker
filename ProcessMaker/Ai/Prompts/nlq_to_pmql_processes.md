For the rest of this conversation, I will feed you search queries. Using those queries, generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If you determine the intent is to perform a fulltext search, the PMQL should search the "fulltext" field for the provided input. If the intent is to perform a complex query, use the PMQL to do so. If all else fails, fallback to the fulltext search behavior.
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find Processes information.
##
Request Data Type can use the following PMQL properties: process, name, status, fulltext.
Data types never can be used with the prefix 'data.'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find records in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
##
Examples
Question: Show me all the process.
Response: 'id > 0'
Question: Show me the process Leave of absence.
Response: 'process = "Leave of absence"'
Question: Show me the processes that contains the word Leave of absence.
Response: 'process LIKE "%Leave of absence%"'
Question: Show me the processes that contains the word leave of absence case insensitive.
Response: 'lower(process) LIKE "%leave of absence%"'
Question: Show me processes where category contains catPao.
Response: 'category LIKE "%catPao%"'
Question: Show me the processes where name starts with proc or name starts with form ignore case sensitive.
Response: 'lower(process) LIKE "proc%" OR lower(process) LIKE "form%"'
Question: Show me the processes where name contains proc or name starts with form ignore case sensitive.
Response: 'lower(process) LIKE "%proc%" OR lower(process) LIKE "form%"'
Question: Find processes that begin with P
Response: 'process LIKE "P%"'
Question: Find processes where status is active
Response: 'status = "ACTIVE"'
Question: Find active processes
Response: 'status = "ACTIVE"'
Question: Find inactive processes
Response: 'status = "ARCHIVED"'
Question: Find all processes where begin with A and have exactly 5 characters following'
Response: 'process LIKE "A____"'
Question: Find processes that begin with T and have exactly 3 characters following'
Response: 'process LIKE "T___"'
Question: 'Find for active processes where the name is form or my process
Response: '(status = ACTIVE") AND (process IN ["form", "my process"])
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
Response: '(fulltext LIKE "%Leave of absence%")'{stopSequence}
Question: {question}
{stopSequence}
Response:
