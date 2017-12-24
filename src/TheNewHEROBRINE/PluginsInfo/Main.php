<?php

namespace TheNewHEROBRINE\PluginsInfo;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\event\TranslationContainer;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase {

    /** @var CommandSender $target */
    private $target;

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!(isset($args[0]) or $command->getName() == "permissions"))
            return false;
        $this->target = $sender;
        switch ($command->getName()) {
            case "plugininfo":
                if (!$plugin = $sender->getServer()->getPluginManager()->getPlugin($args[0])) {
                    foreach ($this->getServer()->getPluginManager()->getPlugins() as $pl) {
                        if (stripos($pl->getName(), $args[0]) !== false) {
                            $plugin = $pl;
                            break;
                        }
                    }
                }

                if ($plugin) {
                    $desc = $plugin->getDescription();
                    $this->sendInfo("name", $desc->getName());
                    $this->sendInfo("authors", $desc->getAuthors());
                    $this->sendInfo("version", $desc->getVersion());
                    $this->sendInfo("apis", $desc->getCompatibleApis());
                    $this->sendInfo("main", $desc->getMain());
                    $this->sendInfo("descriptions", $desc->getDescription());
                    $this->sendInfo("mcpe-protocols", $desc->getCompatibleMcpeProtocols());
                    $this->sendInfo("dependencies", $desc->getDepend());
                    $this->sendInfo("softdependencies", $desc->getSoftDepend());
                    $this->sendInfo("loadbefore", $desc->getLoadBefore());
                    $this->sendInfo("website", $desc->getWebsite());
                    $this->sendInfo("extensions", $desc->getRequiredExtensions());
                    $this->sendInfo("prefix", $desc->getPrefix());
                    $this->sendInfo("loadorder", $desc->getOrder() ? "postworld" : "startup");
                    $this->sendInfo("commands", array_unique(array_map(function ($command) {
                        /** @var Command $command */
                        return $command->getName();
                    }, array_filter($cmds = $this->getServer()->getCommandMap()->getCommands(), function ($command) use ($plugin, $cmds) {
                        /** @var Command $command */
                        return $command instanceof PluginIdentifiableCommand and $command->getPlugin() === $plugin or isset($cmds[strtolower($plugin->getName() . ":" . $command->getName())]);
                    }))));
                    $this->sendInfo("permissions", array_map(function ($permission) {
                        /** @var Permission $permission */
                        return $permission->getName();
                    }, $desc->getPermissions()));
                } else {
                    $sender->sendMessage(new TranslationContainer("pocketmine.command.version.noSuchPlugin"));
                }
                return true;

            case "commandinfo":
                if ($command = $this->getServer()->getCommandMap()->getCommand($args[0])) {
                    $this->sendInfo("name", $command->getName());
                    if ($command instanceof VanillaCommand) {
                        $source = "pocketmine";
                    }else {
                        $source = "plugin";
                        if ($command instanceof PluginIdentifiableCommand) {
                            $plugin = $command->getPlugin();
                        }
                    }
                    $this->sendInfo("source", $source);
                    if (isset($plugin))
                        $this->sendInfo("plugin", $plugin->getName());
                    $this->sendInfo("usage", $command->getUsage());
                    $this->sendInfo("description", $command->getDescription());
                    $this->sendInfo("permissions", explode(";", $command->getPermission()));
                    $permissionMessage = new \ReflectionProperty("\pocketmine\command\Command", "permissionMessage");
                    $permissionMessage->setAccessible(true);
                    $this->sendInfo("permission-message", $permissionMessage->getValue($command));
                    $this->sendInfo("aliases", $command->getAliases());
                }else {
                    $sender->sendMessage("This server doesn't have any command by that name. Use /help to get a list of commands.");
                }
                return true;

            case "permissioninfo":
                if ($permission = $this->getServer()->getPluginManager()->getPermission($args[0])) {
                    $name = $permission->getName();
                    $this->sendInfo("name", $name);
                    $this->sendInfo("description", $permission->getDescription());
                    $source = "plugin";
                    if ($name == DefaultPermissions::ROOT or substr($permission->getName(), 0, strlen(DefaultPermissions::ROOT . ".") == DefaultPermissions::ROOT . "."))
                        $source = "pocketmine";
                    $this->sendInfo("source", $source);
                    $this->sendInfo("commands", array_unique(array_map(function ($command) {
                        /** @var Command $command */
                        return $command->getName();
                    }, array_filter($this->getServer()->getCommandMap()->getCommands(), function ($command) use ($permission) {
                        /** @var Command $command */
                        return in_array($permission->getName(), explode(";", $command->getPermission()));
                    }))));
                    $this->sendInfo("default", $permission->getDefault());
                    $this->sendInfo("children", array_keys($permission->getChildren()));
                }else {
                    $sender->sendMessage("This server doesn't have any permission by that name.");
                }
                return true;

            case "permissions":
                $pageNumber = (isset($args[0]) and is_numeric($args[0])) ? $args[0] : 1;
                var_dump($pageNumber);
                $pageHeight = $sender->getScreenLineHeight();
                    foreach($sender->getServer()->getPluginManager()->getPermissions() as $permission)
                            $permissions[$permission->getName()] = $permission;
                    ksort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
                    $permissions = array_chunk($permissions, $pageHeight);
                    $pageNumber = (int) min(count($permissions), $pageNumber);
                    $pageNumber = $pageNumber > 0 ? $pageNumber : 1;
                    $sender->sendMessage("--- Showing help page " . $pageNumber . " of " . count($permissions) . " (/" . $label . " <page>) ---");
                    if(isset($permissions[$pageNumber - 1])){
                        foreach($permissions[$pageNumber - 1] as $permission){
                            /** @var Permission $permission */
                            $this->sendInfo($permission->getName(), $permission->getDescription(), true);
                        }
                    }
                    return true;
            default:
                return true;
        }
    }

    /**
     * @param string $key
     * @param string|array $value
     * @param bool $force
     */
    public function sendInfo(string $key, $value, bool $force = false) {
        if (!empty($value) or $force) {
            $this->target->sendMessage(TextFormat::DARK_GREEN . $key . ": " . TextFormat::WHITE . trim(is_array($value) ? implode(", ", $value) : $value));
        }
    }
}