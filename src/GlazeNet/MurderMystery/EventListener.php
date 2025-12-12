<?php

declare(strict_types=1);

namespace GlazeNet\MurderMystery;

use GlazeNet\GlazeCore\Player\Inventory;
use GlazeNet\MurderMystery\Arena\Arena;
use GlazeNet\MurderMystery\Code\CodeManager;
use GlazeNet\MurderMystery\Entity\EntityManager;
use GlazeNet\MurderMystery\Entity\MurderCoin;
use GlazeNet\MurderMystery\Entity\MurderLeadboard;
use GlazeNet\MurderMystery\Entity\MurderNPCJoin;
use GlazeNet\MurderMystery\Entity\MurderPoliceHat;
use GlazeNet\MurderMystery\Entity\MurderTomb;
use pocketmine\item\VanillaItems;
use muqsit\dimensionportals\event\PlayerDimensionScreenChangeEvent;
use muqsit\dimensionportals\Loader;
use muqsit\dimensionportals\WorldManager;
use GlazeNet\MurderMystery\Form\FormManager;
use GlazeNet\MurderMystery\Tasks\GameScheduler;
use GlazeNet\PracticeCore\Practice;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\Position;
use function count;
use function time;

class EventListener implements Listener {

	public function onChat(PlayerChatEvent $event) {
		$player = $event->getPlayer();
		foreach (Arena::getArenas() as $arena) {
			if ($player->getWorld()->getFolderName() == Arena::getLobbyName($arena) || $player->getWorld()->getFolderName() == Arena::getName($arena)) {
				if ($player->getGamemode() == GameMode::SURVIVAL() || $player->getGamemode() == GameMode::ADVENTURE() || $player->getGamemode() == GameMode::SPECTATOR()) {
					if($player->getWorld()->getFolderName() === Arena::getName($arena)){
						if(Arena::getRole($player, $arena) === "Inoccent"){
							foreach(Arena::getPlayers($arena) as $p){
								$p->sendMessage($player->getNick() . TF::BOLD . TF::YELLOW . "» " . TF::clean($event->getMessage()));
							}
						}
					}
				}
			}
		}
	}

