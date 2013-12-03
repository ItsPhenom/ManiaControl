<?php
use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Plugins\Plugin;

/**
 * Plugin for the TM Game Mode 'Endurance' by TGYoshi
 *
 * @author steeffeen
 */
class EndurancePlugin extends Plugin implements CallbackListener {
	/**
	 * Constants
	 */
	const VERSION = '1.0';
	const CB_CHECKPOINT = 'Endurance.Checkpoint';
	
	/**
	 * Private properties
	 */
	private $currentMap = null;
	private $playerLapTimes = array();

	/**
	 * Create a new endurance plugin instance
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Plugin information
		self::$name = 'Endurance Plugin';
		self::$version = self::VERSION;
		self::$author = 'steeffeen';
		self::$description = "Plugin enabling Support for the TM Game Mode 'Endurance' by TGYoshi.";
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'callback_OnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'callback_BeginMap');
		$this->maniaControl->callbackManager->registerScriptCallbackListener(self::CB_CHECKPOINT, $this, 'callback_Checkpoint');
	}

	/**
	 * Handle ManiaControl OnInit callback
	 *
	 * @param array $callback        	
	 */
	public function callback_OnInit(array $callback) {
		$this->currentMap = $this->maniaControl->mapManager->getCurrentMap();
		$this->playerLapTimes = array();
	}

	/**
	 * Handle BeginMap callback
	 *
	 * @param array $callback        	
	 */
	public function callback_BeginMap(array $callback) {
		$this->currentMap = $this->maniaControl->mapManager->getCurrentMap();
		$this->playerLapTimes = array();
	}

	/**
	 * Handle Endurance Checkpoint callback
	 *
	 * @param array $callback        	
	 */
	public function callback_Checkpoint(array $callback) {
		$callbackData = json_decode($callback[1]);
		if ($callbackData->Checkpoint % $this->currentMap->nbCheckpoints != 0) {
			return;
		}
		$player = $this->maniaControl->playerManager->getPlayer($callbackData->Login);
		if (!$player) {
			return;
		}
		$time = $callbackData->Time;
		if ($time <= 0) {
			return;
		}
		if (isset($this->playerLapTimes[$player->login])) {
			$time -= $this->playerLapTimes[$player->login];
		}
		$this->playerLapTimes[$player->login] = $callbackData->Time;
		// Trigger trackmania player finish callback
		$finishCallback = array($player->pid, $player->login, $time);
		$finishCallback = array(CallbackManager::CB_TM_PLAYERFINISH, $finishCallback);
		$this->maniaControl->callbackManager->triggerCallback(CallbackManager::CB_TM_PLAYERFINISH, $finishCallback);
	}
}

?>
