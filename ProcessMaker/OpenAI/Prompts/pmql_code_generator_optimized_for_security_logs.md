Generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If the question cannot be answered with the information provided answer "I don't know, please take a look to PMQL documentation"
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find security logs information.
##
Security Logs Data Type can use the following PMQL properties: event, ip, meta, user_id, ocurred_at.
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