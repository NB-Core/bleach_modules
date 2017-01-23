<?php

/*
hooknames and uses:
zanpakutou-selection =>put in information about it, fields: name,desc,link and check if he can have it or not, if not, return '' to the link and explain in "reason" why you won't train him
zanpakutou-training => hook in there to add a training addnav and do there what you want... let him fetch an item to proceed or whatever
*/

/*interface to make it compatible for old modules*/
function zanpakutou_training_getmoduleinfo() {
	$class=new zanpakutou_training;
	return $class->getmoduleinfo();
	unset($class);
	return;
}

function zanpakutou_training_install() {
	$class=new zanpakutou_training;
	$class->install();
	unset($class);
	return;
}

function zanpakutou_training_uninstall() {
	$class=new zanpakutou_training;
	$class->uninstall();
	unset($class);
	return;
}

function zanpakutou_training_dohook($hookname,$args) {
	$class=new zanpakutou_training;
	$hookname=str_replace("-","_",$hookname);
	return $class->do_hook($hookname,$args);
	unset($class);
	return;
}

function zanpakutou_training_run() {

	$class=new zanpakutou_training;

	//two runmodes - one for the bankai special training at the lodge. First point in lodge hook
	// always carry the center parameter, or you will go wrong!
	$center=httpget('center');
	if ($center=="") $center=httppost('center');
	if ($center=="bankai") {
		$class->run_bankai();
	}	else {
		$class->run();
	}
	
	unset($class);
	return;
}

function zanpakutou_training_runevent($type,$link) {
	$class=new zanpakutou_training;
	$class->runevent($type,$link);
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


class zanpakutou_training implements module_base {

	private $masters;

	public function getmoduleinfo() {
		$info = array
			(
			"name"=>"Zanpakutou Training(Bleach)",
			"version"=>"1.0",
			"author"=>"`2Oliver Brendel",
			"category"=>"Zanpakutou",
			"download"=>"",
			"requires"=>array(
				"specialtysystem"=>"1.0|Specialty System Core by `2Oliver Brendel",
				"training_bleach"=>"1.0|Training Grounds by `2Oliver Brendel",
				"zanpakutou"=>"1.1|Zanpakutou - Shikai - Bankai by `2Oliver Brendel",
				),
			"settings"=>array(
				"Zanpakutou Training Settings,title",
				//two caches
				"masterclasses"=>"Internal Master Array - touch and die,viewonly",
				"specialmasterclasses"=>"Internal Special Master Array - touch and die,viewonly",
				),
			"prefs"=> array(
				"Preferences Zanpakutou,title",
				"master"=>"ID of the master,int",
				"special"=>"Master from the Special List?,int",
				"awakened"=>"Shikai awake?,bool",
				"awakened_counter"=>"Counter for the awakened,int"
				),
			);
		return $info;
	}

	public function install(){
		module_addhook("traininggrounds");
		module_addeventhook("forest", "return 100;");
		module_addhook("dragonkill"); //power level up to 5
		
		module_addhook("lodge-desc"); //bankai power up to 9
		
		//wipe the caches
		set_module_setting("masterclasses","","zanpakutou_training");
		set_module_setting("specialmasterclasses","","zanpakutou_training");
		return true;
	}

	public function uninstall(){
		return true;
	}

	public function do_hook($hookname,$args){
		global $session;
		$hookname=str_replace("-","_",$hookname); //need to do this as - breaks in function names
		$method="hook_".$hookname;
		if ($session['user']['race']=="Arrancar") return $args; //not for arrancar		

		if (method_exists($this,$method)) $args=$this->$method($args);
		return $args;
	}
	
	private function hook_dragonkill($args) {
		require_once("modules/zanpakutou/func.php");
		$zanpakutou=get_zanpakutou(false);		
		if ($zanpakutou->get_powerlevel()<=4) {
			$zanpakutou->set_powerlevel($zanpakutou->get_powerlevel()+1);
			set_zanpakutou($zanpakutou);
			output("`n`2%s`q has gained a powerlevel and has now a power level of %s!",$zanpakutou->get_name(),$zanpakutou->get_powerlevel());
		}
		return $args;
	}
	
	private function hook_lodge_desc($args) {
		require_once("modules/zanpakutou/func.php");
		global $session;
		$finaltests=25;
		$zanpakutou=get_zanpakutou(false);
		$level=$zanpakutou->get_powerlevel();
		$ban=$zanpakutou->get_bankai();		
		if ( $ban->get_text()!='') {
			output("Urahara also offers Bankai training, which you already completed.`n`n");
			return $args;
		}
		if ($level>4 && $level<=9 && $session['user']['dragonkills']>=$finaltests) {
			if ($zanpakutou->get_powerlevel()==5) {
				output("`n`2\"`\$I might have an offer to train you for bankai - if you are interested of course... you seem to able to make it. It will be costly, but worth your time.`2\"`n`n");
			} else {
				output("`n`2\"`\$If you want to continue your bankai training, I am all ears!`2\"`n`n");
			}
			addnav("Bankai Training");
			addnav("Improve your bankai summoning skills","runmodule.php?module=zanpakutou_training&center=bankai");
		} else {
			output("Urahara also offers Bankai training ... but you do not qualify yet for it... You need at least a powerlevel of 5 and %s final tests.`n`n",$finaltests);
		}
		return $args;
	
	}
	
	
	private function hook_traininggrounds($args) {	
		addnav("Zanpakutou");
		addnav("Zanpakutou Training","runmodule.php?module=zanpakutou_training");
		return $args;
	}
	
	private function mystrip($text) {
		$text=str_replace(chr(13),"`n",$text);
		$text=str_replace('`c','',$text);
		$text=str_replace('`i','',$text);
		$text=str_replace('`b','',$text);
		$text=str_replace('\"','\'',$text);
		$text=str_replace('\\\'','\'',$text);
return $text;
		//return htmlentities($text,ENT_COMPAT,getsetting('charset','ISO-8859-1'));	
	}
	

	public function runevent($type,$link) {
		global $session;
		$u=&$session['user'];
		require_once("modules/zanpakutou/func.php");
		$zanpakutou=get_zanpakutou(false);
		$from = "forest.php?";
		if (!$this->masters) $this->masters=new zanpakutou_master();
		$u['specialinc'] = "module:zanpakutou_training";
		$op = httpget('op');
		switch ($op) {
			case "setname":
				$name=$this->mystrip(httppost('zanpakutou'));
				if ($name=='') {
					output("`x\"`\$Silly you... I have a name!`x\"");
					break;				
				}
				// key 0 is unknown
				$dummy=$zanpakutou->get_all_forms();
				$form=e_rand(1,count($dummy)-1);
				$dummy=$zanpakutou->get_all_types();
				$type=e_rand(1,count($dummy)-1);				
				$zanpakutou->set_name($name);
				$zanpakutou->set_type($type);
				$zanpakutou->set_form($form);
				output("`xAs you are about to call `\$%s`x as you also realize the form and type of your Zanpakutō...`n`n",$name);
				output("You have a Shikai formed like a `\$%s`x whereas the type is : `\$%s`x...",$zanpakutou->get_form(),$zanpakutou->get_type());
				addnav("Back...",$from."op=fight");
				$buff=$zanpakutou->get_unique_releasebuff($name,$form,$type);
				$buff['expireafterfight']=1;
				apply_buff('zanpakutou_awakened',$buff);
				strip_buff('shikai_awaken');
				set_zanpakutou($zanpakutou);
				$session['user']['weapon']=$name;
				break;				
			case "initialcall":
				addnav("Refresh",$from."op=initialcall");
				output("`xYou sense a tingling sensation coming from your blade that echoes in your entire body.`n`nTime seems to stand still as a cloaked figure appears before your inner eye, vague in shape and form, but possibly human. Or?`n`n");
				output("\"`\$What are you waiting for?`nYou Are One, The Enemy Is One.`nWhat Is There To Fear?`n`n...`n`nCast Off Your Fear`nLook Forward`nGo Forward`nNever Stand Still`nRetreat And You Will Age`nHesitate And You Will Die`n`nShout! My name is...");
				$submit=translate_inline("Shout!");
				require_once("lib/commentary.php");
				rawoutput("<form action='".$from."op=setname' method='POST'>");
				addnav("",$from."op=setname");
				$script=previewfield("zanpakutou",false,false,false,false,false);
				rawoutput("$script<input type='submit' class='button' value='$submit'></form>");
				output("`i`4Note: Color codes can be used, special chars can be used, italic/bold/centered is disabled and will not be saved (!)`i");
				break; 
			case "hilfeichbineinadminholtmichhierraus":
				output("Due to your powers as a god you teleport yourself out of it.");
				$u['specialinc'] = "";
				break;
			case "combatready":
				require_once("lib/battle-skills.php");
				$extraatt=e_rand(1,$session['user']['level']);
				$extradef=$extraatt;
				$extrahp=$extraatt*20;
				require_once("lib/playerfunctions.php");
				
				$this->masters->getRandomMaster(25);
				
				$master=$this->masters->getMasterName();
				$weapon=$this->masters->getMasterWeapon();	
				$masterid=$this->masters->getMasterId();
				$masterspecial=$this->masters->getMasterSpecial();
				
				$badguy = array(
					"creaturename"=>$master,
					"creaturelevel"=>$session['user']['level']+e_rand(1,3),
					"creatureweapon"=>$weapon,
					"creatureattack"=>get_player_attack()+$extraatt,
					"creaturedefense"=>get_player_defense()+$extradef,
					"creaturehealth"=>$u['level']*10+450+$extrahp,
					"creatureexp"=>1,
					"hidehitpoints"=>true,
					"masterid"=>$masterid,
					"masterspecial"=>$masterspecial,
					"diddamage"=>0
				);//debug($badguy);
			   	$battle=true;
				$session['user']['badguy'] = createstring($badguy);
				$op = "combat";
				httpset('op', $op);
			case "combat": case "fight":
				if (e_rand(0,20)) {
					//in every 50th fight round, a chance for the shikai to awaken is here
					increment_module_pref('awakened_counter',1);
				}
				if (((int)get_module_pref('awakened_counter'))>20) {
					require_once("lib/buffs.php");
					if (!has_buff('shikai_awaken') && $zanpakutou->get_name()=='') {
						apply_buff('shikai_awaken',
							array(
							"name"=>"`\$Z`4anpakutou",
							"rounds"=>50,
							"atkmod"=>1.1,
							"expireafterfight"=>1,
							"defmod"=>1.3,
							"minioncount"=>1,
							"roundmsg"=>"`\$Call out my name...",
							"schema"=>"module-zanpakutou_training",						
							)
						);
						set_module_pref('awakened',1);
					}
				}
				include("battle.php");
				if ($victory){ //no exp at all
					require_once("lib/forestoutcomes.php");
					forestvictory(array($badguy));
					if ($zanpakutou->get_name()!='') {
						$masterid=$badguy['masterid'];
						$masterspecial=$badguy['masterspecial'];
						set_module_pref("master",$masterid);
						set_module_pref("special",$special);						
						output("`n`n`l%s`l says, \"Your zanpakutō revealed his or her name to you... you know this is quite some excitement, and perhaps you should visit me later in the `\$Training grounds`l and work on it more. `n`nThanks for the sparring...\"",$badguy['creaturename']);

					}
					$session['user']['specialinc'] = "";
					$badguy=array();

					$session['user']['badguy']="";
			    }elseif ($defeat){ //but a loss of course if you die
					//awakened?
					require_once("lib/forestoutcomes.php");
					$session['user']['specialinc'] = "";
					forestdefeat(array($badguy));
					addnav("Return");
					addnav("Return to the Shades","shades.php");
					$session['user']['specialmisc'] = "";
					$session['user']['hitpoints']=0;
					$session['user']['alive']=0;
					if ($zanpakutou->get_name()!='') {
						$masterid=$badguy['masterid'];
						$masterspecial=$badguy['masterspecial'];
						set_module_pref("master",$masterid);
						set_module_pref("special",$special);
						output("`n`n`l%s`l says, \"Your zanpakutō revealed his or her name to you... you know this is quite some excitement, and perhaps you should visit me later in the `\$Training grounds`l `4when you are alive again`l and work on it more. `n`nThanks for the sparring...\"",$badguy['creaturename']);

					}					
					$badguy=array();
					$session['user']['badguy']="";
			    }else{
					require_once("lib/fightnav.php");
					$allow = true;
					blocknav("forest.php?op=fight&auto=five");
					blocknav("forest.php?op=fight&auto=ten");
					blocknav("forest.php?op=fight&auto=full");
					if (has_buff('shikai_awaken')) {
						if (e_rand(0,2)==1) {
							addnav("`\$Call out thy name...",$link."op=initialcall");						
						}
					}
					fightnav($allow,false);
					if ($session['user']['superuser'] & SU_DEVELOPER) addnav("Escape to Village",$link."op=hilfeichbineinadminholtmichhierraus");
				}
				break;
			default:
				if ($zanpakutou->get_name()!='' || $u['dragonkills']<20 || $session['user']['race']=="Quincy" || $session['user']['race']=="Arrancar" || $session['user']['race']=="Menos"){
					//no need to determine a name
					$gold=e_rand(2,300);
					output("`^Lucky! You find `4%s gold`^ while idling around!",$gold);
					$u['gold']+=$gold;
					$u['specialinc']='';
					break;
				} 
				//go on as instructed
				httpset('op',"combatready");
				redirect($from."op=combatready");
				return;
		}
	}
	
	public function run_bankai() {
		global $session;
		page_header("Urahara - Zanpakutō Training");
		addnav("Navigation");
		addnav("Back to the Main Shop","lodge.php");
		
		output("`#`b`c`n`2Bankai Training Grounds - `vZ`lanpakutō`0`c`b`n`n");
		$trainer="`2Tessai";
		require_once("modules/specialtysystem/datafunctions.php");
		$u=&$session['user'];
		if (!$this->masters) $this->masters=new zanpakutou_master();
		require_once("modules/zanpakutou/func.php");
		$zanpakutou=get_zanpakutou(false);
		$op = httpget('op');
		$level = $zanpakutou->get_powerlevel();
		$multi=870;
		$gemcost=75;
		$gold=$level*$multi;
		$chance=.03*100;
		
		$from="runmodule.php?module=zanpakutou_training&center=bankai";
		addnav(array("Back to %s",$trainer),$from);
		addnav("Actions");
		
		$bankaiformname = $zanpakutou->get_bankaiforms($zanpakutou->get_raw_type());
		$bankaiformweapons = $zanpakutou->get_bankaiforms_weapons($zanpakutou->get_raw_type());
		
		$gender=($u['sex']?"mistress":"master");

		switch ($op) {
			case "standardtrain":
				$u['gold']-=$gold;
				debuglog("paid $gold for new powerlevel of ".$zanpakutou->get_powerlevel);
				if (e_rand(0,100)<=$chance) {
					// success
					output("`4You have `\$SUCCESSFULLY`4 improved yourself towards your way to bankai ... you gain a power level!`n`nYou are now at level %s!`n",$zanpakutou->get_powerlevel()+1);
					$zanpakutou->set_powerlevel($zanpakutou->get_powerlevel()+1);
					set_zanpakutou($zanpakutou);
				} else {
					output("`4You have `lFAILED`4 to improve yourself towards your way to bankai ... `n`nYou stay at level %s!`n",$zanpakutou->get_powerlevel());
				}
				if ($level>7) {
					output("You feel you gain nothing more from this kind of training.");
					break;
				}
				if ($u['gold']<$gold) {
					addnav("Standard Training (not enough gold!)","");
				} else {
					addnav(array("Standard Training (%s gold)",$gold),$from."&op=standardtrain");
				}
				break;
			case "preptrain":
				$u['gems']-=$gemcost;
				debuglog("paid $gemcost for bankai");
				// success
				output("`4You have `\$SUCCESSFULLY`4 prepared yourself towards your way to the final bankai challenge... you gain a power level!`n`nYou are now at level %s!`n",$zanpakutou->get_powerlevel()+1);
				$zanpakutou->set_powerlevel($zanpakutou->get_powerlevel()+1);
				set_zanpakutou($zanpakutou);
				debuglog("reached new powerlevel ".$zanpakutou->get_powerlevel);
				
				break;
			case prefight:
				require_once("lib/battle-skills.php");
				require_once("lib/extended-battle.php");
				$badguy = array(
					"creaturename"=>translate_inline($bankaiformname),
					"creaturelevel"=>$session['user']['level']+3,
					"creatureweapon"=>translate_inline($bankaiformweapons),
					"creatureattack"=>get_player_attack()*4,
					"creaturedefense"=>round(get_player_defense*1.95, 0),
					"creaturehealth"=>round($session['user']['maxhitpoints']*7, 0), 
					"hidehitpoints"=>true,
					"diddamage"=>0,
					"type"=>"quest"
				);
				$session['user']['badguy']=createstring($badguy);
				$battle=true;
				suspend_buff_by_name('mount','`3You will certainly miss your fellow comrade...');
				suspend_companions('bankai');
				// drop through
			   	$battle=true;
				$op = "fight";
				httpset('op', $op);
			
			case fight:
				require_once("lib/fightnav.php");
				$session['user']['specialinc'] = "module:zanpakutou_training";
				blocknav("lodge.php");
				blocknav($from);
				include("battle.php");
				if ($victory) {	
					output("`4%s rushes to you, \"`xYoung %s! You did it! You have have bested the legendary power all Shinigami seek!`n`nQuick now: Rush to your master and finalize the name of your new form!`4\".`n`n",$trainer,$gender);
					output("`\$You have acquired `4B`xan`4K`xai`\$, the final release of your weapon. Use it wisely...");
					$zanpakutou->set_powerlevel($zanpakutou->get_powerlevel()+1);
					set_zanpakutou($zanpakutou);
					debuglog("acquired bankai powers.");
					addnav("Victory!");
					addnav("To the shop!","lodge.php");
					$session['user']['specialinc']='';
					unsuspend_buff_by_name('mount','`3You feel full of new inspiration along with your mount.');
					unsuspend_companions('bankai');
					addnav("Victory!");
					unblocknav($from);
					addnav("To the main shop",$from);
				} elseif ($defeat) {
					$session['user']['specialinc']='';
					$session['user']['gold']=0;
					$session['user']['experience']*=0.9;
					$session['user']['alive'] = false;
					$session['user']['hitpoints']=0;
					debuglog("was killed by his/her bankai.");
					addnews("%s`v's body turned up, torn to shreds!",$u['name']);
					addnav("Death...");
					addnav("To the shades","shades.php");
			    	} else {
					require_once("lib/fightnav.php");
					$allow = true;
					blocknav("forest.php?op=fight&auto=five");
					blocknav("forest.php?op=fight&auto=ten");
					blocknav("forest.php?op=fight&auto=full");
					fightnav($allow,false,"runmodule.php?module=zanpakutou_training&center=bankai");
				}
				break;
			default:
				output("`4Here in the underground extra-dimensional compound you see many hints for extensive bone-grinding training to achieve the final form all Shinigami desire.`n`nAs a supervisor (and fee-taker), %s`4 is here to assist you.`n`nYou are currently at level %s.`n`n",$trainer,$level);
				switch ($level) {
					case 10:
						output("%s`4 says: \"`xYoung %s, what are you looking for? Did you lose something here? Visit your master, if you want to improve further.`4\"",$trainer,$gender);
						break;
					case 9:
						output("%s`4 says: \"`xYoung %s, you are one step shy of bankai. The stage is set... enter if you dare... `4\"",$trainer,$gender);
						addnav("Final Challenge");
						addnav(array("Challenge %s",$bankaiformname),$from."&op=prefight");
						break;
					case 8:
						output("%s`4 says: \"`xYoung %s, there is nothing more you can learn by training yourself. You now need access to the most sacred battleground - and you need to summon your bankais true form. All of this will cost you %s gems - one time fee, if you succeed or not, we will only demand it once. You can try again (if you survive)...`4\"",$trainer,$gender,$gemcost);
						if ($u['gems']<$gemcost) {
							addnav("Prep Training (not enough gems!)","");
						} else {
							addnav(array("Prep Training (%s gems)",$gemcost),$from."&op=preptrain");
						}
						break;
					default:
						output("%s`4 says: \"`xYoung %s, you still need the basic training to challenge your Zanpakutō. For the small price of %s gold pieces, you may use this facility. Success is not guaranteed, however. Only %s out of 100 will achieve a step forward.`4\"",$trainer,$gender,$gold,$chance);
						if ($u['gold']<$gold) {
							addnav("Standard Training (not enough gold!)","");
						} else {
							addnav(array("Standard Training (%s gold)",$gold),$from."&op=standardtrain");
						}
				}
			
		}
		
		page_footer();	
	}

	public function run() {
		global $session;
		page_header("Training Grounds - Zanpakutō");
		addnav("Navigation");
		addnav("Back to the Main Grounds","train.php");
		output("`#`b`c`n`2Training Grounds - `vZ`lanpakutō`0`c`b`n`n");
		require_once("modules/specialtysystem/datafunctions.php");
		if (!$this->masters) $this->masters=new zanpakutou_master();
		require_once("modules/zanpakutou/func.php");
		$zanpakutou=get_zanpakutou(false);
		$op = httpget('op');
		$cost=array(0,48,225,585,990,1575,2250,2790,3420,4230,5040,5850,6840,8010,9000,10350,11500,13775,15850,17030,18270,20020,21150,22500,25550,30000,32000);	
		$multi=75;
		$gold=$session['user']['level']*$multi;


	// if you have an active shikai, but for some occult reason no master... fix
		if ($zanpakutou->get_name()!="" && get_module_pref('awakened') && (!get_module_pref('master') && !get_module_pref('special'))) {
			$this->masters->getRandomMaster(25);
			$masterid=$this->masters->getMasterId();
			set_module_pref("master",$masterid);
			set_module_pref("special",$special);						
			debuglog("set master to $masterid and special to $special");


		}
		
		switch ($op) {
		
			case "asktypes":
				$name=$this->masters->getMasterName();
				output("`%s`t says, \"`gThere are different types of swords and each relies upon another attribute of the wielder.`n`n
						`\$Fire`g requires Constitution to wield.`n
						`1Water`g requires Wisdom to control.`n
						`3Wind`g requires Constitution to endure.`n
						`#Lightning`g requires Intelligence to channel.`n
						`qPower`g requires Strength to enforce.`n
						`%Kidō`g requires Intelligence to manifest.`n
						`xIce`g requires Dexterity to conjure.`n\"
						
						");
				$this->zanpakutou_training_selection_nav();
				break;
			case "askpowerlevel":
				$this->zanpakutou_training_selection_nav();
				$name=$this->masters->getMasterName();
				output("%s`t says, \"`gWell, your %s`g has currently an estimated power level of `\$%s`g...`t\"`n`n",$name,$zanpakutou->get_name(),$zanpakutou->get_powerlevel());
				$level=$zanpakutou->get_powerlevel();
				$shi=$zanpakutou->get_shikai();
				$ban=$zanpakutou->get_bankai();
				if ($shi->get_text()!="") {
					output("You know you already have your shikai abilities...`n");
					
				} else {
					if ($level==1) {
						output("\"`gYou have just learned the name of your Zanpakutō... you have a long way to go to intentionally call on its powers.`t\"");
					} elseif ($level<2) {
						output("\"`gYou are on your way to release your Zanpakutō intentionally... at a power level of 5 you will be able to manifest it at will in Shikai form for an amount of your Reiatsu.`t\"");
					} elseif ($level==5) {
						output("\"`gYou have enough skill to draw out your powers. Doing so will require 10 full turns to meditate. Then your sword will reveal its first true form to you.`t\"");
						if ($session['user']['turns']>=10) {
							addnav("Shikai");
							addnav("Acquire your Shikai Powers","runmodule.php?module=zanpakutou_training&op=trainshikai");
						}
					}
				}
				
				if ($ban->get_text()!="") {
					output("You know you already have your bankai abilities...`n");
				} else {
					if ($level>=5 && $level <10) {
						output("\"`gYou are on your way to release your Zanpakutō's bankai ... at a power level of 10 you will be able to manifest it at will in bankai form for an amount of your Reiatsu.`t\"");
					} elseif ($level==10) {
						output("\"`gYou have enough skill to finalize your bankai. Doing so will require 10 full turns to meditate. Then your sword will reveal its final form to you and accept a name for the final release.`t\"");
						if ($session['user']['turns']>=10) {
							addnav("Bankai");
							addnav("Acquire your Bankai Powers","runmodule.php?module=zanpakutou_training&op=trainbankai");
						}
					}
				}
				
				break;
			case "trainshikai":
				require_once("lib/commentary.php");
				rawoutput("<form action='runmodule.php?module=zanpakutou_training&op=setshikainame' method='POST'>");
				addnav("","runmodule.php?module=zanpakutou_training&op=setshikainame");
				output("`TPlease enter the name of your Shikai:`0`n`n");
				$script=previewfield("zanpakutou_call",false,false,false,false,false);
				rawoutput($script);
				output("`TPlease enter the activation sentence/quote of your Shikai:`0`n`n");
				$script=previewfield("zanpakutou_calltext",false,false,false,false,false);
				$submit=translate_inline("Submit");
				$session['user']['turns']-=10;
				rawoutput("$script<input type='submit' class='button' value='$submit'></form>");
				output("`n`i`4Note: Color codes can be used, special chars can be used, italic/bold/centered is disabled and will not be saved (!)`i");
				$this->zanpakutou_training_selection_nav();
				break;
			case "trainbankai":
				require_once("lib/commentary.php");
				rawoutput("<form action='runmodule.php?module=zanpakutou_training&op=setbankainame' method='POST'>");
				addnav("","runmodule.php?module=zanpakutou_training&op=setbankainame");
				output("`TPlease enter the name of your Bankai:`0`n`n");
				$script=previewfield("zanpakutou_call",false,false,false,false,false);
				rawoutput($script);
				output("`TPlease enter the activation sentence/quote of your Bankai:`0`n`n");
				$script=previewfield("zanpakutou_calltext",false,false,false,false,false);
				$submit=translate_inline("Submit");
				$session['user']['turns']-=10;
				rawoutput("$script<input type='submit' class='button' value='$submit'></form>");
				output("`n`i`4Note: Color codes can be used, special chars can be used, italic/bold/centered is disabled and will not be saved (!)`i");
				$this->zanpakutou_training_selection_nav();
				break;				
			case "setshikainame":
				$name=$this->mystrip(httppost('zanpakutou_call'));
				$callname=$this->mystrip(httppost('zanpakutou_calltext'));
				if ($name=='' || $callname=='') {
					output("`x\"`\$Silly you... I needsome letters here!`x\"");
					break;				
				}
				// key 0 is unknown
				$shi=$zanpakutou->get_shikai();
				$shi->set_name($name);
				$shi->set_text($callname);
				$shi->set_achievedate(date("Y-m-d"));
				$zanpakutou->set_shikai($shi);
				set_zanpakutou($zanpakutou);
				output("`xYou decide to use \"`x%s`x\" as name for the shikai and \"`x%s`x\" as activation phrase...`n`n",$name,$callname);
				output("You have a Shikai formed like a `\$%s`x whereas the type is : `\$%s`x...",$zanpakutou->get_form(),$zanpakutou->get_type());
				$this->zanpakutou_training_selection_nav();
				break;
			case "setbankainame":
				$name=$this->mystrip(httppost('zanpakutou_call'));
				$callname=$this->mystrip(httppost('zanpakutou_calltext'));
				if ($name=='' || $callname=='') {
					output("`x\"`\$Silly you... I needsome letters here!`x\"");
					break;				
				}
				// key 0 is unknown
				$shi=$zanpakutou->get_bankai();
				$shi->set_name($name);
				$shi->set_text($callname);
				$shi->set_achievedate(date("Y-m-d"));
				$zanpakutou->set_bankai($shi);
				set_zanpakutou($zanpakutou);
				output("`xYou decide to use \"`x%s`x\" as name for the bankai and \"`x%s`x\" as activation phrase...`n`n",$name,$callname);
				output("You have a Bankai formed like a `\$%s`x whereas the type is : `\$%s`x...",$zanpakutou->get_form(),$zanpakutou->get_type());
				$this->zanpakutou_training_selection_nav();
				break;				
			default:
				if (is_module_active('addimages')) output_notl("`c<IMG SRC=\"modules/zanpakutou/training.jpg\">`c<BR>\n",true);
				output("Many shingami novices and also higher ranked are training there to improve themselves or simply to be able to complete their respective tasks perfectly to go even higher in rank.");
				$who=array("Zaraki Kenpachi", "Madarame Ikkaku","Hisagi Shuuhei","Matsumoto Rangiku (^^)","Abarai Renji","Kira Izuru","Kuchiki Rukia","the Training Master","the toothless floorcleaner");
				$rand=array_rand($who);
				$who=$who[$rand];
				output("`nThough many do not seem to notice you, `%%s`3 gives you a short glance and nods.",$who);
				output("`n`n`vWhat do you want to do?");
				if (!$this->zanpakutou_training_selection_nav()) {
					output("`3You have no master, and are currently looking if there is somebody who would like to train somebody like you. You know that certainly your level of skill matters, some don't accept fairly new shinigami, others do.`n`n");
					output("To have a master sooner as others is not the best and not the worst, it depends on *how* you like to fight and whom you choose.");
				}
				break;
		}
		page_footer();
	}

	private function zanpakutou_training_selection_nav() {
		global $session;
	
		require_once("modules/zanpakutou/func.php");
		$zanpakutou=get_zanpakutou(false);
		$shi=$zanpakutou->get_shikai();
		$name = $shi->get_name();
		

		if (!$this->masters) $this->masters=new zanpakutou_master();
		addnav("Masters");
		$mastername=$this->masters->getMasterName();
		addnav_notl(sanitize($mastername));
		output("`n`n`x%s`l is currently occupied and cannot train you. Visit another day.`n`n",$mastername);
		addnav_notl(array("`4Train with %s`0",$mastername),"");
		addnav_notl(array("`tAsk %s`t about your power",$mastername),"runmodule.php?module=zanpakutou_training&op=askpowerlevel");
		addnav_notl(array("`tAsk %s`t about the different zanpakutō types",$mastername),"runmodule.php?module=zanpakutou_training&op=asktypes");

		return true;
	}
}

class zanpakutou_master {
	private $masterid;
	private $master;
	private $special_master;
	private $masters;
	private $special_masters;
	
	//20% standard to get a master from the special list
	public function getRandomMaster($specialchance=20) {
		if ($specialchance>100) $specialchance=100;
		if ($specialchance<0) $specialchance=0;
		$special=(e_rand(1,100)<=$specialchance?true:false);
		$is_special=0;
		if ($special===false) {
			$number=e_rand(0,count($this->masters)-1);
			$this->master=$this->masters[$number];
		} else {
			$is_special=1;
			$number=e_rand(0,count($this->special_masters)-1);
			$this->master=$this->special_masters[$number];
		}
		
		$this->masterid=$number;
		$this->special_master=$is_special;
		
		return $number; //return the internal master reference
		
	}
	
	public function getMasterId() {
		if ($this->masterid==="") 
			return false;
		else return $this->masterid;
	}
	
	public function getMasterSpecial() {
		if ($this->special_master==="") 
			return false;
		else return $this->special_master;
	}	
	
	public function getMasterName() {
		if ($this->masterid==="") 
			return "";
		else {
			if (!is_object($this->master)) return "";
			return $this->master->getName();
		}	
	}
	
	public function getMasterWeapon() {
		if ($this->masterid==="") 
			return "";
		else {
			return $this->master->getWeapon();
		}	
	}
	
	public function getMasterGender() {
		if ($this->masterid==="") 
			return "";
		else {
			return $this->master->getGender();
		}		
	}
	
	
	public function __construct() {
		$this->masterid=get_module_pref("master","zanpakutou_training");
		$this->special_master=get_module_pref("special","zanpakutou_training");
		
		$m=get_module_setting("masterclasses","zanpakutou_training");
		$sm=get_module_setting("specialmasterclasses","zanpakutou_training");
		if ($m=="") {
			$this->initializeMasters();
		} else {
			$this->masters=unserialize(stripslashes($m));
			$this->special_masters=unserialize(stripslashes($sm));
		}
		if ($this->masterid!="") {
			//assign the master
			if ($this->special_master) $this->master=$this->special_masters[$this->masterid];
				else $this->master=$this->masters[$this->masterid];
		}		
	}
	
	private function initializeMasters() {
		//change their order and you break it...

		$masters=array(
			"Matsumoto Rangiku|w|`)Haineko",
			"Hitsugaya Toushiro|m|`^Hyōrinmaru",
			"Madarame Ikkaku|m|`^Hōzukimaru",
			"Ukitake Jūshirō|m|`lSōgyo no Kotowari",
			"Komamura Saijin|m|`yT`4enken",
			);
		
		$special_masters=array(
			"Urahara Kisuke|m|`%B`\$enihime",
			"Kurosaki Ichigo|m|`)Z`~angetsu",
			"Kuchiki Byakuya|m|`%Z`\$enbon`\$z`%akura",
			"Kyōraku Shunsui|m|`&K`gaten `&K`gyōkotsu",
			"Zaraki Kenpachi|m|`3Blunt Zanpakutō",
			"Kurotsuchi Mayuri|m|`xA`gshisogi `gJ`xizō",
			"Hinamori Momo|w|`xTobiume",
			"Soifon|w|`yS`vuzumebachi",
			"Shihōin Yoruichi|w|???"
			);

		$this->masters=array();
		$this->special_masters=array();
		foreach ($masters as $master) {
			//build the array that holds the master objects
			$m_internal=explode("|",$master);
			$this->masters[]=new z_master($m_internal[0],$m_internal[1],$m_internal[2]);
		}
		set_module_setting("masterclasses",serialize($this->masters),"zanpakutou_training");	
		
		foreach ($special_masters as $master) {
			//build the array that holds the master objects
			$m_internal=explode("|",$master);
			$this->special_masters[]=new z_master($m_internal[0],$m_internal[1],$m_internal[2]);
		}
		set_module_setting("specialmasterclasses",serialize($this->special_masters),"zanpakutou_training");	
		return;
	}
	


}

class z_master {
	private $name;
	private $gender;
	private $weapon;
	
	public function getName() {
		return $this->name;
	}
	
	public function getGender() {
		return $this->gender;
	}
	
	public function getWeapon() {
		return $this->weapon;
	}
	
	public function __construct($name,$gender,$weapon) {
		$this->name=$name;
		$this->gender=$gender;
		$this->weapon=$weapon;
	}
	
	
}

?>
