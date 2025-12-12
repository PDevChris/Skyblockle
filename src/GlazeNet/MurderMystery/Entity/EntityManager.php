<?php

declare(strict_types=1);

namespace GlazeNet\MurderMystery\Entity;

use GlazeNet\MurderMystery\Murder;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;

use pocketmine\player\PLayer;
use function chr;
use function file_get_contents;
use function getimagesize;
use function imagecolorat;
use function imagecreatefrompng;
use function imagedestroy;

final class EntityManager {

	/**
	 * Helper function which creates minimal NBT needed to spawn an entity.
	 */
	public static function createBaseNBT(Location|Position $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0) : CompoundTag{
		return $nbt = CompoundTag::create()
			->setTag("Pos", new ListTag([
				new DoubleTag($pos->x),
				new DoubleTag($pos->y),
				new DoubleTag($pos->z)
			]))
			->setTag("Motion", new ListTag([
				new DoubleTag($motion !== null ? $motion->x : 0.0),
				new DoubleTag($motion !== null ? $motion->y : 0.0),
				new DoubleTag($motion !== null ? $motion->z : 0.0)
			]))
			->setTag("Rotation", new ListTag([
				new FloatTag($pos->getYaw()),
				new FloatTag($pos->getPitch())
			]));
	}

	public static function setNPCJoin(Player $player) {
		$nbt = EntityManager::createBaseNBT($player->getLocation(), null, 2, 2);
		$dir = Murder::getInstance()->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Skins" . DIRECTORY_SEPARATOR . "MurderNPC.png";
		$img = @imagecreatefrompng($dir);
		$skinbytes = '';
		$values = (int) @getimagesize($dir)[1];
		for($y = 0; $y < $values; $y++) {
			for($x = 0; $x < 64; $x++) {
				$bytes = @imagecolorat($img, $x, $y);
				$a = ((~((int) ($bytes >> 24))) << 1) & 0xff;
				$b = ($bytes >> 16) & 0xff;
				$c = ($bytes >> 8) & 0xff;
				$d = $bytes & 0xff;
				$skinbytes .= chr($b) . chr($c) . chr($d) . chr($a);
			}
		}
		@imagedestroy($img);
		$skin = new Skin("MurderNPC", $skinbytes, "", "geometry.glaze.npc.murder", file_get_contents(Murder::getInstance()->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Geometries" . DIRECTORY_SEPARATOR . "MurderGeometry.json"));
		$npc = new MurderNPCJoin($player->getLocation(), $skin, $nbt);
		$npc->setNameTagAlwaysVisible(true);
		$npc->setNameTagVisible(true);
		$npc->yaw = $player->getLocation()->getYaw();
		$npc->spawnToAll();
	}

	public static function setNPCLeadboard(Player $player) {
		$nbt = EntityManager::createBaseNBT($player->getLocation(), null, 2, 2);
		$npc = new MurderLeadboard($player->getLocation(), $nbt);
		$npc->setNameTag('');
		$npc->setNameTagAlwaysVisible(true);
		$npc->spawnToAll();
	}

	public static function setNPCPoliceHat(Player $player) {
		$nbt = EntityManager::createBaseNBT($player->getLocation(), null, 2, 2);
		$dir = Murder::getInstance()->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Skins" . DIRECTORY_SEPARATOR . "PoliceHat.png";
		$img = @imagecreatefrompng($dir);
		$skinbytes = '';
		$values = (int) @getimagesize($dir)[1];
		for($y = 0; $y < $values; $y++) {
			for($x = 0; $x < 64; $x++) {
				$bytes = @imagecolorat($img, $x, $y);
				$a = ((~((int) ($bytes >> 24))) << 1) & 0xff;
				$b = ($bytes >> 16) & 0xff;
				$c = ($bytes >> 8) & 0xff;
				$d = $bytes & 0xff;
				$skinbytes .= chr($b) . chr($c) . chr($d) . chr($a);
			}
		}
		@imagedestroy($img);
		$skin = new Skin("PoliceHat", $skinbytes, "", "geometry.geometry.policehat", file_get_contents(Murder::getInstance()->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Geometries" . DIRECTORY_SEPARATOR . "PoliceHatGeometry.json"));
		$npc = new MurderPoliceHat($player->getLocation(), $skin, $nbt);
		$npc->setNameTagAlwaysVisible(false);
		$npc->setNameTagVisible(false);
		$npc->yaw = $player->getLocation()->getYaw();
		$npc->spawnToAll();
	}

	public static function setNPCTomb(Player $player) {
		$nbt = EntityManager::createBaseNBT($player->getLocation(), null, 2, 2);
		$dir = Murder::getInstance()->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Skins" . DIRECTORY_SEPARATOR . "Tomb.png";
		$img = @imagecreatefrompng($dir);
		$skinbytes = '';
		$values = (int) @getimagesize($dir)[1];
		for($y = 0; $y < $values; $y++) {
			for($x = 0; $x < 64; $x++) {
				$bytes = @imagecolorat($img, $x, $y);
				$a = ((~((int) ($bytes >> 24))) << 1) & 0xff;
				$b = ($bytes >> 16) & 0xff;
				$c = ($bytes >> 8) & 0xff;
				$d = $bytes & 0xff;
				$skinbytes .= chr($b) . chr($c) . chr($d) . chr($a);
			}
		}
		@imagedestroy($img);
		$skin = new Skin("Tomb", $skinbytes, "", "geometry.geometry.tomb", file_get_contents(Murder::getInstance()->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Geometries" . DIRECTORY_SEPARATOR . "TombGeometry.json"));
		$npc = new MurderTomb($player->getLocation(), $skin, $nbt);
		$npc->setNameTagAlwaysVisible(false);
		$npc->setNameTagVisible(false);
		$npc->yaw = $player->getLocation()->getYaw();
		$npc->spawnToAll();
	}

	public static function setNPCCoin($coinPos) {
		$nbt = EntityManager::createBaseNBT($coinPos, null, 2, 2);
		$dir = Murder::getInstance()->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Skins" . DIRECTORY_SEPARATOR . "Coin.png";
		$img = @imagecreatefrompng($dir);
		$skinbytes = '';
		$values = (int) @getimagesize($dir)[1];
		for($y = 0; $y < $values; $y++) {
			for($x = 0; $x < 64; $x++) {
				$bytes = @imagecolorat($img, $x, $y);
				$a = ((~((int) ($bytes >> 24))) << 1) & 0xff;
				$b = ($bytes >> 16) & 0xff;
				$c = ($bytes >> 8) & 0xff;
				$d = $bytes & 0xff;
				$skinbytes .= chr($b) . chr($c) . chr($d) . chr($a);
			}
		}
		@imagedestroy($img);
		$count = 0;
		foreach($coinPos->getWorld()->getEntities() as $entity){
			if($entity instanceof MurderCoin){
				$count += 1;
			}
		}
		$skin = new Skin("{$count}", $skinbytes, "", "geometry.geometry.coin", file_get_contents(Murder::getInstance()->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Geometries" . DIRECTORY_SEPARATOR . "CoinGeometry.json"));
		$npc = new MurderCoin($coinPos, $skin, $nbt);
		$npc->setNameTagAlwaysVisible(false);
		$npc->spawnToAll();
	}
}

