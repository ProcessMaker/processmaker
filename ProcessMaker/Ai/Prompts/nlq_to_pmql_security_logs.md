For the rest of this conversation, I will feed you search queries. Using those queries, generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If you determine the intent is to perform a fulltext search, the PMQL should search the "fulltext" field for the provided input. If the intent is to perform a complex query, use the PMQL to do so. If all else fails, fallback to the fulltext search behavior.
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find security logs information.
##
Security Logs Data Type can use the following PMQL properties: event, ip, meta, user_id, ocurred_at, fulltext.
Data types never can be used with the prefix 'data.'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find records in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
##
Examples
Question: Find all logs that begin with T
Response: 'event LIKE "T%"'
Question: Get all logs where the events begin with P and have exactly 5 characters following
Response: 'event LIKE "P_____"'
Question: Get all that begin with T and have exactly 3 characters following
Response: 'event LIKE "T___"'
Question: Find logs that starts with Lo
Response: 'event LIKE "Lo%"'
Question: Find events that contains the word port
Response: 'event LIKE "%port%"'
Question: Find logs that begin with Ca
Response: 'event LIKE "Ca%"'
Question: Find logs where os begin with O
Response: 'meta.os.name LIKE "O%"'
Question: Find logs where operating system begin with O
Response: 'meta.os.name LIKE "O%"'
Question: Find logs where os version begin with Ca
Response: 'meta.os.version LIKE "O%"'
Question: Find logs where browser ends with chrome
Response: 'meta.browser.name LIKE "%Chrome"'
Question: Find logs where user agent contains Mozilla
Response: 'meta.user_agent LIKE "%Mozilla%"'
Question: Find all logs that ip begins with 194.0 and have exactly 6 characters following
Response: 'ip LIKE "194.0______"'
Question: Find all logs that ip ends with .244 and have exactly 11 characters'
Response: 'ip LIKE "_______.244"'
Question: Find all records where helper contains this is an example'
Question: Find events that browser are in Mozilla or Chrome
Response: 'meta.browser.name IN ["Mozilla", "Chrome"]'
Question: Find logs where the event is Login or Logout
Response: '(event IN ["login", "logout"])'
Question Show the login logs that are not more than two (2) days old
Response: '(occurred_at < NOW -2 day) AND (event = "login")'
Question: Show me the events for the last 5 days
Response: 'occurred_at > NOW -5 day'
Question: Show me the login logs for the last 3 days
Response: '(event = "login") AND (occurred_at > NOW -5 day)'
Question: Show me the logs for the event login for the last 4 days
Response: '(event = "login") AND (occurred_at > NOW -4 day)'
Question: Find events where event is Login
Response: 'event = "login"'
Question: Find logout events
Response: 'event = "logout"'
Question: Find logout logs
Response: 'event = "logout"'
Question: Show me the logs that start with Lo and browser are Mozilla or Chrome
Response: '(event LIKE "lo%") AND (meta.browser.name IN ["Mozilla", "Chrome"])'
Question: Show me the logs that start with Lo and browser are Mozilla or Chrome and ip ends with 255.12
Response: '(event LIKE "lo%") AND (meta.browser.name IN ["Mozilla", "Chrome"]) AND (ip LIKE "%255.12")'
Question: Show me the logs for ip 127.0.0.1
Response: 'ip = "127.0.0.1"'
Question: Show me the logs for Windows os
Response: 'meta.os.name = "Windows"'
Question: Show me the logs for chrome browser and Mozilla
Response: 'meta.browser.name IN ["Chrome", "Mozilla"]'
Question: Show me the logs for chrome browser and Mozilla where operating system Windows
Response: '(meta.browser.name IN ["Chrome", "Mozilla"]) AND (meta.os.name = "Windows")'
Question: Show me the events that starts with 'Lo' for chrome browser and Mozilla where operating system Windows
Response: '(event LIKE "lo%") AND (meta.browser.name IN ["Chrome", "Mozilla"]) AND (meta.os.name = "Windows")'
Question: Show me the login events
Response: 'event = "login"'
Question: Return the login events for Mozilla and Chrome browsers
Response: '(event = "login") AND (meta.browser.name IN ["Mozilla", "Chrome"])'
Question: Get all the events that starts with Lo or Log for the browsers Safari and Chrome
Response: '((event LIKE "lo%") OR (event LIKE "log%")) AND (meta.browser.name IN ["Chrome", "Safari"])'
Question: Show me the events for Chrome browser and OS X os for the last 2 days
Response: '(meta.browser.name = "Chrome") AND (meta.os.name = "OS X") AND (occurred_at > NOW -2 day)'
Question: Show all for the last week.
Response: 'occurred_at > NOW -7 day'
Question: Show all for the last 2 weeks.
Response: 'occurred_at > NOW -14 day'
Question: Show all for the last month.
Response: 'occurred_at > NOW -30 day'
Question: Show all for the last 2 months.
Response: 'occurred_at > NOW -60 day'
Question: Return all where created in the first week of March. If you are in {currentDate} use {currentDate} if you are in another year use that year.
Response: '(occurred_at >= "{currentYear}-03-01 00:00:00") AND (occurred_at < "{currentYear}-03-07 00:00:00")'
Question: Return all records for the first week of March of past year.
Response: '(occurred_at >= "{pastYear}-03-01 00:00:00") AND (occurred_at < "{pastYear}-03-07 00:00:00")'
Question: Show me all events that created more than a week ago and were ocurred at within the last two days.
Response: '(ocurred_at >= NOW -7 day) AND (ocurred_at <= NOW -2 day)'
Question: Login
Response: '(fulltext LIKE "%Login%")'
Question: Logout
Response: '(fulltext LIKE "%Logout%")'
Question: 127.0.0.1
Response: '(fulltext LIKE "%127.0.0.1%")'
Question: Chrome
Response: '(fulltext LIKE "%Chrome%")'
Question: OS X
Response: '(fulltext LIKE "%OS X%")'
Question: SSL Certificate
Response: '(fulltext LIKE "%SSL Certificate%")'{stopSequence}
Question: {question}
{stopSequence}
Response: