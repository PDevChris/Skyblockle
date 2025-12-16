<?php

namespace GlazeNet\MurderMystery;

use GlazeNet\MurderMystery\API\ScoreAPI;
use GlazeNet\MurderMystery\Arena\Arena;
use GlazeNet\MurderMystery\Command\MurderCommand;
use GlazeNet\MurderMystery\Entity\MurderCoin;
use GlazeNet\MurderMystery\Entity\MurderLeadboard;
use GlazeNet\MurderMystery\Entity\MurderNPCJoin;
use GlazeNet\MurderMystery\Entity\MurderPoliceHat;
use GlazeNet\MurderMystery\Entity\MurderTomb;
use GlazeNet\MurderMystery\Tasks\GameScheduler;
use GlazeNet\MurderMystery\Tasks\NPCRotation;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\World;
use SQLite3;
use function count;
use function is_dir;
use function mkdir;

class Murder extends PluginBase {

	public $db = [];

	public static $instance;

	public static $score;

	public static $data = [
		'prefix' => '§b[§l§cMurder§r§b] §r',
		'id' => '',
		'vote' => [],
		'skins' => [],
		'players' => [],
		'coins' => [],
		'configurator' => [],
		'arenaconfigs' => [],
		'interactDelay' => [],
		'coinDelay' => [],
		'giveArow' => [],
		'dataCode' => [],
	];

	public function onLoad() : void {
		self::$instance = $this;
		self::$score = new ScoreAPI($this);
	}

	public function onEnable() : void {
		$this->loadDatabase();
		$this->saveResources();
		foreach (Arena::getArenas() as $arena) {
			if (count(Arena::getArenas()) >= 0) {
				if (Arena::getStatus($arena) != "disabled") {
					self::getReloadArena($arena);
					ResetMap::resetZip(Arena::getName($arena));
				}
			}
		}
		$this->loadEntitys();
		$this->loadCommands();
		$this->loadEvents();
		$this->loadTasks();
		$this->getLogger()->info('§aSeasonal Games loaded succesfully');
	}

	public static function getInstance() : self {
		return self::$instance;
	}

	public static function getPrefix() : string {
		return self::$data['prefix'];
	}
	public static function getScore() : ScoreAPI {
		return self::$score;
	}

	public static function getConfigs(string $value) : config {
		return new Config(self::getInstance()->getDataFolder() . "{$value}.yml", Config::YAML);
	}

	public static function getReloadArena(string $arena) {
		$config = self::getConfigs('Arenas/' . $arena);
		self::$data['coins'][Arena::getName($arena)] = ['Steve' => 0, 'Enderman' => 0];
		if (Arena::getStatus($arena) !== "waitingcode") {
			$config->set('status', 'waiting');
		}
		$config->set('lobbytime', 40);
		$config->set('startingtime', 11);
		$config->set('gametime', 300);
		$config->set('endtime', 16);
		$config->save();
	}

	public function saveResources() : void {
		$folder = $this->getDataFolder();
		foreach([$folder, $folder . 'Arenas', $folder . 'Backups'] as $dir) {
			if (!is_dir($dir)) {
				@mkdir($dir);
			}
		}
		$this->saveResource('Entities/Geometries/TombGeometry.json');
		$this->saveResource('Entities/Geometries/PoliceHatGeometry.json');
		$this->saveResource('Entities/Geometries/CoinGeometry.json');
		$this->saveResource('Entities/Geometries/MurderGeometry.json');
		$this->saveResource('Entities/Skins/Tomb.png');
		$this->saveResource('Entities/Skins/PoliceHat.png');
		$this->saveResource('Entities/Skins/Coin.png');
		$this->saveResource('Entities/Skins/MurderNPC.png');
	}

	public function loadDatabase() : void {
		$this->db = new SQLite3($this->getDataFolder() . "Murder.db");
		# Stats Database
		$this->db->exec('CREATE TABLE IF NOT EXISTS MurderStats (
			player TEXT NOT NULL,
			gamesPlayed INT NOT NULL,
			wins INT NOT NULL,
			losses INT NOT NULL,
			kills INT NOT NULL,
			deaths INT NOT NULL,
			murdererEliminations INT NOT NULL,
			UNIQUE(player)
		)');
		# Codes Database
		$this->db->exec('CREATE TABLE IF NOT EXISTS MurderCodes (
			code TEXT NOT NULL,
			arena TEXT NOT NULL,
			creator TEXT NOT NULL,
			UNIQUE(code)
		)');
		# MurderRanks Database //TODO
	}

	public function loadEntitys() : void {
		EntityFactory::getInstance()->register(MurderNPCJoin::class, function(World $world, CompoundTag $nbt) : MurderNPCJoin{
			return new MurderNPCJoin(EntityDataHelper::parseLocation($nbt, $world), MurderNPCJoin::parseSkinNBT($nbt), $nbt);
		}, ["glaze:murder_npc"]);
		EntityFactory::getInstance()->register(MurderLeadboard::class, function(World $world, CompoundTag $nbt) : MurderLeadboard{
			return new MurderLeadboard(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ["glaze:murder_leaderboard"]);
		EntityFactory::getInstance()->register(MurderTomb::class, function(World $world, CompoundTag $nbt) : MurderTomb{
			return new MurderTomb(EntityDataHelper::parseLocation($nbt, $world), MurderTomb::parseSkinNBT($nbt), $nbt);
		}, ["glaze:murder_tomb"]);
		EntityFactory::getInstance()->register(MurderPoliceHat::class, function(World $world, CompoundTag $nbt) : MurderPoliceHat{
			return new MurderPoliceHat(EntityDataHelper::parseLocation($nbt, $world), MurderPoliceHat::parseSkinNBT($nbt), $nbt);
		}, ["glaze:murder_policehat"]);
		EntityFactory::getInstance()->register(MurderCoin::class, function(World $world, CompoundTag $nbt) : MurderCoin{
			return new MurderCoin(EntityDataHelper::parseLocation($nbt, $world), MurderCoin::parseSkinNBT($nbt), $nbt);
		}, ["glaze:murder_coin"]);
		unset ($values);
	}

	public function loadCommands() : void {
		$values = [new MurderCommand($this)];
		foreach ($values as $commands) {
			$this->getServer()->getCommandMap()->register('_cmd', $commands);
		}
		unset($values);
	}

	public function loadEvents() : void {
		$values = [new EventListener($this)];
		foreach ($values as $events) {
			$this->getServer()->getPluginManager()->registerEvents($events, $this);
		}
		unset($values);
	}

	public function loadTasks() : void {
		$this->getScheduler()->scheduleRepeatingTask(new GameScheduler($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new NPCRotation($this), 5);
	}
}


