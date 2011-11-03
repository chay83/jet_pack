# Jet Pack

- Version: 0.2
- Author: Chay Palmer (chay@randb.com.au), Brendan Abbott (brendan@bloodbone.ws)
- Release Date: unreleased
- Requirements: Symphony 2.2.1,
				Author Roles 1.0,
				Email Template Manager 3.0

This extension implements a basic workflow rule of sending email notifications to Authors of a particular Author Role when Authors of another Role create entries in a section.

## Installation and Setup

1.	Upload the 'jet_pack' folder to your Symphony 'extensions' folder.

2.	Enable it by selecting the "Jet Pack", choose Enable from the with-selected menu, then click Apply.

3. 	Create an Email Template Manager template to be sent when a new entry is created. Give the template a 'Name' and 'Subject' as well as a 'Reply-To Name' and 'Reply-To Email Address, but don't worry about the recipients field, this will be filled in by Jet Pack.

4.	Create a Jet Pack Rule by going the 'Jet Pack Rules' in the System menu, select the 'Section' you wish the rule to be applied to and select for which Author Role triggers the rule and which Author Role receives the notification, then select which Email Template Manager template to use. Click Save Changes and your rule will now be active.

### Template Tags
- `{$jet-pack-user}`  Prints the name of the user who has created the content
- `{$jet-pack-section}` Prints the name of the section where content was created
- `{$jet-pack-link}` Prints a HTML link to the newly created content (for a plain text email it will only print the URL)
