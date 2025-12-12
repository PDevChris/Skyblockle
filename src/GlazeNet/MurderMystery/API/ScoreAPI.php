<?php

/*
 *
 *      _____ _               _   _      _
 *     / ____| |             | \ | |    | |
 *    | |  __| | __ _ _______|  \| | ___| |_
 *    | | |_ | |/ _` |_  / _ \ . ` |/ _ \ __|
 *    | |__| | | (_| |/ /  __/ |\  |  __/ |_
 *     \_____|_|\__,_/___\___|_| \_|\___|\__|
 *            A minecraft bedrock server.
 *
 *      This project and it’s contents within
 *     are copyrighted and trademarked property
 *   of PrideMC Network. No part of this project or
 *    artwork may be reproduced by any means or in
 *   any form whatsoever without written permission.
 *
 *  Copyright © GlazeNet - All Rights Reserved
 *
 *  www.glazenet.ml               github.com/GlazeNet
 *  twitter.com/GlazeNet         youtube.com/c/GlazeNet
 *  discord.gg/GlazeNet           facebook.com/GlazeNet
 *               bit.ly/JoinInGlazeNet
 *  #GlazeMinigames                           #GlazeNet
 *
 */

declare(strict_types=1);

namespace GlazeNet\MurderMystery\API;

use GlazeNet\MurderMystery\{Murder};

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;

class ScoreAPI {

	/* complement for scoreboards and their instances */
	private static $instance;

	private $scoreboards = [];

	private $plugin;

	public function __construct(Murder $plugin){
		$this->plugin = $plugin;
	}

	public function getInstance() : Scoreboard {
		return self::$instance;
	}

	/* create scoreboards */
	public function new(Player $pl, string $objectiveName, string $displayName) : void {
		if(isset($this->scoreboards[$pl->getName()])){
			$this->remove($pl);
		}
		/* get to packet scoreboard */
		/* and players objetiveName to scoreboard */
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = "sidebar";
		$pk->objectiveName = $objectiveName;
		$pk->displayName = $displayName;
		$pk->criteriaName = "dummy";
		$pk->sortOrder = 0;
		$pl->getNetworkSession()->sendDataPacket($pk);
		$this->scoreboards[$pl->getName()] = $objectiveName;
	}

	public function remove(Player $pl) : void {
		if(isset($this->scoreboards[$pl->getName()])){
			$objectiveName = $this->getObjectiveName($pl);
			/* remove packet, scoreboard */
			$pk = new RemoveObjectivePacket();
			$pk->objectiveName = $objectiveName;
			$pl->getNetworkSession()->sendDataPacket($pk);
			unset($this->scoreboards[$pl->getName()]);
		}
	}

	public function setLine(Player $pl, int $score, string $message) : void {
		if(!isset($this->scoreboards[$pl->getName()])){
			$this->plugin->getLogger()->info("You not have set to scoreboards");
			return;
		}
		if($score > 15 || $score < 1){
			$this->plugin->getLogger()->info("Error, you exceeded the limit of parameters 1-15");
			return;
		}
		$objectiveName = $this->getObjectiveName($pl);
		$entry = new ScorePacketEntry();
		$entry->objectiveName = $objectiveName;
		$entry->type = $entry::TYPE_FAKE_PLAYER;
		$entry->customName = $message;
		$entry->score = $score;
		$entry->scoreboardId = $score;
		$pk = new SetScorePacket();
		$pk->type = $pk::TYPE_CHANGE;
		$pk->entries[] = $entry;
		$pl->getNetworkSession()->sendDataPacket($pk);
	}

	public function getObjectiveName(Player $pl) : ?string {
		return isset($this->scoreboards[$pl->getName()]) ? $this->scoreboards[$pl->getName()] : null;
	}
}
