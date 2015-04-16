<?php

class Report extends PluginBase
{

    protected $storage = 'DbStorage';
    static protected $description = 'Community Action reporting tool';
    static protected $name = "Community_Action";

    protected $defaultProgram = "Select a Program...";

    public function __construct(PluginManager $manager, $id)
    {
        parent::__construct($manager, $id);

        // Register Event-Listeners Plug-in needs
        $this->subscribe('beforeActivate');
        $this->subscribe('afterAdminMenuLoad');
        $this->subscribe('newDirectRequest');
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');
    }

    /**
     * Runs when plugin is activated creates all the necessary tables to support
     * Community Action Reporting Plugin
     **/
    public function beforeActivate()
    {
        //Create Program Table
        if (!$this->api->tableExists($this, 'programs')) {
            $this->api->createTable($this, 'programs', array(
                'id' => 'pk',
                'programName' => 'string'));
            //Save Default Program
            $this->saveProgram($this->defaultProgram);
        }

        //Create Program Enrollment
        if (!$this->api->tableExists($this, 'program_enrollment')) {
            $this->api->createTable($this, 'program_enrollment', array(
                'survey_id' => 'int',
                'programName' => 'string'));
        }

        // Display Welcome Message to User
        $this->pluginManager->getAPI()->setFlash('Thank you for Activating the
            Community Action Plugin.');
    }

    /**
     * Runs after Admin Menu Loads. Used to display New Icon that will link to
     * the Community Action Data report page.
     **/
    public function afterAdminMenuLoad()
    {
        //Check for if current user is authenticated as a superadmin
        if ($this->isSuperAdmin()) {
            $event = $this->event;
            $menu = $event->get('menu', array());
            $menu['items']['left'][] = array(
                //TODO This breaks if you click the link again from the manage program page
                'href' => "plugins/direct?plugin=Report&function=managePrograms", //TODO Grab URL and make dynamic here
                'alt' => gT('CA Report'),
                'image' => 'chart_bar.png',
            );

            $event->set('menu', $menu);
        }
    }

    /**
     * Add extra tab for additional settings for a survey. Allows survey
     * creator to associate a survey with a Community Action program.
     **/
    public function beforeSurveySettings()
    {
        // Get this surveys ID
        $event = $this->getEvent();
        $survey_id = $event->get('survey');

        // Grab all entry's from program table to populate drop down with
        $existingPrograms = $this->getPrograms();

        // This creates the array of options that we will feed in to the event below.
        $options = array();
        foreach ($existingPrograms as $program) {
            $options[$program] = $program;
        }

        //Check for if this survey is already associated with a program if not set to default value
        $programEnrollment = $this->api->newModel($this, 'program_enrollment');
        $results = $programEnrollment->findAll('survey_id=:sid', array(':sid' => $survey_id));
        $program = CHtml::listData($results, "survey_id", "programName");
        //If survey is associated set drop down menus current value to that program
        $current = $results == null ? $this->defaultProgram : $program;

        //Custom settings for survey
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'program_enrollment' => array(
                    // Select = Drop down menu
                    'type' => 'select',
                    'options' => $options,
                    'current' => $current
                ),
            ),
        ));
    }

    /**
     * Triggered each time a surveys settings change. Loops through all settings and saves them.
     */
    public function newSurveySettings()
    {
        $event = $this->getEvent();
        foreach ($event->get('settings') as $name => $value) {
            //Catch our custom setting and save in program_enrollment table instead of generic plugin settings table
            if ($name = "program_enrollment") {
                $enrollmentModel = $this->api->newModel($this, 'program_enrollment');
                $surveyID = $event->get('survey');
                //Delete old record
                $enrollmentModel->deleteAll('survey_id=:sid', array(':sid' => $surveyID));
                //Save new one
                $enrollmentModel->survey_id = $surveyID;
                $enrollmentModel->programName = $value;
                $enrollmentModel->save();
            } else {
                //Everything else let save where lime survey wants it to be
                $this->set($name, $value, 'Survey', $event->get('survey'));
            }
        }
    }

    /**---Direct Requests--**/

    /**
     * Handles and parses out function calls from Url's
     **/
    public function newDirectRequest()
    {
        //Check for if current user is authenticated as a superadmin
        if ($this->isSuperAdmin()) {
            $event = $this->event;
            //get the function param and then you can call that method
            $functionToCall = $event->get('function');
            $content = call_user_func(array($this, $functionToCall));
            $event->setContent($this, $content);
        } else {
            //Redirect to Login Form *This is the proper way to do this with Yii Framework*
            Yii::app()->getController()->redirect(array('/admin/authentication/sa/login'));
        }
    }

    /**
     * Will generate a report based off data sent in form post
     */
    function generateReport()
    {
        //Get all programs user wants a report on from form post
        $inputPrograms = $_GET['programs'];
        $featureYear = $_GET['yearToFeature'];
        $viewFactory = new ViewFactory();
        $content = $viewFactory->buildReportUI($this->getReportData($inputPrograms, $featureYear), $featureYear);
//        $content = $this->buildReportUI();
        return $content;
    }

    /**
     * Defines the content on the report page TODO Encapsulate this?
     **/
    function managePrograms()
    {
        // Get program to add from form post
        $programToAdd = $_GET['program'];
        //Get all programs from table to check against for duplicates
        $existingPrograms = $this->getPrograms();
        // If program to add is not null or already added save to programs table
        $programExists = in_array($programToAdd, $existingPrograms);
        if ($programToAdd != null && !$programExists) {
            $this->saveProgram($programToAdd);
            // Get all programs again to refresh list after adding a program
            $existingPrograms = $this->getPrograms();
        } else if ($programExists) {
            // Let user know cant add duplicate programs
            $this->pluginManager->getAPI()->setFlash('The program: \'' . $programToAdd . '\' already exists.');
        }

        //Build up list of all programs
        $list = "";
        foreach ($existingPrograms as $program) {
            if ($program != $this->defaultProgram) {
                $list .= $program . "<br/>";
            }
        }

        // Build up UI representing the programs and their associated surveys
        $checkboxes = "";
        $currentProgram = "";
        $x = 0;
        $programEnrollementResults = Yii::app()->db->createCommand("SELECT *
            FROM {{community_action_program_enrollment}}
            ORDER BY programName")->query();
        foreach ($programEnrollementResults->readAll() as $programToAdd) {
            if ($programToAdd != $this->defaultProgram) {
                if ($programToAdd["programName"] != $currentProgram) {
                    $checkboxes .= '<div class="checkbox">
                                 <label><strong>
                                ' . $programToAdd["programName"] . '
                                </strong>
                                </label>
                                </div>';
                    $checkboxes .= '<div class="checkbox" style="text-indent: 1em;">
                                 <label>
                                <input type="checkbox" value="' . $programToAdd["survey_id"] . '" name="programs[' . $x++ . ']">
                                ' . $this->getSurveyTitle($programToAdd["survey_id"]) . '
                                </label>
                                </div>';
                    $currentProgram = $programToAdd["programName"];
                } else {
                    $checkboxes .= '<div class="checkbox" style="text-indent: 1em;">
                                 <label>
                                <input type="checkbox" value="' . $programToAdd["survey_id"] . '" name="programs[' . $x++ . ']">
                                ' . $this->getSurveyTitle($programToAdd["survey_id"]) . '
                                </label>
                                </div>';
                }
            }
        }
        // TODO do we want the ability to delete programs??
        // Add hidden form fields to add params to get request and capture inputted program name
        $form = <<<HTML
<h5>Add a Program:</h5>
<form name="addProgram" method="GET" action="direct">
<input type="text" name="plugin" value="Report" style="display: none">
<input type="text" name="function" value="managePrograms" style="display: none">
<input type="text" name="program">
<input type="submit" value="+">
</form>
HTML;

        //$content is what is rendered to page
        $content = '<div class="container well"style="margin-bottom: 20px">' . $form . '<h5>Programs:</h5>' . $list . '</div>';

        //Generate Report Form
        $content .= '<div class="container well" style="margin-bottom: 20px"><h4>Generate Report</h4>';

        $content .= '<form name="generateReport" method="GET" action="direct">
<input type="text" name="plugin" value="Report" style="display: none">
<input type="text" name="function" value="generateReport" style="display: none">
Year to feature:
<select name="yearToFeature">
    <option selected>2015</option>
</select>
' . $checkboxes . '
<input type="submit" value="Generate Report">
</form></div>';

        return $content;
    }


    /**---Helper Functions---**/


    /**
     * @param $surveysToInclude the names of all the programs we want to generate reports for
     * @return array the data we need to generate a report
     */
    private function getReportData($surveysToInclude, $featureYear)
    {
        //Array to hold all surveys to Return
        $surveys = array();
        foreach ($surveysToInclude as $surveyID) {

            //Get all questions associated with current survey TODO give question group name a better name
            $query = "SELECT
              q.sid, q.gid, q.qid, q.question
              FROM {{questions}} q
              INNER JOIN {{groups}} g ON g.gid = q.gid
              WHERE q.sid = $surveyID
              AND g.group_name = 'Community Action\'s Core Questions 03/04/2015'
              GROUP BY q.qid";
            $results = Yii::app()->db->createCommand($query)->query();

            //Holds general data about current survey
            $surveyData = array();
            $surveyData['questions'] = array();

            //Check for if this survey uses tokens
            if (!is_null(Yii::app()->db->schema->getTable("{{tokens_$surveyID}}"))) {
                //Get total # of tokens created for this survey
                $tokensCreated = Yii::app()->db->createCommand(
                    "Select COUNT(*) as tokensCreated
                FROM {{tokens_$surveyID}}
                WHERE sent NOT LIKE 'N'
                AND YEAR(sent) = $featureYear" //Only count tokens that have actually been sent and are in featured year
                )->query()->read();
                $surveyData['tokenCount'] = $tokensCreated["tokensCreated"];

            }

            // Loop through all questions for current survey
            foreach ($results->readAll() as $questionRow) {

                //Get program associated with this survey
                $programEnrollment = $this->api->newModel($this, 'program_enrollment');
                $titleResults = $programEnrollment->find('survey_id=:sid', array(':sid' => $surveyID));
                $surveyData['programTitle'] = $titleResults["programName"];

                //Get surveys title
                $surveyData['title'] = $this->getSurveyTitle($surveyID);

                //This holds general information about each question
                $questionData = array();
                $questionData['title'] = flattenText($questionRow['question']);
                $questionData['possibleAnswers'] = array();

                //Determine this questions column name in DB
                $questionString = $questionRow['sid'] . 'X' . $questionRow['gid'] . 'X' . $questionRow['qid'];

                //Figure out if question is optional
                $optionalAnswerCount = Yii::app()->db->createCommand(
                    "SELECT COUNT(*) AS 'count'
                         FROM {{survey_$surveyID}}
                         WHERE `$questionString` = ''"
                )->query()->read();

                if ($optionalAnswerCount['count'] > 0) {
                    $questionData['isOptional'] = 'true';
                    array_push($questionData['possibleAnswers'], 'No answer');
                } else {
                    $questionData['isOptional'] = 'false';
                }

                //Get all years of valid data
                $yearsOfData = Yii::app()->db->createCommand(
                    "SELECT DISTINCT(year(submitdate)) as 'year' FROM {{survey_$surveyID}}"
                )->query();

                $firstYear = true;
                //Loop on each question for each valid year
                foreach ($yearsOfData->readAll() as $year) {

                    //get current year
                    $currentYear = $year['year'];

                    // *** Get all possible answers for current question ***
                    //TODO draw this out of year loop inefficient to do this every time
                    $answersResults = Yii::app()->db->createCommand(
                        " SELECT `code` AS AnswerValue, answer AS AnswerText
                              FROM {{answers}}
                              WHERE qid = " . $questionRow['qid']
                    )->query();

                    //Read first result
                    $currentAnswer = $answersResults->read();

                    // *** Get Survey Responses for this year ***
                    $responsesResults = Yii::app()->db->createCommand("SELECT  `" . $questionString
                        . "`AS AnswerValue, COUNT(*) AS `Count` FROM {{survey_$surveyID}}
                        WHERE YEAR(submitdate) = $currentYear
                        GROUP BY `"
                        . $questionString . "`")->query();

                    //Holds current questions response data by year in a graph-able format
                    $answerCount = array();

                    //Loop through all returned user results
                    $firstResponse = true;
                    foreach ($responsesResults->readAll() as $responseRow) {

                        // Must have this check for if the question was optional and has no answer result
                        if ($responseRow['AnswerValue'] == "") {
                            array_push($answerCount, array('A0' => (int)$responseRow['Count']));
                        } else {
                            if ($questionData['isOptional'] == 'true' && $firstResponse) {
                                array_push($answerCount, array('A0' => 0));
                            }
                            //Fill Data Holes until next answer has value
                            while ($responseRow['AnswerValue'] != $currentAnswer['AnswerValue'] && $currentAnswer) {
                                array_push($answerCount, array($currentAnswer['AnswerValue'] => 0));
                                if ($firstYear) {
                                    array_push($questionData['possibleAnswers'], $currentAnswer['AnswerText']);
                                }
                                $currentAnswer = $answersResults->read();
                            }
                            //Push valid answer count and value if it exists
                            if ($currentAnswer) {
                                array_push($answerCount, array($currentAnswer['AnswerValue'] => (int)$responseRow['Count']));
                                if ($firstYear) {
                                    array_push($questionData['possibleAnswers'], $currentAnswer['AnswerText']);
                                }
                            }
                            // Move to next answer result
                            $currentAnswer = $answersResults->read();
                        }
                        $firstResponse = false;
                    }

                    //Fill trailing data holes
                    if ($currentAnswer) {
                        array_push($answerCount, array($currentAnswer['AnswerValue'] => 0));
                        if ($firstYear) {
                            array_push($questionData['possibleAnswers'], $currentAnswer['AnswerText']);
                        }
                        $currentAnswer = $answersResults->read();
                        while ($currentAnswer) {
                            array_push($answerCount, array($currentAnswer['AnswerValue'] => 0));
                            if ($firstYear) {
                                array_push($questionData['possibleAnswers'], $currentAnswer['AnswerText']);
                            }
                            $currentAnswer = $answersResults->read();
                        }
                    }

                    //Update question and survey data arrays
                    $questionData['answerCount'][$currentYear] = array();
                    $questionData['answerCount'][$currentYear]['year'] = $currentYear;
                    array_push($questionData['answerCount'][$currentYear], $answerCount);
                    $firstYear = false;
                }
                array_push($surveyData['questions'], $questionData);
            }

            //Get total survey responses
            $surveyData['totalResponses'] = 0;
            //Just look at how many responses there were to the first question
            $x = !is_null($surveyData['questions'][0]['answerCount'][$featureYear]['0']['0']['A0']) ? 0 : 1;
            foreach ($surveyData['questions'][0]['answerCount'][$featureYear]['0'] as $question) {
                $surveyData['totalResponses'] += (int)$question['A' . $x];
                $x++;
            }

            //Push survey survey data
            array_push($surveys, $surveyData);
        }
        //uncomment lines below for helpful debugging view of data structure
//        print_r('<pre>');
//        print_r($surveys);
        return $surveys;
    }

    /**
     * Checks for if user is authenticated and of type superadmin
     * TODO Do we really want to authenticate as super admin? probably just any user being logged in is enough for us?
     */
    private function isSuperAdmin()
    {
        $currentUser = $this->api->getPermissionSet($this->api->getCurrentUser()->uid);
        $currentUserPermissions = $currentUser[1][permission]; // TODO will this break if multiple people are logged in at once??
        if ($currentUserPermissions == 'superadmin') {
            return true;
        }
        return false;

    }

    /**
     * @param $surveyID
     * @return mixed|null
     */
    private function getSurveyTitle($surveyID)
    {
        //Get surveys title
        $titleResults = SurveyLanguageSetting::model()->findByAttributes(array('surveyls_survey_id' => $surveyID, 'surveyls_language' => 'en'));
        return $titleResults->surveyls_title;
    }

    /**
     * Returns an array of all programs
     * @param null $sid
     * @return array
     */
    private function getPrograms($sid = null)
    {
        $programModel = $this->api->newModel($this, 'programs');
        $results = $sid == null ? $programModel->findAll() : $programModel->findAll('survey_id=:sid', array(':sid' => $sid));
        return $results == null ? null : CHtml::listData($results, "id", "programName");
    }

    /**
     * Saves a new program mode
     * @param $programName
     */
    private function saveProgram($programName)
    {
        $programModel = $this->api->newModel($this, 'programs');
        $programModel->programName = $programName;
        $programModel->save();
    }
}


?>