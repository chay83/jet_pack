# Jet Pack

- Version: 1.1.1
- Author: Chay
- Release Date: October 31st 2011
- Requirements: Symphony 2.2.1
				Author Roles 1.0
				Email Template Manager 3.0

Send notifications to members of an Author Role when another group creates content

## Installation and Setup

1.	Upload the 'jet_pack' folder to your Symphony 'extensions' folder.

2.	Enable it by selecting the "Jet Pack", choose Enable from the with-selected menu, then click Apply.

3. 	Create an Email Template Manager template to be sent when content is created. Give the template a 'Name' and 'Subject' as well as a 'Reply-To Name' and 'Reply-To Email Address, don't worry about the recipients field.

### Template Tags
-    {$jet-pack-user}  Prints the name of the user who has created the content
-    {$jet-pack-section} Prints the name of the section where content was created
-    {$jet-pack-link} Prints a HTML link to the newly created content (for a plain text email it will only print the URL)

4.	Create a Jet Pack Rule by going the 'Jet Pack Rules' in the system menu, select the 'Section' you wish the rule to be applied to and select for which user group triggers the rule and which group receives the notification, then select which Email Template Manager template to use. Click Save Changes and your rule will now be active.