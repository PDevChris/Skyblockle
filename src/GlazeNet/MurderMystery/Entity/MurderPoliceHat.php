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
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Human;

use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;

class MurderPoliceHat extends Human {

	public function getName() : string {
		return "";
	}

	public function onUpdate(int $currentTick) : bool {
		//Scale
		$this->setScale(0.625);
		//Rotate
		$this->yaw += 2.25;
		$this->move($this->motion->x, $this->motion->y, $this->motion->z);
		$this->updateMovement();
		//Effect
		$this->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 999, 255));
		return parent::onUpdate($currentTick);
	}

	public function onCollideWithPlayer(Player $player) : void {
		foreach (Arena::getArenas() as $arena) {
			$arenaName = Arena::getName($arena);
			$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName($arenaName);
			if ($player->getWorld()->getFolderName() == $arenaName) {
				if (Arena::getStatus($arena) == 'ingame') {
					if ($player->getEffects()->has(VanillaEffects::BLINDNESS())) return;
					if (Arena::getRole($player, $arena) === "Inoccent") {
						unset(Murder::$data["players"][$arenaName]["Inoccent"][$player->getName()]);
						Murder::$data["players"][$arenaName]["Detective"][$player->getName()] = "Alive";
						$player->getInventory()->clearAll();
						$player->getArmorInventory()->clearAll();
						$player->getCursorInventory()->clearAll();
						$player->getInventory()->setItem(1, VanillaItems::BOW()->setCustomName("§r§9Detective Bow"));
						$player->getInventory()->setItem(2, VanillaItems::ARROW()->setCount(1));
						$player->sendMessage(
							"§l§9» §bYou have become the Detective" . "\n" .
							"§l§9» §r§fEliminate the murderer using your Bow! §7Be careful, wrongful kills will leave you vulnerable."
						);
						foreach ($arenaLevel->getPlayers() as $players) {
							$players->sendMessage("§l§9» §bA new Detective has arrived...");
						}
						PluginUtils::PlaySound($player, "mob.zombie.unfect");
						$this->flagForDespawn();
					}
				}
			}
		}
	}
}
