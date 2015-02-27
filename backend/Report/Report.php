<?php

class Report extends PluginBase
{

    protected $storage = 'DbStorage';
    static protected $description = 'Community Action reporting tool';
    static protected $name = "Community Action";

    protected $settings = array(
        'programs' => array(
            'type' => 'list',
            'label' => 'Programs',
            'items' => array(
                'name' => array(
                    'type' => 'string',
                    'label' => 'Program Name:',
                ),
            ),
        ),
    );

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
        if (!$this->api->tableExists($this, 'programs')) {
            $this->api->createTable($this, 'programs', array(
                'id' => 'pk',
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
        // Get program to add from GET request
        $program = $_GET['program'];

        // Creates a model of our programs table so that we can add rows.
        $programModel = $this->api->newModel($this, 'programs');

        //Get all programs from table to check against for duplicates
        $results = $programModel ->findAll();
        $programs = CHtml::listData($results, "id", "programName");
        // If program to add is not null add to create model and save to programs table
        if ($program != null && !in_array($program, $programs)) {
            $this->pluginManager->getAPI()->setFlash($program);
            $programModel->programName = $program;
            $programModel->save();

            // Do this again to refresh $programs after adding a program
            $results = $programModel->findAll();
            $programs = CHtml::listData($results, "id", "programName");
        } else {
            // echo "The Program you entered already exists!";
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

        // This feels silly.
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
        // Create program model
        $programModel = $this->api->newModel($this, 'programs');
        // Construct options array before feeding it into this event.
        $results = $programModel->findAll();
        $programs = CHtml::listData($results, "id", "programName");

        // This creates the array of options that we will feed in to the event below.
        $options = array();
        foreach ($programs as $program) {
            // Maybe don't want to store the raw program string like this. It will work for now.
            $options[$program] = $program;
        }

        $event = $this->getEvent();
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'program_enrollment' => array(
                    // Select = Drop down menu
                    'type' => 'select',
                    'options' => $options,
                ),
            ),
        ));
    }

    public function newSurveySettings()
    {
        $event = $this->getEvent();
        foreach ($event->get('settings') as $name => $value) {
            $this->set($name, $value, 'Survey', $event->get('survey'));
        }
    }

}

?>
