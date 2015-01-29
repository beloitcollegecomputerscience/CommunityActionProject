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
	}

	/**
	 * Runs when plugin is activated creates all the necesarry tables to support
	 * Commmunity Action Reporting Plugin
	 **/
	public function beforeActivate() {
		// Create CA_Programs table if not created yet
		if (!$this->api->tableExists($this, 'CA_Programs')) {
			$this->api->createTable($this, 'CA_Programs', array(
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
