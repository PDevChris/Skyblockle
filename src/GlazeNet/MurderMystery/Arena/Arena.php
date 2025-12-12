<?php


declare(strict_types=1);

namespace GlazeNet\MurderMystery\Arena;

use GlazeNet\GlazeCore\Core;
use GlazeNet\MurderMystery\Entity\EntityManager;
use GlazeNet\MurderMystery\Murder;

use GlazeNet\MurderMystery\PluginUtils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\Position;
use function array_push;
use function array_rand;
use function array_reverse;
use function array_search;
use function closedir;
use function count;
use function file_exists;
use function implode;
use function max;
use function natsort;
use function opendir;
use function readdir;
use function str_replace;
class Arena {

	public static function getArenas() : array { // Return Arena ID
		$arenas = [];
		if ($handle = opendir(Murder::getInstance()->getDataFolder() . 'Arenas/')) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry !== '.' && $entry !== '..') {
					$name = str_replace('.yml', '', $entry);
					$arenas[] = $name;
				}
			}
			closedir($handle);
		}
		return $arenas;
	}

	public static function getAvailableArenas () : array { // Return Arena ID
		$arenas = [];
		$allArenas = self::getArenas();
		if (count($allArenas) > 0) {
			foreach ($allArenas as $arena) {
				if (self::getStatus($arena) === "waiting") {
					if (count(self::getPlayers($arena)) < self::getMaxSlots($arena)) {
						$arenas[] = $arena;
					}
				}
			}
		}
		return $arenas;
	}

	public static function getPlayers(string $arena) : array {
		$players = [];
		$expectedArena = Server::getInstance()->getWorldManager()->getWorldByName(self::getName($arena));
		if ($expectedArena != null) {
			foreach ($expectedArena->getPlayers() as $player) {
				if ($player->getGamemode() == GameMode::SURVIVAL() || $player->getGamemode() == GameMode::ADVENTURE()) {
					$players[] = $player->getName();
				}
			}
		}
		return $players;
	}

	public static function getLobbyPlayers(string $arena) : array {
		$players = [];
		$expectedArena = Server::getInstance()->getWorldManager()->getWorldByName(self::getLobbyName($arena));
		if ($expectedArena != null) {
			foreach ($expectedArena->getPlayers() as $player) {
				if ($player->getGamemode() == GameMode::SURVIVAL() || $player->getGamemode() == GameMode::ADVENTURE()) {
					$players[] = $player->getName();
				}
			}
		}
		return $players;
	}

	public static function getSpecters(string $arena) : array {
		$specters = [];
		$expectedArena = Server::getInstance()->getWorldManager()->getWorldByName(self::getName($arena));
		if ($expectedArena != null) {
			foreach ($expectedArena->getPlayers() as $player) {
				if ($player->getGamemode() == GameMode::SPECTATOR()) {
					$specters[] = $player->getName();
				}
			}
		}
		return $specters;
	}

	public static function getMurderersAlive(string $arena) : array {
		$murderers = [];
		$arenaName = self::getName($arena);
		$expectedArena = Server::getInstance()->getWorldManager()->getWorldByName($arenaName);
		if ($expectedArena != null) {
			foreach ($expectedArena->getPlayers() as $player) {
				if (self::getRole($player, $arena) === "Murderer") {
					if (Murder::$data["players"][$arenaName]["Murder"][$player->getName()] === "Alive") {
						$murderers[] = $player->getName();
					}
				}
			}
		}
		return $murderers;
	}

	public static function getInoccentsAlive(string $arena) : array {
		$inoccents = [];
		$arenaName = self::getName($arena);
		$expectedArena = Server::getInstance()->getWorldManager()->getWorldByName($arenaName);
		if ($expectedArena != null) {
			foreach ($expectedArena->getPlayers() as $player) {
				if (self::getRole($player, $arena) === "Inoccent") {
					if (Murder::$data["players"][$arenaName]["Inoccent"][$player->getName()] === "Alive") {
						$inoccents[] = $player->getName();
					}
				}
			}
		}
		return $inoccents;
	}

	public static function getDetectivesAlive(string $arena) : array {
		$detectives = [];
		$arenaName = self::getName($arena);
		$expectedArena = Server::getInstance()->getWorldManager()->getWorldByName($arenaName);
		if ($expectedArena != null) {
			foreach ($expectedArena->getPlayers() as $player) {
				if (self::getRole($player, $arena) === "Detective") {
					if (Murder::$data["players"][$arenaName]["Detective"][$player->getName()] === "Alive") {
						$detectives[] = $player->getName();
					}
				}
			}
		}
		return $detectives;
	}

	public static function getInnocentesAndDetectivesAlive(string $arena) : array {
		$alive = [];
		$arenaName = self::getName($arena);
		$expectedArena = Server::getInstance()->getWorldManager()->getWorldByName($arenaName);
		if ($expectedArena != null) {
			foreach ($expectedArena->getPlayers() as $player) {
				if (self::getRole($player, $arena) === "Inoccent") {
					if (Murder::$data["players"][$arenaName]["Inoccent"][$player->getName()] === "Alive") {
						$alive[] = $player->getName();
					}
				} elseif (self::getRole($player, $arena) === "Detective") {
					if (Murder::$data["players"][$arenaName]["Detective"][$player->getName()] === "Alive") {
						$alive[] = $player->getName();
					}
				}
			}
		}
		return $alive;
	}

	public static function getCoinSpawns(string $arena) { // Return Spawns Name
		$spawns = [];
		$config = Murder::getConfigs('Arenas/' . $arena);
		$expectedSpawns = $config->get("coinspawns");
		if ($expectedSpawns != null) {
			foreach ($expectedSpawns as $expectedSpawn => $data) {
				$spawns[] = $expectedSpawn;
			}
		}
		return $spawns;
	}

	public static function getCoinSpawnPos(string $arena, $coinSpawn) {
		$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena));
		$config = Murder::getConfigs('Arenas/' . $arena);
		$configAll = $config->getAll();
		$configCoin = $configAll["coinspawns"][$coinSpawn];
		$spawnX = $configCoin["x"];
		$spawnY = $configCoin["y"];
		$spawnZ = $configCoin["z"];
		$spawnPos = new Location($spawnX, $spawnY, $spawnZ, $arenaLevel, 0, 0);
		return $spawnPos;
	}

	public static function isDetectiveAlive(string $arena) {
		if (count(self::getDetectivesAlive($arena)) < 1) {
			return "§cDead";
		}
		return "§aAlive";
	}

	public static function arenaWin(string $arena, $mode, $winners = null, $murderers = null, $murdererKiller = null) {
		self::setStatus($arena, 'end');
		$arenaName = self::getName($arena);
		$max = [];
		$tops = [];
		$coins = Murder::$data['coins'][Arena::getName($arena)];
		foreach ($coins as $key => $top) {
			array_push($tops, $top);
		}
		natsort($tops);
		$players = array_reverse($tops);
		if (max($tops) != null) {
			$max = array_search($players[0], $coins, true);
		}
		switch ($mode) {
			case 'MurderWon':
				foreach(Arena::getPlayers($arena) as $player){
					$p = Server::getInstance()->getPlayerExact($player);
					$p->sendTitle("§l§cGame Over!");
					PluginUtils::PlaySound($p, "mob.blaze.shoot");
					$p->sendMessage("§aMurderer has won in the arena: " . $arena);
	
				}
			break;

			case 'InoccentsWon':
				if ($murdererKiller != null) {
					foreach(Arena::getPlayers($arena) as $player){
						$p = Server::getInstance()->getPlayerExact($player);
						PluginUtils::PlaySound($p, "mob.blaze.shoot");
					}
				} else {
					foreach(Arena::getPlayers($arena) as $player){
						$p = Server::getInstance()->getPlayerExact($player);
						PluginUtils::PlaySound($p, "mob.blaze.shoot");
					}
				}
			break;

			 case "PartialInnocentWin":
                foreach ($winners as $name) {
                    $p = Server::getInstance()->getPlayerExact($name);
                }
                break;

			case 'NoWinners':
				foreach(Arena::getPlayers($arena) as $player){
					$p = Server::getInstance()->getPlayerExact($player);
					$p->sendTitle("§l§cGame Over!");
					PluginUtils::PlaySound($p, "mob.blaze.shoot");
					$p->sendMessage("§aSurvivors has won in the arena: " . $arena);
				}
				foreach (self::getPlayers($arena) as $losser) {
					PluginUtils::ModifyStats($losser, "LOSSES", "add", 1);
				}
			break;
		}
		if ($winners != null) {
			foreach ($winners as $winner) {
				PluginUtils::ModifyStats($winner, "WINS", "add", 1);
			}
		}
		$players = self::getPlayers($arena);
		foreach ($players as $player) {
			PluginUtils::ModifyStats($player, "GAMESPLAYED", "add", 1);
		}
	}

	public static function declareSurvivorWin(string $arena, array $escapedPlayers): void {
    // Winners = escaped players
    self::arenaWin($arena, "InoccentsWon", $escapedPlayers);

    foreach ($escapedPlayers as $name) {
        $p = Server::getInstance()->getPlayerExact($name);
        if ($p !== null) {
            $p->sendTitle("§aYou Escaped!", "§7You reached the End Portal in time!", 10, 60, 20);
            PluginUtils::PlaySound($p, "random.levelup");
        }
    }
}

