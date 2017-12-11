<?php

define("BASEDIR", __DIR__);
ini_set("display_errors", 1);
error_reporting(E_ALL);

require_once(BASEDIR . "/config/config.php");
require_once(BASEDIR . "/vendor/autoload.php");

use RestCord\DiscordClient;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

$log = new Logger('DScan');
$log->pushHandler(new RotatingFileHandler(__DIR__ . '/log/KeepstarCron.log', Logger::NOTICE));

$restcord = new DiscordClient(['token' => $config['discord']['botToken']]);

foreach(glob(BASEDIR . "/libraries/*.php") as $lib)
    require_once($lib);

//Start Auth
$log->notice("AUTHCHECK INITIATED");
$users = getUsers();
$roles = $restcord->guild->getGuildRoles(['guild.id' => $config['discord']['guildId']]);
foreach ($users as $user){
    $characterId = $user['characterID'];
    $discordId = $user['discordID'];
    $type = json_decode($user['groups'], TRUE);
    $id = $user['id'];
    $characterData = characterDetails($characterId);
    $eveName = $characterData['name'];
    if ($config['discord']['enforceInGameName']) {$restcord->guild->modifyCurrentUsersNick(['guild.id' => (int)$config['discord']['guildId'], 'nick' => $eveName]);}
    if (in_array('corp', $type, true)) {
        foreach ($config["groups"] as $authGroup) {
            $id = $authGroup["id"];
            if ($id !== $characterData['corporation_id']) {
                foreach ($roles as $role) {
                    if ($role->name === $authGroup["role"]) {
                        $restcord->guild->removeGuildMemberRole(['guild.id' => (int)$config['discord']['guildId'], 'user.id' => (int)$discordId, 'role.id' => (int)$role->id]);
                        if ((int)$config['discord']['logChannel'] !== 0) {
                            $restcord->channel->createMessage(['channel.id' => (int)$config['discord']['logChannel'], 'content' => "$eveName has been removed from the role $role->name"]);
                            $log->notice("$eveName has been removed from the role $role->name");
                        }
                        if (($key = array_search('corp', $type)) !== false) {
                            unset($type[$key]);
                        }
                        break 3;
                    }
                }
            }
        }
    }
    if (in_array('alliance', $type, true)) {
        foreach ($config["groups"] as $authGroup) {
            $id = $authGroup["id"];
            if ($id !== $characterData['alliance_id']) {
                foreach ($roles as $role) {
                    if ($role->name === $authGroup["role"]) {
                        $restcord->guild->removeGuildMemberRole(['guild.id' => (int)$config['discord']['guildId'], 'user.id' => (int)$discordId, 'role.id' => (int)$role->id]);
                        if ((int)$config['discord']['logChannel'] !== 0) {
                            $restcord->channel->createMessage(['channel.id' => (int)$config['discord']['logChannel'], 'content' => "$eveName has been removed from the role $role->name"]);
                            $log->notice("$eveName has been removed from the role $role->name");
                        }
                        if (($key = array_search('alliance', $type)) !== false) {
                            unset($type[$key]);
                        }
                        break 3;
                    }
                }
            }
        }
    }
    if (in_array('character', $type, true)) {
        foreach ($config["groups"] as $authGroup) {
            $id = $authGroup["id"];
            if ($id !== $characterId) {
                foreach ($roles as $role) {
                    if ($role->name === $authGroup["role"]) {
                        $restcord->guild->removeGuildMemberRole(['guild.id' => (int)$config['discord']['guildId'], 'user.id' => (int)$discordId, 'role.id' => (int)$role->id]);
                        if ((int)$config['discord']['logChannel'] !== 0) {
                            $restcord->channel->createMessage(['channel.id' => (int)$config['discord']['logChannel'], 'content' => "$eveName has been removed from the role $role->name"]);
                            $log->notice("$eveName has been removed from the role $role->name");
                        }
                        if (($key = array_search('character', $type)) !== false) {
                            unset($type[$key]);
                        }
                        break 3;
                    }
                }
            }
        }
    }
    if (count($type) === 0) {
        deleteUser($id);
    }
}