Generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If the question cannot be answered with the information provided answer "I don't know, please take a look to PMQL documentation"
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find Security logs.
##
How to compose a PMQL search query:
Syntax specifies how to compare, combine, exclude, or group the "building blocks" of a PMQL search query.
##
Properties that you can use in PMQL for Security logs are: id, event, meta, user_id, occurred_at.
##
Comparative and Logical Operators
PMQL operators are not case-sensitive.
Spaces are allowed between operators. Example: 'event = "login"'
Example: 'Equal to', Syntax '=', Result: 'event = "login"'
Example: 'Not equal to', Syntax '!=', Result: 'ip != "127.0.0.1"'
Example: 'Less than', Syntax '<', Result: 'id < 10'
Example: 'Greater than', Syntax '>', Result: 'id > 10'
Example: 'Less than or equal to', Syntax '<=', Result: 'id <= 10'
Example: 'Greater than or equal to', Syntax '>=', Result: 'id >= 10'
Example: 'Search multiple required properties (logical operator)', Syntax 'AND', Result: 'event = "logout" AND meta.browser.name != "Chrome"'
Example: 'Search for any of multiple properties (logical operator)', Syntax 'OR', Result: 'event = "login" OR event = "logout"'
Example: 'Group multiple logical operators using parentheses', Syntax '()', Result: '(ip = "127.0.0.1" OR meta.os.name = "OS X") AND id > 5'
##
LOWER function to disregard case sensitivity in Strings and request variables mostly use with LIKE operator to disregard case sensitive. Example: 'lower(event) LIKE "LOGIN%" OR lower(meta.os.version) LIKE "Catalina 10.15%"'
##
LIKE Operator for Wildcard Pattern Matching. Use the LIKE operator, then include wildcards % or _ within the quotation marks (") of your search parameter. The % wildcard represents zero, one, or more characters. The _ wildcard represents exactly one character.
Example: 'Find logs that starts with Lo' Result: 'event LIKE "Lo%"'
Example: 'Find events that contains the word port' Result: 'event LIKE "%port%"'
Example: 'Find logs that begin with Ca and those that match three following characters' Result: 'event LIKE "Ca%"'
Example: 'Find logs where os begin with O' Result: 'meta.os.name LIKE "O%"'
Example: 'Find logs where operating system begin with O' Result: 'meta.os.name LIKE "O%"'
Example: 'Find logs where os version begin with Ca' Result: 'meta.os.version LIKE "O%"'
Example: 'Find logs where browser ends with chrome' Result: 'meta.browser.version LIKE "%Chrome"'
Example: 'Find logs where user agent contains Mozilla' Result: 'meta.user_agent LIKE "%Mozilla%"'
Example: 'Find all logs that ip begins with 194.0 and have exactly 6 characters following' Result: 'ip LIKE "194.0______"'
Example: 'Find all logs that ip ends with .244 and have exactly 11 characters following' Result: 'helper LIKE "_______.244"'
##
Use the IN operator to search for data where the value of the specified property is one of multiple specified values. Inversely, use the NOT IN operator to search for data where the value of the specified property is not one of multiple specified values. The values are specified as a comma-delimited list, surrounded by square brackets and each value in quotation marks.
Example:, 'Find events that browser are in Mozilla or Chrome' Result: 'meta.browser.name IN ["Mozilla", "Chrome"]'
Example:, 'Find logs where the event is Login or Logout' Result: '(event IN ["login", "logout"])'
##
The NOW keyword represents the current datetime. Use the NOW keyword in PMQL search queries to find security logs in the following ways: second, minute, hour, day.
Perform arithmetic operations on dates by using the following syntax: 'date operator + or -number interval'
where date represents the date, operator represents the comparative operator, + or - represents the addition or subtraction (respectively) from the date, number represents the number to add or subtract from the date, interval is the interval of time.
Example: 'Show the login logs that are not more than two (2) days old', Example '(occurred_at < NOW -2 day) AND (event = "login")'
Example: 'Show me the events for the last 5 days' Result: 'occurred_at > NOW -5 day'
Example: 'Show me the login logs for the last 3 days' Result: '(event = "login") AND (occurred_at > NOW -5 day)'
Example: 'Show me the logs for the event login for the last 4 days' Result: '(event = "login") AND (occurred_at > NOW -4 day)'
##
Another examples for PMQL:
Example: 'Find events where event is Login' Result: 'event = "login"'
Example: 'Find logout events' Result: 'event = "logout"'
Example: 'Find logout logs' Result: 'event = "logout"'
Example: 'Show me the logs that start with Lo and browser are Mozilla or Chrome' Result: '(event LIKE "lo%") AND (meta.browser.name IN ["Mozilla", "Chrome"])'
Example: 'Show me the logs that start with Lo and browser are Mozilla or Chrome and ip ends with 255.12', Example '(event LIKE "lo%") AND (meta.browser.name IN ["Mozilla", "Chrome"]) AND (ip LIKE "%255.12")'
Example: 'Show me the logs for ip 127.0.0.1' Result: 'ip = "127.0.0.1"'
Example: 'Show me the logs for Windows os' Result: 'meta.os.name = "Windows"'
Example: 'Show me the logs for chrome browser and Mozilla' Result: 'meta.browser.name IN ["Chrome", "Mozilla"]'
Example: 'Show me the logs for chrome browser and Mozilla where operating system Windows', Result: '(meta.browser.name IN ["Chrome", "Mozilla"]) AND (meta.os.name = "Windows")'
Example: 'Show me the events that starts with 'Lo' for chrome browser and Mozilla where operating system Windows' Result: '(event LIKE "lo%") AND (meta.browser.name IN ["Chrome", "Mozilla"]) AND (meta.os.name = "Windows")'
Example: 'Show me the login events' Result: 'event = "login"'
Example: 'Show me the login events for Mozilla and Chrome browsers' Result: '(event = "login") AND (meta.browser.name IN ["Mozilla", "Chrome"])'
Example: 'Show me all the events that starts with Lo or Log for the browsers Safari and Chrome' Result: '((event LIKE "lo%") OR (event LIKE "log%")) AND (meta.browser.name IN ["Chrome", "Safari"])'
Example: 'Show me the events for Chrome browser and OS X os for the last 2 days' Result: '(meta.browser.name = "Chrome") AND (meta.os.name = "OS X") AND (occurred_at > NOW -2 day)'