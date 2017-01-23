<?php

function arrancar_train_getmoduleinfo() {
	$info = array
		(
		"name"=>"Masters & Levelup for Arrancar",
		"version"=>"1.0",
		"author"=>"`2Oliver Brendel",
		"category"=>"Arrancar",
		"download"=>"",
		"requires"=>array(
			"specialtysystem"=>"1.0|Specialty System Core by `2Oliver Brendel",
			),
		"settings"=>array(
			"Arrancar Train & Masters Setting,title",
			"petraloc"=>"Where does the Training appear,location|".getsetting("villagename", LOCATION_FIELDS),
			"race"=>"Race name,text|Arrancar",
			),
		);
	return $info;
}

function arrancar_train_install(){
	module_addhook("village-Las Noches");
	return true;
}

function arrancar_train_uninstall(){
	return true;
}

function arrancar_train_dohook($hookname,$args){
	global $session;
	switch ($hookname) {
	default:
		$u=&$session['user'];
		$op=httpget('op');
		if (sanitize($u['race'])!=get_module_setting('race')) break;
		addnav($args['fightnav']);
		addnav(array("%s`x Training Grounds",get_module_setting('race')),"runmodule.php?module=arrancar_train");
		break;
	}
	return $args;
}

function arrancar_train_mastersanitize($string) {
	$var=html_entity_decode(htmlentities(stripslashes($string)));
	$var=mb_convert_encoding($string,'UTF-8','ISO-8859-1');
	return $var;html_entity_decode(htmlentities(stripslashes($string)));
}


function arrancar_train_run() {
	require_once("lib/increment_specialty.php");
	require_once("lib/fightnav.php");
	require_once("lib/taunt.php");
	require_once("lib/substitute.php");
	require_once("lib/villagenav.php");
	require_once("lib/experience.php");
global $session;
$u=&$session['user'];
$race = translate_inline($u['race']);

page_header("%s Training",$race);

modulehook("arrancar-train",array());

$battle = false;
$victory = false;
$defeat = false;
$point=getsetting('moneydecimalpoint',".");
$sep=getsetting('moneythousandssep',",");

output("`b`c%s Training`c`b",$race);

$mid = httpget("master");
if ($mid) {
	$sql = "SELECT * FROM " . db_prefix("masters_arrancar") . " WHERE creatureid=$mid";
} else {
	$sql = "SELECT max(creaturelevel) as level FROM " . db_prefix("masters_arrancar") . " WHERE creaturelevel <= " . $u['level'];
	$res = db_query($sql);
	$row = db_fetch_assoc($res);
	$l = (int)$row['level'];

	$sql = "SELECT * FROM " . db_prefix("masters_arrancar") . " WHERE creaturelevel=$l ORDER BY RAND(".e_rand().") LIMIT 1";
}

$result = db_query($sql);
if (db_num_rows($result) > 0 && $u['level'] < getsetting('maxlevel',15)){
	$master = db_fetch_assoc($result);
	$mid = $master['creatureid'];
	$master['creaturename'] = arrancar_train_mastersanitize($master['creaturename']);
	$master['creaturewin'] = arrancar_train_mastersanitize($master['creaturewin']);
	$master['creaturelose'] = arrancar_train_mastersanitize($master['creaturelose']);
	$master['creatureweapon'] = arrancar_train_mastersanitize($master['creatureweapon']);
	//this is a piece of old work I will leave in, if you don't have Gadriel, then well...
	if ($master['creaturename'] == "Gadriel the Elven Ranger" &&
			$u['race'] == "Elf") {
		$master['creaturewin'] = "You call yourself an Elf?? Maybe Half-Elf! Come back when you've been better trained.";
		$master['creaturelose'] = "It is only fitting that another Elf should best me.  You make good progress.";
	}
	//end of old piece
	$level = $u['level'];
	$dks = $u['dragonkills'];
	$exprequired=exp_for_next_level($level, $dks);

	$op = httpget('op');
	if ($op==""){
		checkday();
		output("The sound of conflict surrounds you.  The clang of weapons in grisly battle inspires your warrior heart. ");
		output("`n`n`^%s stands ready to evaluate you.`0",
				$master['creaturename']);
		addnav("Navigation");
		villagenav();
		addnav("Actions");
		addnav("Question Master","runmodule.php?module=arrancar_train&op=question&master=$mid");
		addnav("M?Challenge Master","runmodule.php?module=arrancar_train&op=challenge&master=$mid");
		if ($u['superuser'] & SU_DEVELOPER) {
			addnav("Superuser Gain level","runmodule.php?module=arrancar_train&op=challenge&victory=1&master=$mid");
		}
	}else if($op=="challenge"){
		if (httpget('victory')) {
			$victory=true;
			$defeat=false;
			if ($u['experience'] < $exprequired)
				$u['experience'] = $exprequired;
			$u['seenmaster'] = 0;
		}
		if ($u['seenmaster']){
			output("You think that, perhaps, you've seen enough of your master for today, the lessons you learned earlier prevent you from so willingly subjecting yourself to that sort of humiliation again.");
			addnav("Navigation");
			villagenav();
			addnav("Actions");
		}else{
			/* OK, let's fix the multimaster thing */
			$u['seenmaster'] = 1;
			debuglog("Challenged master, setting seenmaster to 1");

			if ($u['experience']>=$exprequired){
				restore_buff_fields();
				$dk  = round(get_player_dragonkillmod(true)*0.33,0);

				$atkflux = e_rand(0, $dk);
				$atkflux = min($atkflux, round($dk*.25));
				$defflux = e_rand(0, ($dk-$atkflux));
				$defflux = min($defflux, round($dk*.25));

				$hpflux = ($dk - ($atkflux+$defflux)) * 5;
				debug("DEBUG: $dk modification points total.`n");
				debug("DEBUG: +$atkflux allocated to attack.`n");
				debug("DEBUG: +$defflux allocated to defense.`n");
				debug("DEBUG: +".($hpflux/5)."*5 to hitpoints`n");
				calculate_buff_fields();

				$master['creatureattack']+=$atkflux;
				$master['creaturedefense']+=$defflux;
				$master['creaturehealth']+=$hpflux;
				$attackstack['enemies'][0] = $master;
				$attackstack['options']['type'] = 'train';
				$u['badguy']=createstring($attackstack);

				$battle=true;
				if ($victory) {
					$badguy = unserialize($u['badguy']);
					output("With a flurry of blows you dispatch your master.`n");
				}
			}else{
				output("You ready your %s`0 and %s`0 and approach `^%s`0.`n`n",$u['weapon'],$u['armor'],$master['creaturename']);
				output("A small crowd of onlookers has gathered, and you briefly notice the smiles on their faces, but you feel confident. ");
				output("You bow before `^%s`0, and execute a perfect spin-attack, only to realize that you are holding NOTHING!", $master['creaturename']);
				output("`^%s`0 stands before you holding your weapon.",$master['creaturename']);
				output("Meekly you retrieve your %s`0, and slink out of the training grounds to the sound of boisterous guffaws.",$u['weapon']);
				addnav("Navigation");
				villagenav();
				addnav("Actions");
			}
		}
	}else if($op=="question"){
		checkday();
		addnav("Navigation");
		villagenav();
		addnav("Actions");
		output("You approach `^%s`0 timidly and inquire as to your standing in the class.",$master['creaturename']);
		if($u['experience']>=$exprequired){
			output("`n`n`^%s`0 says, \"Gee, your spirit power is getting bigger...\"",$master['creaturename']);
		}else{
			output("`n`n`^%s`0 states that you will need `%%s`0 more experience before you are ready to challenge him in battle.",$master['creaturename'],number_format($exprequired-$u['experience'],0,$point,$sep));
		}
		addnav("Question Master","runmodule.php?module=arrancar_train&op=question&master=$mid");
		addnav("M?Challenge Master","runmodule.php?module=arrancar_train&op=challenge&master=$mid");
		if ($u['superuser'] & SU_DEVELOPER) {
			addnav("Superuser Gain level","runmodule.php?module=arrancar_train&op=challenge&victory=1&master=$mid");
		}
	}else if($op=="autochallenge"){
		addnav("Fight Your Master","runmodule.php?module=arrancar_train&op=challenge&master=$mid");
		output("`^%s`0 has heard of your prowess as a fighter, and heard of rumors that you think you are so much more powerful than he that you don't even need to fight him to prove anything. ",$master['creaturename']);
		output("His ego is understandably bruised, and so he has come to find you.");
		output("`^%s`0 demands an immediate battle from you, and your own pride prevents you from refusing the demand.",$master['creaturename']);
		if ($u['hitpoints']<$u['maxhitpoints']){
			output("`n`nBeing a fair person, your master gives you a healing potion before the fight begins.");
			$u['hitpoints']=$u['maxhitpoints'];
		}
		modulehook("master-autochallenge");
		if (getsetting('displaymasternews',1)) addnews("`3%s`3 was hunted down by their master, `^%s`3, for being truant.",$u['name'],$master['creaturename']);
	}
	if ($op=="fight"){
		$battle=true;
	}
	if ($op=="run"){
		output("`\$Your pride prevents you from running from this conflict!`0");
		$op="fight";
		$battle=true;
	}

	if($battle){
		require_once("lib/battle-skills.php");
		require_once("lib/extended-battle.php");
		suspend_buffs('allowintrain', "`&Your pride prevents you from using extra abilities during the fight!`0`n");
		suspend_companions("allowintrain");
		if (!$victory) {
			require_once("battle.php");
		}
		if ($victory){
			$badguy['creaturelose']=substitute_array($badguy['creaturelose']);
			output_notl("`b`&");
 	 	 	output($badguy['creaturelose']);
 	 	 	output_notl("`0`b`n");
 	 	 	output("`b`\$You have defeated %s!`0`b`n",$badguy['creaturename']);

			$u['level']++;
			$u['maxhitpoints']+=10;
			$u['soulpoints']+=5;
			$u['attack']++;
			$u['defense']++;
			// Fix the multimaster bug
			if (getsetting("multimaster", 1) == 1) {
				$u['seenmaster']=0;
				debuglog("Defeated master, setting seenmaster to 0");
			}
			output("`#You advance to level `^%s`#!`n",$u['level']);
			output("Your maximum hitpoints are now `^%s`#!`n",$u['maxhitpoints']);
			output("You gain an attack point!`n");
			output("You gain a defense point!`n");
			if ($u['level']<15){
				output("You have a new master.`n");
			}else{
				output("None in the land are mightier than you!`n");
			}
			if ($u['referer']>0 && ($u['level']>=getsetting("referminlevel",4) || $u['dragonkills'] > 0) && $u['refererawarded']<1){
				$sql = "UPDATE " . db_prefix("accounts") . " SET donation=donation+".getsetting("refereraward",25)." WHERE acctid={$u['referer']}";
				db_query($sql);
				$u['refererawarded']=1;
				$subj=array("`%One of your referrals advanced!`0");
				$body=array("`&%s`# has advanced to level `^%s`#, and so you have earned `^%s`# points!", $u['name'], $u['level'], getsetting("refereraward", 25));
				systemmail($u['referer'],$subj,$body);
			}
			increment_specialty("`^");

			// Level-Up companions
			// We only get one level per pageload. So we just add the per-level-values.
			// No need to multiply and/or substract anything.
			if (getsetting("companionslevelup", 1) == true && count($companions)>0) {
				$newcompanions = $companions;
				foreach ($companions as $name => $companion) {
					$companion['attack'] = $companion['attack'] + $companion['attackperlevel'];
					$companion['defense'] = $companion['defense'] + $companion['defenseperlevel'];
					$companion['maxhitpoints'] = $companion['maxhitpoints'] + $companion['maxhitpointsperlevel'];
					$companion['hitpoints'] = $companion['maxhitpoints'];
					$newcompanions[$name] = $companion;
				}
			}

			invalidatedatacache("list.php-warsonline");

			addnav("Navigation");
			villagenav();
			addnav("Actions");
			addnav("Question Master","runmodule.php?module=arrancar_train&op=question");
			addnav("M?Challenge Master","runmodule.php?module=arrancar_train&op=challenge");
			if ($u['superuser'] & SU_DEVELOPER) {
				addnav("Superuser Gain level","runmodule.php?module=arrancar_train&op=challenge&victory=1");
			}
			if ($u['age'] == 1) {
 	 	 	 	if (getsetting('displaymasternews',1)) addnews("`%%s`3 has defeated ".($u['sex']?"her":"his")." master, `%%s`3 to advance to level `^%s`3 after `^1`3 day!!", $u['name'],$badguy['creaturename'],$u['level']);
 	 	 	} else {
 	 	 	 	if (getsetting('displaymasternews',1)) addnews("`%%s`3 has defeated ".($u['sex']?"her":"his")." master, `%%s`3 to advance to level `^%s`3 after `^%s`3 days!!", $u['name'],$badguy['creaturename'],$u['level'],$u['age']);
 	 	 	}
			if ($u['hitpoints'] < $u['maxhitpoints'])
				$u['hitpoints'] = $u['maxhitpoints'];
			modulehook("training-victory", $badguy);
		}elseif($defeat){
			$taunt = select_taunt_array();

			if (getsetting('displaymasternews',1)) addnews("`%%s`5 has challenged their master, %s and lost!`n%s",$u['name'],$badguy['creaturename'],$taunt);
			$u['hitpoints']=$u['maxhitpoints'];
			output("`&`bYou have been defeated by `%%s`&!`b`n",$badguy['creaturename']);
			output("`%%s`\$ halts just before delivering the final blow, and instead extends a hand to help you to your feet, and hands you a complementary healing potion.`n",$badguy['creaturename']);
			$badguy['creaturewin']=substitute_array($badguy['creaturewin']);
			output_notl("`^`b");
			output($badguy['creaturewin']);
			output_notl("`b`0`n");
			addnav("Navigation");
			villagenav();
			addnav("Actions");
			addnav("Question Master","runmodule.php?module=arrancar_train&op=question&master=$mid");
			addnav("M?Challenge Master","runmodule.php?module=arrancar_train&op=challenge&master=$mid");
			if ($u['superuser'] & SU_DEVELOPER) {
				addnav("Superuser Gain level","runmodule.php?module=arrancar_train&op=challenge&victory=1&master=$mid");
			}
			modulehook("training-defeat", $badguy);
		}else{
		  fightnav(false,false, "runmodule.php?module=arrancar_train&master=$mid");
		}
		if ($victory || $defeat) {
			unsuspend_buffs('allowintrain', "`&You now feel free to make use of your buffs again!`0`n");
			unsuspend_companions("allowintrain");
		}
	}
}else{
	checkday();
	output("You stroll into the battle grounds.`n`n");
	output("Younger arrancar huddle together and point as you pass by.");
	output("You know this place well.");
	output("There is nothing left for you here but memories.");
	output("You remain a moment longer, and look at the tiny little wimps in training before you turn to return to your business.");
	addnav("Navigation");
	villagenav();
	addnav("Actions");
}
modulehook("arrancar-footer",array());
page_footer();
}
?>
