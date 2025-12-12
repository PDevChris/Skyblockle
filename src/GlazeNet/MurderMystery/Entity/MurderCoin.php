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

namespace GlazeNet\MurderMystery\Entity;

use GlazeNet\MurderMystery\Arena\Arena;
use GlazeNet\MurderMystery\Murder;
use GlazeNet\MurderMystery\PluginUtils;

use pocketmine\entity\Human;
use pocketmine\player\Player;
use pocketmine\Server;
use function array_push;
use function time;

class MurderCoin extends Human {

	public function getName() : string {
		return "";
	}

	public function onUpdate(int $currentTick) : bool {
		//Scale
		$this->setScale(0.5);
		//Rotate
		$this->getLocation()->yaw += 3.2;
		$this->move($this->motion->x, $this->motion->y, $this->motion->z);
		$this->updateMovement();
		return parent::onUpdate($currentTick);
	}

	public function onCollideWithPlayer(Player $player) : void {
		foreach (Arena::getArenas() as $arena) {
			$arenaName = Arena::getName($arena);
			$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName($arenaName);
			if ($player->getWorld()->getFolderName() == $arenaName) {
				if (Arena::getStatus($arena) == 'ingame') {
					if (Arena::getRole($player, $arena) != null) {
						Murder::$data["coinDelay"][$arenaName][$this->skin->getSkinId()] = time() + 30;
						if(!isset(Murder::$data["coins"][$arenaName][$player->getName()])){
							array_push(Murder::$data["coins"][$arenaName], $player->getName());
							Murder::$data["coins"][$arenaName][$player->getName()] = 1;
							PluginUtils::PlaySound($player, "item.trident.return", 1, 1.4);
							$this->flagForDespawn();
							return;
						}
						Murder::$data["coins"][$arenaName][$player->getName()] += 1;
						PluginUtils::PlaySound($player, "item.trident.return", 1, 1.4);
						$this->flagForDespawn();
						if (Murder::$data["coins"][$arenaName][$player->getName()] == 10) {
							if (Arena::getRole($player, $arena) != "Inoccent") return;
							$player->sendMessage("§l§a» §bYou have received a one shot bow! §cUse wisely or it could cost you your life");
							$player->getInventory()->clearAll();
							$player->getArmorInventory()->clearAll();
							$player->getCursorInventory()->clearAll();
							$player->getInventory()->setItem(1, VanillaItems::BOW()->setCustomName("§r§6Inoccent Bow"));
							$player->getInventory()->setItem(2, VanillaItems::ARROW());
						}
					}
				}
			}
		}
	}
}
