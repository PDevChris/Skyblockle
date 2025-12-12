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

namespace GlazeNet\MurderMystery\Code;

use GlazeNet\MurderMystery\Arena\Arena;
use GlazeNet\MurderMystery\Murder;

use pocketmine\player\GameMode;
use pocketmine\Server;
use function array_rand;
use function count;

class CodeManager {

	public static function getCodes() : array { //Return Codes Name
		$codes = [];
		$codesInfo = Murder::getInstance()->db->query("SELECT * FROM MurderCodes;");
		$i = -1;
		while ($resultArr = $codesInfo->fetchArray(SQLITE3_ASSOC)) {
			$j = $i + 1;
			$code = $resultArr['code'];
			$codes[] = $code;
			$i = $i + 1;
		}
		return $codes;
	}

	public static function getAvailableArenasToCode () : array { // Return Arena ID
		$arenas = [];
		$allArenas = Arena::getArenas();
		if (count($allArenas) > 0) {
			foreach ($allArenas as $arena) {
				if (Arena::getStatus($arena) === "waiting") {
					if (count(Arena::getPlayers($arena)) < Arena::getMaxSlots($arena)) {
						$arenas[] = $arena;
					}
				} elseif (Arena::getStatus($arena) === "disabled") {
					$arenas[] = $arena;
				}
			}
		}
		return $arenas;
	}

	public static function getCodeOfArena($arena) { //If the arena is on some code will return the code, else return null.
		$code = null;
		foreach (self::getCodes() as $Code) {
			if (self::getFromCodesDB($Code, "arena") === $arena) {
				$code = $Code;
			}
		}
		return $code;
	}

	public static function codeExist($codeName) : bool {
		$query = Murder::getInstance()->db->querySingle("SELECT code FROM MurderCodes WHERE code = '$codeName'");
		if ($query === null) {
			return false;
		}
		return true;
	}

	public static function createCode($codeName, string $arena, $creator = null) {
		// Remove players from arena
		foreach (Server::getInstance()->getWorldManager()->getWorldByName(Arena::getName($arena))->getPlayers() as $players) {
			$players->sendMessage(
				"§l§9» §r§bA code has been created for this arena!" . "\n" .
				"§l§9» §r§aConnecting to the lobby..."
			);
			$players->setGamemode(GameMode::ADVENTURE());
			$players->setNameTagAlwaysVisible();
			$players->getInventory()->clearAll();
			$players->getArmorInventory()->clearAll();
			$players->getCursorInventory()->clearAll();
			$players->teleport(Server::getInstance()->getDefaultLevel()->getSafeSpawn());
		}
		// Set Status
		Arena::setStatus($arena, "waitingcode");
		// Create Code in DataBase
		$dbInfo = Murder::getInstance()->db->prepare("INSERT OR IGNORE INTO MurderCodes(code,arena,creator) SELECT :code, :arena, :creator WHERE NOT EXISTS(SELECT * FROM MurderCodes WHERE code = :code);");
		$dbInfo->bindValue(":code", $codeName, SQLITE3_TEXT);
		$dbInfo->bindValue(":arena", $arena, SQLITE3_TEXT);
		$dbInfo->bindValue(":creator", $creator, SQLITE3_TEXT);
		$dbInfo->execute();
	}

	public static function removeCodeFromDB($codeName) {
		Murder::getInstance()->db->query("DELETE FROM MurderCodes WHERE code = '$codeName';");
	}

	public static function getFromCodesDB($codeName, $value) {
		switch ($value) {
			case 'arena':
				return Murder::getInstance()->db->querySingle("SELECT arena FROM MurderCodes WHERE code = '$codeName'");
			break;

			case 'creator':
				return Murder::getInstance()->db->querySingle("SELECT creator FROM MurderCodes WHERE code = '$codeName'");
			break;
		}
	}

	public static function getRandomCode() {
		switch (self::getRandomNumber()) {
			case '1':
				return self::getRandomNumber() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomNumber();
			break;

			case '2':
				return self::getRandomLetter() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomLetter();
			break;

			case '3':
				return self::getRandomNumber() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomNumber();
			break;

			case '4':
				return self::getRandomLetter() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomNumber();
			break;

			case '5':
				return self::getRandomNumber() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomLetter();
			break;

			case '6':
				return self::getRandomNumber() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomLetter();
			break;

			case '7':
				return self::getRandomLetter() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomNumber();
			break;

			case '8':
				return self::getRandomNumber() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomNumber() . self::getRandomNumber();
			break;

			case '9':
				return self::getRandomNumber() . self::getRandomNumber() . self::getRandomLetter() . self::getRandomLetter() . self::getRandomNumber() . self::getRandomLetter();
			break;
		}
	}

	public static function getRandomLetter() {
		$letters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"];
		return $letters[array_rand($letters)];
	}

	public static function getRandomNumber() {
		$numbers = ["1", "2", "3", "4", "5", "6", "7", "8", "9"];
		return $numbers[array_rand($numbers)];
	}
}
