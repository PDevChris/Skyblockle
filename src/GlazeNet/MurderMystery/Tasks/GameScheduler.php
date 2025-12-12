<?php


declare(strict_types=1);

namespace GlazeNet\MurderMystery\Tasks;

use GlazeNet\GlazeCore\Core;
use GlazeNet\GlazeCore\Player\Inventory;
use GlazeNet\MurderMystery\Arena\Arena;
use GlazeNet\MurderMystery\Code\CodeManager;
use GlazeNet\MurderMystery\Entity\EntityManager;
use GlazeNet\MurderMystery\Murder;
use GlazeNet\MurderMystery\PluginUtils;
use GlazeNet\MurderMystery\ResetMap;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat as Color;
use pocketmine\world\Position;
use pocketmine\world\World;
use function count;
use function intval;
use function microtime;
use function time;

class GameScheduler extends Task {

	public static array $added = [];
	public array $coin = [];
	public int $coin_count = 0;
	public $cooldown = null;

	public function onRun() : void {
		unset(Murder::$data["hitDelay"]);
		if (count(Arena::getArenas()) > 0) {
			foreach (Arena::getArenas() as $arena) {
				$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena));
				$lobbyLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getLobbyName($arena));
				$timelobby = Arena::getTimeWaiting($arena);
				$timestarting = Arena::getTimeStarting($arena);
				$timegame = Arena::getTimeGame($arena);
				$timeend = Arena::getTimeEnd($arena);
				if ($arenaLevel instanceof World && $lobbyLevel instanceof World) {
					if (Arena::getStatus($arena) == 'waiting' || Arena::getStatus($arena) == 'waitingcode') {
						foreach ($lobbyLevel->getPlayers() as $player) {
							if(!isset(self::$added[$player->getName()])){
								if(Server::getInstance()->isOp($player->getName())){
									$player->getInventory()->setItem(0, VanillaItems::HEART_OF_THE_SEA()->setCustomName("§r§aStart\n§r§fClick to select"));
								}
								$player->getInventory()->setItem(8, VanillaItems::BED()->setCustomName("§r§cLeave\n§r§fClick to select"));
								$player->getInventory()->setItem(5, VanillaItems::PAPER()->setCustomName("§r§bKits\n§r§fClick to select"));
								self::$added[$player->getName()] = true;
							}
						}
						if (count(Arena::getLobbyPlayers($arena)) < 2) {
							foreach ($lobbyLevel->getPlayers() as $player) {
								$lobbyLevel->setTime(0);
								$lobbyLevel->stopTime();
								$arenaLevel->stopTime();
								$arenaLevel->setTime(0);
								Murder::getReloadArena($arena);
								$player->sendTip("§eWaiting for players..");
								$from = 0;
								$api = Murder::getScore();
								$api->new($player, $player->getName(), '§l§eWC Season One');
								$setlines = [
									Color::RED . " " . "§l§bGame:§r§f Viltrumite Hunt",
									Color::RED . " " . "§l§bMap:§r§f" . Arena::getName($arena),
									Color::RED . " " . "§l§aPlayers:§r" . count(Arena::getLobbyPlayers($arena)),
									Color::YELLOW . "   ",
									Color::RED . " " . "§cWaiting for players..",
									Color::WHITE . "   ",
									"§eserver.ip.com"
								];
								foreach ($setlines as $lines) {
									if ($from < 15) {
										$from++;
										$api->setLine($player, $from, $lines);
										$api->getObjectiveName($player);
									}
								}
							}
						} else {
							$timelobby--;
							Arena::setTimeWaiting($arena, $timelobby);
							foreach ($lobbyLevel->getPlayers() as $player) {
								if (count(Arena::getLobbyPlayers($arena)) == Arena::getMaxSlots($arena)) {
									$player->sendMessage("§l§a» §r§eThe arena is full, starting the game...");
									Arena::setStatus($arena, 'starting');
									$player->getInventory()->clearAll();
									$player->getArmorInventory()->clearAll();
									$player->getCursorInventory()->clearAll();
								}
								$from = 0;
								$api = Murder::getScore();
								$api->new($player, $player->getName(), '§l§eWC Season One');
								if ($timelobby >= 6 && $timelobby <= 40) {
									$player->sendTip('§aStarting game in §l' . $timelobby);
									$setlines = [
										Color::RED . " " . "§l§bGame:§r§f Viltrumite Hunt",
										Color::RED . " " . "§l§bMap:§r§f" . Arena::getName($arena),
										Color::RED . " " . "§l§aPlayers:§r" . count(Arena::getLobbyPlayers($arena)) . "/" . Arena::getMaxSlots($arena),
										Color::YELLOW . "   ",
										Color::RED . " " . '§aStarting game in §l' . $timelobby,
										Color::WHITE . "   ",
										Color::YELLOW . "" . Color::RESET,
										"§eplay.glazenet.ml"
									];
									foreach ($setlines as $lines) {
										if ($from < 15) {
											$from++;
											$api->setLine($player, $from, $lines);
											$api->getObjectiveName($player);
										}
									}
								} elseif ($timelobby >= 1 && $timelobby <= 5) {
									$player->sendTip('§aStarting game in §l§c' . $timelobby);
									$setlines = [
										Color::RED . " " . "§l§bGame:§r§f Viltrumite Hunt",
										Color::RED . " " . "§l§bMap:§r§f" . Arena::getName($arena),
										Color::RED . " " . "§l§aPlayers:§r" . count(Arena::getLobbyPlayers($arena)) . "/" . Arena::getMaxSlots($arena),
										Color::YELLOW . "   ",
										Color::RED . " " . '§aStarting game in §l§c' . $timelobby,
										Color::WHITE . "   ",
										Color::DARK_AQUA . "" . Color::RESET,
										"§eplay.glazenet.ml"
									];
									PluginUtils::PlaySound($player, "random.click");
									foreach ($setlines as $lines) {
										if ($from < 15) {
											$from++;
											$api->setLine($player, $from, $lines);
											$api->getObjectiveName($player);
										}
									}
								} else {
									$api->remove($player);
									$setlines = [
										Color::RED . " " . "§l§bGame:§r§f Viltrumite Hunt",
										Color::RED . " " . "§l§bMap:§r§f" . Arena::getName($arena),
										Color::RED . " " . "§l§aPlayers:§r" . count(Arena::getLobbyPlayers($arena)) . "/" . Arena::getMaxSlots($arena),
										Color::YELLOW . "   ",
										Color::YELLOW . " " . '§aPreparing world...',
										Color::WHITE . "   ",
										Color::YELLOW . "" . Color::RESET,
										"§eplay.glazenet.ml"
									];
									foreach ($setlines as $lines) {
										if ($from < 15) {
											$from++;
											$api->setLine($player, $from, $lines);
											$api->getObjectiveName($player);
										}
									}
									$player->sendTip('§aPreparing world...');
									$player->getInventory()->clearAll();
									$player->getArmorInventory()->clearAll();
									$player->getCursorInventory()->clearAll();
								}
								if ($timelobby == -1) {
									Arena::setStatus($arena, 'starting');
									Arena::tpRandomSpawn($player, $arena);
									$player->sendTip('§aPreparing world...');
								}
							}
						}
					} elseif (Arena::getStatus($arena) == 'starting') {
						$timestarting--;
						Arena::setTimeStarting($arena, $timestarting);
						foreach ($arenaLevel->getPlayers() as $player) {
							if ($timestarting >= 0 && $timestarting <= 10) {
								if (count(Arena::getPlayers($arena)) < 2) {
									Murder::getReloadArena($arena);
									if (CodeManager::getCodeOfArena($arena) != null) {
										Arena::setStatus($arena, "waitingcode");
									}
									$player->setNameTagAlwaysVisible(true);
									$player->sendMessage("§l§c» §r§bCounting cancelled because are needed more players...");
									$config = Murder::getConfigs('Arenas/' . $arena);
									$configAll = $config->getAll();
									$lobbyX = $configAll["lobby"]["x"];
									$lobbyY = $configAll["lobby"]["y"];
									$lobbyZ = $configAll["lobby"]["z"];
									$lobbyPos = new Position($lobbyX, $lobbyY, $lobbyZ, $lobbyLevel);
									$lobbyLevel->loadChunk($lobbyPos->getFloorX(), $lobbyPos->getFloorZ());
									$player->teleport($lobbyPos);
								}
							}
							
								if ($timestarting == 10) {
								    $player->setNameTagAlwaysVisible(false);
								    Arena::setRoles($arena);
								    $player->setNameTag("");
								
								    // Freeze Viltrumite while countdown runs
								    $role = Arena::getRole($player, $arena);
								    if ($role === "Murderer") {
								        $player->setImmobile(true);
								        $player->sendTitle("§cFrozen!", "§7Wait for countdown to finish...");
								    } else {
								        $player->sendTitle("§aRUN!", "§7Viltrumite frozen for 10 seconds!");
								    }
								
								    $player->sendTip("§eGame start §l»§r §e▌▌▌▌▌▌▌▌▌▌ §f10");
								} elseif ($timestarting == 9) {
								    $player->sendTip("§eGame start §l»§r §e▌▌▌▌▌▌▌▌▌§7▌ §f9");
								} elseif ($timestarting == 8) {
								    $player->sendTip("§eGame start §l»§r §e▌▌▌▌▌▌▌▌§7▌▌ §f8");
								} elseif ($timestarting == 7) {
								    $player->sendTip("§eGame start §l»§r §e▌▌▌▌▌▌▌§7▌▌▌ §f7");
								} elseif ($timestarting == 6) {
								    $player->sendTip("§eGame start §l»§r §e▌▌▌▌▌▌§7▌▌▌▌ §f6");
								} elseif ($timestarting == 5) {
								    $player->sendTip("§eGame start §l»§r §e▌▌▌▌▌§7▌▌▌▌▌ §f5");
								} elseif ($timestarting == 4) {
								    $player->sendTip("§eGame start §l»§r §e▌▌▌▌§7▌▌▌▌▌▌ §f4");
								} elseif ($timestarting == 3) {
								    $player->sendTip("§eGame start §l»§r§c ▌▌▌§7▌▌▌▌▌▌▌ §f3");
								    PluginUtils::PlaySound($player, "random.toast", 1, 1.5);
								} elseif ($timestarting == 2) {
								    $player->sendTip("§eGame start §l»§r§c ▌▌§7▌▌▌▌▌▌▌▌ §f2");
								    PluginUtils::PlaySound($player, "random.toast", 1, 1.5);
								} elseif ($timestarting == 1) {
								    $player->sendTip("§eGame start §l»§r§c ▌§7▌▌▌▌▌▌▌▌▌ §f1");
								    PluginUtils::PlaySound($player, "random.toast", 1, 1.5);
								} elseif ($timestarting == 0) {
								    Arena::setStatus($arena, "ingame");
								
								    // Release the Viltrumite when countdown finishes
								    $role = Arena::getRole($player, $arena);
								    if ($role === "Murderer") {
								        $player->setImmobile(false);
								        $player->sendTitle("§aGo!", "§7You’re free to hunt!");
								        PluginUtils::PlaySound($player, "random.levelup", 1, 1);
								    } else {
								        $player->sendTitle("§aGame Started!", "§7Survive the Viltrumite!");
								    }
								}
					} elseif (Arena::getStatus($arena) == 'ingame') {
						foreach (Arena::getCoinSpawns($arena) as $coin) {
							$coinPos = Arena::getCoinSpawnPos($arena, $coin);
							if(!isset($this->coin[$coin])){
								if($this->cooldown === null) $this->cooldown = intval(microtime(true));
								if(intval(microtime(true)) - $this->cooldown > 5){
									if($this->coin_count < 10){
										$world = $coinPos->getWorld();
                    					$item = \pocketmine\item\VanillaItems::ENDER_EYE();
                  					    $world->dropItem($coinPos, $item);
										$this->coin_count += 1;
										$this->coin[] = $coin;
										$this->cooldown = microtime(true);
									} else {
										$this->coin_count = 0;
									}
								}
							} else {
								continue;
							}
						}
						$timegame--;
						Arena::setTimeGame($arena, $timegame);
						foreach ($arenaLevel->getPlayers() as $player) {
							if (!isset(Murder::$data["coins"][Arena::getName($arena)][$player->getName()])) {
								Murder::$data["coins"][Arena::getName($arena)][$player->getName()] = 0;
							}
							$from = 0;
							$api = Murder::getScore();
							$api->new($player, $player->getName(), '§l§eWC Season One');
							if (Arena::getRole($player, $arena) != null) {
								$setlines = [
									Color::RED . " " . "§l§bGame:§r§f Viltrumite Hunt",
									Color::RED . " " . "§l§bMap: §f" . Arena::getName($arena),
									Color::RED . "   ",
									Color::RED . " " . "§l§aInnocents left: §r" . count(Arena::getInnocentesAndDetectivesAlive($arena)),
									Color::RED . " " . "§l§aTime left: §e" . PluginUtils::getTimeParty($timegame),
									Color::RED . "   ",
									Color::RED . " " . "§fRole: §a" . Arena::getRole($player, $arena),
									Color::BLUE . "   ",
									"§eplay.glazenet.ml"
								];
							} else {
								if ($player->getGamemode() == GameMode::SURVIVAL() || $player->getGamemode() == GameMode::ADVENTURE()) {
									$player->sendMessage("§l§c» §r§bYou can't be here ... Redirecting you to the lobby..");
									$player->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
									$player->setGamemode(GameMode::ADVENTURE());
								}
								$setlines = [
									Color::RED . " " . "§l§bGame:§r§f Viltrumite Hunt",
									Color::RED . " " . "§l§bMap: §f" . Arena::getName($arena),
									Color::RED . "   ",
									Color::RED . " " . "§l§aInnocents left: §r" . count(Arena::getInnocentesAndDetectivesAlive($arena)),
									Color::RED . " " . "§l§aTime left: §e" . PluginUtils::getTimeParty($timegame),
									Color::RED . "   ",
									Color::RED . " " . "§fRole: §a" . Arena::getRole($player, $arena),
									Color::BLUE . "   ",
									"§eplay.glazenet.ml"
								];
							}
							foreach ($setlines as $lines) {
								if ($from < 15) {
									$from++;
									$api->setLine($player, $from, $lines);
									$api->getObjectiveName($player);
								}
							}
						}
						foreach (Murder::$data["coinDelay"] as $arenaName => $coins) {
							foreach ($coins as $coin => $spawnCoinTime) {
								if ($arenaName === Arena::getName($arena)) {
									if (time() >= $spawnCoinTime) {
										unset(Murder::$data["coinDelay"][$arenaName][$coin]);
										if ($coin != "Murder.Coin") {
											$coinPos = Arena::getCoinSpawnPos($arena, intval($coin));
											$world = $coinPos->getWorld();
											$world->dropItem($coinPos, VanillaItems::ENDER_EYE());

										}
									}
								}
							}
						}
						foreach (Murder::$data["giveArow"] as $arenaName => $playersName) {
							foreach ($playersName as $playerName => $giveArowTime) {
								$player = Murder::getInstance()->getServer()->getPlayerExact($playerName);
								if ($arenaName === Arena::getName($arena)) {
									if ($player->getWorld()->getFolderName() == $arenaName) {
										if (Arena::getRole($player, $arena) === "Detective") {
											if (Murder::$data["players"][$arenaName]["Detective"][$playerName] === "Alive") {
												if (time() >= $giveArowTime) {
													unset(Murder::$data["giveArow"][$arenaName][$playerName]);
													if ($player->getInventory()->isSlotEmpty(2)) {
														$player->getInventory()->setItem(2, VanillaItems::ARROW()->setCount(1));
													} else {
														$player->getInventory()->setItem(3, VanillaItems::ARROW()->setCount(1));
													}
												}
											}
										}
									}
								}
							}
						}
						if ($timegame == 0) {
						    $murderers = Arena::getMurderersAlive($arena);
						    $innocents = Arena::getInnocentsAlive($arena);
						    $escaped = Arena::getEscapedPlayers($arena); // players that entered End portal
						
						    if (!empty($escaped)) {
						        // Escaped players win
						        Arena::declareSurvivorWin($arena, $escaped);
						    } elseif (!empty($innocents)) {
						        // Some innocents survived but didn’t escape
						        Arena::declarePartialSurvivor($arena, $innocents);
						    } else {
						        // No one survived
						        Arena::arenaWin($arena, "NoWinners", null, $murderers);
						    }
						
						    $api = Murder::getScore();
						    $api->remove($player);
						\
					} elseif (Arena::getStatus($arena) == 'end') {
						foreach ($arenaLevel->getPlayers() as $player) {
							if(isset(self::$added[$player->getName()])) unset(self::$added[$player->getName()]);
								Murder::getScore()->remove($player);
						}
						$timeend--;
						foreach ($arenaLevel->getPlayers() as $player) {
							$player->setNameTagAlwaysVisible();
							$player->getInventory()->clearAll();
							$player->getArmorInventory()->clearAll();
							$player->getCursorInventory()->clearAll();
						}
						Arena::setTimeEnd($arena, $timeend);
						if ($timeend == 5) {
							$arenaCode = CodeManager::getCodeOfArena($arena);
							if ($arenaCode != null) {
								CodeManager::removeCodeFromDB($arenaCode);
							}
							foreach ($arenaLevel->getPlayers() as $player) {
								$player->sendMessage("§l§a» §r§7Looking for an available game...");
								Arena::joinArena($player);
							}
						} elseif ($timeend >= 1 && $timeend <= 3) {
							foreach ($arenaLevel->getPlayers() as $player) {
								$player->sendTip("§eLeaving game in " . $timeend . " seconds.");
							}
						} elseif ($timeend == 0) {
							foreach ($arenaLevel->getPlayers() as $player) {
								$player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
								Inventory::lobbyInventory($player);
								$player->setGamemode(GameMode::ADVENTURE());
								$player->setNameTagAlwaysVisible(true);
								$player->setNameTag($player->getNick());
							}
							ResetMap::resetZip(Arena::getName($arena));
							Murder::getReloadArena($arena);
							unset(Murder::$data["players"][Arena::getName($arena)]);
							unset(Murder::$data["coins"][Arena::getName($arena)]);
							unset(Murder::$data["giveArow"][Arena::getName($arena)]);
							unset(Murder::$data["coinDelay"][Arena::getName($arena)]);
						} else {
							foreach ($arenaLevel->getPlayers() as $player) {
								$player->sendTip("§eLeaving game in " . $timeend . " seconds.");
							}
						}
					}
				}
			}
		}
	}
}