	public function onInteract(PlayerItemUseEvent $event) {
		$player = $event->getPlayer();
		$name = $event->getItem()->getCustomName();
		foreach (Arena::getArenas() as $arena) {
			if ($player->getWorld()->getFolderName() == Arena::getLobbyName($arena) || $player->getWorld()->getFolderName() == Arena::getName($arena)) {
				if ($player->getGamemode() == GameMode::SURVIVAL() || $player->getGamemode() == GameMode::ADVENTURE() || $player->getGamemode() == GameMode::SPECTATOR()) {
					if (PluginUtils::verifyInteractDelay($player)) {
						$arenaCode = CodeManager::getCodeOfArena($arena);
						if ($name == "§r§aStart\n§r§fClick to select") {
							if ($arenaCode != null) {
								$arenaAdmin = CodeManager::getFromCodesDB($arenaCode, "creator");
								if ($player->getName() !== $arenaAdmin) {
									$player->sendMessage(Murder::getPrefix() . "§cOnly the creator of the code can use this function!");
									PluginUtils::PlaySound($player, "note.bass");
									return;
								}
							} else {
								if (!$player->hasPermission('glaze.murder.start')) {
									$player->sendMessage(Murder::getPrefix() . "§cYou do not have permission to start the game!");
									PluginUtils::PlaySound($player, "note.bass");
									return;
								}
							}
							if (count(Server::getInstance()->getWorldManager()->getWorldByName($player->getWorld()->getFolderName())->getPlayers()) < 1) {
								$player->sendMessage(Murder::getPrefix() . "§cMore players are needed to start the game!");
								PluginUtils::PlaySound($player, "note.bass");
								return;
							}
							if (Arena::getTimeWaiting($arena) >= 0 && Arena::getTimeWaiting($arena) <= 5) {
								$player->sendMessage(Murder::getPrefix() . "§c¡Oops! It seems that the game is about to begin...");
								PluginUtils::PlaySound($player, "note.bass");
								return;
							}
							Arena::setTimeWaiting($arena, 5);
							foreach ($player->getWorld()->getPlayers() as $players) {
								$players->sendMessage("§6An admin has started the game! It will be start in 5 seconds.");
								PluginUtils::PlaySound($players, "random.toast");
							}
						}
						if ($name == "§r§bKits\n§r§fClick to select") {
							    // Open kit selection form
							    FormManager::sendForm($player, "KitManager");
							    return;
							}
						if ($name == "§r§cLeave\n§r§fClick to select") {
							$player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
							$player->getInventory()->clearAll();
							$player->getCursorInventory()->clearAll();
							$player->getArmorInventory()->clearAll();
							$player->setNoClientPredictions(false);
							$player->setAllowFlight(false);
							$player->setFlying(false);
							$player->getEffects()->clear();
							$player->setGamemode(GameMode::ADVENTURE());
							$player->setHealth(20);
							$player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
							$player->sendMessage("§cYou have leave successfully the game!");
							Inventory::lobbyInventory($player);
							unset(GameScheduler::$added[$player->getName()]);
						}
						if ($player->getGamemode() == GameMode::SPECTATOR()) {
							if ($name == "§r§l§9Play Again!") {
								$player->sendMessage("§l§a» §r§7Looking for an available game...");
								Arena::joinArena($player);
							}
						}
					}
					if ($name == "§r§9Nearest Player") {
						$playerName = $player->getName();
						if (Arena::getRole($player, $arena) === "Murderer") {
							if (!PluginUtils::verifyInteractDelay($player)) {
								Murder::$data["interactDelay"][$playerName] = time() + 5;
								Arena::sendNearestPlayerMessage($player);
							} else {
								PluginUtils::PlaySound($player, "mob.blaze.shoot");
								$player->sendMessage("§cYou must wait " . (Murder::$data["interactDelay"][$playerName] - time()) . " seconds to use the compass again...");
							}
						}
					}
				}
			}
		}
	}

