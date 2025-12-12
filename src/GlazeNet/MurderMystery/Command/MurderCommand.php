<?php

namespace GlazeNet\MurderMystery\Command;

use GlazeNet\MurderMystery\Arena\Arena;
use GlazeNet\MurderMystery\Code\CodeManager;
use GlazeNet\MurderMystery\Entity\EntityManager;
use GlazeNet\MurderMystery\Entity\MurderLeadboard;
use GlazeNet\MurderMystery\Entity\MurderNPCJoin;
use GlazeNet\MurderMystery\Form\FormManager;
use GlazeNet\MurderMystery\Murder;
use GlazeNet\MurderMystery\PluginUtils;
use GlazeNet\MurderMystery\ResetMap;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use function count;
use function file_exists;
use function getWorldManager;
use function in_array;
use function intval;
use function is_numeric;

class MurderCommand extends Command {

	/**
	 * MurderCommand Constructor
	 */
	public function __construct(Murder $plugin) {
		parent::__construct("vh', "", "/vh <option:args>");
		$this->setDescription('Viltrumite Hunt Command');
		$this->setPermission('wc.viltrumite.command');
	}

	public function execute(CommandSender $sender, string $label, array $args) {

		if(!$sender instanceof Player) {
			$sender->sendMessage(Murder::getPrefix() . "§cYou can only use this at the game!");
			return;
		}

		if (isset($args[0])) {
			switch ($args[0]) {
				case 'create':
					if (!$sender->getServer()->isOp($sender->getName())) {
						$sender->sendMessage(Murder::getPrefix() . "§cYou do not have permissions to use this command!");
						return true;
					}
					if (!$sender instanceof Player) {
						$sender->sendMessage(Murder::getPrefix() . "§cYou can only use this command in the game!");
						return true;
					}
					if (!isset($args[1], $args[2], $args[3])) {
						$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh create <arena> <maxslots> <id>");
						return true;
					}
					if (!file_exists(Server::getInstance()->getDataPath() . 'worlds/' . $args[1])) {
						$sender->sendMessage(Murder::getPrefix() . "§cThe {$args[1]} world does not exist!");
						return true;
					}
					if ($args[2] <= 1) {
						$sender->sendMessage(Murder::getPrefix() . "§cMax Slots must be a valid number!");
						return true;
					}
					if (Arena::ArenaExisting($args[3])) {
						$sender->sendMessage(Murder::getPrefix() . "§cThe {$args[3]} arena id already exist!");
					} else {
						Arena::addArena($sender, $args[1], $args[2], $args[3]);
					}
				break;

				case 'arena':
					if (isset($args[2])) {
						if (!Arena::ArenaExisting($args[1])) {
							$sender->sendMessage(Murder::getPrefix() . "§cArena Viltrumite-" . $args[1] . " does not exist!");
							return true;
						}
						$arena = "Murder-{$args[1]}";
						$arenaName = Arena::getName($arena);
						$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName($arenaName);
						switch ($args[2]) {
							case 'disable':
								if (Arena::getStatus($arena) != "disabled") {
									Arena::setStatus($arena, 'disabled');
									if (Server::getInstance()->getWorldManager()->isWorldLoaded($arenaName)) {
										foreach ($arenaLevel->getPlayers() as $players) {
											$players->sendMessage("§l§c» §r§bArena has been disabled, connecting to the lobby...");
											$players->setNameTagAlwaysVisible();
											$players->setGamemode(GameMode::ADVENTURE());
											$players->getInventory()->clearAll();
											$players->getArmorInventory()->clearAll();
											$players->getCursorInventory()->clearAll();
											$players->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
										}
										ResetMap::resetZip($arenaName);
									}
									$sender->sendMessage(Murder::getPrefix() . "§aArena §e" . $arena . " §ahas been disabled successfully!");
								} else {
									$sender->sendMessage(Murder::getPrefix() . "§cArena " . $arena . " is already disabled!");
								}
							break;

							case 'enable':
								if (Arena::getStatus($arena) == "disabled") {
									$sender->sendMessage("§eReseting arena...");
									if (Server::getInstance()->getWorldManager()->isWorldLoaded($arenaName)) {
										foreach ($arenaLevel->getPlayers() as $players) {
											$players->setNameTagAlwaysVisible();
											$players->setGamemode(GameMode::ADVENTURE());
											$players->getInventory()->clearAll();
											$players->getArmorInventory()->clearAll();
											$players->getCursorInventory()->clearAll();
											$players->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
										}
									}
									Murder::getReloadArena($arena);
									ResetMap::resetZip($arenaName);
									$sender->sendMessage(Murder::getPrefix() . "§aArena §e" . $arena . " §ahas been enabled successfully!");
								} else {
									$sender->sendMessage(Murder::getPrefix() . "§cArena " . $arena . " is already enabled!");
								}
							break;

							case 'edit':
								if(!isset($args[3])){
									$sender->sendMessage(
										"§aUse: /vh arena <arena> edit setlobby §7» Setlobby world from an Arena." . "\n" .
										"§aUse: /vh arena <arena> edit setspawn §7» Set spawn world from an Arena." . "\n" .
										"§aUse: /vh arena <arena> edit setspawneye §7» Set spawn ender eye from an Arena." . "\n" .
										"§aUse: /vh arena <arena> edit lobbytime <int: seconds> §7» Set lobby time from an Arena." . "\n" .
										"§aUse: /vh arena <arena> edit gametime <int: seconds> §7» Set game time from an Arena."
									);
									return;
								}
								switch($args[3]){
									case "setlobby":
									Murder::$data["arenaconfigs"][$args[1]]["lobby"]["world"] = $sender->getPosition()->getWorld()->getFolderName();
									Murder::$data["arenaconfigs"][$args[1]]["lobby"]["x"] = intval($sender->getPosition()->getX());
									Murder::$data["arenaconfigs"][$args[1]]["lobby"]["y"] = intval($sender->getPosition()->getY());
									Murder::$data["arenaconfigs"][$args[1]]["lobby"]["z"] = intval($sender->getPosition()->getZ());

									Arena::saveArenaConfigs($args[1]);
									$sender->sendMessage(Murder::getPrefix() . "§aSuccessfully set lobby to this arena.");
							break;
						case "setspawn":
						if($sender->getWorld()->getFolderName() == $arenaName){
							if(!isset(Murder::$data["arenaconfigs"][$args[1]]["spawns"])) Murder::$data["arenaconfigs"][$args[1]]["spawns"] = [];
							Murder::$data["arenaconfigs"][$args[1]]["spawns"][] = ["x" => intval($sender->getPosition()->getX()), "y" => intval($sender->getPosition()->getY()), "z" => intval($sender->getPosition()->getZ())];
							Arena::saveArenaConfigs($args[1]);
							$sender->sendMessage(Murder::getPrefix() . "§aSuccessfully set spawn to this arena.");
						} else {
								$sender->sendMessage(Murder::getPrefix() . "§cYou cannot add spawns in other worlds!");
							}
							break;
						case "setspawneye":
						if($sender->getWorld()->getFolderName() == $arenaName){
							if(!isset(Murder::$data["arenaconfigs"][$args[1]]["coinspawns"])) Murder::$data["arenaconfigs"][$args[1]]["coinspawns"] = [];
							Murder::$data["arenaconfigs"][$args[1]]["coinspawns"][] = ["x" => intval($sender->getPosition()->getX()), "y" => intval($sender->getPosition()->getY()), "z" => intval($sender->getPosition()->getZ())];
							Arena::saveArenaConfigs($args[1]);
							$sender->sendMessage(Murder::getPrefix() . "§aSuccessfully set spawn ender eye to this arena.");
						} else {
							$sender->sendMessage(Murder::getPrefix() . "§cYou cannot add spawns in other worlds!");
						}
							break;
						case "lobbytime":
						if(!isset($args[4])){
							$sender->sendMessage(Murder::getPrefix() . "§cYou must specify time in seconds!");
							return;
						}
							Murder::$data["arenaconfigs"][$args[1]]["lobbytime"] = $args[4];
							Arena::saveArenaConfigs($args[1]);
							$sender->sendMessage(Murder::getPrefix() . "§aSuccessfully set lobby time to {$args[4]} seconds!");
							break;
						case "gametime":
						if(!isset($args[4])){
							$sender->sendMessage(Murder::getPrefix() . "§cYou must specify time in seconds!");
							return;
						}
							Murder::$data["arenaconfigs"][$args[1]]["gametime"] = $args[4];
							Arena::saveArenaConfigs($args[1]);
							$sender->sendMessage(Murder::getPrefix() . "§aSuccessfully set lobby time to {$args[4]} seconds!");
							break;
							default:
								$sender->sendMessage(
										"§aUse: /vh arena <arena> edit setlobby §7» Setlobby world from an Arena." . "\n" .
										"§aUse: /vh arena <arena> edit setspawn §7» Set spawn world from an Arena." . "\n" .
										"§aUse: /vh arena <arena> edit setspawneye §7» Set spawn ender eye from an Arena." . "\n" .
										"§aUse: /vh arena <arena> edit lobbytime <int: seconds> §7» Set lobby time from an Arena." . "\n" .
										"§aUse: /vh arena <arena> edit gametime <int: seconds> §7» Set game time from an Arena."
									);
								break;
								}
							break;

							case 'save':
								if (Arena::getStatus($arena) == "disabled") {
									if($sender->getWorld()->getFolderName() == $arenaName){
										$sender->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
									}
									if (Server::getInstance()->getWorldManager()->isWorldLoaded($arenaName)) {
										Server::getInstance()->getWorldManager()->unloadWorld(Server::getInstance()->getWorldManager()->getWorldByName($arenaName));
									}
									$sender->sendMessage(Murder::getPrefix() . "§eSaving arena world " . $arena . " ...");
									PluginUtils::setZip($arenaName);
									Murder::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($sender, $arenaName) : void{
										Server::getInstance()->getWorldManager()->loadWorld($arenaName);
										Server::getInstance()->getWorldManager()->getWorldByName($arenaName)->loadChunk(Server::getInstance()->getWorldManager()->getWorldByName($arenaName)->getSafeSpawn()->getFloorX(), Server::getInstance()->getWorldManager()->getWorldByName($arenaName)->getSafeSpawn()->getFloorZ());
										$sender->sendMessage(Murder::getPrefix() . "§aArena world " . $arenaName . " has been saved successfully!");
									}), 40);
								} else {
									$sender->sendMessage(Murder::getPrefix() . "§cArena must be disabled to be saved!");
								}
							break;
							default:
								$sender->sendMessage(
									"§aUse: /vh arena <arena> disable §7» Disable an Arena." . "\n" .
									"§aUse: /vh arena <arena> enable §7» Enable an Arena." . "\n" .
									"§aUse: /vh arena <arena> edit §7» Edit arena config." . "\n" .
									"§aUse: /vh arena <arena> save §7» Save a world from an Arena." .
									"§aUse: /vh arena <arena> edit §7» Edit world from an Arena."
								);
							break;
						}
					} else {
						$sender->sendMessage(
							"§aUse: /vh arena <arena> disable §7» Disable an Arena." . "\n" .
							"§aUse: /vh arena <arena> enable §7» Enable an Arena." . "\n" .
							"§aUse: /vh arena <arena> edit §7» Edit arena config." . "\n" .
							"§aUse: /vh arena <arena> edit §7» Save a world from an Arena."
						);
					}
				break;

				case 'npc':
				case 'slapper':
					if (!$sender->getServer()->isOp($sender->getName())) {
						$sender->sendMessage(Murder::getPrefix() . "§cYou do not have permissions to use this command!");
						return true;
					}
					if (!$sender instanceof Player) {
						$sender->sendMessage(Murder::getPrefix() . "§cYou can only use this command in the game!");
						return true;
					}
					if (empty($args[1])) {
						$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh npc|slapper <join|game, stats|leadboard, remove>");
						return true;
					}
					switch ($args[1]) {
						case 'join':
						case 'game':
							EntityManager::setNPCJoin($sender);
							$sender->sendMessage(Murder::getPrefix() . "§aYou have successfully spawned NPC to Join the game!");
						break;

						case 'leadboard':
						case 'stats':
							EntityManager::setNPCLeadboard($sender);
							$sender->sendMessage(Murder::getPrefix() . "§aYou have successfully spawned Murder Leadboard!");
						break;

						case 'remove':
							if (empty($args[2])) {
								$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh npc|slapper remove <join|game, stats|leadboard>");
								return true;
							}
							switch ($args[2]) {
								case 'join':
								case 'game':
									foreach ($sender->getWorld()->getEntities() as $entity) {
										if ($entity instanceof MurderNPCJoin) {
											$entity->kill();
										}
									}
								break;

								case 'stats':
								case 'leadboard':
									foreach ($sender->getWorld()->getEntities() as $entity) {
										if ($entity instanceof MurderLeadboard) {
											$entity->kill();
										}
									}
								break;

								default:
									$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh npc|slapper remove <join|game, stats|leadboard>");
								break;
							}
						break;

						default:
							$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh npc|slapper <join|game, stats|leadboard, remove>");
						break;
					}
				break;

				case 'code':
					if (isset($args[1])) {
						switch ($args[1]) {
							case 'manage':
								if (!$sender->getServer()->isOp($sender->getName())) {
									$sender->sendMessage(Murder::getPrefix() . "§cYou do not have permissions to use this command!");
									return true;
								}
								if ($sender instanceof Player) {
									FormManager::sendForm($sender, "CodesManager");
								} else {
									$sender->sendMessage("§cYou can only use this command in the game!");
								}
							break;

							case 'create':
								if (!$sender->getServer()->isOp($sender->getName())) {
									$sender->sendMessage(Murder::getPrefix() . "§cYou do not have permissions to use this command!");
									return true;
								}
								if (isset($args[3])) {
									$codeName = $args[2];
									if ($codeName === "random") {
										$codeName = CodeManager::getRandomCode();
									}
									if (CodeManager::codeExist($codeName)) {
										$sender->sendMessage("§c§l» §r§c {$codeName} already exist!");
										return true;
									}
									$arena = "Murder-{$args[3]}";
									if (!Arena::ArenaExisting($args[3])) {
										$sender->sendMessage(Murder::getPrefix() . "§cArena " . $arena . " does not exist!");
										return true;
									}
									if (CodeManager::getCodeOfArena($arena) != null) {
										$sender->sendMessage("§l§c» §r§cA code has already been created for this arena!");
										return true;
									}
									if (!in_array($arena, CodeManager::getAvailableArenasToCode(), true)) {
										$sender->sendMessage("§l§c» §r§cOops, you've been late and the arena is now in game or is not avaible, to avoid this error it is recommended to disable the arena before creating a code.");
										return true;
									}
									CodeManager::createCode($codeName, $arena, $sender->getName());
									$sender->sendMessage(
										"§aYou have created a new Custom Server!" . "\n" . "\n" .
										"§eCode:§b " . $codeName . "\n" .
										"§eOwner:§b " . $sender->getName() . "\n" .
										"§eID:§b " . $arena
									);
								} else {
									if ($sender instanceof Player) {
										FormManager::sendForm($sender, "CreateCode");
									} else {
										$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh code create <code> <arena>");
									}
								}
							break;

							case "delete":
								if (!$sender->getServer()->isOp($sender->getName())) {
									$sender->sendMessage(Murder::getPrefix() . "§cYou do not have permissions to use this command!");
									return true;
								}
								if (count($args) != 3) {
									$sender->sendMessage(Murder::getPrefix() . "§cYou must specify a code!");
									return true;
								}
								$code = $args[2];
								if (CodeManager::codeExist($code)) {
									$arena = CodeManager::getFromCodesDB($code, "arena");
									$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena));
									if (Arena::getStatus($arena) === "waitingcode") {
										Arena::setStatus($arena, "waiting");
									}
									Murder::getReloadArena($arena);
									ResetMap::resetZip(Arena::getName($arena));
									foreach ($arenaLevel->getPlayers() as $players) {
										$players->sendMessage("§l§c» §r§bArena code has been deleted, connecting to the lobby...");
										$players->setNameTagAlwaysVisible();
										$players->setGamemode(2);
										$players->getInventory()->clearAll();
										$players->getArmorInventory()->clearAll();
										$players->getCursorInventory()->clearAll();
										$players->teleport(Server::getInstance() - getWorldManager()->getDefaultWorld()->getSafeSpawn());
									}
									CodeManager::removeCodeFromDB($code);
									$sender->sendMessage("§l§9» §r§aYou have successfully deleted the code §e" . $code . "§a!");
								} else {
									$sender->sendMessage(Murder::getPrefix() . "§cThe code " . $code . " does not exist!");
								}
							break;

							case "list":
								if (!$sender->getServer()->isOp($sender->getName())) {
									$sender->sendMessage(Murder::getPrefix() . "§cYou do not have permissions to use this command!");
									return true;
								}
								if (!$sender instanceof Player) {
									$sender->sendMessage("§bCodes (" . count(CodeManager::getCodes()) . "):");
									foreach (CodeManager::getCodes() as $code) {
										$sender->sendMessage("§a" . $code . " §b> §5ID: §e" . CodeManager::getFromCodesDB($code, "arena") . "§b, §5Owner: §e" . CodeManager::getFromCodesDB($code, "creator"));
									}
								} else {
									FormManager::sendForm($sender, "CodesList");
								}
							break;

							case 'enter':
								if (!$sender instanceof Player) {
									$sender->sendMessage(Murder::getPrefix() . "§cYou can only use this command in the game!");
									return true;
								}
								if (count($args) == 3) {
									if (CodeManager::codeExist($args[2])) {
										$arena = CodeManager::getFromCodesDB($args[2], "arena");
										Arena::joinArena($sender, $arena);
									} else {
										PluginUtils::PlaySound($sender, "mob.blaze.shoot");
										$sender->sendMessage("§c§l» §r§cThis code does not exist");
									}
								} else {
									FormManager::sendForm($sender, "EnterCode");
								}
							break;
						}
					} else {
						if ($sender->getServer()->isOp($sender->getName())) {
							$sender->sendMessage(
								"§eUse: §a/vh code manage §7» Open Codes Manager UI." . "\n" .
								"§eUse: §a/vh code create §7» Create a code." . "\n" .
								"§eUse: §a/vh code delete §7» Delete a code." . "\n" .
								"§eUse: §a/vh code list §7» Show code list." . "\n" . "\n" .
								"§eUse: §a/vh code enter §7» Write a code to enter to a private arena."
							);
						} else {
							$sender->sendMessage("§eUse: §a/vh code enter §7» Write a code to enter to a private arena.");
						}
					}
				break;

