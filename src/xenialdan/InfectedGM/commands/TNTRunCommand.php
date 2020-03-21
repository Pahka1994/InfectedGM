<?php

declare(strict_types=1);

namespace xenialdan\InfectedGM\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use xenialdan\gameapi\API;
use xenialdan\gameapi\Game;
use xenialdan\InfectedGM\Loader;

class InfectedGMCommand extends PluginCommand
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct("infectedGM", $plugin);
        $this->setPermission("infectedGM.command");
        $this->setDescription("InfectedGM commands for setup or leaving a game");
        $this->setUsage("/infectedGM | /infectedGM setup | /infectedGM endsetup | /infectedGM leave | /infectedGM forcestart | /infectedGM stop | /infectedGM status | /infectedGM info");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        /** @var Player $sender */
        $return = $sender->hasPermission($this->getPermission());
        if (!$return) {
            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
            return true;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command is for players only");
            return false;
        }
        try {
            $return = true;
            switch ($args[0] ?? "setup") {
                case "setup":
                    {
                        if (!$sender->hasPermission("infectedGM.command.setup")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        /** @var Game $p */
                        $p = $this->getPlugin();
                        $p->setupArena($sender);
                        break;
                    }
                case "leave":
                    {
                        if (!$sender->hasPermission("infectedGM.command.leave")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        $arena = API::getArenaOfPlayer($sender);
                        if (is_null($arena) || !API::isArenaOf($this->getPlugin(), $arena->getLevel())) {
                            /** @var Game $plugin */
                            $plugin = $this->getPlugin();
                            $sender->sendMessage(TextFormat::RED . "It appears that you are not playing " . $plugin->getPrefix());
                            return true;
                        }
                        if (API::isPlaying($sender, $this->getPlugin())) $arena->removePlayer($sender);
                        break;
                    }
                case "endsetup":
                    {
                        if (!$sender->hasPermission("infectedGM.command.endsetup")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        /** @var Game $p */
                        $p = $this->getPlugin();
                        $p->endSetupArena($sender);
                        break;
                    }
                case "stop":
                    {
                        if (!$sender->hasPermission("infectedGM.command.stop")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        API::getArenaByLevel(Loader::getInstance(), $sender->getLevel())->stopArena();
                        break;
                    }
                case "forcestart":
                    {
                        if (!$sender->hasPermission("infectedGM.command.forcestart")) {
                            $sender->sendMessage(TextFormat::RED . "You do not have permissions to run this command");
                            return true;
                        }
                        $arena = API::getArenaOfPlayer($sender);
                        if (is_null($arena) || !API::isArenaOf($this->getPlugin(), $arena->getLevel())) {
                            /** @var Game $plugin */
                            $plugin = $this->getPlugin();
                            $sender->sendMessage(TextFormat::RED . "It appears that you are not playing " . $plugin->getPrefix());
                            return true;
                        }
                        $arena->startTimer($arena->getOwningGame());
                        $arena->forcedStart = true;
                        $arena->setTimer(5);
                        $sender->getServer()->broadcastMessage("Arena will start immediately due to a forced start by " . $sender->getDisplayName(), $arena->getPlayers());
                        break;
                    }
                default:
                    {
                        $return = false;
                        throw new \InvalidArgumentException("Unknown argument supplied: " . $args[0]);
                    }
            }
        } catch (\Throwable $error) {
            $this->getPlugin()->getLogger()->logException($error);
            $return = false;
        } finally {
            return $return;
        }
    }
}
