<?php

/*

*/

/*interface to make it compatible for old modules*/
function zanpakutou_getmoduleinfo() {
	$class=new zanpakutou_main;
	return $class->getmoduleinfo();
	unset($class);
	return;
}

function zanpakutou_install() {
	$class=new zanpakutou_main;
	$class->install();
	unset($class);
	return;
}

function zanpakutou_uninstall() {
	$class=new zanpakutou_main;
	$class->uninstall();
	unset($class);
	return;
}

function zanpakutou_dohook($hookname,$args) {
	$class=new zanpakutou_main;
	return $class->do_hook($hookname,$args);
	unset($class);
	return;
}

function zanpakutou_run() {
	$class=new zanpakutou_main;
	$class->run();
	unset($class);
	return;
}

/*end of legacy support*/


if (!interface_exists("module_base")) {
	interface module_base {
		public function getmoduleinfo();
		public function install ();
		public function uninstall();
		public function do_hook($hookname,$args);
		public function run();
	}
}

class formeffects {

	protected $effects;
	
	
	function __construct() {
		$this->effects=array(
			0=>array("text"=>"","buff"=>""),
			1=>array("text"=>"Your attacks will be increased by 10%","effect"=>"atkmod|atkmod"),
			2=>array("text"=>"Your attacks will be increased by 5, your damage by 5%","effect"=>"atkmod|dmgmod"),
			3=>array("text"=>"Your defense will be increased by 10%","effect"=>"defmod|defmod"),
			4=>array("text"=>"Your attacks will be increased by 5%, your defense by 5%.","effect"=>"atkmod|defmod"),
			5=>array("text"=>"Your damage will be increased by 10%","effect"=>"dmgmod|dmgmod"),
			6=>array("text"=>"Your defense will be increased by 5%, your damage will be increased by 5%","effect"=>"dmgmod|defmod"),
			);
	}
	
	function get_effect_text($effect) {
		if (count($this->effects)+1<$effect) return '';
		return ($this->effects[$effect]['text']);
	}
	
	function get_effect_buff($effect) {
		if (count($this->effects)+1<$effect) return array();
		$eff=explode("|",$this->effects[$effect]['effect']);
		$buff=array();
		foreach ($eff as $value) {
			if (isset($buff[$value])) {
				$buff[$value]+=.05;
			} else {
				$buff[$value]=1.05;
			}
		}
		return $buff;
	}	
}

class bankai_formeffects extends formeffects {
	function __construct() {
		$this->effects=array(
			0=>array("text"=>"","buff"=>""),
			1=>array("text"=>"Your attacks will be increased by 80%","effect"=>"atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod"),
			2=>array("text"=>"Your attacks will be increased by 40%, your damage by 40%","effect"=>"atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod"),
			3=>array("text"=>"Your defense will be increased by 80%","effect"=>"defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|"),
			4=>array("text"=>"Your attacks will be increased by 40%, your defense by 40%.","effect"=>"atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod"),
			5=>array("text"=>"Your damage will be increased by 80%","effect"=>"dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod"),
			6=>array("text"=>"Your defense will be increased by 40%, your damage will be increased by 40%","effect"=>"dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod"),
			);
	}
}

class typeeffects {
	
	protected $effects;

	function __construct() {
		$this->effects=array(
			0=>array("text"=>"","buff"=>""),
			1=>array("text"=>"Your attacks will be increased by 5%, your damage by 5%","effect"=>"atkmod|dmgmod"),
			2=>array("text"=>"Your defense will be increased by 10%","effect"=>"defmod|defmod"),
			3=>array("text"=>"Your attacks will be increased by 10%","effect"=>"atkmod|atkmod"),
			4=>array("text"=>"Your attacks will be increased by 5%, your defense by 5%.","effect"=>"atkmod|defmod"),
			5=>array("text"=>"Your damage will be increased by 10%","effect"=>"dmgmod|dmgmod"),
			6=>array("text"=>"Your defense will be increased by 5%, your damage will be increased by 5%","effect"=>"dmgmod|defmod"),
			7=>array("text"=>"Enemy attacks will be reflected by 5%, your damage will be increased by 5%","effect"=>"dmgmod|dmgshield"),
		);
	}
	
	function get_effect_text($effect) {
		if (count($this->effects)+1<$effect) return '';
		return ($this->effects[$effect]['text']);
	}
	
	function get_effect_buff($effect) {
		if (count($this->effects)+1<$effect) return array();
		$eff=explode("|",$this->effects[$effect]['effect']);
		$buff=array();
		foreach ($eff as $value) {
			if (isset($buff[$value])) {
				$buff[$value]+=.05;
			} else {
				if ($value=="dmgshield") {
					$buff[$value]=0.5;
				} else {
					$buff[$value]=1.05;
				}
			}
		}
		return $buff;
	}
}

class bankai_typeeffects extends typeeffects {
	function __construct() {
		$this->effects=array(
			0=>array("text"=>"","buff"=>""),
			1=>array("text"=>"Your damage will be increased by 40%, 20% of enemy damage will be reflected","effect"=>"dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgshield|dmgshield|dmgshield|dmgshield"),
			2=>array("text"=>"Your defense will be increased by 60%","effect"=>"defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod|defmod"),
			3=>array("text"=>"Your attacks will be increased by 60%","effect"=>"atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|atkmod"),
			4=>array("text"=>"Your attacks will be increased by 30%, your defense by 30%.","effect"=>"atkmod|atkmod|atkmod|atkmod|atkmod|atkmod|defmod|defmod|defmod|defmod|defmod|defmod"),
			5=>array("text"=>"Your damage will be increased by 60%","effect"=>"dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod"),
			6=>array("text"=>"Your defense will be increased by 30%, your damage will be increased by 30%","effect"=>"defmod|defmod|defmod|defmod|defmod|defmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod"),
			7=>array("text"=>"Enemy attacks will be reflected by 30%, your damage will be increased by 30%","effect"=>"dmgshield|dmgshield|dmgshield|dmgshield|dmgshield|dmgshield|dmgshield|dmgshield|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod|dmgmod"),
		);
	}
	
}



class zanpakutou {
	private $name;
	private $type;
	private $form;
	private $image;
	private $released;
	private $shikai;
	private $bankai;
	private $image_validated;
	private $image_showbio;
	private $allreleased;
	private $twoweapon;
	private $powerlevel;
	
	public function release() {
		//only done in a fight, so it is safe to assume we have the proper global variables to be accessed.
		global $session,$badguy,$enemies;
		require_once("lib/buffs.php");
		if ($this->is_released()) return false;
		$this->released=1;
		$shi=$this->get_shikai();
		output_notl($session['user']['name']."`4: \"".$shi->get_text()."`4\"...`n`n");
		$effect=new typeeffects();
		$buff=$effect->get_effect_buff($this->get_raw_type());
		$effect=new formeffects();
		$buff=array_merge($buff,$effect->get_effect_buff($this->get_raw_form()));
		$buff=array_merge(
			array(
				"name"=>array("`)%s `\$Shikai",$this->get_name()),
				"rounds"=>$this->get_powerlevel()*10,
				"wearoff"=>"`\$Your Shikai recedes...",
				"roundmsg"=>array("%s`@ supports you...",$this->get_name()),
				"minioncount"=>1,
				"schema"=>"module-zanpakutou",
			),$buff
		);
		apply_buff("shikai",$buff);
		return 1;
	}
	
	public function bankai_release() {
		//only done in a fight, so it is safe to assume we have the proper global variables to be accessed.
		global $session,$badguy,$enemies;
		require_once("lib/buffs.php");
		if (has_buff("bankai")) return false;
		$this->released=1;
		$shi=$this->get_bankai();
		output_notl($session['user']['name']."`4: \"".$shi->get_text()."`4\"...`n`n");
		$effect=new bankai_typeeffects();
		$buff=$effect->get_effect_buff($this->get_raw_type());
		$effect=new bankai_formeffects();
		$buff=array_merge($buff,$effect->get_effect_buff($this->get_raw_form()));
		$buff=array_merge(
			array(
				"name"=>array("`)%s `\$Bankai",$this->get_name()),
				"rounds"=>$this->get_powerlevel()*10,
				"wearoff"=>"`\$Your Bankai recedes...",
				"roundmsg"=>array("%s`@ supports you...",$this->get_name()),
				"minioncount"=>1,
				"schema"=>"module-zanpakutou",
			),$buff
		);
		
		strip_buff("shikai");
		apply_buff("bankai",$buff);
		return 1;		
	}
	
	public function is_released() {
		if (!isset($this->released)) $this->released=0;
		if (!has_buff('shikai') && !has_buff('bankai')) $this->released=0;
		if ($this->released>0) {
			return true;
		} else return false;
	}
	
	public function get_name() {
		if (!isset($this->name)) return '';
		return $this->name;
	}
	
	public function set_name($name) {
		$this->name=$name;	
	}
	
	public function get_powerlevel() {
		if (!isset($this->powerlevel)) return 1;
		return $this->powerlevel;
	}
	
	public function set_powerlevel($powerlevel) {
		$this->powerlevel=$powerlevel;	
	}

	public function get_allreleased() {
		if (!isset($this->allreleased)) return '';
		return $this->allreleased;
	}
	
	public function set_allreleased($allreleased) {
		$this->allreleased=($allreleased?true:false);	
	}	
	
	public function get_all_types() {
		return array(
			0=>translate_inline("unknown"),
			1=>translate_inline("Power (Melee)"),
			2=>translate_inline("Kidō"),
			3=>translate_inline("Fire"),
			4=>translate_inline("Water"),
			5=>translate_inline("Wind"),
			6=>translate_inline("Ice"),
			7=>translate_inline("Lightning"),
		);	
	}
	
	public function get_bankaiforms($type) {
		
		$forms=array(
			0=>translate_inline("unknown"),
			1=>translate_inline("`^Golden Samurai"),
			2=>translate_inline("`%Illusion Weaver"),
			3=>translate_inline("`\$Fire Cat"),
			4=>translate_inline("`!Water Elemental"),
			5=>translate_inline("`QHurricane Spirit"),
			6=>translate_inline("`1Mystic Ice Dragon"),
			7=>translate_inline("`6Thunder God Raijin"),
		);
		
		if (isset($forms[$type])) $val=$forms[$type];
			else $val="Void Form";
		
		return $val;
	}
	
	public function get_bankaiforms_weapons($type) {
		
		$forms=array(
			0=>translate_inline("unknown"),
			1=>translate_inline("`^Golden Katana"),
			2=>translate_inline("`%Nightmares"),
			3=>translate_inline("`\$Fiery Claws"),
			4=>translate_inline("`!Water Sickles"),
			5=>translate_inline("`QCutting Winds"),
			6=>translate_inline("`1Razorsharp Iceshards"),
			7=>translate_inline("`6Lightning"),
		);
		
		if (isset($forms[$type])) $val=$forms[$type];
			else $val="Void Claws";
		
		return $val;
	}
	
	public function get_unique_releasebuff($name,$form,$type) {
		global $session;
		$u=&$session['user'];
		$form=$this->get_form($form);
		require_once("lib/playerfunctions.php");
		switch ($type) {

			case 7:
				$buff=array(
				"name"=>"`qLightning Release!",
				"rounds"=>-1,
				"atkmod"=>1.5,
				"dmgshield"=>min(round($u['intelligence']/100,2),0.5),
				"minioncount"=>$u['wisdom'],
				"effectmsg"=>array("`)%s`): Your %s`) throws {damage} back at {badguy}!",str_replace("%","%%",$name),$form),
				"roundmsg"=>array("`)%s`): Your %s`) entangles the enemy with lightning and surrounds you with electricity!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;	
		
			case 6:
				$buff=array(
				"name"=>"`qIce Release!",
				"rounds"=>-1,
				"minbadguydamage"=>max(2,min($u['wisdom']-20,3)),
				"maxbadguydamage"=>min(20,$u['dexterity'])+round($u['wisdom']/3),
				"minioncount"=>$u['dexterity'],
				"effectmsg"=>array("`)%s`): Your %s`) hits {badguy} for {damage} damage with an ice needle!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;	
		
			case 5:
				$buff=array(
				"name"=>"`qWind Release!",
				"rounds"=>-1,
				"minbadguydamage"=>max(2,min($u['wisdom']-20,3)),
				"maxbadguydamage"=>min(20,$u['dexterity'])+round($u['wisdom']/3),
				"minioncount"=>$u['wisdom'],
				"effectmsg"=>array("`)%s`): Your %s`) sends a slicing air wave at {badguy} that deal {damage} damage!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;		
		
			case 4:
				$buff=array(
				"name"=>"`qWater Release!",
				"rounds"=>-1,
				"minbadguydamage"=>max(2,min($u['wisdom']-20,3)),
				"maxbadguydamage"=>min(20,$u['dexterity'])+round($u['wisdom']/3),
				"minioncount"=>$u['wisdom'],
				"effectmsg"=>array("`)%s`): Water that splashes out from your %s`) hits {badguy} for {damage} damage!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;	
			case 3:
				$buff=array(
				"name"=>"`qFire Release!",
				"rounds"=>-1,
				"minbadguydamage"=>max(2,min($u['dexterity']-10,20)),
				"maxbadguydamage"=>min(20,$u['dexterity'])+$u['strength'],
				"minioncount"=>1,
				"dmgshield"=>0.1,
				"roundmsg"=>"You are surrounded by fire.",
				"effectmsg"=>array("`)%s`): Your %s`) hits {badguy} with {damage} fire damage!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;
			case 2: 
				$buff=array(
				"name"=>"`qKidō Release!",
				"rounds"=>-1,
				"badguyatkmod"=>0.7,
				"badguydefmod"=>0.7,
				"minioncount"=>1,
				"roundmsg"=>array("`)%s`): Your %s`) enthralls {badguy} who has a hard time trying to hit you!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;
			default:
			case 1:
				$buff=array(
				"name"=>"`qPower Release!",
				"rounds"=>-1,
				"minbadguydamage"=>$u['strength'],
				"maxbadguydamage"=>5*$u['strength']+20,
				"minioncount"=>min(1,round(get_player_speed()/10)),
				"effectmsg"=>array("`)%s`): A blast from your %s`) hits {badguy} for {damage} damage!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
					
		}
		return $buff;
	}

	public function bankai_get_unique_releasebuff($name,$form,$type) {
		global $session;
		$u=&$session['user'];
		$form=$this->get_form($form);
		$rounds=min(25,($u['strength']+$u['intelligence'])/2);
		require_once("lib/playerfunctions.php");
		switch ($type) {

			case 7:
				$buff=array(
				"name"=>"`qRaijin Bankai Release!",
				"rounds"=>$rounds,
				"atkmod"=>2.5,
				"dmgshield"=>min(round($u['intelligence']/30,2),0.9),
				"minioncount"=>$u['wisdom']*2,
				"effectmsg"=>array("`)%s`): Your bankai form of %s`) throws {damage} back at {badguy}!",str_replace("%","%%",$name),$form),
				"roundmsg"=>array("`)%s`): Your bankai form of %s`) entangles your enemy with lightning and surrounds you with electricity!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;	
		
			case 6:
				$buff=array(
				"name"=>"`qMystic Ice Dragon Bankai Release!",
				"rounds"=>$rounds,
				"minbadguydamage"=>max(5,min($u['wisdom']-10,9)),
				"maxbadguydamage"=>min(100,$u['dexterity'])+round($u['wisdom']/2),
				"minioncount"=>$u['dexterity']*2,
				"effectmsg"=>array("`)%s`): Your %s`) hits {badguy} for {damage} damage with an ice needle!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;	
		
			case 5:
				$buff=array(
				"name"=>"`qHurricane Bankai Release!",
				"rounds"=>$rounds,
				"minbadguydamage"=>max(5,min($u['wisdom']-10,9)),
				"maxbadguydamage"=>min(100,$u['dexterity'])+round($u['wisdom']/2),
				"minioncount"=>$u['wisdom']*2,
				"effectmsg"=>array("`)%s`): Your %s`) sends a slicing air wave at {badguy} that deal {damage} damage!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;		
		
			case 4:
				$buff=array(
				"name"=>"`qWater Elemental Bankai Release!",
				"rounds"=>$rounds,
				"minbadguydamage"=>max(5,min($u['wisdom']-10,9)),
				"maxbadguydamage"=>min(100,$u['dexterity'])+round($u['wisdom']/2),
				"minioncount"=>$u['wisdom'],
				"effectmsg"=>array("`)%s`): Water that splashes out from your %s`) hits {badguy} for {damage} damage!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;	
			case 3:
				$buff=array(
				"name"=>"`qFire Cat Bankai Release!",
				"rounds"=>$rounds,
				"minbadguydamage"=>max(2,min($u['dexterity']-10,80)),
				"maxbadguydamage"=>min(100,$u['dexterity'])+$u['strength'],
				"minioncount"=>3,
				"dmgshield"=>0.2,
				"roundmsg"=>"You are surrounded by fire.",
				"effectmsg"=>array("`)%s`): Your bankai form of %s`) hits {badguy} with {damage} fire damage!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;
			case 2: 
				$buff=array(
				"name"=>"`qKidō Bankai Release!",
				"rounds"=>$rounds,
				"badguyatkmod"=>0.5,
				"badguydefmod"=>0.5,
				"minioncount"=>1,
				"roundmsg"=>array("`)%s`): Your bankai release %s`) enthralls {badguy} who has a hard time trying to hit you!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
				break;
			default:
			case 1:
				$buff=array(
				"name"=>"`qBankai Power Release!",
				"rounds"=>$rounds,
				"minbadguydamage"=>$u['strength']*3,
				"maxbadguydamage"=>7*$u['strength']+50,
				"minioncount"=>min(1,round(get_player_speed()/5)),
				"effectmsg"=>array("`)%s`): A blast from your bankai form of %s`) hits {badguy} for {damage} damage!",str_replace("%","%%",$name),$form),
				"schema"=>"module-zanpakutou",
				);
					
		}
		return $buff;
	}
	
	public function get_all_forms() {
		return array(
			0=>translate_inline("unknown"),
			1=>translate_inline("Katana"),
			2=>translate_inline("Spear"),
			3=>translate_inline("Sai"),
			4=>translate_inline("Wakizashi"),
			5=>translate_inline("Morning Star"),
			6=>translate_inline("Dagger"),
		);	
	}
	
	public function get_form() {
		$form_array=$this->get_all_forms();
		$form=(int)$this->form;
		return $form_array[$form];
	}
	
	public function get_raw_form() {
		$form=(int)$this->form;
		return $form;
	}
	
	public function set_form($form) {
		$this->form=$form;	
	}
	
	public function image_validate() {
		$this->image_validated=true;	
	}
	
	public function image_unvalidate() {
		$this->image_validated=false;	
	}
	
	public function image_validated() {
		if (!isset($this->image_validated)) return false;
		return $this->image_validated;
	}
	
	public function get_image() {
		if (!isset($this->image)) return '';
		return $this->image;
	}
	
	public function set_image($image) {
		$this->image=$image;	
	}

	public function display_image() {
		if ($this->image!='') {
			$usedefault = 0;
			$file = "modules/avatar/default.gif";
			if ($this->image_validated()) {
				$picname=str_replace(" ","",$this->image);
				$image="<img align='left' src='".$picname."' ";
				if (get_module_setting("restrictsize","zanpakutou")) {
					//stripped lines from Anpera's avatar module =)
					$maxwidth = get_module_setting("maxwidth","zanpakutou");
					$maxheight = get_module_setting("maxheight","zanpakutou");
					$pic_size = @getimagesize($picname); // GD2 required here - else size always is recognized as 0
					$pic_width = $pic_size[0];
					$pic_height = $pic_size[1];
					//other arguments are channels,bits etc
					
					//aspect ratio. We are scaling for height/width ratio
					$resizedwidth=$pic_width;
					$resizedheight=$pic_height;
					if ($pic_height > $maxheight) {
						$resizedheight=$maxheight;
						$resizedwidth=round($pic_width*($maxheight/$pic_height));
					}
					if ($resizedwidth > $maxwidth) {
						$resizedheight=round($resizedheight*($maxwidth/$resizedwidth));
						$resizedwidth=$maxwidth;
						
					}
					$image.=" height=\"$resizedheight\"  width=\"$resizedwidth\" alt='Image'";
					
				}
				$image.=">";
			} else {
				$image=translate_inline("Avatar not validated yet");
			}		
			output_notl($image,true);
		}
	}

	public function get_twoweapon() {
		if (!isset($this->twoweapon)) return 0;
		return $this->twoweapon;
	}
	
	public function set_twoweapon($twoweapon) {
		$this->twoweapon=$twoweapon;	
	}

	
	public function set_bankai($bankai) {
		$this->bankai=$bankai;
	}
	
	public function get_bankai() {
		$ban=new bankai;
		if (!is_a($this->bankai,"bankai")) {
			$this->bankai=$ban;
		}
		return $this->bankai;
	}
	
	public function set_shikai($shikai) {
		$this->shikai=$shikai;
	}
	
	public function get_shikai() {
		$ban=new shikai;
		if (!is_a($this->shikai,"shikai")) {
			$this->shikai=$ban;
		}
		return $this->shikai;
	}	
	
	public function get_type() {
		$form=$this->get_all_types();
		$type=(int)$this->type;
		return $form[$type];
	}
	
	public function get_raw_type() {
		$type=(int)$this->type;
		return $type;
	}
	
	public function set_type($type) {
		/*
			0=nothing specific
			1=all-released type
			2=kidou-based
			*/
		$this->type=$type;	
	}
	
/*	public function __sleep() {
		if ($this->shikai!='') $this->shikai=serialize($this->shikai);
		if ($this->bankai!='') $this->bankai=serialize($this->bankai);
	
	}

	public function __wakeup() {
		if ($this->shikai!='') $this->shikai=unserialize($this->shikai);
		if ($this->bankai!='') $this->bankai=unserialize($this->bankai);
	
	}
	*/
	public function __construct($serializedpref=false) {
		/*if ($serializedpref=='') return;
		$init=unserialize(stripslashes($serializedpref));
		debug($init);
		$array=array("name","type","form","image","released","shikai","bankai");
		foreach ($array as $entry) {
			if (!isset($init[$entry])) continue;
			$this->$$entry=$init[$entry];
		}
		*/
	}



}

class zanpakutou_base {
	private $name;
	private $eval_newday;
	private $eval_activation;
	private $eval_deactivation;
	private $duration;
	private $jutsu;
	private $text;
	private $achievedate;
	private $image;
	

	public function set_name($name) {
		$this->name=str_replace("\"","{quote}",$name);
	}
	
	public function get_name() {
		if (!isset($this->name)) return '';
		return str_replace("{quote}","\"",$this->name);
	}

	public function set_eval_newday($eval_newday) {
		$this->eval_newday=$eval_newday;
	}
	
	public function get_eval_newday() {
		return $this->eval_newday;
	}

	public function set_text($text) {
		$this->text=str_replace("\"","{quote}",$text);
	}
	
	public function get_text() {
		if (!isset($this->text)) return '';
		return str_replace("{quote}","\"",$this->text);
	}

	public function set_eval_activation($eval_activation) {
		$this->eval_activation=$eval_activation;
	}
	
	public function get_eval_activation() {
		return $this->eval_activation;
	}

	public function set_eval_deactivation($eval_deactivation) {
		$this->eval_deactivation=$eval_deactivation;
	}
	
	public function get_eval_deactivation() {
		return $this->eval_deactivation;
	}

	public function set_duration($duration) {
		$this->duration=$duration;
	}
	
	public function get_duration() {
		return $this->duration;
	}
	
	public function set_achievedate($achievedate) {
		$this->achievedate=$achievedate;
	}
	
	public function get_achievedate() {
		return $this->achievedate;
	}

	public function set_jutsu($jutsu) {
		$this->jutsu=$jutsu;
	}
	
	public function get_jutsu() {
		return $this->jutsu;
	}	
	
	public function get_image() {
		if (!isset($this->image)) return '';
		return $this->image;
	}
	
	public function set_image($image) {
		$this->image=$image;	
	}

	public function display_image() {
		if ($this->image!='') {
			output_notl("<img src=\"".$this->image."\" alt='Image'>",true);
		}
	}

	public function __construct($pref=false) {
		/*if ($pref===false) return;
		$init=unserialize(stripslashes($pref));
		$array=array("name","eval_newday","eval_activation","eval_deactivation","duration","jutsu","text");
		foreach ($array as $entry) {
			$this->$$entry=$init[$entry];
		}		
		*/
	}

}

class shikai extends zanpakutou_base {


}

class bankai extends zanpakutou_base {

}

class zanpakutou_main implements module_base {

	private $zanpakutou;
	
	const shikaicosts=3;
	const bankaicosts=10;

	public function getmoduleinfo() {
		$info = array
			(
			"name"=>"Zanpakutou (Bleach)",
			"version"=>"1.1",
			"author"=>"`2Oliver Brendel",
			"category"=>"Zanpakutou",
			"download"=>"",
			"settings"=>array(
				"Zanpakutou Settings,title",
				"Image Size restrictions,title",
				"restrictsize"=>"Is the size restricted?,bool|1",
				"maxwidth"=>"Max. width of personal image (Pixel),range,20,400,20|200",
				"maxheight"=>"Max. height of personal image (Pixel),range,20,400,20|200",
				),
			"requires"=>array(
				"specialtysystem"=>"1.0|Specialtysystem by Oliver Brendel",
				),
			"prefs"=> array(
				"Preferences Zanpakutou,title",
				"zanpakutou"=>"Serialized Class Zanpakutou,viewonly",
				),
			);
		return $info;
	}

	public function install(){
		module_addhook("biostat");
		module_addhook("superuser");
		module_addhook("newday");
		module_addhook("fightnav-specialties");
		module_addhook("apply-specialties");
		return true;
	}

	public function uninstall(){
		return true;
	}

	public function do_hook($hookname,$args){
		if (!is_a($this->zanpakutou,"zanpakutou")) {
			$zan=get_module_pref('zanpakutou','zanpakutou');
			if ($zan=='') {
				$zan='';
			} else $zan=unserialize(stripslashes($zan));//debug($zan);
			$this->zanpakutou=$zan;
		}
		$hookname=str_replace("-","_",$hookname); //need to do this as - breaks in function names
		$method="hook_".$hookname;
		if (method_exists($this,$method)) $args=$this->$method($args);
		return $args;
	}
	
	private function hook_fightnav_specialties($args) {
		global $session;
		$zan=$this->zanpakutou;
		if ($zan=="") return $args;
		$name=$zan->get_name();
	

		if ($name=="") return $args;
		
		require_once("lib/buffs.php");
		require_once("modules/specialtysystem/functions.php");
		$uses=specialtysystem_availableuses();
		$cost=self::shikaicosts;
		$bancost=self::bankaicosts;
		$shi=$zan->get_shikai();
		$ban=$zan->get_bankai();
		
		if ($shi->get_text()!='' && !$zan->is_released() && $uses>=$cost) {
			$script = $args['script'];
			if ($session['user']['race']=="Menos" || $session['user']['race']=="Arrancar") {
			addnav("Resurrección");
			addnav(array("Release: `\$%s`)(%s reiatsu)",$shi->get_text(),$cost),$script."op=fight&skill=shikairelease");
			} else {
			addnav("Zanpakutō");
			addnav(array("Release: `\$%s`)(%s reiatsu)",$shi->get_text(),$cost),$script."op=fight&skill=shikairelease");
		}
		}
		
		if ($ban->get_text()!='' && !has_buff("bankai") && $uses>=$bancost) {
			$script = $args['script'];
			if ($session['user']['race']=="Menos" || $session['user']['race']=="Arrancar") {
			addnav("Resurrección");
			addnav(array("Segunda Etapa: `\$%s`)(%s reiatsu)",$ban->get_text(),$bancost),$script."op=fight&skill=bankairelease");
			} else {
			addnav("Zanpakutō");
			addnav(array("Final Release: `\$%s`)(%s reiatsu)",$ban->get_text(),$bancost),$script."op=fight&skill=bankairelease");
		}
		}
		return $args;
	}
	
	private function hook_apply_specialties($args) {
		$op=httpget('skill');
		if ($op!="shikairelease" && $op!="bankairelease") return $args;
		$zan=&$this->zanpakutou;
		switch ($op) {
			case "shikairelease":
				$success=$zan->release();
				if ($success) {
					require_once("modules/specialtysystem/functions.php");
					$uses=specialtysystem_availableuses();
					$cost=self::shikaicosts;
					set_module_pref("cache",'',"specialtysystem");
					specialtysystem_incrementuses("specialtysystem_binding",$cost);
					require_once("modules/zanpakutou/func.php");
					set_zanpakutou($zan);
				}
				break;
			case "bankairelease":
				$success=$zan->bankai_release();
				if ($success) {
					require_once("modules/specialtysystem/functions.php");
					$uses=specialtysystem_availableuses();
					$cost=self::bankaicosts;
					set_module_pref("cache",'',"specialtysystem");
					specialtysystem_incrementuses("specialtysystem_binding",$cost);
					require_once("modules/zanpakutou/func.php");
					set_zanpakutou($zan);
				}
				break;
		} 
		return $args;		
	}
	
	private function hook_biostat($args) {
		global $session;
		$zan=get_module_pref('zanpakutou','zanpakutou',$args['acctid']);
		$zanpakutou=unserialize(stripslashes($zan));
		if (!is_a($zanpakutou,"zanpakutou")) {
			return $args;
		}		
		$name=$zanpakutou->get_name();
		if ($name!='') {
			rawoutput("<table style='border:0px;'>");
			if ($session['user']['race']=="Menos" || $session['user']['race']=="Arrancar") {
			output("<tr><td>`^Resurrección:</td><td></td><td> `2%s</td><td></td></tr>",$zanpakutou->get_name(),true);
			$shikai=$zanpakutou->get_shikai();
			$bankai=$zanpakutou->get_bankai();
			} else {
			output("<tr><td>`^Zanpakutō:</td><td></td><td> `2%s</td><td></td></tr>",$zanpakutou->get_name(),true);
			$shikai=$zanpakutou->get_shikai();
			$bankai=$zanpakutou->get_bankai();
			}
			if ($zanpakutou->get_image()!='') {
				rawoutput("<tr><td></td><td></td><td>");
				$zanpakutou->display_image();
				rawoutput("</td><td></td></tr>");
			}
			if ($shikai->get_text()!='') output("<tr><td></td><td>awakens with</td><td>%s</td><td></td></tr>",$shikai->get_text(),true);
			output("<tr><td></td><td>Type:</td><td>`2%s</td><td></td></tr>",$zanpakutou->get_type(),true);
			output("<tr><td></td><td>Form:</td><td>`2%s</td><td></td></tr>",$zanpakutou->get_form(),true);
			if ($bankai->get_name()!='') {

				if ($bankai->get_achievedate()>date("Y-m-d H:i:s",strtotime("-10 days"))) {
					//fewer than 10 days
					output("<tr><td></td><td></td><td>%s`\$ has recently obtained `b`i`lB`4ankai`i`b`0</td><td></td></tr>",$args['name'],true);
				} else if ($session['user']['race']=="Menos" || $session['user']['race']=="Arrancar") {
					output("<tr><td></td><td></td><td>%s`\$ has attained `b`i`lS`4egunda `lE`4tapa `i`b`0</td><td></td></tr>",$args['name'],true);
				} else {
					output("<tr><td></td><td></td><td>%s`\$ has attained `b`i`lB`4ankai`i`b`0</td><td></td></tr>",$args['name'],true);
				}
			}
			rawoutput("</table>");
		}
		return $args;
	}
	
	
	
	private function hook_superuser($args) {
		global $session;
		if (($session['user']['superuser']&SU_MEGAUSER) == SU_MEGAUSER) {
			addnav("Zanpakutō");
			addnav("Zanpakutō Editor","runmodule.php?module=zanpakutou&op=editor");
		}
		return $args;
	}
	
	private function hook_newday($args) {
		global $session;
		$zan=get_module_pref('zanpakutou','zanpakutou',$session['user']['acctid']);
		// debug($zan);
		$zanpakutou= unserialize(stripslashes($zan));
		if (!is_a($zanpakutou,"zanpakutou")) {
			return $args;
		}		
		if ($session['user']['weapon']!=$zanpakutou->get_name()) {
			$session['user']['weapon']=$zanpakutou->get_name();
			debuglog("reset user zanpakutou to name ".$zanpakutou->get_name());
		} else debuglog("user is carrying his zanpakutou ".$zanpakutou->get_name());

		return $args;
	}

	public function run() {
		global $session;
		$op=httpget('op');
		switch ($op) {
			case "editor":
				debug($_POST);
				page_header("Zanpakutō Editor");
				$subop=httpget('subop');
				if ($subop=='') $subop="search";
				require_once("lib/superusernav.php");
				superusernav();			
				addnav("Actions");
				addnav("Edit a Zanpakutou","runmodule.php?module=zanpakutou&op=editor&subop=search");
				switch ($subop) {
					case "save":
						$target=(int)httppost('target');
						if ($target==0) {
							output("ERROR, no target supplied, ==0");
							break;
						}
						addnav("Back to editing this zanpakutō","runmodule.php?module=zanpakutou&op=editor&subop=edit&target=$target");
						//debug($_POST);
						$zan=get_module_pref('zanpakutou','zanpakutou',$target);
						$zanpakutou=unserialize(stripslashes($zan));
						//debug($zan);
						if (!is_a($zanpakutou,"zanpakutou")) {
							debug("new zan");
							$zanpakutou=new zanpakutou;
						}						
						$name=httppost('name');
						if ($name!=$zanpakutou->get_name()) {
							$zanpakutou->set_name($name);
						}
						
						$name=(int)httppost('type');
						if ($name!=$zanpakutou->get_type()) {
							$zanpakutou->set_type($name);
						}
						
						$name=(int)httppost('powerlevel');
						if ($name!=$zanpakutou->get_powerlevel()) {
							$zanpakutou->set_powerlevel($name);
						}
						
						$name=(int)httppost('form');
						if ($name!=$zanpakutou->get_form()) {
							$zanpakutou->set_form($name);
						}
						
						$name=httppost('image');
						if ($name!=$zanpakutou->get_image()) {
							$zanpakutou->set_image($name);
						}
						
						$name=httppost('image_validated');
						if ($name!=$zanpakutou->image_validated()) {
							$zanpakutou->image_validate();
						}

						$shikai=$zanpakutou->get_shikai();
						
						$name=httppost('shikai_name');
						if ($name!=$shikai->get_name()) {
							$shikai->set_name($name);
						}

						$name=httppost('shikai_text');
						if ($name!=$shikai->get_text()) {
							debug("setting shikai text to $name");
							$shikai->set_text($name);
						}
						
						$name=httppost('shikai_achievedate');
						if ($name!=$shikai->get_achievedate()) {
							$shikai->set_achievedate($name);
						}
						
						
						$bankai=$zanpakutou->get_bankai();
						
						$name=httppost('bankai_name');
						$bankai=$zanpakutou->get_bankai();
						if ($name!=$bankai->get_name()) {
							$bankai->set_name($name);
						}

						$name=httppost('bankai_text');
						if ($name!=$bankai->get_text()) {
							debug("setting bankai text to $name");
							$bankai->set_text($name);
						}
						
						$name=httppost('bankai_achievedate');
						if ($name!=$bankai->get_achievedate()) {
							$bankai->set_achievedate($name);
						}
						
						$zanpakutou->set_shikai($shikai);
						$zanpakutou->set_bankai($bankai);
						//debug(serialize($shikai));
						$zan=serialize($zanpakutou);
						//debug($zan);
						set_module_pref('zanpakutou',$zan,'zanpakutou',$target);
						output("TODO");
						break;
					case "edit":
						$target=(int)httpget('target');
						if ($target==0) {
							output("ERROR, no target supplied, ==0");
							break;
						}
						addnav("Back to editing this zanpakutō","runmodule.php?module=zanpakutou&op=editor&subop=edit&target=$target");
						$zan=get_module_pref('zanpakutou','zanpakutou',$target);
						//debug($zan);
						$zanpakutou= unserialize(stripslashes($zan));
						if (!is_a($zanpakutou,"zanpakutou")) {
							$zanpakutou=new zanpakutou;
						}
						$name=translate_inline("Zanpakutō");
						$property=translate_inline("Property");
						$value=translate_inline("Value");
						$newvalue=translate_inline("New value");
						$submit=translate_inline("Save");
						addnav("","runmodule.php?module=zanpakutou&op=editor&subop=save");
						rawoutput("<form action='runmodule.php?module=zanpakutou&op=editor&subop=save' method='POST'><input type='hidden' name='target' value='$target'>");
						//<table style='border:0px width: 100%;'><tr><td>$name</td><td>$property</td><td>$value</td><td>$newvalue</td></tr>");
						
						$z=$zanpakutou;
						$shikai=$z->get_shikai();
						$bankai=$z->get_bankai(); 
						
						$formenum='';
						$dummy=$z->get_all_forms();
						foreach ($dummy as $key=>$val) {
							$formenum.=",".$key.",".$val;
						}
						
						$typeenum='';
						$dummy=$z->get_all_types();
						foreach ($dummy as $key=>$val) {
							$typeenum.=",".$key.",".$val;
						}
						
						require_once("lib/showform.php");
						$show=array(	
							"Zanpakutō Properties,title",
							"name"=>"Name of the weapon(s),text",
							"form"=>"Form of the weapon(s),enum".$formenum,
							"type"=>"Type of the weapon(s),enum".$typeenum,
							"powerlevel"=>"Powerlevel",
							"image"=>"Image of the weapon(s),text",
							"image_validated"=>"Is the image validated?,bool",
							"Shikai Properties,title",
							"shikai_name"=>"Name,text",
							"shikai_text"=>"Text to activate Shikai,text",
							"shikai_achievedate"=>"Date when Shikai was achieved - in YYYY:MM:DD HH:MM:SS,text",
							"shikai_evalnewday"=>"Eval String on Newday,textarearesizeable",
							"shikai_eval_activation"=>"Eval String on Release,textarearesizeable",
							"shikai_eval_deactivation"=>"Eval String on Sealing,textarearesizeable",
							"shikai_duration"=>"How long does Shikai last (-1 for permanent until newday)?,int",
							"shikai_jutsu"=>"No idea what to do there",
							"Bankai Properties,title",
							"bankai_name"=>"Name,text",
							"bankai_text"=>"Text to activate Bankai,text",
							"bankai_achievedate"=>"Date when Bankai was achieved - in YYYY:MM:DD HH:MM:SS,text",
							"bankai_evalnewday"=>"Eval String on Newday,textarearesizeable",
							"bankai_eval_activation"=>"Eval String on Release,textarearesizeable",
							"bankai_eval_deactivation"=>"Eval String on Sealing,textarearesizeable",
							"bankai_duration"=>"How long does Bankai last (-1 for permanent until newday)?,int",
							"bankai_jutsu"=>"No idea what to do there",							
							);
						$set=array(
							"name"=>$z->get_name(),
							"form"=>$z->get_raw_form(),
							"type"=>$z->get_raw_type(),
							"powerlevel"=>$z->get_powerlevel(),
							"image"=>$z->get_image(),
							"image_validated"=>$z->image_validated(),
							"shikai_name"=>$shikai->get_name(),
							"shikai_text"=>$shikai->get_text(),
							"shikai_achievedate"=>$shikai->get_achievedate(),
							"shikai_evalnewday"=>$shikai->get_eval_newday(),
							"shikai_eval_activation"=>$shikai->get_eval_activation(),
							"shikai_eval_deactivation"=>$shikai->get_eval_deactivation(),
							"shikai_duration"=>$shikai->get_duration(),
							"shikai_jutsu"=>'',
							"bankai_name"=>$bankai->get_name(),
							"bankai_text"=>$bankai->get_text(),
							"bankai_achievedate"=>$bankai->get_achievedate(),
							"bankai_evalnewday"=>$bankai->get_eval_newday(),
							"bankai_eval_activation"=>$bankai->get_eval_activation(),
							"bankai_eval_deactivation"=>$bankai->get_eval_deactivation(),
							"bankai_duration"=>$bankai->get_duration(),							
							);
						showform($show,$set);
		
						rawoutput("</form>");
						
						
						
						break;					
					case "search":
						$limit=100;
						$target=httppost('target');
						$ta=addslashes($target);
						output("`c`b`tFind User`0`b`c`n`n");
						
						if ($target!='') {
							$sql="SELECT a.name as name,a.acctid FROM ".db_prefix('accounts')." AS a WHERE (name LIKE '%$ta%' OR login LIKE '%$ta') limit $limit;";
							$result=db_query($sql);
							if (db_num_rows($result)<1) {
								$end=strlen($target);
								$search='%';
								for ($x=0;$x<$end;$x++){
									$search .= substr($target,$x,1)."%";
								}
								$sql="SELECT a.name as name,a.acctid FROM ".db_prefix('accounts')." AS a WHERE name LIKE '$search' OR LOGIN LIKE '$search' LIMIT $limit;";
								$result=db_query($sql);
							}
							if (db_num_rows($result)>0) {
								$name=translate_inline("Name");
								$message=translate_inline("Message");
								rawoutput("<center><table cellpadding='3' cellspacing='0' border='0' ><tr class='trhead'><td>$name</td></tr>");//<td>$message</td></tr>");
								while ($row=db_fetch_assoc($result)) {
									$class=($class=='trlight'?'trdark':'trlight');
									rawoutput("<tr class='$class'><td>");
									$link="<a href='runmodule.php?module=zanpakutou&op=editor&subop=edit&target=".$row['acctid']."'>".$row['name']."</a>";
									addnav("","runmodule.php?module=zanpakutou&op=editor&subop=edit&target=".$row['acctid']);
									output_notl($link,true);
									// rawoutput("</td><td>");
									// output_notl($row['message']);
									rawoutput("</td></tr>");
								}
								rawoutput("</table></center>");
							} else {
								output("`\$Sorry, I was unable to find anybody with that supplied name!`n`n");
							}
						}
						output("`xWhom are you looking for?`n`4Try to enter the name without the title, or completely to narrow down the search (results limited to %s hits).`2`n`n",$limit);
						rawoutput("<form action='runmodule.php?module=zanpakutou&op=editor&subop=search' method='POST'>");
						addnav("","runmodule.php?module=zanpakutou&op=editor&subop=search");
						rawoutput("<input type='input' length='50' name='target' value='".addslashes($target)."'><br>");
						$submit=translate_inline("Search!");
						rawoutput("<input type='submit' class='button' value='$submit'>");
						rawoutput("</form>");
						break;							
				
				}
				// TODO
			break;
		}
		page_footer();
	}
	
	

	
	public function __construct() {
		
	//segfault
	/*$sql="SELECT value from ".db_prefix("module_userprefs")." WHERE modulename='z	
		if ($zan=='') {
			$zan='';
		} else $zan=unserialize($zan);
		
		$this->zanpakutou=$zan;*/
	}
}

?>