				case 'db':
					if (!$sender->getServer()->isOp($sender->getName())) {
						$sender->sendMessage(Murder::getPrefix() . "§cYou do not have permissions to use this command!");
						return true;
					}
					if (isset($args[1])) {
						switch ($args[1]) {
						   case 'register':
								if (!isset($args[2])) {
									$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db register <player>");
									return true;
								}
								if (PluginUtils::verifyPlayerInDB($args[2])) {
									$sender->sendMessage(Murder::getPrefix() . "§c{$args[2]} is already in the database!");
									return true;
								}
								$sender->sendMessage(Murder::getPrefix() . "§aRegistering {$args[2]} into the database...");
								PluginUtils::addNewPLayer($args[2]);
							break;

							case 'delete':
								if (!isset($args[2])) {
									$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db delete <player>");
									return true;
								}
								if (!PluginUtils::verifyPlayerInDB($args[2])) {
									$sender->sendMessage(Murder::getPrefix() . "§c{$args[2]} is not in the database!");
									return true;
								}
								$sender->sendMessage(Murder::getPrefix() . "§cDeleting§b {$args[2]} from the database...");
								PluginUtils::deletePlayerFromDB($args[2]);
							break;

							case 'add':
								if (!isset($args[4])) {
									$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db add <player> <wins|losses|kills> <amount>");
									return true;
								}
								if (!PluginUtils::verifyPlayerInDB($args[2])) {
									$sender->sendMessage(Murder::getPrefix() . "§c{$args[2]} is not in the database!");
									return true;
								}
								if (is_numeric($args[4]) || $args[4] < 0) {
									$sender->sendMessage(Murder::getPrefix() . "§cAmount must be a valid number!");
									return true;
								}
								switch ($args[3]) {
									case 'wins':
										$value = "WINS";
										PluginUtils::ModifyStats($args[2], $value, $args[1], $args[4]);
										$sender->sendMessage(Murder::getPrefix() . "§bYou have successfully §aadded§e $args[4] §bwins to§e {$args[2]}§b!");
									break;

									case 'losses':
										$value = "LOSSES";
										PluginUtils::ModifyStats($args[2], $value, $args[1], $args[4]);
										$sender->sendMessage(Murder::getPrefix() . "§bYou have successfully §aadded§e $args[4] §blosses to§e {$args[2]}§b!");
									break;

									case 'kills':
										$value = "KILLS";
										PluginUtils::ModifyStats($args[2], $value, $args[1], $args[4]);
										$sender->sendMessage(Murder::getPrefix() . "§bYou have successfully §aadded§e $args[4] §bkills to§e {$args[2]}§b!");
									break;

									default:
										$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db add <wins|losses|kills> <amount>");
									break;
								}
							break;

							case 'remove':
								if (!isset($args[4])) {
									$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db remove <player> <wins|losses|kills> <amount>");
									return true;
								}
								if (!PluginUtils::verifyPlayerInDB($args[2])) {
									$sender->sendMessage(Murder::getPrefix() . "§c{$args[2]} is not in the database!");
									return true;
								}
								if (is_numeric($args[4]) || $args[4] < 0) {
									$sender->sendMessage(Murder::getPrefix() . "§cAmount must be a valid number!");
									return true;
								}
								switch ($args[3]) {
									case 'wins':
										$value = "WINS";
										PluginUtils::ModifyStats($args[2], $value, $args[1], $args[4]);
										$sender->sendMessage(Murder::getPrefix() . "§bYou have successfully §cremoved§e $args[4] §bwins to§e {$args[2]}§b!");
									break;

									case 'losses':
										$value = "LOSSES";
										PluginUtils::ModifyStats($args[2], $value, $args[1], $args[4]);
										$sender->sendMessage(Murder::getPrefix() . "§bYou have successfully §cremoved§e $args[4] §blosses to§e {$args[2]}§b!");
									break;

									case 'kills':
										$value = "KILLS";
										PluginUtils::ModifyStats($args[2], $value, $args[1], $args[4]);
										$sender->sendMessage(Murder::getPrefix() . "§bYou have successfully §cremoved§e $args[4] §bkills to§e {$args[2]}§b!");
									break;

									default:
										$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db remove <wins|losses|kills> <amount>");
									break;
								}
							break;

							case 'set':
								if (!isset($args[4])) {
									$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db set <player> <wins|losses|kills> <amount>");
									return true;
								}
								if (!PluginUtils::verifyPlayerInDB($args[2])) {
									$sender->sendMessage(Murder::getPrefix() . "§c{$args[2]} is not in the database!");
									return true;
								}
								if (is_numeric($args[4]) || $args[4] < 0) {
									$sender->sendMessage(Murder::getPrefix() . "§cAmount must be a valid number!");
									return true;
								}
								switch ($args[3]) {
									case 'wins':
										$value = "WINS";
										PluginUtils::ModifyStats($args[2], $value, $args[1], $args[4]);
										$sender->sendMessage(Murder::getPrefix() . "§bYou have successfully §aset§e $args[4] §bwins to§e {$args[2]}§b!");
									break;

									case 'losses':
										$value = "LOSSES";
										PluginUtils::ModifyStats($args[2], $value, $args[1], $args[4]);
										$sender->sendMessage(Murder::getPrefix() . "§bYou have successfully §aset§e $args[4] §blosses to§e {$args[2]}§b!");
									break;

									case 'kills':
										$value = "KILLS";
										PluginUtils::ModifyStats($args[2], $value, $args[1], $args[4]);
										$sender->sendMessage(Murder::getPrefix() . "§bYou have successfully §aset§e $args[4] §bkills to§e {$args[2]}§b!");
									break;

									default:
										$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db set <wins|losses|kills> <amount>");
									break;
								}
							break;

							default
							$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db <register|delete|add|remove|set>");
							break;
						}
					} else {
						$sender->sendMessage(Murder::getPrefix() . "§cUse: /vh db <register|delete|add|remove|set>");
					}
				break;

