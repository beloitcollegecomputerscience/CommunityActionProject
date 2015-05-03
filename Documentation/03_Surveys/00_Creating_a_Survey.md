This section is for creating a completely new survey. In most cases for Community Action, you would like to include a specific question group that already exists. (In most cases you would want to include the core question group for the surveys tied to different programs in addition to the program specific groups).

* To create a brand new survey, first, from LimeSurvey’s administrator main menu, in the top right corner click the _Create, import, or copy survey_ button.

<br />

![Create Survey Button](../../img/create-survey.png)

<br />

* Fill in the title, description, and the welcome message for the Survey. Participants will see all these before they take the survey.

* Next, hover over the pencil icon and click on _General Settings_. 

<br />

![General Settings List](../../img/general-settings-list.png)

<br />

* In the _General_ tab the following options are available:

	* **Additional languages:** If you want the survey available in another language, select the language from the right box and click **<<Add. NOTE: Adding a language to a survey does not translate the questions. You may need to edit questions and add the text and answers from the other language, otherwise they will appear in English.**
	* **Administrator:** This name will be visible to participants.
	* **Admin email:** This email is visible to participants and will receive emails.
	* **Bounce email:** This email is used to track the surveys that were not delivered correctly to the recipient, and this information will be sent back to the bounce email.

<p></p>

* The _Presentation & Navigation_ tab contains settings on how the overall flow, look, and feel of the survey is organized.  **All of these settings are optional.**  It allows you to control how the questions are grouped, the visual theme, whether or not there is a welcome screen, etc. 

* The _Publication & Access Control_ tab allows you to set whether or not the survey is listed publicly, the use of a captcha to take the survey, setting a cookie to prevent repeated participation, and a start and end time.  **All of these settings are optional, though we do not recommend you set Start or Expiry dates.**

* The _Notification & Data Management_ tab allows you to set the  email for admin notification, whether to include a date stamp,  whether the participant can save and resume later, etc.
	* We recommend that you insert the admin’s email in the first two boxes and set **Date Stamp** to **Yes**.

<p></p>

* The _Tokens_ tab allows you to set settings such as  whether responses are anonymous, if confirmation emails are sent, and token length.
	* **We recommend that you do not change these settings.**

<p></p>

* The _Panel Integration_ and _Resources_ tabs are optional.
	* **We recommend you not change these settings.**

* If the _Plugin_ tab appears next, **do not change it yet**. There are further instructions regarding this tab.

* After you have set up the survey settings to your specifications, **click the save button at the bottom**.

* **<u>From here you will either:</u>**
	* **<a href="#create">Create a new question group</a>**
	* **<a href="#import">Import a question group</a>**
* **<u>Please go to the corresponding section.</u>**

**Optional:** If you wish to edit emails sent to Participants, hover over the Pencil icon and click on _Email templates_

<br />

<h1><a name="create">Creating a Question Group</a></h1>

<br />

![Create Question Group Button](../../img/create-question-group.png)

<br />

* If you click the button to create a new question group, you will be taken to a new screen where you will now enter in the title of your question group and a description. Once you have done so and clicked save at the bottom, a third navigation bar will be added under the first two. Click the plus button to add a new question to the group.

* A new screen will be displayed with fields to enter the:

	* **Question Code:** your ID, or number or code for the question. This field is only for quick identification for a question in export or for evaluation. Try to be consistent with your coding in this field. Planning makes your evaluation a lot easier at a later time. This field is normally not displayed to people taking the survey.
	* **Question:** the question itself
	* **Help:** to give the user a hint on how to answer the question
	* **Question Type:** whether the question is multiple choice, free text, etc.
		* **<u>Note that for reports to be generated correctly the only compatible question type so far is _List (radio)_</u>**
	* **Question Group**
	* **Mandatory: <u>Please make each question mandatory</u>**

<p></p>

* Advanced settings can also be set by clicking the link, and LimeSurvey question file can be imported  at the bottom of the page

* After you are finished, click the _Add Question_ button and the question will be added to the group. Repeat this process for all other questions needed in the survey.

**Adding Responses:** 
* If you wish to edit emails sent to Participants, hover over the Pencil icon and click on _Email templates_

<br />

![Edit answers](../../img/answers.png)

<br />

* Enter the first answer in the Answers option box, then click on the little plus icon on the right and repeat
	* **Please ensure that the codes are numbered in order: A1, A2, A3, etc.**

<p></p>

* When you are finished, click the _Save_ button.

<br />

<h1 style="text-decoration:none;"><a name="import">Importing a Question Group</a></h1>

<br />

* To create a survey for a program you may want to include the core question group which is required for surveys for every program.

* To import a question group, first create a survey by performing the steps above.

* After filling out the fields in the _General_ tab, click on _Save_ and you will be directed to a page with a summary of the current survey.

* Now you are supposed to make a question group. Click on the "+" to the right just above the summary next to the question groups drop down menu. (Not the "+" on the top right next to surveys drop down menu!).

<br />

![Create Question Group Button](../../img/create-question-group.png)

<br />

* Since you want to import an existing question group, go to the _Import question group_ tab.

<br />

![Import question group tab](../../img/question-group-tab.png)

<br />

* Then browse and upload the question group file (.lsg).

<br />

![Import question group](../../img/import-question-group.png)

<br />

* You can **add or import question groups** specific to a survey or program in this way, or create new ones as outlined in the _Creating a Question Group_ section above.

* If everything was done correctly you should now have a complete survey!

<br />

For more information, please visit https://manual.limesurvey.org/Creating_surveys_-_Introduction