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

namespace GlazeNet\MurderMystery\Entity;

use GlazeNet\MurderMystery\PluginUtils;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;

use pocketmine\nbt\tag\CompoundTag;
use function str_repeat;

class MurderLeadboard extends Human {

	public function __construct(Location $location, CompoundTag $nbt) {
		parent::__construct($location, new Skin('Standard_Custom', str_repeat("\x00", 8192)), $nbt);
		$this->setNameTagAlwaysVisible(true);
	}

	public function entityBaseTick(int $tickDiff = 1) : bool {
		$this->setNameTag($this->getLeaderboardText());
		return parent::entityBaseTick($tickDiff);
	}

	private function getLeaderboardText() : string {
		return PluginUtils::getMurderLeadboard();
	}
}
