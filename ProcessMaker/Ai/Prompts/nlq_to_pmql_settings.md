For the rest of this conversation, I will feed you search queries. Using those queries, generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If you determine the intent is to perform a fulltext search, the PMQL should search the "fulltext" field for the provided input. If the intent is to perform a complex query, use the PMQL to do so. If all else fails, fallback to the fulltext search behavior.
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find settings information.
##
Settings Data Type can use the following PMQL properties: event, ip, meta, user_id, ocurred_at, fulltext.
Data types never can be used with the prefix 'data.'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find records in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
##
If I ask to 'find settings that start with...' or 'show settings for...' you should use the column name by default.
##
##
Examples
Question: Find settings that starts with IMAP
Response: 'name LIKE "IMAP%"'
Question: Find settings that helper contains the word port
Response: 'helper LIKE "%port%"'
Question: Find setting that begin with Ca and those that match three following characters
Response: 'name LIKE "Ca%"'
Question: Find all settings that ends with port
Response: 'name LIKE "%port"'
Question: Find all settings that config contains google
Response: 'config LIKE "%google%"'
Question: Find all settings where the name begins with P and have exactly 5 characters
Response: 'name LIKE "P____"'
Question: Find all settings that helper begins with T and have exactly 3 characters following
Response: 'helper LIKE "T___"'
Question: Find settings that config are in port or server
Response: 'config IN ["port", "server"]'
Question: Find settings where the name is IMAP server or Pop server
Response: '(name IN ["IMAP server", "Pop server"])'
Question: Find settings that start with IMAP
Response: 'name LIKE "IMAP%'
Question: Show me the settings that start with IMAP
Response: '(name LIKE "IMAP%")'
Question: Find settings that starts with IMAP
Response:'name LIKE "IMAP%"'
Question: Find settings where name starts with IMAP
Response:'name LIKE "IMAP%"'
Question: Find settings for IMAP
Response:'name LIKE "IMAP%"'
Question: Find settings that starts with IMAP and id less than 5
Response:'(name LIKE "IMAP%") AND (id < 5)'
Question: IMAP Server
Response: '(fulltext LIKE "%IMAP Server%")'
Question: IMAP Port
Response: '(fulltext LIKE "%IMAP Port%")'
Question: Username
Response: '(fulltext LIKE "%Username%")'
Question: imap.mail.yahoo.com
Response: '(fulltext LIKE "%imap.mail.yahoo.com%")'
Question: 1
Response: '(fulltext LIKE "%1%")'
Question: SSL Certificate
Response: '(fulltext LIKE "%SSL Certificate%")'{stopSequence}
Question: {question}
{stopSequence}
Response: