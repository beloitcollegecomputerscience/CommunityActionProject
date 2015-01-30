<?php
class Report extends PluginBase {

	protected $storage = 'DbStorage';
	static protected $description = 'Community Action reporting tool';
	static protected $name = "Community Action";

	public function __construct(PluginManager $manager, $id) {
		parent::__construct($manager, $id);

		// Register Event-Listners Plug-in needs
		$this->subscribe('beforeActivate');
		$this->subscribe('afterAdminMenuLoad');
		$this->subscribe('newDirectRequest');
		$this->subscribe('newSurveySettings');
	}

	/**
	 * Runs when plugin is activated creates all the necesarry tables to support
	 * Commmunity Action Reporting Plugin
	 * TODO: Additional SQL fragments can be passed in as an $options parameter
	 *  		 to createTable() Tie to Survey and program tables here?
	 **/
	public function beforeActivate() {
		// Create CA_Programs table if not created yaml_emit(data)
		if (!$this->api->tableExists($this, 'programs')) {
			$this->api->createTable($this, 'programs', array(
				'id' => 'pk',
				'programName' => 'string'));
		}

		if (!$this->api->tableExists($this, 'program_enrollement')) {
			$this->api->createTable($this, 'program_enrollement', array(
				'sid' => 'pk',
				'pid' => 'string'));
		}

		// Display Welcome Message to User
		$this->pluginManager->getAPI()->setFlash('Thank you for Activating the
            Community Action Plugin.');
	}

	/**
	 * Runs after Admin Menu Loads. Used to display New Icon that will link to
	 * the Community Action Data report page.
	 **/
	public function afterAdminMenuLoad() {
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
	public function newDirectRequest() {
		$event = $this->event;
		$request = $event->get('request');
		//get the function param and then you can call that method
		$functionToCall = $event->get('function');
		$content = call_user_func(array($this, $functionToCall));
		$event->setContent($this, $content);
	}

	/**
	 * Defines the content on the report page
	 **/
	function showReports() {
		$content = "<h1> Here is where we will show the results!! </h1>";
		return $content;
	}

	/**
	 * On survey creation this should eventually save SID with PID
	 **/
	public function newSurveySettings() {
		// $event = $this->getEvent();
		// foreach ($event->get('settings') as $name => $value) {
		// 	$this->set($name, $value, 'Survey', $event->get('survey'));
		// }
	}

}
?>
