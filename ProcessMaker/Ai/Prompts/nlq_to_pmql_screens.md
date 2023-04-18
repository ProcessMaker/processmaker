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
Question: Show me all the screens.
Response: 'id > 0'
Question: Show me all the email screens.
Response: 'type = "EMAIL"'
Question: Show me all the form screens.
Response: 'type = "FORM"'
Question: Show me all the display screens.
Response: 'type = "DISPLAY"'
Question: Show me screens for type form.
Response: 'type = "FORM"'
Question: Show me the screen Create State.
Response: 'screen = "Create State"'
Question: Show me the screens that contains the words Leave of absence form.
Response: 'screen LIKE "%Leave of absence form%"'
Question: Show me the screens that contains leave of absence form case insensitive.
Response: 'lower(name) LIKE "%leave of absence form%"'
Question: Show me screens where category contains catSreenPao.
Response: 'category LIKE "%catScreenPao%"'
Question: Show me the catSreenPao screens
Response: 'category = "catScreenPao"'
Question: Show me the screens where name starts with screen or title starts with form ignore case sensitive.
Response: 'lower(title) LIKE "screen%" OR lower(name) LIKE "form%"'
Question: Show me the screens where name contains form or name starts with scree ignore case sensitive.
Response: 'lower(name) LIKE "%form%" OR lower(name) LIKE "scree%"'
Question: Find screens that begin with P
Response: 'name LIKE "P%"'
Question: Find screens where status is active
Response: 'status = "ACTIVE"'
Question: Find inactive screens
Response: 'status = "INACTIVE"'
Question: Find all screens where begin with A and have exactly 5 characters following'
Response: 'name LIKE "A____"'
Question: Find screens that begin with T and have exactly 3 characters following'
Response: 'title LIKE "T___"'
Question: 'Find for active screens where the name is form or my screen
Response: '(status = ACTIVE") AND (name IN ["form", "my screen"])
Question: Form leave of absence
Response: '(fulltext LIKE "%Form leave of absence%")'
Question: My test screen
Response: '(fulltext LIKE "%My test screen%")'
Question: Email
Response: '(fulltext LIKE "%Email%")'
Question: 56
Response: '(fulltext LIKE "%56%")'
Question: 188
Response: '(fulltext LIKE "%188%")'
Question: Leave of absence form
Response: '(fulltext LIKE "%Leave of absence form%")'{stopSequence}
Question: {question}
{stopSequence}
Response:
