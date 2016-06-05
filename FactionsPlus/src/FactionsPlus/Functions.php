<?php

namespace FactionsOP;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\entity\Effect;
use pocketmine\utils\Config;
class Functions extends PluginBase  {

        public function onEnable(){
            
            $this->getServer()->getLogger()->info("FactionsOP is enabled!");
            $this->fCommand = new Commands($this);
            @mkdir($this->getDataFolder());
            if(!file_exists($this->getDataFolder() . "factions_data/")){
			   @mkdir($this->getDataFolder() . "factions_data/");
			   @mkdir($this->getDataFolder() . "factions_players/");
		    }
		    
		
        }
        public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
            $this->fCommand->onCommand($sender,$command,$label,$args);
        }
    
        public $invitations = array();
    
        public function create_faction($name_of_faction,$name_of_leader){
            
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            @mkdir($this->getDataFolder() . $path);
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML));
		    $data->set("name", $name_of_faction);
		    $data->set("strength", 0);
		    $data->set("description", "Not set");
            $data->set("leader", $name_of_leader);
            $data->set("officer", array());
            $data->set("member", array());
		    
            $data->save();
            
            $path = "factions_players/" . strtolower($name_of_leader[0]) . "/";
            @mkdir($this->getDataFolder() . $path);
            
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_leader.yml");
            $pdata = (new Config($this->getDataFolder() . "$path" . "$name_of_leader.yml", Config::YAML));
            $pdata->set("name", $name_of_leader);
		    $pdata->set("faction", $name_of_faction);
		    $pdata->set("rank", "leader");
            
            $pdata->save();
        }
    
        public function delete_faction($name_of_faction){
            $leader = $this->get_leader($name_of_faction);
            $officers = $this->get_officers($name_of_faction);
            $members = $this->get_members($name_of_faction);
            
            for($i=0;$i<sizeof($members);$i++){
                @unlink("factions_players/" . strtolower($members[$i][0]) . "/$members[$i].yml");
            }
            for($i=0;$i<sizeof($officers);$i++){
                @unlink("factions_players/" . strtolower($officers[$i][0]) . "/$officers[$i].yml");
            }
            @unlink($this->getDataFolder() . "factions_players/" . strtolower($leader[0]) . "/$leader.yml");
		    @unlink($this->getDataFolder() . "factions_data/" . strtolower($name_of_faction[0]) . "/$name_of_faction.yml");
       }
    
        public function leave_the_faction($name_of_player){
            $name_of_faction = $this->get_faction_of($name_of_player);
            $name_of_rank = $this->get_rank_of($name_of_player);
            
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML))->getAll();
            
            
            $data[$name_of_rank][array_search($name_of_player,$data[$name_of_rank])]=NULL;
            
            $data->save();
            
		    @unlink($this->getDataFolder() . "factions_players" . strtolower($name_of_player[0]) . "/$name_of_player.yml");
        }
    
        public function invite($name_of_player, $invited_by) {
            $this->invitations[$name_of_player][0] = $invited_by;
            $this->invitations[$name_of_player][1] = $this->get_faction_of($invited_by);
            $this->invitations[$name_of_player][2] = time();
        }
       
        public function add_to_faction($name_of_player,$name_of_faction){
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML))->getAll();
            
            array_push($data["members"],$name_of_player);
            
            $data->save();
            
            $path = "factions_players/" . strtolower($name_of_player[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_player.yml");
            $pdata = (new Config($this->getDataFolder() . "$path" . "$name_of_player.yml", Config::YAML));
            
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_player.yml");
		    $pdata->set("faction", $name_of_faction);
		    $pdata->set("rank", "member");
            $pdata->save();
            
        }
    
        public function kick_from_faction($name_of_player, $kick_reason){
            $name_of_faction = $this->get_faction_of($name_of_player);
            $name_of_rank = $this->get_rank_of($name_of_player);
            
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML))->getAll();
            
            
            $data[$name_of_rank][array_search($name_of_player,$data[$name_of_rank])]=NULL;
            
            $data->save();
            
		    @unlink($this->getDataFolder() . "factions_players" . strtolower($name_of_player[0]) . "/$name_of_player.yml");
    
        }
    
        public function set_rank_of($name_of_player, $chosen_rank) {
            $name_of_faction = $this->get_faction_of($name_of_player);
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML))->getAll();
            
            $current_rank = $this->get_rank_of($name_of_player);
            array_push($data[$chosen_rank],$name_of_player);
            $data[$current_rank][array_search($name_of_player,$data[$current_rank])]=NULL;
            
            $data->save();
            
            $path = "factions_players/" . strtolower($name_of_player[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_player.yml");
            $pdata = (new Config($this->getDataFolder() . "$path" . "$name_of_player.yml", Config::YAML));
            
            $pdata->set('rank', "$chosen_rank");
            $pdata->save();
            
        }
    
        public function get_rank_of($name_of_player) : string {
            $name_of_faction = $this->get_faction_of($name_of_player);
            $members = $this->get_members($name_of_faction);
            $officers = $this->get_officers($name_of_faction);
            $leader = $this->get_leader($name_of_faction);
            
            if(in_array($name_of_player,$members)){
                return "member";
            } else if(in_array($name_of_player,$officers)){
                return "officer";
            } else if($leader == $name_of_player) {
                return "leader";
            }
        }
    
        public function get_faction_of($name_of_player) : string {
            $path = "factions_players/" . strtolower($name_of_player[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_player.yml");
            $pdata = (new Config($this->getDataFolder() . "$path" . "$name_of_player.yml", Config::YAML))->getAll();
            
            return $pdata['faction'];
          
        }
    
        public function invitation($name_of_player,$action) {
            $player = $this->getServer()->getPlayerExact($name_of_player);
            if(!isset($this->invitations[$name_of_player][0])){
                $player->sendMessage("You are not invited to any factions!");
            } else {
                $invby = $this->getServer()->getPlayerExact($this->invitations[$name_of_player][0]);
                if($this->invitations[$name_of_player][2] > 60){
                    $player->sendMessage("Invitation timed out!");
                    $this->unset_invitations($name_of_player);
                } else {
                    if($action == "accept"){
                        $faction_to_join = $this->invitations[$name_of_player][1];
                        $this->add_to_faction($name_of_player, $faction_to_join);
                        $player->sendMessage("Welcome to $faction_to_join, $name_of_player!");
                        if($invby instanceof Player){
                            $invby->sendMessage("$name_of_player successfully joined the faction!");
                        }
                    } else {
                        $player->sendMessage("Invitation was successfully denied!");
                        if($invby instanceof Player){
                            $invby->sendMessage("$name_of_player denied the invitation!");
                        }
                    }
                    $this->unset_invitations($name_of_player);
                }
            }
        }
    
        public function unset_invitations($name_of_player){
            $this->invitations[$name_of_player][0]=NULL;
            $this->invitations[$name_of_player][1]=NULL;
            $this->invitations[$name_of_player][2]=NULL;
        }
    
        public function faction_exists($name_of_faction) : bool {
            if(file_exists($this->getDataFolder() . "factions_data/" . strtolower($name_of_faction[0]) . "/" . "$name_of_faction.yml")){
               return true;
            }
            return false;
        }
    
        public function is_in_faction($name_of_player) : bool {
            if(file_exists($this->getDataFolder() . "factions_players/" . strtolower($name_of_player[0]) . "/" . "$name_of_player.yml")){
               return true;
            }
            return false;
        }
    
        public function get_leader($name_of_faction) : string {
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML))->getAll();
            
            return $data['leader'];
        }
    
        public function get_officers($name_of_faction) : array {
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML))->getAll();
            
            return $data['officer'];
        }
    
        public function get_members($name_of_faction) : array {
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML))->getAll();
            
            return $data['member'];
        }
        public function get_description($name_of_faction) : string {
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML))->getAll();
            
            return $data['description'];
        }
        public function set_description($name_of_faction, $description){
            $path = "factions_data/" . strtolower($name_of_faction[0]) . "/";
            $this->saveResource($this->getDataFolder() . "$path" . "$name_of_faction.yml");
            $data = (new Config($this->getDataFolder() . "$path" . "$name_of_faction.yml", Config::YAML));
            $data->set('description', "$description");
            
            $data->save();
            
        }
        public function info($sender, $name_of_faction) {
            $leader = $this->get_leader($name_of_faction);
            $description = $this->get_description($name_of_faction);
            $officers = $this->get_officers($name_of_faction);
            $members = $this->get_members($name_of_faction);
            $players = 1 + sizeof($officers) + sizeof($members);
            $sender->sendMessage("Information about $name_of_faction");
            $sender->sendMessage("Description - $description");
            $sender->sendMessage("Players - $players");
            $sender->sendMessage("Leader - $leader");
        }
        public function print_ranks($sender, $name_of_faction, $rank){
            switch($rank){
                case "member":
                    $members = $this->get_members($name_of_faction);
                    if(sizeof($members)==0){
                        $sender->sendMessage("This faction doesn't have any members!");
                    } else {
                        $sender->sendMessage("Listing the members of $name_of_faction :");
                        for($i = 0; $i < sizeof($members); $i = $i + 1){
                            $sender->sendMessage($members[$i] . " || ");
                        }
                    }
                    break;
                case "officer":
                    $officers = $this->get_officers($name_of_faction);
                    if(sizeof($officers)==0){
                        $sender->sendMessage("This faction doesn't have any officers!");
                    } else {
                        $sender->sendMessage("Listing the officers of $name_of_faction :");
                        for($i = 0; $i < sizeof($officers); $i = $i + 1){
                            $sender->sendMessage($officers[$i] . " || ");
                        }
                    }
                    break;
                case "leader":
                    $leader = $this->get_leader($name_of_faction);
                    $sender->sendMessage("The leader of $name_of_faction is $leader!");
                    break;
            }
        }
       
    
        public function onDisable(){
            
            $this->getServer()->getLogger()->info("FactionsOP is disabled!");
        }

}
