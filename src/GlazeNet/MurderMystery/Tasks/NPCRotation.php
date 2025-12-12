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

namespace GlazeNet\MurderMystery\Tasks;

use GlazeNet\GlazeCore\Player\Player;
use GlazeNet\MurderMystery\Entity\MurderNPCJoin;

use GlazeNet\MurderMystery\Murder;
use pocketmine\scheduler\Task;
use pocketmine\utils\{Config};

class NPCRotation extends Task {

	public function onRun() : void{
		$level = Murder::getInstance()->getServer()->getWorldManager()->getDefaultWorld();
		foreach ($level->getEntities() as $entity) {
			if ($entity instanceof MurderNPCJoin) {
				$this->sendMovement($entity);
			}
		}
	}

	public function sendMovement($entity) {
		foreach ($entity->getWorld()->getNearbyEntities($entity->getBoundingBox()->expandedCopy(15, 15, 15), $entity) as $player) {
			if($player instanceof Player){

			}
		}
	}
}
