
<?php

namespace FactionsOP;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;
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
class Commands {
        
        public $plugin;
        public function __construct(Functions $pg){
            $this->plugin = $pg;
            
        }
      
        public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
            
            if(strtolower($command->getName('f'))) {
               
                if(empty($args)){
                    $sender->sendMessage("Use /f <1-2>");
                    return true;
                }
                $s_name = $sender->getName();
                switch($args[0]){
                    case "1":
                        $sender->sendMessage("/f create <name> {Create a faction}");
                        $sender->sendMessage("/f leave {Leave a faction}");
                        $sender->sendMessage("/f del {Delete your faction}");
                        $sender->sendMessage("/f invite <player> {Invite sb to your faction}");
                        $sender->sendMessage("/f kick <player> {Kick sb from your faction}");
                        break;
                    case "2":
                        $sender->sendMessage("/f accept {Accept the faction invitation}");
                        $sender->sendMessage("/f deny {Deny the faction invitation}");
                        $sender->sendMessage("/f info [faction] {Create a faction}");
                        $sender->sendMessage("/f promote <player> {Promote a player}");
                        $sender->sendMessage("/f demote <player> {Demote a player}");
                        $sender->sendMessage("/f desc <message> {Set a description for your faction}");
                        break;
                }
                if($args[0] == "create"){
                    
                    if(!isset($args[1])){
                        $sender->sendMessage("Usage : /f create <name>");
                        return true;
                    }
                    if($this->plugin->is_in_faction($s_name)){
                        $sender->sendMessage("You must not be in a faction!");
                        return true;
                    }
                    if($this->plugin->faction_exists($args[1])){
                        $sender->sendMessage("The selected faction already exists!");
                        return true;
                    }
                    if(!(ctype_alnum($args[1]))) {
				        $sender->sendMessage("You may only use letters and numbers in the name!");
                        return true;
				    }
                    if(strlen($args[1]) < 3 or strlen($args[1]) > 15){
                        $sender->sendMessage("This name is too short/long. Try again!");
                        return true;
                    }
                    $this->plugin->create_faction($args[1],$s_name);
                    $sender->sendMessage("Faction $args[1] has been successfully created!");
                    return true;
                    
                } else if ($args[0] == "del"){
                    
                    if(!($this->plugin->is_in_faction($s_name))){
                        $sender->sendMessage("You must be in a faction!");
                        return true;
                    }
                    $s_faction = $this->plugin->get_faction_of($s_name);
                    $s_rank = $this->plugin->get_rank_of($s_name);
                    if($s_rank != "leader"){
                        $sender->sendMessage("You must be the leader!");
                        return true;
                    }
                    $this->plugin->delete_faction($s_faction);
                    $sender->sendMessage("Faction $s_faction has been successfully deleted!");
                    return true;
                    
                } else if ($args[0] == "invite"){
                    if(!isset($args[1])){
                        $sender->sendMessage("Usage : /f invite <player>");
                        return true;
                    }
                    if(!($this->plugin->is_in_faction($s_name))){
                        $sender->sendMessage("You must be in a faction!");
                        return true;
                    }
                    $s_faction = $this->plugin->get_faction_of($s_name);
                    $s_rank = $this->plugin->get_rank_of($s_name);
                    if($s_rank == "member"){
                        $sender->sendMessage("You must be a leader/officer to invite!");
                        return true;
                    }
                    $r_name = $args[1];
                    $r_player = $this->plugin->getServer()->getPlayerExact($args[1]);
                    
                    if(!($r_player instanceof Player)){
                        $sender->sendMessage("The selected player is offline!");
                        return true;
                    }
                    if(!($this->plugin->is_in_faction($r_name))){
                        $sender->sendMessage("The selected player is already in a faction!");
                        return true;
                    }
                    $this->plugin->invite($r_name, $s_name);
                    $sender->sendMessage("$r_name has been successfully invited to the faction!");
                    return true;
                    
                } else if ($args[0] == "leave"){
                    if(!($this->plugin->is_in_faction($s_name))){
                        $sender->sendMessage("You must be in a faction!");
                        return true;
                    }
                    $s_faction = $this->plugin->get_faction_of($s_name);
                    $s_rank = $this->plugin->get_rank_of($s_name);
                    if($s_rank == "leader"){
                        $sender->sendMessage("You must pass the leadership to sb else!");
                        return true;
                    }
                    $this->plugin->leave_the_faction($s_name);
                    $sender->sendMessage("You have successfully left the faction!");
                    return true;
                    
                } else if ($args[0] == "kick"){
                    if(!isset($args[1])){
                        $sender->sendMessage("Usage : /f kick <player> [reason]");
                        return true;
                    }
                    if(!($this->plugin->is_in_faction($s_name))){
                        $sender->sendMessage("You must be in a faction!");
                        return true;
                    }
                    $s_faction = $this->plugin->get_faction_of($s_name);
                    $s_rank = $this->plugin->get_rank_of($s_name);
                    if($s_rank != "leader"){
                        $sender->sendMessage("You must be the leader to kick ppl!");
                        return true;
                    }
                    if(strtolower($s_name) == strtolower($args[1])){
                        
                        $sender->sendMessage("You can't kick yourself.");
                        return true;
                    }
                    if(!($this->plugin->is_in_faction($args[1]))){
                        $sender->sendMessage("The selected player is not in the faction!");
                        return true;
                    }
                    $r_player = $this->plugin->getServer()->getPlayerExact($args[1]);
                    $r_name = $args[1];
                    $r_faction = $this->plugin->get_faction_of($r_name);
                    
                    if($r_faction != $s_faction){
                        $sender->sendMessage("The selected player is not in your faction!");
                        return true;
                    }
                    
                    if(!isset($args[2])){
                        $kick_reason = "Unknown";
                    } else {
                        $kick_reason = $args[2];
                    }
                    
                    $this->plugin->kick_from_faction($r_name, $kick_reason);
                    $sender->sendMessage("You kicked $r_name from your faction for --- $kick_reason");
                    return true;
                    
                } else if ($args[0] == "promote"){
                    if(!isset($args[1])){
                        $sender->sendMessage("Usage : /f promote <player>");
                        return true;
                    }
                    if(!($this->plugin->is_in_faction($s_name))){
                        $sender->sendMessage("You must be in a faction!");
                        return true;
                    }
                    $s_faction = $this->plugin->get_faction_of($s_name);
                    $s_rank = $this->plugin->get_rank_of($s_name);
                    if($s_rank != "leader"){
                        $sender->sendMessage("You must be the leader to promote ppl!");
                        return true;
                    }
                    if(strtolower($s_name) == strtolower($args[1])){
                        
                        $sender->sendMessage("You can't promote/demote yourself.");
                        return true;
                    }
                    
                    if(!($this->plugin->is_in_faction($args[1]))){
                        $sender->sendMessage("The selected player is not in the faction!");
                        return true;
                    }
                    $r_player = $this->plugin->getServer()->getPlayerExact($args[1]);
                    $r_name = $args[1];
                    $r_faction = $this->plugin->get_faction_of($r_name);
                    $r_rank = $this->plugin->get_rank_of($r_name);
                    
                    if($r_faction != $s_faction){
                        $sender->sendMessage("The selected player is not in your faction!");
                        return true;
                    }
                    if($r_rank == "officer"){
                        $sender->sendMessage("The selected player is already an officer!");
                        return true;
                    }
                    
                    $this->plugin->set_rank_of($r_name, "officer");
                    $sender->sendMessage("$r_name has been promoted to Officer!");
                    return true;
                    
                } else if ($args[0] == "demote"){
                    if(!isset($args[1])){
                        $sender->sendMessage("Usage : /f demote <player>");
                        return true;
                    }
                    if(!($this->plugin->is_in_faction($s_name))){
                        $sender->sendMessage("You must be in a faction!");
                        return true;
                    }
                    $s_faction = $this->plugin->get_faction_of($s_name);
                    $s_rank = $this->plugin->get_rank_of($s_name);
                    if($s_rank != "leader"){
                        $sender->sendMessage("You must be the leader to demote ppl!");
                        return true;
                    }
                    if(strtolower($s_name) == strtolower($args[1])){
                        
                        $sender->sendMessage("You can't promote/demote yourself.");
                        return true;
                    }
                    
                    if(!($this->plugin->is_in_faction($args[1]))){
                        $sender->sendMessage("The selected player is not in the faction!");
                        return true;
                    }
                    $r_player = $this->plugin->getServer()->getPlayerExact($args[1]);
                    $r_name = $args[1];
                    $r_faction = $this->plugin->get_faction_of($r_name);
                    $r_rank = $this->plugin->get_rank_of($r_name);
                    
                    if($r_faction != $s_faction){
                        $sender->sendMessage("The selected player is not in your faction!");
                        return true;
                    }
                    if($r_rank == "member"){
                        $sender->sendMessage("The selected player is already a member!");
                        return true;
                    }
                    
                    $this->plugin->set_rank_of($r_name, "member");
                    $sender->sendMessage("$r_name has been demoted to Member!");
                    return true;
                    
                } else if ($args[0] == "accept"){
                    
                    $this->plugin->invitation($s_name, "accept");
                    return true;
                    
                } else if ($args[0] == "deny"){
                    
                    $this->plugin->invitation($s_name, "deny");
                    return true;
                    
                } else if ($args[0] == "info"){
                    if(!isset($args[1])){
                        if(!($this->plugin->is_in_faction($s_name))){
                            $sender->sendMessage("You must be in a faction!");
                        } else {
                            $s_faction = $this->plugin->get_faction_of($s_name);
                            $this->plugin->info($sender, $s_faction);
                        }
                        return true;
                    }
                    if($this->plugin->faction_exists($args[1])){
                        
                        $this->plugin->info($sender, $args[1]);
                    } else {
                        $sender->sendMessage("The requested faction doesn't exist!");
                    }
                    return true;
                } else if ($args[0] == "desc"){
                    if(!isset($args[1])){
                        $sender->sendMessage("Usage : /f desc <msg>");
                        return true;
                    }
                    if(!($this->plugin->is_in_faction($s_name))){
                        $sender->sendMessage("You must be in a faction!");
                        return true;
                    }
                    if(!($this->plugin->get_rank_of($s_name)=="leader")){
                        $sender->sendMessage("You must be a leader!");
                        return true;
                    }
                    if(strlen($args[1]) < 10 and strlen($args[1]) > 100){
                        $sender->sendMessage("The description must contain from 10 to 100 characters!");
                        return true;
                    }
                    $s_faction = $this->plugin->get_faction_of($s_name);
                    $this->plugin->set_description($s_faction,$args[1]);
                    $sender->sendMessage("The description was successfully set!");
                    return true;
                } else {
                    return true;
                } 
            
            }
            
        }
        

}
