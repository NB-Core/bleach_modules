<?php

function specialtysystem_arrancar_getmoduleinfo(){
	$info = array(
		"name" => "Specialty System - Destructive",
		"author" => "`2Oliver`0",
		"version" => "1.0",
		"download" => "",
		"category" => "Specialty System",
		"requires"=> array(
			"specialtysystem"=>"1.0|Specialty System by `2Oliver Brendel",
			),
	);
	return $info;
}

function specialtysystem_arrancar_install(){
	module_addhook("specialtysystem-register");
	return true;
}

function specialtysystem_arrancar_uninstall(){
	require_once("modules/specialtysystem/uninstall.php");
	specialtysystem_uninstall("specialtysystem_arrancar");
	return true;
}

function specialtysystem_arrancar_fightnav(){
	global $session;
	require_once("modules/specialtysystem/functions.php");
	$uses=specialtysystem_availableuses("specialtysystem_arrancar");
	$name=translate_inline('Arrancar Powers');
	tlschema('module-specialtysystem_arrancar');
	if ($uses > 0) {
		specialtysystem_addfightheadline($name, $uses,specialtysystem_getskillpoints("specialtysystem_arrancar"));
		specialtysystem_addfightnav("Bala","arrancar1&cost=1",1);
	}
	if ($uses > 1) {
		specialtysystem_addfightnav("Sonido","arrancar2&cost=2",2);
	}
	if ($uses > 2 && $session['user']['dragonkills']>69) {
		specialtysystem_addfightnav("Enhanced Hierro","arrancar3&cost=3",3);
	}
	if ($uses > 4) {
		specialtysystem_addfightnav("Cero","arrancar4&cost=5",5);
	}
	if ($uses > 9 && $session['user']['dragonkills']>99) {
		specialtysystem_addfightnav("Gran Rey Cero","arrancar5&cost=40",40);
	}
	tlschema();
	return specialtysystem_getfightnav();
}

function specialtysystem_arrancar_apply($skillname){
	global $session;
	require_once("modules/specialtysystem/functions.php");
	$u=&$session['user'];
	switch($skillname){
		case "arrancar1":
			apply_buff('arrancar1',array(
				"startmsg"=>"`i`\$`bBala!`b`i`n`qYou `L`bfire a fast blast of reiatsu at your enemy!`b",
				"name"=>"`x`bBa`4l`xa`b",
				"rounds"=>1,
				"wearoff"=>"",
				"areadamage"=>false,
				"minbadguydamage"=>$u['strength']*2+$u['intelligence']*1.7+sqrt($session['user']['dragonkills']),
				"maxbadguydamage"=>$u['strength']*3+$u['intelligence']*2.7+sqrt($session['user']['dragonkills']),
				"minioncount"=>1,
				"effectmsg"=>"`q{badguy}`q suffers {damage} damage!",
				"effectnodmgmsg"=>"`qThe bala was neutralized.",
				"schema"=>"module-specialtysystem_arrancar"
			));
			break;
		case "arrancar2":
			apply_buff('arrancar2',array(
				"startmsg"=>"`i`QYou `7implement a high-speed movement technique`i. `n`\${badguy}`7 is unable to keep up completely with your speed.",
				"name"=>"`4So`\$ni`4do",
				"rounds"=>10,
				"wearoff"=>"You stop using Sonido.",
				"badguyatkmod"=>0.82,
				"minbadguydamage"=>0,
				"maxbadguydamage"=>0,
				"minioncount"=>1,
				"effectnodmgmsg"=>"`&{badguy}`& could not attack well!",
				"schema"=>"module-specialtysystem_arrancar"
			));
			break;
		case "arrancar3":
			apply_buff('arrancar3',array(
				"startmsg"=>"`i`QYou `7harden your reiatsu around your skin, increasing your defense!`i",
				"name"=>"`4Enhanced `)Hi`ver`)ro",
				"rounds"=>10,
				"wearoff"=>"Your defense returns to normal.",
				"badguyatkmod"=>0.65,
				"minbadguydamage"=>0,
				"maxbadguydamage"=>0,
				"minioncount"=>1,
				"effectnodmgmsg"=>"`&{badguy} `&attack deflects off your Hierro!",
				"schema"=>"module-specialtysystem_arrancar",
				"requirements"=>array(
			"dks"=>70,
			),
			));
			break;
		case "arrancar4":
			apply_buff('arrancar4',array(
				"startmsg"=>"`i`\$`bCero!`b`i`n`qYou `L`bfire a huge blast of reiatsu at your enemy!`b",
				"name"=>"`x`bBa`4l`xa`b",
				"rounds"=>1,
				"wearoff"=>"",
				"areadamage"=>false,
				"minbadguydamage"=>$u['strength']*4+$u['intelligence']*2+sqrt($session['user']['dragonkills']),
				"maxbadguydamage"=>$u['strength']*4+$u['intelligence']*4+sqrt($session['user']['dragonkills']),
				"minioncount"=>1,
				"effectmsg"=>"`q{badguy}`q gets hits by your Cero for {damage} damage!",
				"effectnodmgmsg"=>"`qThe Cero was neutralized.",
				"schema"=>"module-specialtysystem_arrancar"
			));
			break;
		case "arrancar5":
			apply_buff('arrancar5',array(
				"startmsg"=>"`i`\$`bGran Rey Cero!`b`i`n`qYou `L`bfire the ultimate form of the Cero, blasting away your enemy!`b",
				"name"=>"Gran Rey Cero", 
				"rounds"=>1,
				"wearoff"=>"The immense force of the reiatsu heavily wounds {badguy}.",
				"badguyatkmod"=>0,
				"areadamage"=>true,
				"minbadguydamage"=>$u['strength']*5+$u['intelligence']*4+sqrt($session['user']['dragonkills']),
				"maxbadguydamage"=>$u['strength']*5+$u['intelligence']*6.2+sqrt($session['user']['dragonkills']),
				"minioncount"=>3,
				"effectmsg"=>"`7{badguy}`7 is rooted in fear from the immense reiatsu and takes {damage} damage from the powerful blast!",
				"effectnodmgmsg"=>"`7The Cero missed.",
				"schema"=>"module-specialtysystem_arrancar",
				"requirements"=>array(
			"dks"=>100,
			),
			));
			break;


	}
	specialtysystem_incrementuses("specialtysystem_arrancar",httpget('cost'));
	return;
}

function specialtysystem_arrancar_dohook($hookname,$args){
	switch ($hookname) {
	case "specialtysystem-register":
		$args[]=array(
			"spec_name"=>'Arrancar Spells',
			"spec_colour"=>'`x',
			"spec_shortdescription"=>'`$The vile hollow power that destroys!',
			"spec_longdescription"=>'`5As a hollow being, you have inherted powers to your form to do great feats. Those power can manifest in different ways. From shooting out reiatsu to making yourself more durable.',
			"modulename"=>'specialtysystem_arrancar',
			"fightnav_active"=>'1',
			"newday_active"=>'0',
			"dragonkill-active"=>'0',
			"race_requirements"=>array(
				'Arrancar',
				),
			);
		break;
	}
	return $args;
}

function specialtysystem_arrancar_run(){
}
?>
