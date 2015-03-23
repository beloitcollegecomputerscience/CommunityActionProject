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
        //TODO For now just test
        print_r('<pre>');
        $query = "SELECT
              q.sid, q.gid, q.qid
              FROM questions q
              INNER JOIN groups g ON g.gid = q.gid
              WHERE g.group_name = 'Community Action\'s Core Questions 03/04/2015'
              AND q.sid IN (SELECT
                survey_id
                FROM community_action_program_enrollment pge
                INNER JOIN community_action_programs pg ON pge.programName = pg.programName
                WHERE pg.programName ='Housing Program')";

        //get data
        $results = Yii::app()->db->createCommand($query)->query();

        foreach ($results->readAll() as $row) {
            print_r($row);
        }
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