public static function declarePartialSurvivor(string $arena, array $innocentsAlive): void {
    // Some innocents survived but didn’t escape
    self::arenaWin($arena, "PartialInnocentWin", $innocentsAlive);

    foreach ($innocentsAlive as $name) {
        $p = Server::getInstance()->getPlayerExact($name);
        if ($p !== null) {
            $p->sendTitle("§eTime's Up!", "§7You survived, but didn't escape.", 10, 60, 20);
            PluginUtils::PlaySound($p, "mob.villager.yes");
        }
    }
}


	public static function joinArena(Player $player, $arena = null) {
		if (!PluginUtils::verifyPlayerInDB($player->getName())) {
			PluginUtils::addNewPLayer($player->getName());
			$player->sendMessage(
				"§b§l» §r§7Hey, {$player->getName()}, this is your first game!" . "\n" .
				"§9§l» §r§7We are adding you to the database to follow your progress in your games..."
			);
		}
		if ($arena === null) {
			$arena = self::getRandomArena();
			if ($arena != null) {
				$player->sendMessage("§l§a» §r§eNew found arena, you will be transferred...");
			} else {
				$player->sendMessage("§l§a» §cNo arenas available for now, try again later...");
				return;
			}
		}
		if ($arena != null) {
			$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena));
			$arenaName = self::getName($arena);
			$config = Murder::getConfigs('Arenas/' . $arena);
			$configAll = $config->getAll();
			$player->getEffects()->clear();
			$player->getInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
			$player->getCursorInventory()->clearAll();
			$player->setAllowFlight(false);
			$player->setFlying(false);
			$player->setGamemode(GameMode::ADVENTURE());
			$player->setHealth(20);
			$player->getHungerManager()->setFood(20);
			//Teleport to Waiting Lobby
			$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName($configAll["lobby"]["world"]);
			$lobbyX = $configAll["lobby"]["x"];
			$lobbyY = $configAll["lobby"]["y"];
			$lobbyZ = $configAll["lobby"]["z"];
			$lobbyPos = new Position($lobbyX, $lobbyY, $lobbyZ, $arenaLevel);
			$arenaLevel->loadChunk($lobbyPos->getFloorX(), $lobbyPos->getFloorZ());
			$player->teleport($lobbyPos);
			foreach ($arenaLevel->getPlayers() as $players) {
				$players->sendMessage("§l§a» §r§7" . $player->getName() . " joined the match . §8[" . count(Arena::getLobbyPlayers($arena)) . "/" . Arena::getMaxSlots($arena) . "]");
			}
		} else {
			$player->sendMessage("§l§a» §cNo arenas available for now, try again later..");
		}
	}

	public static function getRandomArena() { // Return Arena ID
		$availableArenas = self::getAvailableArenas();
		if (count($availableArenas) <= 0) {
			return null;
		} else {
			foreach ($availableArenas as $arena) {
				if (count(self::getPlayers($arena)) > 0) {
					return $arena;
				}
			}
			return $availableArenas[array_rand($availableArenas)];
		}
	}

	public static function ArenaExisting(string $id) : bool {
		if (file_exists(Murder::getInstance()->getDataFolder() . 'Arenas/Murder-' . $id . '.yml')) {
			return true;
		} else {
			return false;
		}
	}

	public static function Kill(Player $player, Player $killer = null, string $arena) {
		$playerName = $player->getName();
		$arenaName = self::getName($arena);
		$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName($arenaName);
		$player->sendTitle("§cYou died!");
		$player->sendSubTitle("§bBetter luck next time");
		$player->setGamemode(GameMode::SPECTATOR());
		if ($killer != null) {
			$killerName = $killer->getName();
			if (self::getRole($killer, $arena) === "Murderer") { //If the Killer is a Murderer
				PluginUtils::ModifyStats($killerName, "KILLS", "add", 1);
			}
			if (self::getRole($killer, $arena) === "Detective" ||
				self::getRole($killer, $arena) === "Inoccent") { //If the Killer is a Detective or Inoccent
				if (self::getRole($player, $arena) === "Murderer") {
					PluginUtils::ModifyStats($killerName, "MURDERERELIMINATIONS", "add", 1);
				}
			}
		} else {
			$config = Murder::getConfigs('Arenas/' . $arena);
			$configAll = $config->getAll();
			$lobbyspX = $configAll["lobbyspecters"]["x"];
			$lobbyspY = $configAll["lobbyspecters"]["y"];
			$lobbyspZ = $configAll["lobbyspecters"]["z"];
			$lobbyspPos = new Position($lobbyspX, $lobbyspY, $lobbyspZ, $arenaLevel);
			$player->teleport($lobbyspPos);
			$arenaLevel->loadChunk($lobbyspPos->getFloorX(), $lobbyspPos->getFloorZ());
		}
		if (self::getRole($player, $arena) === "Murderer") {
			Murder::$data["players"][$arenaName]["Murder"][$playerName] = "Dead";
		} elseif (self::getRole($player, $arena) === "Detective") {
			Murder::$data["players"][$arenaName]["Detective"][$playerName] = "Dead";
			EntityManager::setNPCPoliceHat($player);
		} elseif (self::getRole($player, $arena) === "Inoccent") {
			Murder::$data["players"][$arenaName]["Inoccent"][$playerName] = "Dead";
		}
		$player->getEffects()->clear();
		$player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 1, 40, true));
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->getInventory()->setItem(4, VanillaItems::HEART_OF_THE_SEA()->setCustomName("§r§l§9Play Again!"));
		PluginUtils::ModifyStats($playerName, "GAMESPLAYED", "add", 1);
		PluginUtils::ModifyStats($playerName, "LOSSES", "add", 1);
		PluginUtils::ModifyStats($playerName, "DEATHS", "add", 1);
		foreach ($arenaLevel->getPlayers() as $players) {
			PluginUtils::PlaySound($players, "game.player.hurt");
		}
		EntityManager::setNPCTomb($player);
	}

	public static function addArena(Player $player, string $arena, string $slots, string $id) {
		$player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
		if (Server::getInstance()->getWorldManager()->isWorldLoaded($arena)) {
			Server::getInstance()->getWorldManager()->unloadWorld(Server::getInstance()->getWorldManager()->getWorldByName($arena));
		}
		PluginUtils::setZip($arena);
		Murder::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $arena) : void{
			Server::getInstance()->getWorldManager()->loadWorld($arena);
			Server::getInstance()->getWorldManager()->getWorldByName($arena)->loadChunk(Server::getInstance()->getWorldManager()->getWorldByName($arena)->getSafeSpawn()->getFloorX(), Server::getInstance()->getWorldManager()->getWorldByName($arena)->getSafeSpawn()->getFloorZ());
			$player->teleport(Server::getInstance()->getWorldManager()->getWorldByName($arena)->getSafeSpawn(), 0, 0);
			$player->setGamemode(GameMode::CREATIVE());
		}), 40);
		Murder::$data['id'] = $id;
		Murder::$data['configurator'][] = $player->getName();
		Murder::$data['coins'][$arena] = ['Steve' => 0, 'Enderman' => 0];

		Murder::$data["arenaconfigs"][Murder::$data['id']]["arena"] = $arena;
		Murder::$data["arenaconfigs"][Murder::$data['id']]["maxslots"] = $slots;
		Murder::$data["arenaconfigs"][Murder::$data['id']]["status"] = 'editing';
		Murder::$data["arenaconfigs"][Murder::$data['id']]["lobbytime"] = 40;
		Murder::$data["arenaconfigs"][Murder::$data['id']]["startingtime"] = 11;
		Murder::$data["arenaconfigs"][Murder::$data['id']]["gametime"] = 600;
		Murder::$data["arenaconfigs"][Murder::$data['id']]["endtime"] = 16;
		Arena::saveArenaConfigs($id);
		$player->setGamemode(GameMode::CREATIVE());
		$player->sendMessage(Murder::getPrefix() . '§aArena created successfully.' . "\n" . '§aYou are now in configuration mode.');
	}

	public static function saveArenaConfigs(string $id) {
		$config = Murder::getConfigs('Arenas/Murder-' . $id);
		$config->setAll(Murder::$data["arenaconfigs"][$id]);
		$config->save();
	}

	public static function tpRandomSpawn(Player $player, string $arena) {
		$arenaLevel = Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena));
		$spawns = [];
		$config = Murder::getConfigs('Arenas/' . $arena);
		$configAll = $config->getAll();
		$expectedSpawns = $config->get("spawns");
		if($expectedSpawns != null) {
			foreach ($expectedSpawns as $expectedSpawn => $data) {
				$spawns[] = $expectedSpawn;
			}
		}
		$newSpawn = $spawns[array_rand($spawns)];
		$spawnX = $configAll["spawns"][$newSpawn]["x"];
		$spawnY = $configAll["spawns"][$newSpawn]["y"];
		$spawnZ = $configAll["spawns"][$newSpawn]["z"];
		$spawnPos = new Position($spawnX, $spawnY, $spawnZ, $arenaLevel);
		$arenaLevel->loadChunk($spawnPos->getFloorX(), $spawnPos->getFloorZ());
		$player->teleport($spawnPos);
	}

	public static function setRoles(string $arena) {
		$allPlayers = self::getPlayers($arena);
		$arenaName = self::getName($arena);
		//var_export($allPlayers);
		$randomPlayer = array_rand($allPlayers, 2); //Get two random players...
		$randomPlayer1 = Murder::getInstance()->getServer()->getPlayerExact($allPlayers[$randomPlayer[0]]);
		//Set Murderer Role to Random player 1
		Murder::$data["players"][$arenaName]["Murder"][$randomPlayer1->getName()] = "Alive";
		PluginUtils::PlaySound($randomPlayer1, "armor.equip_iron");
		$randomPlayer1->sendTitle("§cViltrumite");
		$randomPlayer1->setFlying(true);
		$randomPlayer1->sendSubTitle("§6Eliminate the other players!");
		$randomPlayer1->sendMessage(
			"§l§c» Viltrumite" . "\n" .
			"§l§c» §r§fKill everyone! Don't let the players escape with the ender eyes!"
		);
		$randomPlayer1->getInventory()->setItem(1, VanillaItems::IRON_SWORD()->setCustomName("§r§cKiller Weapon"));
		foreach ($allPlayers as $player) {
			$player = Murder::getInstance()->getServer()->getPlayerExact($player);
			$playerName = $player->getName();
			if (self::getRole($player, $arena) === null) {
				Murder::$data["players"][$arenaName]["Inoccent"][$playerName] = "Alive";
				PluginUtils::PlaySound($player, "mob.villager.yes");
				$player->addTitle("§aInoccent", "§bTry to survive!");
				$player->sendMessage(
					"§l§a» Survivors" . "\n" .
					"§l§a» §r§fTry to survive! §7Collect Eyes of Ender and escape to the End Portal."
				);
			}
		}
	}

	public static function getRole(Player $player, string $arena) {
		$arenaName = self::getName($arena);
		$playerName = $player->getName();
		if (isset(Murder::$data["players"][$arenaName]["Inoccent"][$playerName])) {
			return "Inoccent";
		}
		if (isset(Murder::$data["players"][$arenaName]["Murder"][$playerName])) {
			return "Murderer";
		}
		return null;
	}
    public static function getLobbyName(string $arena) : string {
    $config = Murder::getConfigs('Arenas/' . $arena);
    return (string) $config->getNested('lobby.world', "default_lobby");
}

