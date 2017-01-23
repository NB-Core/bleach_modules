<?php

function training_arrancar_getmoduleinfo() {
	$info = array
		(
		"name"=>"Training Grounds (Arrancar)",
		"version"=>"1.0",
		"author"=>"`2Oliver Brendel",
		"category"=>"Arrancar",
		"download"=>"",
		"requires"=>array(
			"specialtysystem"=>"1.0|Specialty System Core by `2Oliver Brendel",
			),
		);
	return $info;
}

function training_arrancar_install(){
	module_addhook("arrancar-footer");
	return true;
}

function training_arrancar_uninstall(){
	return true;
}

function training_arrancar_dohook($hookname,$args){
	global $session;
	switch ($hookname) {
	case "arrancar-footer":
		$op=httpget('op');
		if ($op!='' && $op!='question') break;
		addnav("Training");
		addnav("Training Grounds","runmodule.php?module=training_arrancar");
		break;
	}
	return $args;
}

function training_arrancar_run() {
	global $session;
	page_header("Training Grounds");
	addnav("Navigation");
	addnav("Back to the Main Grounds","runmodule.php?module=arrancar_train");
	output("`#`b`c`n`2Training Grounds`0`c`b`n`n");
	require_once("modules/specialtysystem/datafunctions.php");
	$op = httpget('op');
	$cost=array(48,225,585,990,1575,2250,2790,3420,4230,5040,5850,6840,8010,9000,10350,11500,13775,15850,17030,18270,20020,21150,22500,25550,30000,32000,34000,38000);	
	$multi=75;
	$gold=$session['user']['level']*$multi;
	modulehook("arrancargrounds",array());
	switch ($op) {
	case "mountsummonexecute":
		$action = httpget('action');
		$who=httpget('who');
		$gold=round($gold/2,0);
		if ($session['user']['gold']<$gold) {
			output("`3Shame on you! You do not have enough gold with you!");
			break;
		}
		$session['user']['gold']-=$gold;
		require_once("lib/battle-skills.php");
		switch ($action) {
		case 1:
			output("`%%s`3 intonates some strange syllables... you join in and together you call your mount back from its rest...",$who);
			output_notl("`n`n");
			unsuspend_buff_by_name('mount','`3You feel full of new inspiration along with your mount.');
			break;
		case 0:
			output("`%%s`3 intonates some strange syllables... you join in and together you send your mount at rest for some time...",$who);
			output_notl("`n`n");
			suspend_buff_by_name('mount','`3You will certainly miss your fellow comrade...');
			break;
		}
		break;
	case "mountsummon":
		global $playermount;
		$gold=round($gold/2,0);
		addnav("Back to the Training Grounds","runmodule.php?module=training_arrancar");
		$who=array("Nnoitora", "Stark","Barragan","Cirruci Thunderwitch (^^)","Tesla","Grimmjow","Luppi","Szayel Apollo","Ulquiorra");
		$rand=array_rand($who);
		$who=$who[$rand];
		$action=httpget('action');
		$actionword=($action==1?translate_inline("Summon"):translate_inline("Unsummon"));
		output("`3You decide to %s your permanent mount... you could do this on your own, too, but it is always convenient to have somebody helping you.",($action==1?translate_inline("summon"):translate_inline("unsummon")));
		output("`n`nKnowing it would take also more time to do this all by yourself, you decide to go to `%%s`3 to ask for help.`n`n",$who);
		output("`3\"`tSo... let me see... you want to %s `v%s`t? No big deal, this won't take much  time, so it costs only `^%s gold`t to relieve me from duty.`3\".`n`n",($action==1?translate_inline("summon"):translate_inline("unsummon")),$playermount['mountname'],$gold);
		addnav("Actions");
		addnav(array("%s your mount",$actionword),"runmodule.php?module=training_arrancar&op=mountsummonexecute&action=$action&who=$who");
		break;
	case "setspecialty":
		if ($session['user']['gold']<$gold) {
			output("`3Shame on you! You do not have enough gold with you!");
			break;
		}
		output("`3\"`tYou are now working for your new specialty...good luck!`3\"");
		output_notl("`n`n");//debug(httppost('ssystem'));
		specialtysystem_set(array("active"=>httppost('ssystem')));
		if ($session['user']['specialty']!='SS') $session['user']['specialty']='SS';
		$session['user']['gold']-=$gold;
		break;
	case "specialty":
		addnav("Back to the Training Grounds","runmodule.php?module=training_arrancar");
		$who=array("Nnoitora", "Stark","Barragan","Cirruci Thunderwitch (^^)","Tesla","Grimmjow","Luppi","Szayel Apollo","Ulquiorra");
		$rand=array_rand($who);
		$who=$who[$rand];
		output("`3You decide to change your specialty... you want to work for a new kind of technique from now on.");
		output("`n`nKnowing it would take time to do this all by yourself, you decide to go to `%%s`3 to ask for help.`n`n",$who);
		output("`3\"`tSo... let me see... you want to switch your techniques... I can help you. But for my time you need to pay me off for my other duties at this time. Currently that would be `^%s gold pieces`t. If you want, select your new kind of jutsu and we can keep going.`3\".`n`n",$gold);
		output("You ponder about that offer... what are you going to do?");
		output_notl("`n`n");
		rawoutput("<form action='runmodule.php?module=training_arrancar&op=setspecialty' method='POST'>");
		addnav("","runmodule.php?module=training_arrancar&op=setspecialty");
		$specs=specialtysystem_getspecs();//debug($specs);
		if ($specs==array()) {
			output("Sorry, I have no registered specialties for you here...");
			break;
		}
		rawoutput("<select name='ssystem'>");
		$active=specialtysystem_get("active");
		foreach ($specs as $key=>$data) {//debug($data);
			$name=translate_inline($data['spec_name']);
			if ($data['dragonkill_minimum_requirement']>$session['user']['dragonkills']) continue;
			if (((int)$data['dragonkill_minimum_requirement'])==-1) continue;
			if ($data['modulename']==$active) continue;
			rawoutput("<option value='{$data['modulename']}'>$name</option>");
		}
		rawoutput("</select>");
		$submit=translate_inline("Submit");
		rawoutput("<br><br><input type='submit' value='$submit'></form>");
		output("`n`n`lPS: You still retain the knowledge of your current specialty/specialties. You simply get new skillpoints in the new specialty you select here.");
		break;
	case "trainoffensive":
		addnav("Back to the Training Grounds","runmodule.php?module=training_arrancar");
		addnav("Actions");
		$who=array("Nnoitora", "Stark","Barragan","Cirruci Thunderwitch (^^)","Tesla","Grimmjow","Luppi","Szayel Apollo","Ulquiorra");
		output("`c`b`1~~~ `\$Offensive Training`1 ~~~`c`n`n");
		$rand=array_rand($who);
		$who=$who[$rand];
		$lev=$session['user']['weapondmg'];
		$dummy=modulehook("training-costs-o",array("user"=>$session['user'],"cost"=>$cost));
		$cost=$dummy['cost'];
		switch(httpget('action')) {
			case "train":
				$session['user']['gold']-=$cost[$lev];
				$session['user']['weapondmg']=$lev+1;
				$session['user']['attack']++;
				output("`1You have successfully gained `%one attack point`1 due to harsh and rigorous training!`n`n");
				debuglog("trained and got +1 attack, now has ".$session['user']['weapondmg']." points and a total of ".$session['user']['attack'].", paid ".$cost[$lev]." gold.");
				if (e_rand(0,10)==10) {
					output("`2You also feel you have satisfied Death pretty well!");
					output("`n`~(You gain 20 favours)");
					$session['user']['deathpower']+=20;
				}
				break;
			default:
			output("`3You look for somebody who has enough time to teach you something about how to improve your offensive skills... %s`3 is currently free.`n`n",$who);
			output("\"`tDisgusting Ant... it will cost you `^%s gold`t to improve your current skills who are at level %s currently.`3\"",$cost[$lev],$lev);
			$link='';
			if ($cost[$lev]<=$session['user']['gold']) $link="runmodule.php?module=training_arrancar&op=trainoffensive&action=train";
			addnav("Train Yourself",$link);
		}
		
		break;
		
	case "traindefensive":
		addnav("Back to the Training Grounds","runmodule.php?module=training_arrancar");
		addnav("Actions");
		$who=array("Nnoitora", "Stark","Barragan","Neliel (^^)","Tesla","Grimmjow","Luppi","Szayel Apollo","Ulquiorra");
		output("`c`b`1~~~ `\$Defensive Training`1 ~~~`c`n`n");
		$rand=array_rand($who);
		$who=$who[$rand];
		$lev=$session['user']['armordef'];
		$dummy=modulehook("training-costs-d",array("user"=>$session['user'],"cost"=>$cost));
		$cost=$dummy['cost'];
		switch(httpget('action')) {
			case "train":
				$session['user']['gold']-=$cost[$lev];
				$session['user']['armordef']=$lev+1;
				$session['user']['defense']++;
				output("`1You have successfully gained `%one defense point`1 due to harsh and rigorous training!`n`n");
				debuglog("trained and got +1 defense, now has ".$session['user']['armordef']." points and a total of ".$session['user']['defense'].", paid ".$cost[$lev]." gold.");
				if (e_rand(0,10)==10) {
					$fav=e_rand(2,20);
					output("`2You also feel you have satisfied Death pretty well!");
					output("`n`~(You gain %s favours)",$fav);
					$session['user']['deathpower']+=$fav;
				}
				break;
			default:
			output("`3You look for somebody who has enough time to teach you something about how to improve your defensive skills... %s`3 is currently free.`n`n",$who);
			output("\"`tOh - not yet another one of those... my time will cost you `^%s gold`t. That  to improve your current skills who are at level %s currently.`3\"",$cost[$lev],$lev);
			$link='';
			if ($cost[$lev]<=$session['user']['gold']) $link="runmodule.php?module=training_arrancar&op=traindefensive&action=train";
			addnav("Train Yourself",$link);
		}
		break;
		case "wiseman":
			$array=array(
				"Resolve is hard like a diamond, sharper than steel and clearer than the sun in the sky... either you do the crushing, or you are crushed.",
				"You need to grow more.",
				"Death is only the beginning.",
				"Don't eat too late. And also don't eat yellow hollows.",
				"Treat other souls with little respect.",
				"Don't wash your hands after visiting the restroom.",
				"Prey on the weak. Imagine they become strong one day. Wait! In that case, wait and harvest later.",
				"Being self-sufficient is good when out alone in Hueco Mundo. But also think about leaving this desert some day to find Shinigami and take them out.",
				"We all are actors in a gigantic stage. Try to be in the spotlight.",
				"Death is just a game. But with great graphics...",
				"A proper meal consists of at least one healthy vegan soul.",
				"Meat eaters give fatty souls.",
			);
			$array=translate_inline($array);
			$cnt=date("d")%count($array);
			output_notl("`\$".$array[$cnt]);
			break;
	default:
		modulehook("header-arrancar-train");
		output("`3You enter the vast training grounds you know about.`n`n");
		output("This place looks almost deserted - except for the signs of decay and destruction that hovers over the entire area. This is used for special training ineed.");
		$who=array("Nnoitora", "Stark","Barragan","Cirruci Thunderwitch (^^)","Tesla","Grimmjow","Luppi","a random Fracción","the toothless hollow floorcleaner");
		$rand=array_rand($who);
		$who=$who[$rand];
		output("`nThough many do not seem to notice you, `%%s`3 gives you a short glance and looks away in disgust.",$who);
		output("`n`n`vWhat do you want to do?");
		training_arrancarnav();
		addnav("Wise Hollow");
		addnav("Ask...","runmodule.php?module=training_arrancar&op=wiseman");
		break;
	}
	page_footer();
}

function training_arrancarnav() {
	global $session;
	addnav("Actions");
// Not currently
//	if (is_module_active("specialtysystem")) addnav("Switch your specialty","runmodule.php?module=training_arrancar&op=specialty");
	if (has_buff('mount')) {
		if ($session['bufflist']['mount']['suspended']) {
			$sw=1;
			$action=translate_inline("Summon");
		} else {
			$sw=0;
			$action=translate_inline("Unsummon");
		}
		addnav(array("%s your mount",$action),"runmodule.php?module=training_arrancar&op=mountsummon&action=$sw");
	}
	$lev=$session['user']['weapondmg']+$session['user']['armordef'];
	if ($lev<30) {
		if ($session['user']['weapondmg']<25) {
		addnav("Train your Offensive Zanpakutou Skills","runmodule.php?module=training_arrancar&op=trainoffensive");
		}
		if ($session['user']['armordef']<25) {
			addnav("Train your Defensive Zanpakutou Skills","runmodule.php?module=training_arrancar&op=traindefensive");
		}
	}
}


?>