				case 'join':
					if (!$sender instanceof Player) {
						$sender->sendMessage(Murder::getPrefix() . "§cYou can only use this command in the game!");
						return true;
					}
					$sender->sendMessage("§l§a» §r§7Looking for an available game...");
					Arena::joinArena($sender);
				break;

				default:
					if ($sender->getServer()->isOp($sender->getName())) {
						$sender->sendMessage(
							"§a---- §bViltrumite §bCommands §a----" . "\n" . "\n" .
							"§eUse:§a /vh create §7(Create a Viltrumite Hunt arena.)" . "\n" .
							"§eUse:§a /vh arena §7(Manage a Viltrumite Hunt arena.)" . "\n" . //TODO
							"§eUse:§a /vh npc|slapper §7(Spawn the leadboard or Join NPC.)" . "\n" .
							"§eUse:§a /vh code §7(Codes System.)" . "\n" . //TODO
							"§eUse:§a /vh db §7(Modify something from database.)" . "\n" . "\n" .
							"§eUse:§a /vh join §7(Open an UI to join to an arena.)" . "\n" .
							"§eUse:§a /vh profile §7(Show your Viltrumite Hunt profile.)"
						);
					} else {
						$sender->sendMessage(
							"§a---- §bViltrumite §bCommands §a----" . "\n" . "\n" .
							"§eUse:§a /vh join §7(Open an UI to join to an arena.)" . "\n" .
							"§eUse:§a /vh profile §7(Show your Viltrumite Hunt profile.)"
						);
					}
				break;
			}
		} else {
			if ($sender->getServer()->isOp($sender->getName())) {
				$sender->sendMessage(
					"§a---- §cMurder §bCommands §a----" . "\n" . "\n" .
					"§eUse:§a /vh create §7(Create a Viltrumite Hunt arena.)" . "\n" .
					"§eUse:§a /vh arena §7(Manage a Viltrumite Hunt arena.)" . "\n" .
					"§eUse:§a /vh npc|slapper §7(Spawn the leadboard or Join NPC.)" . "\n" .
					"§eUse:§a /vh code §7(Codes System.)" . "\n" .
					"§eUse:§a /vh db §7(Modify something from database.)" . "\n" . "\n" .
					"§eUse:§a /vh join §7(Open an UI to join to an arena.)" . "\n" .
					"§eUse:§a /vh profile §7(Show your Viltrumite Hunt profile.)"
				);
			} else {
				$sender->sendMessage(
					"§a---- §bViltrumite Hunt §bCommands §a----" . "\n" . "\n" .
					"§eUse:§a /vh join §7(Open an UI to join to an arena.)" . "\n" .
					"§eUse:§a /vh profile §7(Show your Viltrumite Hunt profile.)"
				);
			}
		}
		return true;
	}
}
