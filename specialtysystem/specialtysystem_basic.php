<?php

function specialtysystem_basic_getmoduleinfo(){
	$info = array(
		"name" => "Specialty System - Basic Techniques",
		"author" => "`2Oliver Brendel`0",
		"version" => "1.0",
		"download" => "",
		"category" => "Specialty System",
		"requires"=> array(
			"specialtysystem"=>"1.0|Specialty System by `2Oliver Brendel",
			),
	);
	return $info;
}

function specialtysystem_basic_install(){
	module_addhook("specialtysystem-register");
	return true;
}

function specialtysystem_basic_uninstall(){
	require_once("modules/specialtysystem/uninstall.php");
	specialtysystem_uninstall("specialtysystem_basic");
	return true;
}

function specialtysystem_basic_fightnav(){
	global $session;
	require_once("modules/specialtysystem/functions.php");
	$uses=specialtysystem_availableuses("specialtysystem_basic");
	$name=translate_inline('Basic Abilites');
	tlschema('module-specialtysystem_basic');
	if ($uses > 0) {
		specialtysystem_addfightheadline($name, $uses,specialtysystem_getskillpoints("specialtysystem_basic"));
		specialtysystem_addfightnav("Empowered Attack","basic1&cost=1",1);
		specialtysystem_addfightnav("Empowered Defense","basic2&cost=1",1);
		if ($uses>39) specialtysystem_addfightnav("`\$Spirit Force Oppression","basic3&cost=40",40);
	}
	tlschema();
	return specialtysystem_getfightnav();
}

function specialtysystem_basic_apply($skillname){
	global $session;
	require_once("modules/specialtysystem/functions.php");
	$rounds=e_rand(0,$session['user']['level']/3)+2;
	switch($skillname){
		case "basic1":
			apply_buff('basic1',array(
				"startmsg"=>"`v`iPower Slash!`i`n`tYou `qempower your next attacks.",
				"name"=>"`vPower Slash",
				"rounds"=>$rounds,
				"atkmod"=>1.1,
				"schema"=>"module-specialtysystem_basic"
			));
			break;
		case "basic2":
			apply_buff('basic2',array(
				"startmsg"=>"`v`iIron Defense!`i`n`tYou `qrelease reiatsu to aid your defense.",
				"name"=>"`vIron Defense",
				"rounds"=>$rounds,
				"wearoff"=>"You fail to hide any longer.",
				"badguyatkmod"=>0.8,
				"defmod"=>1.1,
				"roundmsg"=>"{badguy} is hindered by your reiatsu!",
				"schema"=>"module-specialtysystem_basic"
			));
			break;
		case "basic3":
			$defmod=1-min(0.4,round($session['user']['dragonkills']/0.08,2));
			$atkmod=$defmod-0.1;
			apply_buff('basic3',array( //to be revised
				"startmsg"=>"`v`iSpirit Force Oppression!`i`n`tYou `qrelease an amount of your reiatsu to show your power to your enemy...",
				"name"=>"`vSpirit Force Oppression",
				"rounds"=>5,
				"wearoff"=>"You stop releasing reiatsu.",
				"badguydefmod"=>$defmod,
				"badguyatkmod"=>$atkmod,
				"roundmsg"=>"Your released reiatsu startles the enemy quite a bit!",
				"schema"=>"module-specialtysystem_basic"
			));
			break;
	}
	specialtysystem_incrementuses("specialtysystem_basic",httpget('cost'));
	return;
}

function specialtysystem_basic_dohook($hookname,$args){
	switch ($hookname) {
	case "specialtysystem-register":
		$args[]=array(
			"spec_name"=>'Basic Zanpakutō',
			"spec_colour"=>'`v',
			"spec_shortdescription"=>'-internal-',
			"spec_longdescription"=>'-internal-',
			"modulename"=>'specialtysystem_basic',
			"fightnav_active"=>'1',
			"newday_active"=>'0',
			"dragonkill-active"=>'0',
			"basic_uses"=>1,
			"dragonkill_minimum_requirement"=>-1
			);
		break;
	}
	return $args;
}

function specialtysystem_basic_run(){
}
?>