	public function onDrop(PlayerDropItemEvent $event) {
		$player = $event->getPlayer();
		foreach (Arena::getArenas() as $arena) {
			if ($player->getWorld()->getFolderName() == Arena::getLobbyName($arena) || $player->getWorld()->getFolderName() == Arena::getName($arena)) {
				$event->cancel();
			}
		}
	}

	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		$playerName = $player->getName();
		foreach (Arena::getArenas() as $arena) {
			$arenaName = Arena::getName($arena);
			$lobbyName = Arena::getLobbyName($arena);
			$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName($arenaName);
			$lobbyLevel = Server::getInstance()->getWorldManager()->getWorldByName($lobbyName);
			if ($player->getWorld()->getFolderName() == Arena::getLobbyName($arena) || $player->getWorld()->getFolderName() == Arena::getName($arena)) {
				if (Arena::getStatus($arena) == 'ingame') {
					if (Arena::getRole($player, $arena) === "Murderer") {
						Murder::$data["players"][$arenaName]["Murderer"][$playerName] = "Dead";
						if (count(Arena::getMurderersAlive($arena)) < 1) {
							$winners = Arena::getInnocentesAndDetectivesAlive($arena);
							Arena::arenaWin($arena, "InoccentsWon", $winners);
						}
					} elseif (Arena::getRole($player, $arena) === "Detective") {
						if (Murder::$data["players"][$arenaName]["Detective"][$playerName] === "Alive") {
							EntityManager::setNPCPoliceHat($player);
						}
						Murder::$data["players"][$arenaName]["Detective"][$playerName] = "Dead";
						if (count(Arena::getInnocentesAndDetectivesAlive($arena)) < 1) {
							$murderers = Arena::getMurderersAlive($arena);
							Arena::arenaWin($arena, "MurderWon", $murderers, $murderers);
						}
					} elseif (Arena::getRole($player, $arena) === "Inoccent") {
						Murder::$data["players"][$arenaName]["Inoccent"][$playerName] = "Dead";
						if (count(Arena::getInnocentesAndDetectivesAlive($arena)) < 1) {
							$murderers = Arena::getMurderersAlive($arena);
							Arena::arenaWin($arena, "MurderWon", $murderers, $murderers);
						}
					}
				} elseif (Arena::getStatus($arena) == 'waiting' ||
					Arena::getStatus($arena) == 'waitingcode' ||
					Arena::getStatus($arena) == 'starting') {
					foreach ($lobbyLevel->getPlayers() as $players) {
						$players->sendMessage("§l§c» §r§7" . $playerName . " left the game. §8[" . count(Arena::getPlayers($arena)) . "/" . Arena::getMaxSlots($arena) . "]");
						unset(GameScheduler::$added[$playerName]);
					}
					foreach ($arenaLevel->getPlayers() as $players) {
						$players->sendMessage("§l§c» §r§7" . $playerName . " left the game. §8[" . count(Arena::getPlayers($arena)) . "/" . Arena::getMaxSlots($arena) . "]");
						unset(GameScheduler::$added[$playerName]);
					}
				}
				if (isset(Murder::$data["giveArow"][$arenaName][$playerName])) {
					unset(Murder::$data["giveArow"][$arenaName][$playerName]);
				}
				$player->setNameTagAlwaysVisible();
			}
		}
	}

	public function onLevelChange(EntityTeleportEvent $event) {
		$player = $event->getEntity();
		if (!$player instanceof Player) return;
		$playerName = $player->getName();
		if($event->getFrom()->getWorld()->getFolderName() !== $event->getTo()->getWorld()->getFolderName()){
			foreach (Arena::getArenas() as $arena) {
				$arenaName = Arena::getName($arena);
				$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena));
				$lobbyLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getLobbyName($arena));
				if ($event->getFrom()->getWorld()->getFolderName() == Arena::getName($arena) || $event->getFrom()->getWorld()->getFolderName() == Arena::getLobbyName($arena)) {
					$api = Murder::getScore();
					$api->remove($player);
					if (Arena::getStatus($arena) == 'ingame') {
						if (Arena::getRole($player, $arena) === "Murderer") {
							Murder::$data["players"][$arenaName]["Murderer"][$playerName] = "Dead";
							if (count(Arena::getMurderersAlive($arena)) < 1) {
								$winners = Arena::getInnocentesAndDetectivesAlive($arena);
								Arena::arenaWin($arena, "InoccentsWon", $winners);
								unset(GameScheduler::$added[$playerName]);
							}
						} elseif (Arena::getRole($player, $arena) === "Detective") {
							if (Murder::$data["players"][$arenaName]["Detective"][$playerName] === "Alive") {
								EntityManager::setNPCPoliceHat($player);
								unset(GameScheduler::$added[$playerName]);
							}
							Murder::$data["players"][$arenaName]["Detective"][$playerName] = "Dead";
							if (count(Arena::getInnocentesAndDetectivesAlive($arena)) < 1) {
								$murderers = Arena::getMurderersAlive($arena);
								Arena::arenaWin($arena, "MurderWon", $murderers, $murderers);
								unset(GameScheduler::$added[$playerName]);
							}
						} elseif (Arena::getRole($player, $arena) === "Inoccent") {
							Murder::$data["players"][$arenaName]["Inoccent"][$playerName] = "Dead";
							if (count(Arena::getInnocentesAndDetectivesAlive($arena)) < 1) {
								$murderers = Arena::getMurderersAlive($arena);
								Arena::arenaWin($arena, "MurderWon", $murderers, $murderers);
								unset(GameScheduler::$added[$playerName]);
							}
						}
					} elseif (Arena::getStatus($arena) == 'waiting' ||
						Arena::getStatus($arena) == 'waitingcode') {
						foreach ($lobbyLevel->getPlayers() as $players) {
							$players->sendMessage("§l§c» §r§7" . $playerName . " left the game. §8[" . count(Arena::getPlayers($arena)) . "/" . Arena::getMaxSlots($arena) . "]");
							unset(GameScheduler::$added[$playerName]);
						}
					} elseif(Arena::getStatus($arena) == 'starting'){
						foreach ($arenaLevel->getPlayers() as $players) {
							$players->sendMessage("§l§c» §r§7" . $playerName . " left the game. §8[" . count(Arena::getPlayers($arena)) . "/" . Arena::getMaxSlots($arena) . "]");
							unset(GameScheduler::$added[$playerName]);
						}
					}
					if (isset(Murder::$data["giveArow"][$arenaName][$playerName])) {
						unset(Murder::$data["giveArow"][$arenaName][$playerName]);
					}
					$player->setNameTagAlwaysVisible();
				}
			}
		}
	}

	public function onMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		foreach (Arena::getArenas() as $arena) {
			$config = Murder::getConfigs('Arenas/' . $arena);
			$lobby = $config->get('lobby');
			if ($player->getWorld()->getFolderName() == Arena::getLobbyName($arena) || $player->getWorld()->getFolderName() == Arena::getName($arena)) {
				if (Arena::getStatus($arena) == 'waiting' ||
					Arena::getStatus($arena) == 'waitingcode' ||
					Arena::getStatus($arena) == 'starting' ||
					Arena::getStatus($arena) == 'end'
				) {
					if ($player->getLocation()->getY() < 3) {
						$config = Murder::getConfigs('Arenas/' . $arena);
						$configAll = $config->getAll();
						$lobbyName = Arena::getLobbyName($arena);
						$lobbyLevel = Server::getInstance()->getWorldManager()->getWorldByName($lobbyName);
						$lobbyX = $configAll["lobby"]["x"];
						$lobbyY = $configAll["lobby"]["y"];
						$lobbyZ = $configAll["lobby"]["z"];
						$lobbyPos = new Position($lobbyX, $lobbyY, $lobbyZ, $lobbyLevel);
						$lobbyLevel->loadChunk($lobbyPos->getFloorX(), $lobbyPos->getFloorZ());
						$player->teleport($lobbyPos);
					}
				}
			}
		}
	}

	public function onDamageToNPC(EntityDamageEvent $event) {
		$npc = $event->getEntity();
		if ($event instanceof EntityDamageByEntityEvent) {
			$player = $event->getDamager();
			if ($player instanceof Player) {
				$playerName = $player->getName();
				if ($npc instanceof MurderNPCJoin) {
					if (!PluginUtils::verifyPlayerInDB($player->getName())) {
						PluginUtils::addNewPLayer($player->getName());
						$player->sendMessage(
							"§b§l» §r§7Hey, {$player->getName()}, is your first game!" . "\n" .
							"§9§l» §r§7We are adding you to the Viltrumite Hunt database to follow your progress in your games..."
						);
					}
					FormManager::sendForm($player, "GamePanelUI");
					$event->cancel();
				}
				if ($npc instanceof MurderLeadboard) {
					if (!PluginUtils::verifyPlayerInDB($playerName)) {
						PluginUtils::addNewPLayer($playerName);
						$player->sendMessage(
							"§b§l» §r§7Hey, {$playerName}, is your first game!" . "\n" .
							"§9§l» §r§7We are adding you to the Viltrumite Hunt database to follow your progress in your games..."
						);
					}
					$player->sendTip("§l§cViltrumite Hunt§7 » §r§aaYour Total Victories:§b " . PluginUtils::getFromStatsDB($playerName, 'WINS'));
					$event->cancel();
				}
			}
		}
		if ($npc instanceof MurderPoliceHat || $npc instanceof MurderTomb || $npc instanceof MurderCoin) {
			switch ($event->getCause()) {
				case EntityDamageEvent::CAUSE_SUICIDE:
				case EntityDamageEvent::CAUSE_VOID:
					# Do nothing xD
				break;

				default:
					$event->cancel();
				break;
			}
		}
	}

	
     public function onPortalTeleport(PlayerPortalTeleportEvent $event): void {
        $player = $event->getPlayer();

        // Only trigger for End portals
        if ($event->dimension !== "the_end") return;

        foreach (Arena::getArenas() as $arena) {
            $arenaName = Arena::getName($arena);

            if ($player->getWorld()->getFolderName() !== $arenaName) continue;

            // Cancel actual teleport (don’t move worlds)
            $event->cancel();

            // Turn them into a spectator
            $player->setGamemode(GameMode::SPECTATOR());
            $player->sendTitle("§aYou Escaped!", "§7You survived the Viltrumite!", 10, 60, 20);
            $player->sendMessage("§aYou escaped through the End portal and can now spectate freely!");
            $player->getWorld()->addParticle($player->getPosition(), new PortalParticle());

            // Store them as escaped
            Murder::$data["players"][$arenaName]["Escaped"][$player->getName()] = true;
            unset(Murder::$data["players"][$arenaName]["Alive"][$player->getName()]);

            // Check survivors left (excluding murderers)
            $alive = Murder::$data["players"][$arenaName]["Alive"] ?? [];
            $murderers = Arena::getMurderersAlive($arena);

            foreach ($murderers as $m) unset($alive[$m->getName()]);

            // ✅ If no survivors left alive, survivors (escaped) win
            if (empty($alive)) {
                $escaped = Murder::$data["players"][$arenaName]["Escaped"] ?? [];
                Arena::arenaWin($arena, "InnocentsWon", $escaped, null);
            }

            return;
        }
    }

	public static function getEscapedPlayers(string $arena): array {
    if (!isset(Murder::$data["players"][$arena]["Escaped"])) {
        return [];
    }

    $escaped = [];
    foreach (Murder::$data["players"][$arena]["Escaped"] as $playerName => $status) {
        if ($status === true) {
            $player = Server::getInstance()->getPlayerExact($playerName);
            if ($player !== null && $player->isOnline()) {
                $escaped[$playerName] = $player;
            }
        }
    }

    return $escaped;
}



	public function onDamageToPlayer(EntityDamageEvent $event) {
		$player = $event->getEntity();
		foreach (Arena::getArenas() as $arena) {
			if ($player->getWorld()->getFolderName() == Arena::getLobbyName($arena) || $player->getWorld()->getFolderName() == Arena::getName($arena)) {
				if ($player instanceof Player) {
					if (Arena::getStatus($arena) == 'waiting' ||
						Arena::getStatus($arena) == 'waitingcode' ||
						Arena::getStatus($arena) == 'starting' ||
						Arena::getStatus($arena) == 'end'
					) {
						$event->cancel();
					} elseif (Arena::getStatus($arena) == 'ingame') {
						switch ($event->getCause()) {
							case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
								if ($event instanceof EntityDamageByEntityEvent) {
									$damager = $event->getDamager();
									if ($damager instanceof Player) {
										if (Arena::getRole($damager, $arena) === "Murderer") {
											$item = $damager->getInventory()->getItemInHand();
											$name = $item->getCustomName();
											if ($name == "§r§cMurderer Sword") {
												Murder::$data["hitDelay"][$damager->getName()] = $player->getName();
												Arena::Kill($player, $damager, $arena);
												$damager->sendMessage("§l§a» §r§aYou killed §b{$player->getName()}");
												if (count(Arena::getInnocentesAndDetectivesAlive($arena)) < 1) {
													$murderers = Arena::getMurderersAlive($arena);
													Arena::arenaWin($arena, "MurderWon", $murderers, $murderers);
												}
												$event->cancel();
											} else {
												$event->cancel();
											}
										} elseif (Arena::getRole($damager, $arena) === "Inoccent" ||
											Arena::getRole($damager, $arena) === "Detective") {
											$event->cancel();
										}
									}
								}
							break;

							case EntityDamageEvent::CAUSE_PROJECTILE:
								if ($event instanceof EntityDamageByChildEntityEvent) {
									$damager = $event->getDamager();
									if ($damager instanceof Player) {
										if (Arena::getRole($damager, $arena) === "Detective" ||
											Arena::getRole($damager, $arena) === "Inoccent") {
											if (Arena::getRole($player, $arena) === "Inoccent" ||
												Arena::getRole($player, $arena) === "Detective") {
												Arena::Kill($player, $damager, $arena);
												$damager->sendMessage("§l§c» §r§b{$player->getName()} §awas §cNOT §athe Murder...");
												$damager->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 3, 2, false));
												$damager->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 3, 1, false));
												Murder::$data["coins"][Arena::getName($arena)][$damager->getName()] = 0;
												$damager->getInventory()->clearAll();
												$damager->getArmorInventory()->clearAll();
												$damager->getCursorInventory()->clearAll();
												if (Arena::getRole($damager, $arena) === "Detective") {
													EntityManager::setNPCPoliceHat($damager);
													unset(Murder::$data["players"][Arena::getName($arena)]["Detective"][$damager->getName()]);
													Murder::$data["players"][Arena::getName($arena)]["Inoccent"][$damager->getName()] = "Alive";
													$damager->sendMessage("§l§c» You have lost your Detective role due to negligence.");
												}
												$event->cancel();
												if (count(Arena::getInnocentesAndDetectivesAlive($arena)) < 1) {
													$murderers = Arena::getMurderersAlive($arena);
													Arena::arenaWin($arena, "MurderWon", $murderers, $murderers);
												}
											} elseif (Arena::getRole($player, $arena) === "Murderer") {
												Arena::Kill($player, $damager, $arena);
												$damager->sendMessage("§l§a» §r§aYou killed the Murder!");
												if (count(Arena::getMurderersAlive($arena)) < 1) {
													$winners = Arena::getInnocentesAndDetectivesAlive($arena);
													$hero = $damager->getName();
													Arena::arenaWin($arena, "InoccentsWon", $winners, null, $hero);
													$event->cancel();
												}
											}
										} else {
											
											$event->cancel();
										}
									}
								}
							break;

							case EntityDamageEvent::CAUSE_VOID:
								if ($event->getFinalDamage() >= $player->getHealth()) {
									Arena::Kill($player, null, $arena);
								}
							break;

							 default:
                  				  if ($player->getHealth() - $event->getFinalDamage() <= 0) {
                       				 Arena::Kill($player, null, $arena);
                   				   }
                  					  $event->cancel(); // Always cancel default damage
              				       break;
						}
					}
				}
			}
		}
	}

	public function onShootBow(EntityShootBowEvent $event) {
		$player = $event->getEntity();
		if ($player instanceof Player) {
			foreach (Arena::getArenas() as $arena) {
				if ($player->getWorld()->getFolderName() == Arena::getName($arena)) {
					$projectile = $event->getProjectile();
					$item = $player->getInventory()->getItemInHand();
					$name = $item->getCustomName();
					if (Arena::getStatus($arena) == 'ingame') {
						if (Arena::getRole($player, $arena) === "Inoccent") {
							if ($name == "§r§6Inoccent Bow") {
								//$projectile->namedtag->setString("custom_data", "murdermystery_arrow");
								$event->setForce(3);
							}
						} elseif (Arena::getRole($player, $arena) === "Detective") {
							if ($name == "§r§9Detective Bow") {
								//$projectile->namedtag->setString("custom_data", "murdermystery_arrow");
								$event->setForce(3);
								Murder::$data["giveArow"][Arena::getName($arena)][$player->getName()] = time() + 5;
							}
						}
						if (!Arena::getRole($player, $arena) === "Inoccent" ||
							!Arena::getRole($player, $arena) === "Detective") {
							$event->cancel();
						}
					}
				}
			}
		}
	}

	public function onArrowHitBlock(ProjectileHitBlockEvent $event) {
		$arrow = $event->getEntity();
		foreach (Arena::getArenas() as $arena) {
			if ($arrow->getWorld()->getFolderName() == Arena::getName($arena)) {
				$arrow->flagForDespawn();
			}
		}
	}

	public function onHunger(PlayerExhaustEvent $event) {
		$player = $event->getPlayer();
		foreach (Arena::getArenas() as $arena) {
			$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena));
			$lobbyLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getLobbyName($arena));
			if ($player->getWorld() == $arenaLevel || $player->getWorld() == $lobbyLevel) {
				$event->cancel();
			}
		}
	}
}
