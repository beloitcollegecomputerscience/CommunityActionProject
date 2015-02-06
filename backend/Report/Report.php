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
     * TODO: Additional SQL fragments can be passed in as an $options parameter
     *         to createTable() Tie to Survey and program tables here?
     **/
    public function beforeActivate()
    {
        // Display Welcome Message to User
        $this->pluginManager->getAPI()->setFlash('Thank you for Activating the
            Community Action Plugin.');

        if (!$this->api->tableExists($this, 'programs')) {
            $this->api->createTable($this, 'programs', array(
                'id' => 'pk',
                'programName' => 'string'));
        }

        // Create CA_program_enrollement
        if (!$this->api->tableExists($this, 'program_enrollment')) {
            $this->api->createTable($this, 'program_enrollment', array(
                'sid' => 'pk',
                'pid' => 'string'));
        }

        $table = $this->api->getTable($this, 'program_enrollment');

        $responses = $table->findAllAsArray();

        $this->pluginManager->getAPI()->setFlash($responses);

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
        $program = $_GET['program'];
        $this->pluginManager->getAPI()->setFlash($program);
        $programModel = $this->api->newModel($this, 'programs');
        $programModel->programName = $program;
        $programModel->save();
        $content = '
        <form name="addProgram" method="GET"  action="direct">
            <input type="text" name="plugin" value="Report" style="display: none">
            <input type="text" name="function" value="showReports" style="display: none">
            <input type="text" name="program">
            <input type="submit" value="Submit">
        </form>
        <script type=\'text/javascript\'></script>
        ';
        return $content;
    }

    /**
     * Add extra tab for additional settings for a survey. Allows survey
     * creator to associate a survey with a Community Action program.
     **/
    public function beforeSurveySettings()
    {
        $event = $this->getEvent();
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'program_enrollment' => array(
                    // Right now string. Could use label type and pass array of existing programs.
                    'type' => 'string',
                    'default' => 'No Program',
                    'label' => 'Select Program',
                    'current' => $this->get('program', 'Survey', $event->get('survey')),
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