/*
	 public static function getLobbyName(string $arena) : string { //Return Arena Name (World Name)
		$config = Murder::getConfigs('Arenas/' . $arena);
		return $config->getNested('lobby.world');
	}
    */

	public static function getName(string $arena) : string { //Return Arena Name (World Name)
		$config = Murder::getConfigs('Arenas/' . $arena);
		return $config->get('arena');
	}

	public static function getMaxSlots(string $arena) : string {
		$config = Murder::getConfigs('Arenas/' . $arena);
		return $config->get('maxslots');
	}

	public static function setStatus(string $arena, string $value) {
		$config = Murder::getConfigs('Arenas/' . $arena);
		$config->set('status', $value);
		$config->save();
	}

	public static function getStatus(string $arena) : string {
		$config = Murder::getConfigs('Arenas/' . $arena);
		return $config->get('status');
	}

	public static function setTimeWaiting(string $arena, int $value) {
		$config = Murder::getConfigs('Arenas/' . $arena);
		$config->set('lobbytime', $value);
		$config->save();
	}

	public static function getTimeWaiting(string $arena) {
		$config = Murder::getConfigs('Arenas/' . $arena);
		return $config->get('lobbytime');
	}

	public static function setTimeStarting(string $arena, int $value) {
		$config = Murder::getConfigs('Arenas/' . $arena);
		$config->set('startingtime', $value);
		$config->save();
	}

	public static function getTimeStarting(string $arena) {
		$config = Murder::getConfigs('Arenas/' . $arena);
		return $config->get('startingtime');
	}

	public static function setTimeGame(string $arena, int $value) {
		$config = Murder::getConfigs('Arenas/' . $arena);
		$config->set('gametime', $value);
		$config->save();
	}

	public static function getTimeGame(string $arena) {
		$config = Murder::getConfigs('Arenas/' . $arena);
		return $config->get('gametime');
	}

	public static function setTimeEnd(string $arena, int $value) {
		$config = Murder::getConfigs('Arenas/' . $arena);
		$config->set('endtime', $value);
		$config->save();
	}

	public static function getTimeEnd(string $arena) {
		$config = Murder::getConfigs('Arenas/' . $arena);
		return $config->get('endtime');
	}
}
