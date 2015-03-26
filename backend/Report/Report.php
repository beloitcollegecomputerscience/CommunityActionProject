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
                'href' => "plugins/direct?plugin=Report&function=managePrograms",
                'alt' => gT('CA Report'),
                'image' => 'chart_bar.png',
            );

            $event->set('menu', $menu);
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

    /**
     * @param $programNames
     */
    function generateReport($programNames)
    {
        //TODO In future we will put code below inside loop to execute for each program we want a report on
//        $numberOfPrograms = count($programNames);
//        for ($i = 0; $i < $numberOfPrograms; $i++) {
//            //Get sid, pid's and gid's associated with each program
//        }
        print_r('<pre>');

        //TODO give question group name a better name
        $query = "SELECT
              q.sid, q.gid, q.qid, q.question
              FROM questions q
              INNER JOIN groups g ON g.gid = q.gid
              WHERE g.group_name = 'Community Action\'s Core Questions 03/04/2015'
              AND q.sid IN (SELECT
                survey_id
                FROM community_action_program_enrollment pge
                INNER JOIN community_action_programs pg ON pge.programName = pg.programName
                WHERE pg.programName ='Housing Program')";

        //get all questions associated with current program
        $results = Yii::app()->db->createCommand($query)->query();
        $returnReportData = [];
        $surveyData = [];

        // Loop through all returned questions and organize by their related surveys
        $currentSurvey = '';
        foreach ($results->readAll() as $row) {
            $questionString = $row['sid'] . 'X' . $row['gid'] . 'X' . $row['qid'];
            $responsesQuery = "SELECT  ". $questionString ."  AS AnswerValue, COUNT(*) AS `Count` FROM lime_survey.survey_" . $row['sid'] . " GROUP BY " . $questionString;
            //TODO add ability to filter on date ranges here
            $responsesResults = Yii::app()->db->createCommand($responsesQuery)->query();

            //check for if onto a new survey
            if ($currentSurvey != $row['sid']) {
                //Only add previous survey if not initial pass
                if ($currentSurvey != '') {
                    array_push($returnReportData, $surveyData);
                    $surveyData = []; // Reset survey array for new survey's questions
                }
                $currentSurvey = $row['sid'];
                $surveyData['title'] = $row['sid']; //TODO Could query for survey title to show instead of ID

            }

            //This holds general information about each question
            $questionData = [];
            $questionData['title'] = flattenText($row['question']);
            //Holds current questions data in a graph-able format
            $graphData = [];

            //Push graph header data first for each question
            array_push($graphData, array('Answer', 'Count', array('role' => 'style')));

            //Get all possible answers for current question
            $answersQuery = " SELECT `code` AS AnswerValue, answer AS AnswerText
                              FROM lime_survey.answers
                              WHERE qid = " . $row['qid'];
            $answersResults = Yii::app()->db->createCommand($answersQuery)->query();
            //Read first result
            $currentAnswer = $answersResults->read();

            //Loop through all returned user results
            foreach ($responsesResults->readAll() as $responseRow) {
                //TODO Probably use answer short code as graoh labels and have legend in future text to long
                while ($responseRow['AnswerValue'] != $currentAnswer['AnswerValue']) {
                    //Fill Data Holes until next answer has value
                    array_push($graphData, [$currentAnswer['AnswerText'], 0, 'red']);
                    $currentAnswer = $answersResults->read();
                }

                //Push valid
                array_push($graphData, [$currentAnswer['AnswerText'], $responseRow['Count'], 'red']);
                // Move to next answer result
                $currentAnswer = $answersResults->read();
            }
            //Fill trailing data holes
            if($currentAnswer){
                array_push($graphData, [$currentAnswer['AnswerText'], 0, 'red']);
                $currentAnswer = $answersResults->read();
                while($currentAnswer){
                    array_push($graphData, [$currentAnswer['AnswerText'], 0, 'red']);
                    $currentAnswer = $answersResults->read();
                }
            }
            //Update question and survey data arrays
            $questionData['graphData'] = $graphData;
            array_push($surveyData, $questionData);
        }

        //Push final question to return array
        array_push($returnReportData, $surveyData);

        // Final formatted data TODO Do some thing with this
        print_r($returnReportData);
        print_r(json_encode($returnReportData));
    }

    /**
     * Defines the content on the report page
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

        // Build up UI representing the programs
        $list = "";
        foreach ($existingPrograms as $programToAdd) {
            if ($programToAdd != $this->defaultProgram) {
                $list = $list . "$programToAdd<br/>";
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
        $content = '<div class="container"style="margin-bottom: 20px">' . $form . '<h5>Programs:</h5>' . $list . '</div>';
        return $content;
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

    /**---Helper Functions---**/

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
