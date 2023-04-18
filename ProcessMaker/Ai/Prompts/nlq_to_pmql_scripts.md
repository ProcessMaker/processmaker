For the rest of this conversation, I will feed you search queries. Using those queries, generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If you determine the intent is to perform a fulltext search, the PMQL should search the "fulltext" field for the provided input. If the intent is to perform a complex query, use the PMQL to do so. If all else fails, fallback to the fulltext search behavior.
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find Processes information.
##
Request Data Type can use the following PMQL properties: title, name, description, status, category, type, fulltext.
Data types never can be used with the prefix 'data.'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find records in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
##
Examples
Question: Show me all the scripts.
Response: 'id > 0'
Question: Show me all the email scripts.
Response: 'type = "EMAIL"'
Question: Show me all the form scripts.
Response: 'type = "FORM"'
Question: Show me all the display scripts.
Response: 'type = "DISPLAY"'
Question: Show me scripts for type form.
Response: 'type = "FORM"'
Question: Show me the script Create State.
Response: 'script = "Create State"'
Question: Show me the scripts that contains the words Leave of absence form.
Response: 'script LIKE "%Leave of absence form%"'
Question: Show me the scripts that contains leave of absence form case insensitive.
Response: 'lower(title) LIKE "%leave of absence form%"'
Question: Show me scripts where category contains catScriptPao.
Response: 'category LIKE "%catScriptPao%"'
Question: Show me the catScriptPao scripts
Response: 'category = "catScriptPao"'
Question: Show me all the Uncategorized scripts
Response: 'category = "Uncategorized"'
Question: Show me all the php scripts
Response: 'language = "php"'
Question: Show me all the javascript scripts
Response: 'language = "javascript"'
Question: Show me all the lua scripts
Response: 'language = "lua"'
Question: Show me all the javascript ssr scripts
Response: 'language = "javascript-ssr"'
Question: Show me the scripts where name starts with script or title starts with calculate ignore case sensitive.
Response: 'lower(title) LIKE "script%" OR lower(name) LIKE "calculate%"'
Question: Show me the scripts where name contains form or name starts with scrip ignore case sensitive.
Response: 'lower(name) LIKE "%form%" OR lower(name) LIKE "scrip%"'
Question: Find scripts that begin with P
Response: 'name LIKE "P%"'
Question: Find scripts where status is active
Response: 'status = "ACTIVE"'
Question: Find inactive scripts
Response: 'status = "INACTIVE"'
Question: Find all scripts where begin with A and have exactly 5 characters following'
Response: 'name LIKE "A____"'
Question: Find scripts that begin with T and have exactly 3 characters following'
Response: 'title LIKE "T___"'
Question: 'Find for active scripts where the name is form or my script
Response: '(status = ACTIVE") AND (name IN ["form", "my script"])
Question: Script leave of absence
Response: '(fulltext LIKE "%Script leave of absence%")'
Question: My test script
Response: '(fulltext LIKE "%My test script%")'
Question: Email
Response: '(fulltext LIKE "%Email%")'
Question: 56
Response: '(fulltext LIKE "%56%")'
Question: 188
Response: '(fulltext LIKE "%188%")'
Question: Leave of absence Script
Response: '(fulltext LIKE "%Leave of absence Script%")'{stopSequence}
Question: {question}
{stopSequence}
Response:
