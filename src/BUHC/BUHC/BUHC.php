<?php

namespace BUHC\BUHC;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TE;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;

class BUHC extends PluginBase implements Listener {

    public $prefix = TE::AQUA . "[" . TE::GREEN. TE::BOLD . "Build" . TE::YELLOW . "UHC". TE::RESET . TE::AQUA. "]";
	public $mode = 0;
	public $arenas = array();
	public $currentLevel = "";
        public $op = array();
        public $hud = null;
        public $economy = null;
	
	public function onEnable()
	{
		$this->getLogger()->info(TE::GREEN . "Build".TE::YELLOW."UHC");
                $this->getServer()->getPluginManager()->registerEvents($this ,$this);
                $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                $this->hud = $this->getServer()->getPluginManager()->getPlugin("BasicHUD");
                if(!empty($this->economy))
                {
                $this->api = EconomyAPI::getInstance();
                }
		@mkdir($this->getDataFolder());
                @mkdir($this->getDataFolder()."mapas");
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		if($config->get("arenas")!=null)
		{
			$this->arenas = $config->get("arenas");
		}
		foreach($this->arenas as $lev)
		{
			$this->getServer()->loadLevel($lev);
		}
		$config->save();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 20);
	}
        
        public function onDisable() {
            foreach($this->arenas as $arena)
            {
                $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                $config->set($arena . "inicio", 0);
                $config->save();
                $confi = new Config($this->getDataFolder()."mapas/".$arena.".yml", Config::YAML);
                $confi->set("Players", array("@refertitiano"));
                $confi->save();
                $this->reload($arena);
            }
        }
        
        public function daritems(Player $player) {
            $item = Item::get(Item::DIAMOND_SWORD, 0, 1);
            $item->addEnchantment(Enchantment::getEnchantment(9)->setLevel(2));
            $item1 = Item::get(Item::DIAMOND_HELMET, 0, 1);
            $item1->addEnchantment(Enchantment::getEnchantment(0)->setLevel(4));
            $item2 = Item::get(Item::DIAMOND_CHESTPLATE, 0, 1);
            $item2->addEnchantment(Enchantment::getEnchantment(0)->setLevel(4));
            $item3 = Item::get(Item::DIAMOND_LEGGINGS, 0, 1);
            $item3->addEnchantment(Enchantment::getEnchantment(0)->setLevel(4));
            $item4 = Item::get(Item::DIAMOND_BOOTS, 0, 1);
            $item4->addEnchantment(Enchantment::getEnchantment(0)->setLevel(4));
            $item5 = Item::get(Item::BOW, 0, 1);
            $item5->addEnchantment(Enchantment::getEnchantment(0)->setLevel(2));
            $item6 = Item::get(Item::DIAMOND_PICKAXE, 0, 1);
            $item6->addEnchantment(Enchantment::getEnchantment(15)->setLevel(5));
            $player->getInventory()->setItem(0,$item);
            $player->getInventory()->setItem(1,$item5);
            $player->getInventory()->setItem(2,Item::get(322,0,5));
            $player->getInventory()->setItem(3,Item::get(259,0,1));
            $player->getInventory()->setItem(4,$item6);
            $player->getInventory()->setItem(5,Item::get(5,0,64));
            $player->getInventory()->setItem(6,Item::get(325,8,1));
            $player->getInventory()->setItem(7,Item::get(325,10,1));
            $player->getInventory()->setItem(8,Item::get(354,0,1));
            $player->getInventory()->addItem(Item::get(262,0,30));
            $player->getInventory()->addItem(Item::get(297,0,5));
            $player->getInventory()->addItem(Item::get(373,9,1));
            $player->getInventory()->addItem(Item::get(373,12,1));
            $player->getInventory()->addItem(Item::get(373,14,1));
            $player->getInventory()->addItem(Item::get(373,16,1));
            $player->getInventory()->addItem(Item::get(373,21,1));
            $player->getInventory()->addItem(Item::get(438,17,1));
            $player->getInventory()->addItem(Item::get(438,23,1));
            $player->getInventory()->addItem(Item::get(438,25,1));
            $player->getInventory()->addItem(Item::get(438,34,1));
            $player->getInventory()->setHelmet($item1);
            $player->getInventory()->setChestplate($item2);
            $player->getInventory()->setLeggings($item3);
            $player->getInventory()->setBoots($item4);
            $player->getInventory()->sendArmorContents($player);
            $player->getInventory()->setHotbarSlotIndex(0, 0);
            $player->setMaxHealth(20);
            $player->setHealth(20);
            $player->setFood(20);
            return true;
        }
        
        public function refreshArenas()
	{
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		$config->set("arenas",$this->arenas);
		foreach($this->arenas as $arena)
		{
			$config->set($arena . "PlayTime", 600);
			$config->set($arena . "StartTime", 10);
                        $config->set($arena . "inicio", 0);
		}
		$config->save();
	}
        
        public function onLog(PlayerLoginEvent $event)
	{
		$player = $event->getPlayer();
                if(in_array($player->getLevel()->getFolderName(),$this->arenas))
		{
		$player->getInventory()->clearAll();
		$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
		$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
		$player->teleport($spawn,0,0);
                }
	}
        
        public function removesiesta($name) {
            foreach($this->arenas as $arena)
            {
                    $this->removePlayerFromArena($arena, $name);
            }
        }
        
        public function reload($lev)
        {
            if ($this->getServer()->isLevelLoaded($lev))
            {
                    $this->getServer()->unloadLevel($this->getServer()->getLevelByName($lev));
            }
            $zip = new \ZipArchive;
            $zip->open($this->getDataFolder() . 'arenas/' . $lev . '.zip');
            $zip->extractTo($this->getServer()->getDataPath() . 'worlds');
            $zip->close();
            unset($zip);
            $this->getServer()->loadLevel($lev);
            return true;
        }
        
        public function zipper($player, $name)
        {
        $path = realpath($player->getServer()->getDataPath() . 'worlds/' . $name);
				$zip = new \ZipArchive;
				@mkdir($this->getDataFolder() . 'arenas/', 0755);
				$zip->open($this->getDataFolder() . 'arenas/' . $name . '.zip', $zip::CREATE | $zip::OVERWRITE);
				$files = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($path),
					\RecursiveIteratorIterator::LEAVES_ONLY
				);
                                foreach ($files as $datos) {
					if (!$datos->isDir()) {
						$relativePath = $name . '/' . substr($datos, strlen($path) + 1);
						$zip->addFile($datos, $relativePath);
					}
				}
				$zip->close();
				$player->getServer()->loadLevel($name);
				unset($zip, $path, $files);
        }
        
        public function onMorth(PlayerDeathEvent $event){
        $jugador = $event->getEntity();
        $mapa = $jugador->getLevel()->getFolderName();
        if(in_array($mapa,$this->arenas))
	{
                $event->setDeathMessage("");
                if($event->getEntity()->getLastDamageCause() instanceof EntityDamageByEntityEvent)
                {
                $asassin = $event->getEntity()->getLastDamageCause()->getDamager();
                if($asassin instanceof Player){
                foreach($jugador->getLevel()->getPlayers() as $pl){
				$pl->sendMessage(TE::YELLOW.$jugador->getName() . TE::GREEN. " wurde besiegt von" .TE::AQUA. $asassin->getNameTag());
			}
                }
                }
                else
                {
                foreach($jugador->getLevel()->getPlayers() as $pl){
                $pl->sendMessage(TE::YELLOW.$jugador->getName() . TE::GREEN . " besiegt");
                }
                }
                $jugador->setNameTag($jugador->getName());
                $this->removesiesta($jugador->getName());
                }
        }
        
        public function onQuit(PlayerQuitEvent $event)
        {
            $pl = $event->getPlayer();
            $level = $pl->getLevel()->getFolderName();
            if(in_array($level,$this->arenas))
            {
            $pl->removeAllEffects();
            $pl->getInventory()->clearAll();
            $pl->setNameTag($pl->getName());
            $this->removePlayerFromArena($level, $pl);
            }
        }
        
        public function onDam(EntityDamageEvent $event){
            $player = $event->getEntity();
            $mapa = $player->getLevel()->getFolderName();
            if(in_array($mapa,$this->arenas))
            {
                if($player instanceof Player){
                $this->actNameTag($player);
                if ($event instanceof EntityDamageByEntityEvent && $event->getDamager() instanceof Player) {
                 $golpeado = $event->getEntity()->getNameTag();
                 $golpeador = $event->getDamager()->getNameTag();
                if((strpos($golpeado, "§c[RED]") !== true) && (strpos($golpeador, "§c[RED]") !== true)){
                $event->setCancelled();
                }
                elseif((strpos($golpeado, "§9[BLUE]") !== true) && (strpos($golpeador, "§9[BLUE]") !== true)){
                $event->setCancelled();
                }
                }
                }
            }
        }
        
        public function onEntityRegainHealth(EntityRegainHealthEvent $event){
		$player = $event->getEntity();
                $level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
		if($player instanceof Player){
		$this->actNameTag($player);	
		}
                }
	}
        
        public function actNameTag(Player $player){
            $hp = $player->getHealth();
            $full = $player->getMaxHealth();
            $real = intval($hp/$full*100);
            if(strpos($player->getNameTag(), "§c[RED]") !== true)
            {
                $player->setNameTag("§c[RED]".$player->getName()."\n".TE::BOLD.TE::GOLD.$real.TE::YELLOW."%");
            }
            elseif(strpos($player->getNameTag(), "§9[BLUE]") !== true)
            {
                $player->setNameTag("§9[BLUE]".$player->getName()."\n".TE::BOLD.TE::GOLD.$real.TE::YELLOW."%");
            }
	}
        
        public function PlayertocaSign(PlayerInteractEvent $event) {
            $player = $event->getPlayer();
            $block = $event->getBlock();
            $tile = $player->getLevel()->getTile($block);
            if($tile instanceof Sign) 
            {
                if(($this->mode==26)&&(in_array($player->getName(), $this->op)))
                {
                        $tile->setText(TE::AQUA . "[Beitreten]",TE::GREEN  . "0 / 4","§f" . $this->currentLevel,$this->prefix);
                        $this->refreshArenas();
                        $this->currentLevel = "";
                        $this->mode = 0;
                        array_shift($this->op);
                        $player->sendMessage($this->prefix . "Arena Registered!");
                }
                else
                {
                    $text = $tile->getText();
                    if($text[3] == $this->prefix)
                    {
                        if($text[0]==TE::AQUA . "[Beitreten]")
                        {
                            $namemap = str_replace("§f", "", $text[2]);
                            $config = new Config($this->getDataFolder()."mapas/".$namemap.".yml", Config::YAML);
                            $players = $this->getPlayers($namemap);
                            $this->removesiesta($player->getName());
                            foreach ($players as $pn){
                                if($pn == $player->getName()){
                                    $player->sendMessage($this->prefix.TE::RED."Du hast die Wartezeit verlassen");
                                    return;
                                }
                            }
                            $players[] = $player->getName();
                            $config->set("Players", $players);
                            $config->save();
                            $player->sendMessage($this->prefix.TE::GREEN."Du hast die Wartezeit betreten");
                        }
                        else
                        {
                            $player->sendMessage($this->prefix.TE::RED."Du kannst nicht die Runde beitreten");
                        }
                    }
                }
            }
            elseif(in_array($player->getName(), $this->op)&&$this->mode==1)
            {
                $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                $config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
                $player->sendMessage($this->prefix . "Spawn wurde registriert!");
                $config->set("arenas",$this->arenas);
                $config->set($this->currentLevel . "Spawn", 0);
                $confi = new Config($this->getDataFolder()."mapas/".$this->currentLevel.".yml", Config::YAML);
                $confi->set("Players", array("@refertitiano"));
                $player->sendMessage($this->prefix . "Tippen sie auf ein Schild um die Arena zu registrieren!");
                $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
                $this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
                $player->teleport($spawn,0,0);
                $config->save();
                $confi->save();
                $this->mode=26;
            }
        }
        
        public function getteam($player, $level)
        {
            $players = $this->getPlayers($level);
            if($players[0]==$player->getName() || $players[1]==$player->getName())
            {
               $player->setNameTag("§9[BLUE]".$player->getName());
               $player->sendMessage($this->prefix.TE::BLUE."Du bist in dem Blauen Team");
            }
            elseif($players[2]==$player->getName() || $players[3]==$player->getName())
            {
                $player->setNameTag("§c[RED]".$player->getName());
                $player->sendMessage($this->prefix.TE::RED."Du bist in dem Roten Team");
            }
        }
        
        public function getPlayers($arena){
        $config = new Config($this->getDataFolder()."mapas/".$arena.".yml", Config::YAML);
        $playersXXX = $config->get("Players");
        $players = array();
        foreach ($playersXXX as $x){
            if($x != "@refertitiano"){
                $players[] = $x;
            }
        }
        return $players;
        }
        
        public function removePlayerFromArena($arena, $name){
        $config = new Config($this->getDataFolder()."mapas/".$arena.".yml", Config::YAML);
        $playersXXX = $config->get("Players");
        $players = array();
        foreach ($playersXXX as $pn){
            if($pn != $name){
                $players[] = $pn;
            }
        }
        $config->set("Players", $players);
        $config->save();
        }
        
        public function onCommand(CommandSender $player, Command $cmd, $label, array $args): bool {
        switch($cmd->getName()){
			case "buhc":
				if($player->isOp())
				{
					if(!empty($args[0]))
					{
						if($args[0]=="make")
						{
							if(!empty($args[1]))
							{
								if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1]))
								{
									$this->getServer()->loadLevel($args[1]);
									$this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorZ());
									array_push($this->arenas,$args[1]);
									$this->currentLevel = $args[1];
									$this->mode = 1;
									$player->sendMessage($this->prefix . "Tippe den Spawn für den Kampf!");
									$player->setGamemode(1);
                                                                        array_push($this->op, $player->getName());
									$player->teleport($this->getServer()->getLevelByName($args[1])->getSafeSpawn(),0,0);
                                                                        $name = $args[1];
                                                                        $this->zipper($player, $name);
								}
								else
								{
									$player->sendMessage($this->prefix . "ERROR missing world.");
								}
							}
							else
							{
								$player->sendMessage($this->prefix . "ERROR missing parameters.");
							}
                                                }
						else
						{
							$player->sendMessage($this->prefix . "Invalid Command.");
						}
					}
					else
					{
					 $player->sendMessage($this->prefix . "BUHC Commands!");
                                         $player->sendMessage($this->prefix . "/BUHC make [world] Create a game!");
					}
				}
			return true;
	}
        }
}

class RefreshSigns extends PluginTask {
    public $prefix = TE::AQUA . "[" . TE::GREEN. TE::BOLD . "Build" . TE::YELLOW . "UHC". TE::RESET . TE::AQUA. "]";
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$level = $this->plugin->getServer()->getDefaultLevel();
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if($text[3]==$this->prefix)
				{
                                        $namemap = str_replace("§f", "", $text[2]);
					$players = $this->plugin->getPlayers($namemap);
					$ingame = TE::AQUA . "[Beitreten]";
                                        $allplayers = $this->plugin->getServer()->getLevelByName($namemap)->getPlayers();
					if(count($allplayers)>=1)
					{
						$ingame = TE::DARK_PURPLE . "[Ingame]";
					}
					elseif(count($players)>=4)
					{
						$ingame = TE::GOLD . "[Voll]";
					}
                                        $t->setText($ingame,TE::GREEN  . (count($players)) . " / 4",$text[2],$this->prefix);
				}
			}
		}
	}
}

class GameSender extends PluginTask {
    public $prefix = "";
	public function __construct(BUHC $plugin)
	{
		$this->plugin = $plugin;
                $this->prefix = $this->plugin->prefix;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
		$arenas = $config->get("arenas");
		if(!empty($arenas))
		{
			foreach($arenas as $arena)
			{
				$time = $config->get($arena . "PlayTime");
				$timeToStart = $config->get($arena . "StartTime");
                                $players = $this->plugin->getPlayers($arena);
				$levelArena = $this->plugin->getServer()->getLevelByName($arena);
                                if($timeToStart>0)
                                {
                                    foreach($players as $pn){
                                        $p = $this->plugin->getServer()->getPlayerExact($pn);
                                        if($p != null) {
                                            $this->plugin->hud->sendPopup($p,TE::GREEN . "Warte auf weitere Spieler");
                                        } else {
                                            $this->plugin->removePlayerFromArena($arena, $pn);
                                        }
                                    }
                                }
				if($levelArena instanceof Level)
				{
                                        $playersArena = $levelArena->getPlayers();
                                        foreach($players as $pn){
                                        $p = $this->plugin->getServer()->getPlayerExact($pn);
                                        if($p==null)
                                        {
                                        $this->plugin->removePlayerFromArena($arena, $pn);
                                        }
                                        }
                                        if(count($players)>=4)
                                        {
                                            $config->set($arena . "Spawn", 1);
                                            $config->save();
                                        }
                                        elseif(count($players)==0)
                                        {
                                            $config->set($arena . "Spawn", 0);
                                            $config->save();
                                        }
                                        if($config->get($arena . "Spawn")==1)
                                        {
                                            if($timeToStart>0)
                                            {
                                                    $timeToStart--;
                                                    foreach($players as $pley)
                                                    {
                                                        $pl = $this->plugin->getServer()->getPlayerExact($pley);
                                                        if($pl==null) return;
                                                        $this->plugin->hud->sendPopup($pl,TE::WHITE."Beginnt in ".TE::GREEN . $timeToStart . TE::RESET);
                                                        if($timeToStart<=0)
                                                        {
                                                            $this->plugin->getteam($pl, $arena);
                                                            $thespawn = $config->get($arena . "Spawn1");
                                                            if(strpos($pl->getNameTag(), "§c[RED]") !== true)
                                                            {
                                                            $spawn = new Position($thespawn[0]+10.5,$thespawn[1]+1,$thespawn[2]+0.5,$levelArena);
                                                            $levelArena->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
                                                            $pl->teleport($spawn,90,0);
                                                            }
                                                            elseif(strpos($pl->getNameTag(), "§9[BLUE]") !== true)
                                                            {
                                                            $spawn = new Position($thespawn[0]-10.5,$thespawn[1]+1,$thespawn[2]+0.5,$levelArena);
                                                            $levelArena->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
                                                            $pl->teleport($spawn,-90,0);   
                                                            }
                                                            else
                                                            {
                                                            $spawn = new Position($thespawn[0]+0.5,$thespawn[1]+1,$thespawn[2]+0.5,$levelArena);
                                                            $levelArena->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
                                                            $pl->teleport($spawn,-90,0);       
                                                            }
                                                            $this->plugin->daritems($pl);
                                                        }
                                                    }
                                                    if($timeToStart==9)
                                                    {
                                                        $levelArena->setTime(7000);
                                                        $levelArena->stopTime();
                                                    }
                                                    $config->set($arena . "StartTime", $timeToStart);
                                            }
                                            else
                                            {
                                                    $aop = count($playersArena);
                                                    $colors = array();
                                                    foreach($players as $pley)
                                                    {
                                                    $pl = $this->getOwner()->getServer()->getPlayerExact($pley);
                                                    if($pl!=NULL)
                                                    {
                                                    array_push($colors, $pl->getNameTag());
                                                    }
                                                    }
                                                    $names = implode("-", $colors);
                                                    $red = substr_count($names, "§c[RED]");
                                                    $blue = substr_count($names, "§9[BLUE]");
                                                    $second = $time % 60;
                                                    $timer = ($time - $second) / 60;
                                                    $minutes = $timer % 60;
                                                    $seconds = str_pad($second, 2, "0", STR_PAD_LEFT);
                                                    foreach($playersArena as $pla)
                                                    {
                                                    $this->plugin->hud->sendPopup($pla,TE::BOLD.TE::RED."R:".$red.TE::BLUE."  B:".$blue.TE::GREEN."  Terminal ".TE::YELLOW.$minutes.TE::GRAY.":".TE::YELLOW.$seconds. TE::RESET);
                                                    }
                                                    if($aop>=1)
                                                    {
                                                        if($time>0)
                                                        {
                                                                $winner = null;
                                                                $winners = array();
                                                                if($red!=0 && $blue==0)
                                                                {
                                                                    $winner = TE::RED."[RED]".TE::GREEN." besiegte".TE::BLUE."[BLUE]".TE::GREEN." en ";
                                                                    foreach($players as $pley)
                                                                    {
                                                                        $pl = $this->getOwner()->getServer()->getPlayerExact($pley);
                                                                        if(strpos($pl->getNameTag(), "§c[RED]") !== true)
                                                                        {
                                                                            array_push($winners, $pl->getName());
                                                                            $lal = TE::RED;
                                                                        }
                                                                    }
                                                                }
                                                                if($red==0 && $blue!=0)
                                                                {
                                                                    $winner = TE::BLUE."[BLUE]".TE::GREEN." besiegte".TE::RED."[RED]".TE::GREEN." en ";
                                                                    foreach($players as $pley)
                                                                    {
                                                                        $pl = $this->getOwner()->getServer()->getPlayerExact($pley);
                                                                        if(strpos($pl->getNameTag(), "§9[BLUE]") !== true)
                                                                        {
                                                                            array_push($winners, $pl->getName());
                                                                            $lal = TE::BLUE;
                                                                        }
                                                                    }
                                                                }
                                                                if($winner!=null)
                                                                {
                                                                    $this->getOwner()->getServer()->broadcastMessage($this->prefix .TE::YELLOW. ">> ".$winner.TE::AQUA.$arena);
                                                                    $namewin = implode(", ", $winners);
                                                                    $this->getOwner()->getServer()->broadcastMessage($this->prefix .TE::YELLOW. ">> ".TE::AQUA."Sie haben gewonnen: ".$lal.$namewin);
                                                                    foreach($playersArena as $pl)
                                                                    {
                                                                        $pl->getInventory()->clearAll();
                                                                        $pl->removeAllEffects();
                                                                        $pl->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
                                                                        if(in_array($pl->getNameTag(), $winners))
                                                                        {
                                                                            $this->plugin->api->addMoney($pl,1000);
                                                                        }
                                                                        $pl->setNameTag($pl->getName());
                                                                        $this->plugin->reload($arena);
                                                                        $config->set($arena . "PlayTime", 600);
                                                                        $config->set($arena . "StartTime", 10);
                                                                        $config->set($arena . "Spawn", 0);
                                                                        $config->save();
                                                                    }
                                                                }
                                                        }
                                                    }
                                                    $time--;
                                                    if($time == 599)
                                                    {
                                                        foreach($players as $pley)
                                                        {
                                                            $pl = $this->getOwner()->getServer()->getPlayerExact($pley);
                                                            $pl->sendMessage(TE::YELLOW.">--------------------------------");
                                                            $pl->sendMessage(TE::YELLOW.">".TE::RED."Ich habe aufgepasst: ".TE::GOLD."BUHC hat begonnen");
                                                            $pl->sendMessage(TE::YELLOW.">".TE::GREEN." Du hast".TE::AQUA."10".TE::GREEN." Minuten um zu gewinnen!");
                                                            $pl->sendMessage(TE::YELLOW.">--------------------------------");
                                                        }
                                                    }
                                                    if($time <= 0)
                                                    {
                                                            foreach($playersArena as $pl)
                                                            {
                                                                $pl->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn(),0,0);
                                                                $pl->getInventory()->clearAll();
                                                                $pl->removeAllEffects();
                                                                $pl->setFood(20);
                                                                $pl->setHealth(20);
                                                                $pl->setNameTag($pl->getName());
                                                                $this->plugin->reload($arena);
                                                                $config->set($arena . "Spawn", 0);
                                                                $config->save();
                                                            }
                                                            $this->getOwner()->getServer()->broadcastMessage($this->prefix .TE::YELLOW. ">> ".TE::RED."Es gibt keinen Gewinner in ".TE::AQUA.$arena);
                                                            $time = 600;
                                                    }
                                                    $config->set($arena . "PlayTime", $time);
                                            }
                                        }
                                        else
                                        {
                                        $config->set($arena . "PlayTime", 600);
                                        $config->set($arena . "StartTime", 10);
                                        }
				}
			}
		}
		$config->save();
	}
}