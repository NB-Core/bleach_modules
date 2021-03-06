<?php

function specialtysystem_ice_getmoduleinfo(){
	$info = array(
		"name" => "Specialty System - Zanpaktou Ice Techniques",
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

function specialtysystem_ice_install(){
	module_addhook("specialtysystem-register");
	return true;
}

function specialtysystem_ice_uninstall(){
	require_once("modules/specialtysystem/uninstall.php");
	specialtysystem_uninstall("specialtysystem_ice");
	return true;
}

function specialtysystem_ice_feats() {
	global $session;
	$rounds=e_rand(0,$session['user']['level']/3)+2;
	$feats=array(
				array(
					"name"=>"Ice Bolt",
					"buffname"=>"shikai1",
					"cost"=>"1",
					"buff"=>array(
						"startmsg"=>"`t`i次の舞・白漣, tsugi no mai, hakuren!`i`nYou fire a bolt of ice against {badguy}.",
						"name"=>"`t次の舞・白漣, tsugi no mai",
						"rounds"=>1,
						"minioncount"=>1,
						"effectmsg"=>"{badguy} takes some serious damage ({damage} points)...!",
						"minbadguydamage"=>round($session['user']['dexterity']*2.5+$session['user']['level']+sqrt($session['user']['dragonkills'])),
						"maxbadguydamage"=>round($session['user']['dexterity']*3.5+$session['user']['level']+sqrt($session['user']['dragonkills'])),
						"schema"=>"module-specialtysystem_ice",
						),
					),
			);
	return $feats;

}

function specialtysystem_ice_fightnav(){
	global $session;

	$specname="ice";
	require_once("modules/specialtysystem/functions.php");
	$uses=specialtysystem_availableuses();
	if ($session['user']['race']=="Menos" || $session['user']['race']=="Arrancar") {
	$name=translate_inline('Resurrección Abilites');
	} else {
		$name=translate_inline('Zanpakutō Abilites');
		}
	tlschema('module-specialtysystem_$specname');
	
	require_once("modules/zanpakutou/func.php");
	$zan=get_zanpakutou();
	if ($zan->get_raw_type()!=6) return;
	

	$setheadline=0;
	if ($uses > 0) {

		$feats=specialtysystem_ice_feats();

		foreach ($feats as $feat) {
			//cycle through all and see which ones to take

			if ($feat['cost']<=$uses) {

				if ($setheadline==0) specialtysystem_addfightheadline($name, false,specialtysystem_getskillpoints("specialtysystem_$specname"));
				specialtysystem_addfightnav($feat['name'],$feat['buffname']."&cost=".$feat['cost'],$feat['cost']);
			}
		}
	}
	tlschema();
	return specialtysystem_getfightnav();
}

function specialtysystem_ice_apply($skillname){
	global $session;
	require_once("modules/specialtysystem/functions.php");
	
	//check if in shikai state
	
	require_once("modules/zanpakutou/func.php");
	$zan=get_zanpakutou();
	if (!$zan->is_released()) {
		output("`\$You cannot use this ability as your Zanpakuto is still not released in at least Shikai form!`n`n");
		return;
	}
	
	
	$feats=specialtysystem_ice_feats();
	foreach ($feats as $feat) {
		if ($feat['buffname']==$skillname) {
			//execute the ordered one 
			if (isset($feat['buff'])) {
				apply_buff($feat['buffname'],$feat['buff']);
				break 1; //we have what we were looking for
			}
		}
	}	
	//add the costs and invalidate the cache
	specialtysystem_incrementuses("specialtysystem_ice",httpget('cost'));
	return;
}

function specialtysystem_ice_dohook($hookname,$args){
	switch ($hookname) {
	case "specialtysystem-register":
		$args[]=array(
			"spec_name"=>'Ice Zanpakutō',
			"spec_colour"=>'`t',
			"spec_shortdescription"=>'-internal-', //will not be displayed to public
			"spec_longdescription"=>'-internal-',
			"modulename"=>'specialtysystem_ice',
			"fightnav_active"=>'1',
			"newday_active"=>'0',
			"dragonkill-active"=>'0',
			"noaddskillpoints"=>1,			
			"dragonkill_minimum_requirement"=>-1
			);
		break;
	}
	return $args;
}

function specialtysystem_ice_run(){
}
?>
