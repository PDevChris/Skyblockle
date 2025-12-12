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

namespace GlazeNet\MurderMystery\Form;

use GlazeNet\MurderMystery\Arena\Arena;
use GlazeNet\MurderMystery\Code\CodeManager;
use GlazeNet\MurderMystery\Murder;
use GlazeNet\MurderMystery\PluginUtils;

use pocketmine\player\Player;
use pocketmine\Server;
use function count;
use function in_array;

class FormManager {

	public static $target = [];

	public static function sendForm(Player $player, $ui) {
		switch ($ui) {
			case 'GamePanelUI':
				PluginUtils::PlaySound($player, "random.pop2");
				$form = new SimpleForm(function (Player $player, int $data = null) {
					$playerName = $player->getName();
					$result = $data;
					if ($result === null) {
						return true;
					}
					switch ($result) {
						case 0:
							$player->sendMessage("§l§a» §r§7Looking for an available game...");
							Arena::joinArena($player);
						break;

						case 1:
							self::$target[$playerName] = $playerName;
							self::sendForm($player, "ProfileUI");
						break;

						case 2:
							self::sendForm($player, "EnterCode");
						break;
					}
				});
				$form->setTitle("§l§7» §bViltrumite Hunt Game Panel §7«");
				$form->setContent("§7Select an option");
				$form->addButton("§l§9Play\n§r§7Play Viltrumite Hunt", 0, "textures/ui/glaze/icons/gamemodes/murder/icon.png");
				$form->addButton("§l§9Profile\n§r§7Your Profile", 0, "textures/ui/glaze/icons/gamemodes/murder/profile.png");
				$form->addButton("§l§9Enter Code\n§r§7Join a private arena", 0, "textures/ui/glaze/icons/gamemodes/murder/code.png");
				$form->sendToPlayer($player);
			break;

			case 'KitManager':
				    $form = new SimpleForm(function (Player $player, ?int $data = null) {
				        if ($data === null) {
				            return true;
				        }
				        switch ($data) {
				            case 0:
				                $player->sendMessage("§aYou selected the §lSpeed Kit§r§a!");
				                break;
				            case 1:
				                $player->sendMessage("§aYou selected the §lStrength Kit§r§a!");
				                break;
				        }
				    });
				    $form->setTitle("§l§9Kit Manager");
				    $form->setContent("§7Choose your kit for the next game.");
				    $form->addButton("§l§bSpeed Kit\n§r§7Fast movement", 0, "textures/ui/icon_import");
				    $form->addButton("§l§cStrength Kit\n§r§7Extra damage", 0, "textures/ui/icon_best3");
				    $form->sendToPlayer($player);
				break;


			case 'ProfileUI':
				$form = new SimpleForm(function (Player $player, int $data = null) {
					$result = $data;
					if ($result === null) {
						return true;
					}
					switch ($result) {
						case 0:
							// XD
						break;
					}
				});
				$form->setTitle("§e" . self::$target[$player->getName()] . " MurderMystery Stats");
				$form->setContent(
					"§l§aGames Played §r§b" . PluginUtils::getFromStatsDB(self::$target[$player->getName()], "GAMESPLAYED") . "\n" .
					"§l§aVictories §r§b" . PluginUtils::getFromStatsDB(self::$target[$player->getName()], "WINS") . "\n" .
					"§l§cLosses §r§b" . PluginUtils::getFromStatsDB(self::$target[$player->getName()], "LOSSES") . "\n" .
					"§l§cKills §r§b" . PluginUtils::getFromStatsDB(self::$target[$player->getName()], "KILLS") . "\n" .
					"§l§cDeaths §r§b" . PluginUtils::getFromStatsDB(self::$target[$player->getName()], "DEATHS") . "\n" .
					"§l§cEliminations §r§b" . PluginUtils::getFromStatsDB(self::$target[$player->getName()], "MURDERERELIMINATIONS")
				);
				$form->sendToPlayer($player);
				unset(self::$target[$player->getName()]);
			break;

			case 'EnterCode':
				PluginUtils::PlaySound($player, "random.pop2");
				$form = new CustomForm(function (Player $player, array $data = null) {
					if ($data === null) {
						return true;
					}
					if (CodeManager::codeExist($data[1])) {
						$arena = CodeManager::getFromCodesDB($data[1], "arena");
						Arena::joinArena($player, $arena);
					} else {
						PluginUtils::PlaySound($player, "note.bass");
						$player->sendMessage("§c§l» §r§cThis code does not exist..");
					}
				});
				$form->setTitle("§l§7» §bViltrumite-Hunt Enter Code §7«");
				$form->addLabel("§fInsert the Code...");
				$form->addInput("Code:", "Code123");
				$form->sendToPlayer($player);
			break;

			case 'CodesManager':
				$form = new SimpleForm(function (Player $player, int $data = null) {
					$playerName = $player->getName();
					$result = $data;
					if ($result === null) {
						return true;
					}
					switch ($result) {
						case 0:
							self::sendForm($player, "EnterCode");
						break;

						case 1:
							self::sendForm($player, "CreateCode");
						break;

						case 2:
							self::sendForm($player, "CodeList");
						break;

						case 3:
							self::sendForm($player, "CodeList");
						break;
					}
				});
				$form->setTitle("§l§9Private Game");
				$form->setContent("§7Select an option");
				$form->addButton("§l§9Enter Code\n§r§7Join to an a private arena", 1, "textures/ui/glaze/icons/gamemodes/murder/code.png");
				$form->addButton("§l§9Create Server\n§r§7Create a private arena", 1, "textures/ui/glaze/icons/gamemodes/murder/create_code.png");
				$form->addButton("§l§9Code List\n§r§7Show a list of codes", 0, "textures/ui/glaze/icons/gamemodes/murder/list_code.png");
				$form->addButton("§l§9Delete Code\n§r§7Remove a code", 0, "textures/ui/glaze/icons/gamemodes/murder/delete_code.png");
				$form->sendToPlayer($player);
			break;

			case 'CreateCode':
				$form = new CustomForm(function (Player $player, array $data = null) {
					if ($data === null) {
						return true;
					}
					$codeName = $data[2];
					if ($codeName === "random") {
						$codeName = CodeManager::getRandomCode();
					}
					if (CodeManager::codeExist($codeName)) {
						Murder::$data["dataCode"][$player->getName()]["Error"] = "§c§l» §r§c {$codeName} already exist!";
						self::sendForm($player, "CreateCode");
						return true;
					}
					Murder::$data["dataCode"][$player->getName()]["codeName"] = $codeName;
					self::sendForm($player, "CreateCode2");
				});
				$form->setTitle("§l§9Create Viltrumite Hunt Code");
				$form->addLabel("§7If you write random a random code will be generated...");
				if (isset(Murder::$data["dataCode"][$player->getName()]["Error"])) {
					$form->addLabel(Murder::$data["dataCode"][$player->getName()]["Error"]);
					unset(Murder::$data["dataCode"][$player->getName()]["Error"]);
				} else {
					$form->addLabel("§fInsert the Code...");
				}
				$form->addInput("Code:", "Code123");
				$form->sendToPlayer($player);
			break;

			case 'CreateCode2':
				if(!isset(Murder::$data["dataCode"][$player->getName()]["codeName"])) {//It is almost impossible for this error to happen xD
					$player->sendMessage("§c§l» §r§cOops, an unexpected error occurred: Missing code name!");
					return;
				}
				$form = new SimpleForm(function (Player $player, $data = null) {
					$arena = $data;
					if ($arena === null) {
						return true;
					}
					if (CodeManager::getCodeOfArena($arena) !== null) {
						Murder::$data["dataCode"][$player->getName()]["Error"] = "§l§c» §r§cA code has already been created for this arena!";
						self::sendForm($player, "CreateCode2");
						return true;
					}
					if (!in_array($arena, CodeManager::getAvailableArenasToCode(), true)) {
						Murder::$data["dataCode"][$player->getName()]["Error"] = "§l§c» §r§cOops, you've been late and the arena is now in game or is not avaible, to avoid this error it is recommended to disable the arena before creating a code.";
						self::sendForm($player, "CreateCode2");
						return true;
					}
					$player->sendMessage(
						"§aYou have created a new server!" . "\n" . "\n" .
						"§eCode:§b " . Murder::$data["dataCode"][$player->getName()]["codeName"] . "\n" .
						"§eOwner:§b " . $player->getName() . "\n" .
						"§eID:§b " . $arena
					);
					CodeManager::createCode(Murder::$data["dataCode"][$player->getName()]["codeName"], $arena, $player->getName());
					Arena::joinArena($player, $arena);
					unset(Murder::$data["dataCode"][$player->getName()]);
				});
				$form->setTitle("§l§9Create MurderMystery Code");
				if (isset(Murder::$data["dataCode"][$player->getName()]["Error"])) {
					$form->setContent(Murder::$data["dataCode"][$player->getName()]["Error"]);
					unset(Murder::$data["dataCode"][$player->getName()]["Error"]);
				} else {
					$form->setContent(
						"§fSelect a Arena for " . Murder::$data["dataCode"][$player->getName()]["codeName"] . " Code..." . "\n" .
						"§7If you cannot find an specific arena, it is probably that it is in game and you should disable it."
					);
				}
				foreach(CodeManager::getAvailableArenasToCode() as $arena) {
					$form->addButton("§l§9" . $arena . "\n§r§5Map: §b" . Arena::getName($arena), -1, "", $arena);
				}
				$form->sendToPlayer($player);
				return $form;
			break;

			case 'CodeList':
				$form = new SimpleForm(function (Player $player, $data = null) {
					$code = $data;
					if ($code === null) {
						return true;
					}
					self::$target[$player->getName()] = $code;
					self::sendForm($player, "CodeInfo");
				});
				$form->setTitle("§l§9Viltrumite Hunt Codes List");
				$form->setContent("§bCodes (" . count(CodeManager::getCodes()) . "):");
				foreach (CodeManager::getCodes() as $code) {
					$form->addButton("§l§9" . $code . "\n§r§5Arena: §e" . CodeManager::getFromCodesDB($code, "arena"), -1, "", $code);
				}
				$form->sendToPlayer($player);
				return $form;
			break;

			case 'CodeInfo':
				if (self::$target[$player->getName()] === null) {
					$sender->sendMessage("§c§l» §r§cAn unexpected error occurred!");
				}
				$form = new SimpleForm(function (Player $player, $data = null) {
					if ($data === null) {
						return true;
					}
					Server::getInstance()->dispatchCommand($player, 'murder code delete "' . $data . '"');
				});
				$code = self::$target[$player->getName()];
				unset(self::$target[$player->getName()]);
				$arena = CodeManager::getFromCodesDB($code, "arena");
				$form->setTitle("§l§9Viltrumite Hunt Code Information");
				$form->setContent(
					"§eCode: §l§9" . $code . "\n" . "\n" .
					"§r§eOwner: §l§e" . CodeManager::getFromCodesDB($code, "creator") . "\n" .
					"§r§eID: §l§e" . $arena . "\n" .
					"§r§5Map: §l§e" . Arena::getName($arena)
				);
				$form->sendToPlayer($player);
				$form->addButton("§l§cDelete Code\n§r§7Remove the code", 0, "textures/ui/glaze/icons/gamemodes/murder/delete_code.png", $code);
				$form->sendToPlayer($player);
			break;
		}
	}
}


