<?php

declare(strict_types=1);

namespace GlazeNet\MurderMystery;

use GlazeNet\MurderMystery\{Murder};
use pocketmine\Server;
use function basename;
use function file_exists;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function scandir;

use function unlink;

class ResetMap {

	public static function resetZip(string $arena) {
		if (Server::getInstance()->getWorldManager()->isWorldLoaded($arena)) {
			Server::getInstance()->getWorldManager()->unloadWorld(Server::getInstance()->getWorldManager()->getWorldByName($arena));
		}
		//Delete World
		if(file_exists(Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $arena)){
			self::removeDir(Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $arena);
		}

		if(!file_exists(Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $arena)){
			@mkdir(Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $arena);
		}
		//Set Zip
		$zipPath = Murder::getInstance()->getDataFolder() . 'Backups' . DIRECTORY_SEPARATOR . $arena . '.zip';
		$zip = new \PhpZip\ZipFile();
		try {
			$zip->openFile($zipPath);
			$zip->extractTo(Murder::getInstance()->getServer()->getDataPath() . 'worlds' . DIRECTORY_SEPARATOR . $arena);
		}catch(\PhpZip\Exception\ZipException $e){
			Server::getInstance()->getLogger()->error("An error ocurred while extracting the zip file: " . $zipPath . ", Reason: " . $e->getMessage());
		}finally{
			$zip->close();
		}
		Server::getInstance()->getWorldManager()->loadWorld($arena);
		Murder::getInstance()->getLogger()->info('§a' . $arena . ' §barena has been reset successfully.');
		return true;
	}

	public static function removeDir(string $dirPath) {
		if (basename($dirPath) == "." || basename($dirPath) == "..") {
			return;
		}
		foreach (scandir($dirPath) as $item) {
			if ($item != "." || $item != "..") {
				if (is_dir($dirPath . DIRECTORY_SEPARATOR . $item)) {
					self::removeDir($dirPath . DIRECTORY_SEPARATOR . $item);
				}
				if (is_file($dirPath . DIRECTORY_SEPARATOR . $item)) {
					self::removeFile($dirPath . DIRECTORY_SEPARATOR . $item);
				}
			}
		}
		rmdir($dirPath);
	}

	public static function removeFile(string $path) {
		unlink($path);
	}
}

