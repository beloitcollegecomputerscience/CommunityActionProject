Tokens allow the administrator to invite a group of people to participate in a survey, keep track of who has completed the survey, and ensure that each person only participated once. 

**NOTE: The following steps must be done for each individual survey.**

With tokens, you can:

* Import a list of names and email addresses for participants (from a CSV file or an LDAP query)
* Generate a unique token number for each participant (invitation code)
* Send an email invitation to each person in your list, by group or individually
* Send a reminder email to each person in your list who has not yet responded, by group or individually
* Track who from your list has responded
* Restrict access against people who have not got a token, and those with a token who have already responded
* Edit/change any details in your list
* Create email templates for invitations & reminders

To get to token management, navigate to the desired survey (in this example, Community Action Program Satisfaction Survey), and click on the little man button shown below.

![Access Token Management](../img/access-token-management.png)

<br />

* To make tokens in bulk, prepare a spreadsheet (**saved as a .csv file**) with three columns beginning with _firstname_, _lastname_, and _email_. Fill out these columns with the information of the recipients of the survey. 
* The spreadsheet should resemble:

<br />


| firstname | lastname   | email        |
|-----------|------------|--------------|
| Doc       | Huckepack  | dh@email.com |
| Grumpy	| Naseweis   | gn@email.com |
| Happy	    | Packe	     | hp@email.com |
| Sleepy	| Pick	     | sp@email.com |
| Bashful	| Puck	     | bp@email.com |
| Sneezy	| Purzelbaum | spb@email.com|
| Dopey	    | Rumpelbold | dr@email.com |

<br />

* Additional fields (columns) can be added, such as _language_.
* Remember to save the spreadsheet as a .csv file.
* To upload these tokens, press the _Import tokens from CSV file_ button, circled on the left. 

<br />

![Upload Tokens](../img/upload-tokens.png)

<br />

* After uploading, don’t forget to click _Generate Tokens_, pictured on the right.
* If you now go to _Display Tokens_ you can see the newly added tokens at the bottom of the list.

<br />

![Display Tokens](../img/display-tokens.png)

<br />

* To email the surveys out, click on the middle email button with an arrow, on the top toolbar. It will automatically find the new entries, show you the preview of the outgoing email, and allow you to edit it. 

<br />

![E-mail Tokens](../img/email-tokens.png)

<br />

* The above buttons send to all applicable tokens (e-mails send to new tokens, reminders send to tokens that have not yet replied yet, but have received an invitation.)
* If you want to e-mail or remind specific tokens, check the boxes you want, and use the buttons on the bottom of the screen.

<br />

![Remind Tokens](../img/remind-tokens.png)

<br />

For more information, please visit https://manual.limesurvey.org/Tokens