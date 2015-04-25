<?php

/**
 * Community Action Reporting tool built to help with mandated federal reporting
 *
 * Built by Beloit College Database Capstone Class 2014-2015
 *
 */
class Report extends PluginBase
{
    //Plugin Settings
    protected $storage = 'DbStorage';
    static protected $description = 'Community Action reporting tool';
    static protected $name = "Community_Action";

    /***************** LimeSurvey Plugin Functions ********************/

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
                'programName' => 'string',
                'description' => 'string'));
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
                //TODO Make this URL Dynamic
                'href' => "/index.php/plugins/direct?plugin=Report&function=managePrograms",
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

        //Check for if this survey is already associated with a program if not set to default value
        $programEnrollment = $this->api->newModel($this, 'program_enrollment');
        $results = $programEnrollment->findAll('survey_id=:sid', array(':sid' => $survey_id));
        $program = CHtml::listData($results, "survey_id", "programName");

        // Creates the array of options that we will feed in to the event below.
        $options = array();
        //If survey is associated set drop down menus current value to that program
        if($results != null){
            $current = $program;
        }else{
            //Else populate with default value
            $current = "Select a Program...";
            $options["Select a Program..."] = "Select a Program...";
        }

        // Grab all entry's from program table to populate drop down with
        $existingPrograms = $this->getPrograms();
        foreach ($existingPrograms as $program) {
            $options[$program] = $program;
        }

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
                //Make sure were not saving default program
                if($value != "Select a Program...") {
                    $enrollmentModel = $this->api->newModel($this, 'program_enrollment');
                    $surveyID = $event->get('survey');
                    //Delete old record
                    $enrollmentModel->deleteAll('survey_id=:sid', array(':sid' => $surveyID));
                    //Save new one
                    $enrollmentModel->survey_id = $surveyID;
                    $enrollmentModel->programName = $value;
                    $enrollmentModel->save();
                }
            } else {
                //Everything else let save where lime survey wants it to be
                $this->set($name, $value, 'Survey', $event->get('survey'));
            }
        }
    }

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


    /************************ Report Generation ************************/

    /**
     * Will generate a report based off data sent in form post
     */
    function generateReport()
    {
        //Get all programs user wants a report on from form post
        $surveysToReportOn = $_GET['surveys'];
        $featureYear = $_GET['yearToFeature'];
        $viewFactory = new ReportFactory();
        $content = $viewFactory->buildReportUI($this->getReportData($surveysToReportOn, $featureYear), $featureYear);
        return $content;
    }

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
                $surveyProgramData = $programEnrollment->find('survey_id=:sid', array(':sid' => $surveyID));
                $surveyData['programTitle'] = $surveyProgramData["programName"];
                $programTitle = $surveyData['programTitle'];
                //Get program data
                $program = Yii::app()->db->createCommand(
                    "SELECT * FROM {{community_action_programs}}
                     WHERE programName = '$programTitle'")->query()->read();
                $surveyData['programDescription'] = $program["description"];

                //Get surveys title
                $surveyData['title'] = $this->getSurveyTitle($surveyID);

                //This holds general information about each question
                $questionData = array();
                $questionData['title'] = flattenText($questionRow['question']);
                $questionData['possibleAnswers'] = array();

                //Determine this questions column name in DB
                $questionDBColumnName = $questionRow['sid'] . 'X' . $questionRow['gid'] . 'X' . $questionRow['qid'];

                //Figure out if question is optional
                $optionalAnswerCount = Yii::app()->db->createCommand(
                    "SELECT COUNT(*) AS 'count'
                         FROM {{survey_$surveyID}}
                         WHERE `$questionDBColumnName` = ''"
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
                    $responsesResults = Yii::app()->db->createCommand("SELECT  `" . $questionDBColumnName
                        . "`AS AnswerValue, COUNT(*) AS `Count` FROM {{survey_$surveyID}}
                        WHERE YEAR(submitdate) = $currentYear
                        GROUP BY `"
                        . $questionDBColumnName . "`")->query();

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


    /**************** Program CRUD Functionality and UI *****************/

    /**
     * Returns an array of all programs
     * @param null $sid
     * @return array
     */
    private function getPrograms()
    {
        $results = $this->api->newModel($this, 'programs')->findAll();
        return $results == null ? null : CHtml::listData($results, "id", "programName");
    }

    /**
     * Builds the content on the Manage Program page
     **/
    function managePrograms()
    {
        //Get all programs from table to check against for duplicates
        $existingPrograms = $this->getPrograms();

        //  ** Build up add a program button and list of existing programs
        $list = "";
        foreach ($existingPrograms as $program) {
            $list .= $program
                . ' <a href="/index.php/plugins/direct?plugin=Report&function=viewProgramDetails&programName='
                . $program
                . '">Details</a><br/>';
        }

        $content = '<div class="container well"style="margin-bottom: 20px; margin-top: 20px;">'
            . '<h5>Programs:</h5>'
            . $list
            . '<br/><a href="/index.php/plugins/direct?plugin=Report&function=addProgramForm" class="btn btn-lg btn-success">Add a Program</a>'
            . '</div>';

        // ** Generate Report Form

        //Build up UI representing the programs and their associated surveys
        $checkboxes = "";
        $currentProgram = "";
        $x = 0;
        $programEnrollementResults = Yii::app()->db->createCommand("SELECT *
            FROM {{community_action_program_enrollment}}
            ORDER BY programName")->query();
        //Loop through returned results showing surveys that are associated with a program
        foreach ($programEnrollementResults->readAll() as $programToAdd) {
            if ($programToAdd["programName"] != $currentProgram) {
                $checkboxes .= '<div class="checkbox">
                                 <label><strong>
                                ' . $programToAdd["programName"] . '
                                </strong>
                                </label>
                                </div>';
                $checkboxes .= '<div class="checkbox" style="text-indent: 1em;">
                                 <label>
                                <input type="checkbox" value="' . $programToAdd["survey_id"] . '" name="surveys[' . $x++ . ']">
                                ' . $this->getSurveyTitle($programToAdd["survey_id"]) . '
                                </label>
                                </div>';
                $currentProgram = $programToAdd["programName"];
            } else {
                $checkboxes .= '<div class="checkbox" style="text-indent: 1em;">
                                 <label>
                                <input type="checkbox" value="' . $programToAdd["survey_id"] . '" name="surveys[' . $x++ . ']">
                                ' . $this->getSurveyTitle($programToAdd["survey_id"]) . '
                                </label>
                                </div>';
            }
        }
        $content .= '<div class="container well" style="margin-bottom: 20px">
                     <h4>Generate Report</h4>';


        // Generate feature year drop down menu
        $yearDropDown = '<select name="yearToFeature">';
        $thisYear = date('Y');
        $startYear = ($thisYear - 20); //Just go back 20 years? this is probably enough for now
        foreach (range($thisYear, $startYear) as $year) {
            if ($year == $thisYear) {
                $yearDropDown .= "<option selected>$year</option>";
            } else {
                $yearDropDown .= "<option>$year</option>";
            }
        }
        $yearDropDown .= "</select>";

        $content .= '<form name="generateReport" method="GET" action="direct">
                        <input type="text" name="plugin" value="Report" style="display: none">
                        <input type="text" name="function" value="generateReport" style="display: none">
                        Year to feature:

                        ' . $yearDropDown . $checkboxes . '
                        <input type="submit" value="Generate Report">
                      </form>
                      </div>';

        return $content;
    }

    /**
     * The UI with form for adding a program
     * @return string the
     */
    private function addProgramForm()
    {
        $content = '"<div class="container">';
        $form = <<<HTML
        <h5>Add a new Program</h5>
        <br/>
        <form name="addProgram" method="GET" action="direct">
            <!--Add hidden form fields to add params to request and capture inputted program name-->
            <input type="text" name="plugin" value="Report" style="display: none">
            <input type="text" name="function" value="addProgram" style="display: none">
            <label><strong>Name</strong></label><br/>
            <input type="text" name="programName" required>
            <br/>
            <br/>
            <label><strong>Description</strong></label><br/>
            <textarea COLS=50 ROWS=5 name="programDescription" required></textarea>
            <br/>
            <input type="submit" value="Save Program" style="margin-top: 20px;margin-bottom: 20px;">

        </form>
HTML;
        $content .= $form . "</div>";
        return $content;
    }

    /**
     * Actually adds new program and then reroutes to manage program view
     */
    private function addProgram()
    {
        // Get program to add details from form post
        $programName = $_GET['programName'];
        $programDescription = $_GET['programDescription'];

        //Get all programs from table to check against for duplicates
        $existingPrograms = $this->getPrograms();
        // If program to add is not null or already added save to programs table
        $programExists = in_array($programName, $existingPrograms);
        if ($programName != null && !$programExists) {
            $this->saveProgram($programName, $programDescription);
        } else if ($programExists) {
            // Let user know cant add duplicate programs
            $this->pluginManager->getAPI()->setFlash('The program: \'' . $programName . '\' already exists.');
        }
        //Redirect to manage programs page
        Yii::app()->getController()->redirect(array('/plugins/direct?plugin=Report&function=managePrograms'));
    }

    /**
     * Saves a new program to DB
     * @param $programName
     */
    private function saveProgram($programName, $description)
    {
        $programModel = $this->api->newModel($this, 'programs');
        $programModel->programName = $programName;
        $programModel->description = $description;
        $programModel->save();
    }


    /**
     * Builds the content on the Program Details page
     *
     * @return string the program details page html
     */
    private function viewProgramDetails()
    {
        $programName = $_GET['programName'];

        //Get program data
        $program = Yii::app()->db->createCommand(
            "SELECT * FROM {{community_action_programs}}
            WHERE programName = '$programName'")->query()->read();

        // ** Build the view **

        $content = '<div class="container"><br/><h1>'
            . $programName
            . '</h1>
            <br/>';

        //Description
        $content .= '<h4>Description:</h4>
        <div class="well" style="padding-bottom: 10px">
        ' . $program["description"] . '</div></br>';

        //Delete Button
        $content .= '<a class="btn-large btn-warning"style="margin: 20px;text-decoration: none;"
        href="/index.php/plugins/direct?plugin=Report&function=editProgram&programName='
            . $programName
            . '">Edit Program</a>
            <a class="btn-large btn-danger"style="margin: 20px;text-decoration: none;"
            href="/index.php/plugins/direct?plugin=Report&function=deleteProgram&programName='
            . $programName
            . '">Delete Program</a>
            *Note this only deletes the program and its data <strong>not</strong> any survey data.
            <br/>
            <br/>';

        $content .= "</div>";
        return $content;
    }

    /**
     * Builds the content on the Edit Program page
     *
     * @return string the edit program page html
     */
    private function editProgram()
    {
        $programName = $_GET['programName'];

        $content = '"<div class="container">';
        $content .= "<h2>$programName</h2>";

        $form = <<<HTML
        <form name="addProgram" method="GET" action="direct">
            <!--Add hidden form fields to add params to request and capture inputted program name-->
            <input type="text" name="plugin" value="Report" style="display: none">
            <input type="text" name="function" value="updateProgram" style="display: none">
HTML;
        $form .= '<input type="text" name="programName" value="' . $programName . '"style="display: none;" >';
        $form .= <<<HTML
            <label><strong>Description</strong></label><br/>
            <textarea COLS=50 ROWS=5 name="programDescription" required>
HTML;

        //Get program data
        $program = Yii::app()->db->createCommand(
            "SELECT * FROM {{community_action_programs}}
            WHERE programName = '$programName'")->query()->read();

        $form .= $program["description"];
        $form .= '</textarea>
            <br/>
            <input type="submit" value="Update Program" style="margin-top: 20px;margin-bottom: 20px;">

        </form>';

        $content .= $form . "</div>";
        return $content;
    }


    /**
     * Updates the actual program record and then redirects to the manage program page
     */
    private function updateProgram()
    {
        $programName = $_GET['programName'];
        $newProgramDescription = $_GET['programDescription'];

        //Get program data need primary key to update name is not enough
        $program = Yii::app()->db->createCommand(
            "SELECT * FROM {{community_action_programs}}
            WHERE programName = '$programName'")->query()->read();

        //Update program record
        $pid = $program['id'];
        Yii::app()->db->createCommand(
            "UPDATE {{community_action_programs}}
            SET description= '$newProgramDescription'
            WHERE id = $pid")->query();

        //Redirect to manage programs page
        Yii::app()->getController()->redirect(array('/plugins/direct?plugin=Report&function=managePrograms'));
    }

    /**
     * Deletes the requested program and its data. It also de-associates any surveys that were tied to the deleted
     * program.
     */
    private function deleteProgram()
    {
        $programName = $_GET['programName'];

        //Delete program record
        $programModel = $this->api->newModel($this, 'programs');
        $programModel->deleteAll('programName=:PN', array(':PN' => $programName));

        //Delete survey associations with this program
        $enrollmentModel = $this->api->newModel($this, 'program_enrollment');
        $enrollmentModel->deleteAll('programName=:PN', array(':PN' => $programName));

        //Redirect to manage programs page
        Yii::app()->getController()->redirect(array('/plugins/direct?plugin=Report&function=managePrograms'));
    }


    /**************** Utility Functions *****************/

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

}

?>