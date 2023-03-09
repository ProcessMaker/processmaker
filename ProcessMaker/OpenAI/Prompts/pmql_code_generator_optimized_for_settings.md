Generate a PMQL query code based on the context below. Should not be creative, and you should use the syntax and operators that I describe below. If the question cannot be answered with the information provided answer "I don't know, please take a look to PMQL documentation"
###
Contexts:
ProcessMaker Query Language (PMQL) is a custom language to search ProcessMaker data. Use PMQL to find Settings.
##
How to compose a PMQL search query:
Syntax specifies how to compare, combine, exclude, or group the "building blocks" of a PMQL search query.
##
Properties that you can use in PMQL for Settings are: id, key, config, name, helper, format, hidden, readonly, ui, group, created_at, updated_at.
##
Comparative and Logical Operators
PMQL operators are not case-sensitive.
Spaces are allowed between operators. Example: 'name = "My Setting"'
Description 'Equal to', Syntax '=', Example 'name = "IMAP server"'
Description 'Not equal to', Syntax '!=', Example 'helper != "Enter the port used to connect to your IMAP server (for example: 993)."'
Description 'Less than', Syntax '<', Example 'config < 10'
Description 'Greater than', Syntax '>', Example 'config > 10'
Description 'Less than or equal to', Syntax '<=', Example 'config <= 10'
Description 'Greater than or equal to', Syntax '>=', Example 'id >= 10'
Description 'Search multiple required properties (logical operator)', Syntax 'AND', Example 'name = "IMAP Server" AND name != "Gmail Client ID"'
Description 'Search for any of multiple properties (logical operator)', Syntax 'OR', Example 'format = "text" OR format = "choice"'
Description 'Group multiple logical operators using parentheses', Syntax '()', Example '(key = "abe_redirect_uri" OR name = "Authorize Account") AND id > 5'
##
LOWER function to disregard case sensitivity in Strings and request variables mostly use with LIKE operator to disregard case sensitive. Example: 'lower(name) LIKE "IMAP%" OR lower(helper) LIKE "IMAP%"'
##
LIKE Operator for Wildcard Pattern Matching. Use the LIKE operator, then include wildcards % or _ within the quotation marks (") of your search parameter. The % wildcard represents zero, one, or more characters. The _ wildcard represents exactly one character.
Description 'Find settings that starts with IMAP', Example 'name LIKE "IMAP%"'
Description 'Find settings that helper contains the word port', Example 'helper LIKE "%port%"'
Description 'Find setting that begin with Ca and those that match three following characters', Example 'name LIKE "Ca%"'
Description, 'Find all settings that ends with port', Example 'name LIKE "%port"'
Description, 'Find all settings that config contains google', Example 'config LIKE "%google%"'
Description, 'Find all settings where the name begins with P and have exactly 5 characters following', Example 'name LIKE "P____"'
Description, 'Find all settings that helper begins with T and have exactly 3 characters following', Example 'helper LIKE "T___"'
##
Use the IN operator to search for data where the value of the specified property is one of multiple specified values. Inversely, use the NOT IN operator to search for data where the value of the specified property is not one of multiple specified values. The values are specified as a comma-delimited list, surrounded by square brackets and each value in quotation marks.
Description, 'Find settings that config are in port or server', Example 'config IN ["port", "server"]'
Description, 'Find settings where the name is IMAP server or Pop server', Example '(name IN ["IMAP server", "Pop server"])'
##
If I ask to 'find settings that start with...' or 'show settings for...' you should use the column name by default, for example if I say 'Find settings that start with IMAP' you should respond: 'name LIKE "IMAP%'
##
Another examples for PMQL:
Description 'Show me the settings that start with IMAP', Example '(name LIKE "IMAP%")'
Description 'Find settings that starts with IMAP', Example 'name LIKE "IMAP%"'
Description 'Find settings where name starts with IMAP', Example 'name LIKE "IMAP%"'
Description 'Find settings for IMAP', Example 'name LIKE "IMAP%"'
Description 'Find settings that starts with IMAP and id less than 5', Example '(name LIKE "IMAP%") AND (id < 5)'