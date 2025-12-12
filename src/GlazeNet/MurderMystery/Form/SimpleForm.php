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

declare(strict_types = 1);

namespace GlazeNet\MurderMystery\Form;

use function count;

class SimpleForm extends Form {

	const IMAGE_TYPE_PATH = 0;
	const IMAGE_TYPE_URL = 1;

	/** @var string */
	private $content = "";

	private $labelMap = [];

	public function __construct(?callable $callable) {
		parent::__construct($callable);
		$this->data["type"] = "form";
		$this->data["title"] = "";
		$this->data["content"] = $this->content;
		$this->data["buttons"] = [];
	}

	public function processData(&$data) : void {
		$data = $this->labelMap[$data] ?? null;
	}

	public function setTitle(string $title) : void {
		$this->data["title"] = $title;
	}

	public function getTitle() : string {
		return $this->data["title"];
	}

	public function getContent() : string {
		return $this->data["content"];
	}

	public function setContent(string $content) : void {
		$this->data["content"] = $content;
	}

	/**
	 * @param string $label
	 */
	public function addButton(string $text, int $imageType = -1, string $imagePath = "", ?string $label = null) : void {
		$content = ["text" => $text];
		if($imageType !== -1) {
			$content["image"]["type"] = $imageType === 0 ? "path" : "url";
			$content["image"]["data"] = $imagePath;
		}
		$this->data["buttons"][] = $content;
		$this->labelMap[] = $label ?? count($this->labelMap);
	}

}
