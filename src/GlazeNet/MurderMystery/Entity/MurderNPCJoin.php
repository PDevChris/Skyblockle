<?php


declare(strict_types=1);

namespace GlazeNet\MurderMystery\Entity;

use GlazeNet\MurderMystery\Arena\Arena;
use pocketmine\color\Color;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;

use pocketmine\Server;
use function array_push;
use function count;
use function rand;

class MurderNPCJoin extends Human {

	private array $collided = [];

	/**
	 * MainEntity constructor.
	 */
	public function __construct(Location $location, Skin $skin, CompoundTag $nbt) {
		parent::__construct($location, $skin, $nbt);
		$this->setNameTagAlwaysVisible(true);
		$this->setNameTagVisible(true);
	}

	public function getName() : string {
		return '';
	}

	public function onUpdate(int $currentTick) : bool {
		if ($this->getScale() != 1.2) {
			$this->setScale(1.2);
		}
		$this->setNoClientPredictions(true);
		$this->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 1, 255, true, false, new Color(rand(0, 255), rand(0, 255), rand(0, 255))));
		$this->setNameTag(
			    "§l§aSEASONAL GAME\n" .
			    "§l" . SeasonManager::getSeasonTitle() . "\n" .
			    "§b" . $this->getAllPlayers() . " Boarding!"
			);
		return parent::onUpdate($currentTick);
	}

	public function getAllPlayers() : int {
		$players = [];
		foreach (Arena::getArenas() as $arena) {
			if (Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena)) !== null) {
				foreach (Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena))->getPlayers() as $player) {
					array_push($players, $player->getName());
				}
			}
		}
		return count($players);
	}

	public function getInitialGravity() : float {
		return 0.0;
	}

	public function getInitialDragMultiplier() : float {
		return 0.0;
	}
}


