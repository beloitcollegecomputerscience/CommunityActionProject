<?php

class Report extends PluginBase
{
    protected $storage = 'DbStorage';
    static protected $description = 'Community Action reporting tool';
    static protected $name = "Community Action";

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
        }

        //Create Program Enrollment
        if (!$this->api->tableExists($this, 'program_enrollment')) {
            $this->api->createTable($this, 'program_enrollment', array(
                'survey_id' => 'int',
                'programName' => 'string'));
        }

        //Insert Default program check first to see if its has already been added
        $programModel = $this->api->newModel($this, 'programs');
        //Get all programs from table to check against for duplicates
        $results = $programModel->findAll();
        $programs = CHtml::listData($results, "id", "programName");
        if (!in_array("Select a Program...", $programs)) {
            $programModel->programName = "Select a Program...";
            $programModel->save();
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
        $event = $this->event;
        $menu = $event->get('menu', array());
        $menu['items']['left'][] = array(
            'href' => "plugins/direct?plugin=Report&function=showReports",
            'alt' => gT('CA Report'),
            'image' => 'chart_bar.png',
        );

        $event->set('menu', $menu);
    }

    /**
     * Handles and parses out function calls from Url's
     **/
    public function newDirectRequest()
    {
        $event = $this->event;
        //get the function param and then you can call that method
        $functionToCall = $event->get('function');
        $content = call_user_func(array($this, $functionToCall));
        $event->setContent($this, $content);
    }

    /**
     * Defines the content on the report page
     **/
    function showReports()
    {
        // Get program to add from form post
        $program = $_GET['program'];

        //Get all programs from table to check against for duplicates
        $programModel = $this->api->newModel($this, 'programs');
        $results = $programModel->findAll();
        $programs = CHtml::listData($results, "id", "programName");

        // If program to add is not null or already added save to programs table
        if ($program != null && !in_array($program, $programs)) {
            $this->pluginManager->getAPI()->setFlash($program);
            $programModel->programName = $program;
            $programModel->save();

            // Do this again to refresh $programs after adding a program
            $results = $programModel->findAll();
            $programs = CHtml::listData($results, "id", "programName");
        } else {
            // Let user know cant add duplicate programs
            $this->pluginManager->getAPI()->setFlash('That program already exists.');
        }

        // Throw together a bunch of li elements to represent the programs
        $list = "";
        foreach ($programs as $program) {
            $list = $list . "<li>$program</li>";
        }

        // Add hidden form fields to add params to get request and capture inputted program name
        $form = <<<HTML
<h5>Add a Program:</h5>
<form name="addProgram" method="GET" action="direct">
<input type="text" name="plugin" value="Report" style="display: none">
<input type="text" name="function" value="showReports" style="display: none">
<input type="text" name="program">
<input type="submit" value="Submit">
</form>
<h5>Programs:</h5>
HTML;

        // Set $content
        $content = $form . "<ul>" . $list . "</ul>";
        //$content is what is rendered to page
        return $content;
    }

    /**
     * Add extra tab for additional settings for a survey. Allows survey
     * creator to associate a survey with a Community Action program.
     **/
    public function beforeSurveySettings()
    {
        // Get this settings ID
        $event = $this->getEvent();
        $survey_id = $event->get('survey');

        // Grab all entry's from program table to populate drop down with
        $programModel = $this->api->newModel($this, 'programs');
        $results = $programModel->findAll();
        $programs = CHtml::listData($results, "id", "programName");
        // This creates the array of options that we will feed in to the event below.
        $options = array();
        foreach ($programs as $program) {
            $options[$program] = $program;
        }

        //Check for if this survey is already associated with a program if not set to default value
        $programEnrollment = $this->api->newModel($this, 'program_enrollment');
        $results = $programEnrollment->findAll('survey_id=:sid', array(':sid' => $survey_id));
        $program = CHtml::listData($results, "survey_id", "programName");
        //If survey is associated set drop down menus current value to that program
        if ($results == null) {
            $current = 'Select a program...';
        } else {
            $current = $program;
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
                $enrollmentModel = $this->api->newModel($this, 'program_enrollment');
                $enrollmentModel->survey_id = $event->get('survey');
                $enrollmentModel->programName = $value;
                $enrollmentModel->save();
            } else {
                //Everything else let save where lime survey wants it to be
                $this->set($name, $value, 'Survey', $event->get('survey'));
            }
        }
    }
}

?>
