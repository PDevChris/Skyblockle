<?php


declare(strict_types=1);

namespace GlazeNet\MurderMystery;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;

use function array_search;
use function count;
use function date;
use function gmdate;
use function is_file;
use function is_numeric;
use function time;
use function unlink;

class PluginUtils {

	public static function getTimeParty(int $value) {
		return gmdate("i:s", $value);
	}

	public static function setZip(string $arena) {
		$worldPath = Murder::getInstance()->getServer()->getDataPath() . 'worlds' . DIRECTORY_SEPARATOR . $arena;
		$zipPath = Murder::getInstance()->getDataFolder() . 'Backups' . DIRECTORY_SEPARATOR . $arena . '.zip';
		if (is_file($zipPath)) {
			unlink($zipPath);
		}
		$zip = new \PhpZip\ZipFile();
		try {
			$zip->addDirRecursive($worldPath);
		}catch(\PhpZip\Exception\ZipException $e){
			Server::getInstance()->getLogger()->error("An error ocurred while archiving the zip file: " . $worldPath . ", Reason: " . $e->getMessage());
		}finally{
			$zip->saveAsFile($zipPath);
			$zip->close();
		}
	}

	public static function PlaySound(Player $player, string $sound, $volume = 1, $pitch = 1) {
		$pk = new PlaySoundPacket();
		$pk->x = $player->getLocation()->getX();
		$pk->y = $player->getLocation()->getY();
		$pk->z = $player->getLocation()->getZ();
		$pk->soundName = $sound;
		$pk->volume = $volume;
		$pk->pitch = $pitch;
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public static function calculateNearestPlayer(Player $player) : ?Player {
		$closest = null;
		if ($player instanceof Position) {
			$lastSquare = -1;
			$onLevelPlayer = $player->getWorld()->getPlayers();
			unset($onLevelPlayer[array_search($player, $onLevelPlayer, true)]);
			foreach ($onLevelPlayer as $p) {
				$square = $player->distanceSquared($p);
				if ($p->getGamemode() === 0 || $p->getGamemode() === 2) {
					if ($lastSquare === -1 || $lastSquare > $square) {
						$closest = $p;
						$lastSquare = $square;
					}
				}
			}
		}
		return $closest;
	}

	public static function verifyInteractDelay(Player $player) {
		$playerName = $player->getName();
		if (!isset(Murder::$data["interactDelay"][$playerName])) {
			Murder::$data["interactDelay"][$playerName] = time() + 1;
			return false;
		} else {
			if (time() >= Murder::$data["interactDelay"][$playerName]) {
				unset(Murder::$data["interactDelay"][$playerName]);
			}
			return true;
		}
	}

	public static function verifyPlayerInDB($playerName) : bool {
		$query = Murder::getInstance()->db->querySingle("SELECT player FROM MurderStats WHERE player = '$playerName'");
		if ($query === null) {
			return false;
		}
		return true;
	}

	public static function isMonthEnd() : bool {
		if (date("d") === date("t")) {
			return true;
		}
		return false;
	}

	/**
	 * Configure leaderboard
	 * @return string
	 */
	public static function getMurderLeadboard() {
		$leaderboard = [];
		$result = null;
		$result = Murder::getInstance()->db->query("SELECT player, wins FROM MurderStats ORDER BY wins DESC LIMIT 10");
		if ($result === null) {
			return '';
		}
		$index = 0;
		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
			$leaderboard[$index++] = $row;
		}
		$count = count($leaderboard);
		$break = "\n";
		if ($count > 0) {
			$top1 = "§6#1 §f" . $leaderboard[0]['player'] . "  §7- §b" . $leaderboard[0]['wins'] . " §6wins";
		} else {
			$top1 = '';
		}
		if ($count > 1) {
			$top2 = "§3#2 §f" . $leaderboard[1]['player'] . "  §7- §b" . $leaderboard[1]['wins'] . " §3wins";
		} else {
			$top2 = '';
		}
		if ($count > 2) {
			$top3 = "§3#3 §f" . $leaderboard[2]['player'] . "  §7- §b" . $leaderboard[2]['wins'] . " §3wins";
		} else {
			$top3 = '';
		}
		if ($count > 3) {
			$top4 = "§3#4 §f" . $leaderboard[3]['player'] . "  §7- §b" . $leaderboard[3]['wins'] . " §3wins";
		} else {
			$top4 = '';
		}
		if ($count > 4) {
			$top5 = "§3#5 §f" . $leaderboard[4]['player'] . "  §7- §b" . $leaderboard[4]['wins'] . " §3wins";
		} else {
			$top5 = '';
		}
		if ($count > 5) {
			$top6 = "§3#6 §f" . $leaderboard[5]['player'] . "  §7- §b" . $leaderboard[5]['wins'] . " §3wins";
		} else {
			$top6 = '';
		}
		if ($count > 6) {
			$top7 = "§3#7 §f" . $leaderboard[6]['player'] . "  §7- §b" . $leaderboard[6]['wins'] . " §3wins";
		} else {
			$top7 = '';
		}
		if ($count > 7) {
			$top8 = "§3#8 §f" . $leaderboard[7]['player'] . "  §7- §b" . $leaderboard[7]['wins'] . " §3wins";
		} else {
			$top8 = '';
		}
		if ($count > 8) {
			$top9 = "§3#9 §f" . $leaderboard[8]['player'] . "  §7- §b" . $leaderboard[8]['wins'] . " §3wins";
		} else {
			$top9 = '';
		}
		if ($count > 9) {
			$top10 = "§3#10 §f" . $leaderboard[9]['player'] . "  §7- §b" . $leaderboard[9]['wins'] . " §3wins";
		} else {
			$top10 = '';
		}
		return "§l§f✘ §6Leaderboard §cMurder §f✘" . "\n" . $top1 . $break . $top2 . $break . $top3 . $break . $top4 . $break . $top5 . $break . $top6 . $break . $top7 . $break . $top8 . $break . $top9 . $break . $top10;
	}

	public static function addNewPLayer ($playerName) {
		$dbInfo = Murder::getInstance()->db->prepare("INSERT OR IGNORE INTO MurderStats(player,gamesPlayed,wins,losses,kills,deaths,murdererEliminations) SELECT :player, :gamesPlayed, :wins, :losses, :kills, :deaths, :murdererEliminations WHERE NOT EXISTS(SELECT * FROM MurderStats WHERE player = :player);");
		$dbInfo->bindValue(":player", $playerName, SQLITE3_TEXT);
		$dbInfo->bindValue(":gamesPlayed", 0, SQLITE3_NUM);
		$dbInfo->bindValue(":wins", 0, SQLITE3_NUM);
		$dbInfo->bindValue(":losses", 0, SQLITE3_NUM);
		$dbInfo->bindValue(":kills", 0, SQLITE3_NUM);
		$dbInfo->bindValue(":deaths", 0, SQLITE3_NUM);
		$dbInfo->bindValue(":murdererEliminations", 0, SQLITE3_NUM);
		$dbInfo->execute();
	}

	public static function deletePlayerFromDB ($playerName) {
		Murder::getInstance()->db->query("DELETE FROM MurderStats WHERE player = '$playerName';");
	}

	public static function ModifyStats ($playerName, $value, $mode, $amount) {
		if (!is_numeric($amount)) return;
		switch ($value) {
			case 'GAMESPLAYED':
				switch ($mode) {
					case 'add':
						$result = self::getFromStatsDB($playerName, $value) + $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `gamesPlayed`='$result' WHERE player='$playerName';");
					break;

					case 'remove':
						$result = self::getFromStatsDB($playerName, $value) - $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `gamesPlayed`='$result' WHERE player='$playerName';");
					break;

					case 'set':
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `gamesPlayed`='$amount' WHERE player='$playerName';");
					break;
				}
			break;

			case 'WINS':
				switch ($mode) {
					case 'add':
						$result = self::getFromStatsDB($playerName, $value) + $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `wins`='$result' WHERE player='$playerName';");
					break;

					case 'remove':
						$result = self::getFromStatsDB($playerName, $value) - $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `wins`='$result' WHERE player='$playerName';");
					break;

					case 'set':
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `wins`='$amount' WHERE player='$playerName';");
					break;
				}
			break;

			case 'LOSSES':
				switch ($mode) {
					case 'add':
						$result = self::getFromStatsDB($playerName, $value) + $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `losses`='$result' WHERE player='$playerName';");
					break;

					case 'remove':
						$result = self::getFromStatsDB($playerName, $value) - $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `losses`='$result' WHERE player='$playerName';");
					break;

					case 'set':
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `losses`='$amount' WHERE player='$playerName';");
					break;
				}
			break;

			case 'KILLS':
				switch ($mode) {
					case 'add':
						$result = self::getFromStatsDB($playerName, $value) + $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `kills`='$result' WHERE player='$playerName';");
					break;

					case 'remove':
						$result = self::getFromStatsDB($playerName, $value) - $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `kills`='$result' WHERE player='$playerName';");
					break;

					case 'set':
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `kills`='$amount' WHERE player='$playerName';");
					break;
				}
			break;

			case 'DEATHS':
				switch ($mode) {
					case 'add':
						$result = self::getFromStatsDB($playerName, $value) + $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `deaths`='$result' WHERE player='$playerName';");
					break;

					case 'remove':
						$result = self::getFromStatsDB($playerName, $value) - $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `deaths`='$result' WHERE player='$playerName';");
					break;

					case 'set':
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `deaths`='$amount' WHERE player='$playerName';");
					break;
				}
			break;

			case 'MURDERERELIMINATIONS':
				switch ($mode) {
					case 'add':
						$result = self::getFromStatsDB($playerName, $value) + $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `murdererEliminations`='$result' WHERE player='$playerName';");
					break;

					case 'remove':
						$result = self::getFromStatsDB($playerName, $value) - $amount;
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `murdererEliminations`='$result' WHERE player='$playerName';");
					break;

					case 'set':
						Murder::getInstance()->db->exec("UPDATE `MurderStats` SET `murdererEliminations`='$amount' WHERE player='$playerName';");
					break;
				}
			break;
		}
	}

	public static function getFromStatsDB ($playerName, $value) {
		switch ($value) {
			case 'GAMESPLAYED':
				return (int) Murder::getInstance()->db->querySingle("SELECT gamesPlayed FROM MurderStats WHERE player = '$playerName'");
			break;

			case 'WINS':
				return (int) Murder::getInstance()->db->querySingle("SELECT wins FROM MurderStats WHERE player = '$playerName'");
			break;

			case 'LOSSES':
				return (int) Murder::getInstance()->db->querySingle("SELECT losses FROM MurderStats WHERE player = '$playerName'");
			break;

			case 'KILLS':
				return (int) Murder::getInstance()->db->querySingle("SELECT kills FROM MurderStats WHERE player = '$playerName'");
			break;

			case 'DEATHS':
				return (int) Murder::getInstance()->db->querySingle("SELECT deaths FROM MurderStats WHERE player = '$playerName'");
			break;

			case 'MURDERERELIMINATIONS':
				return (int) Murder::getInstance()->db->querySingle("SELECT murdererEliminations FROM MurderStats WHERE player = '$playerName'");
			break;
		}
	}
}
