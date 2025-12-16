<?php

namespace GlazeNet\MurderMystery\Season;

use pocketmine\utils\Config;
use GlazeNet\MurderMystery\Murder;

final class SeasonManager {

    private static Config $config;

    public static function init() : void {
        @mkdir(Murder::getInstance()->getDataFolder());
        self::$config = new Config(
            Murder::getInstance()->getDataFolder() . "season.yml",
            Config::YAML,
            [
                "current" => "viltrumite_hunt"
            ]
        );
    }

    public static function setSeason(string $season) : void {
        self::$config->set("current", $season);
        self::$config->save();
    }

    public static function getSeason() : string {
        return self::$config->get("current", "viltrumite_hunt");
    }

    public static function getSeasonTitle() : string {
        return match (self::getSeason()) {
            "winter" => "§e§lMountain Hike",
            "summer" => "§c§lFlight Closed",
            "fall" => "§e§lMafia",
            "spring" => "§c§lFlight Closed",
            default => "§cFlight Closed",
        };
    }
}
