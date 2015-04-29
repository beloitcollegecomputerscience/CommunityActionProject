This section is for creating a completely new survey. In most cases for Community Action, you would like to include a specific question group that already exists. In such a case, we recommend referring to the <a href="http://communityactionsurvey.org/guide/index.php/Surveys/Importing_a_Survey">Importing a Survey</a> section and not this section. (In most cases you would want to include the core question group for the surveys tied to different programs in addition to the program specific groups).

* To create a brand new survey, first, from LimeSurvey’s administrator main menu, in the top right corner click the _Create, import, or copy survey_ button.

<br />

![Create Survey Button](../../img/create-survey.png)

<br />

* Next, in the _General_ tab, fill out your title and description (which will appear at the beginning of the survey). Select the base language of the survey, and fill in the other fields on the screen.

	* **Field Meanings:**
		* The _end URL_ field is the URL that can be presented to the user on the screen after they have completed the survey. 
		* The _bounce email_ field is used to track the surveys that were not delivered correctly to the recipient, and this information will be sent back to the bounce email provided.
		* The _URL description_ gives the user a description of what the _end URL_ will lead them to.
		* The _welcome message_ is shown to the user on the first page of the survey before the questions are shown for the user to enter.
		* The _end message_ is what the user will see once they have answered the final question and submitted the survey.

<p></p>

* The _Presentation & Navigation_ tab contains settings on how the overall flow, look, and feel of the survey is organized . It allows you to control how the questions are grouped, the visual theme, whether or not there is a welcome screen, etc.

* The _Publication & Access Control_ tab allows you to set whether or not the survey is listed publicly, the use of a captcha to take the survey, setting a cookie to prevent repeated participation, and a start and end time.

* The _Notification & Data Management_ tab allows you to set the  email for admin notification, whether to include a date stamp,  whether the participant can save and resume later, etc.

* The _Tokens_ tab allows you to set settings like  whether responses are anonymous, if confirmation emails are sent, and token length.

* The _Import_ tab allows for importing of a survey from a previously exported LimeSurvey survey file.

* The _Copy_ tab allows the reuse of  a survey  that has been previously created.

* After you have set up the survey settings to your specifications, click the save button at the bottom.

* You will then be taken to a screen where your survey’s general information will be displayed, and  some red text informing you that you need to add a question/question group. To do this, click the plus button in the right hand corner, or select a previously created group in the drop down.

<br />

![Create Question Group Button](../../img/create-question-group.png)

<br />

* If you click the button to create a new question group, you will be taken to a new screen where you will now enter in the title of your question group and a description. Once you have done so and clicked save at the bottom, a third navigation bar will be added under the first two. Click the plus button to add a new question to the group.

* A new screen will be displayed with fields to enter the:

	* **Question Code:** your ID, or number or code for the question. This field is only for quick identification for a question in export or for evaluation. Try to be consistent with your coding in this field. Planning makes your evaluation a lot easier at a later time. This field is normally not displayed to people taking the survey.
	* **Question:** the question itself
	* **Help:** to give the user a hint on how to answer the question
	* **Question Type:** whether the question is multiple choice, free text, etc.
		* Note that for reports to be generated correctly the only compatible question type so far is _List (radio)_
	* **Question Group**
	* **Mandatory:** whether or not the survey is mandatory

<p></p>
* Advanced settings can also be set by clicking the link, and LimeSurvey question file can be imported  at the bottom of the page

* After you are finished, click the _Add Question_ button and the question will be added to the group. Repeat this process for all other questions needed in the survey.

<br />

For more information, please visit https://manual.limesurvey.org/Creating_surveys_-_Introduction