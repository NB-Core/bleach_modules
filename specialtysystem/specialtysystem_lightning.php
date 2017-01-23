<?php

function specialtysystem_lightning_getmoduleinfo(){
	$info = array(
		"name" => "Specialty System - Zanpaktou Lightning Techniques",
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

function specialtysystem_lightning_install(){
	module_addhook("specialtysystem-register");
	return true;
}

function specialtysystem_lightning_uninstall(){
	require_once("modules/specialtysystem/uninstall.php");
	specialtysystem_uninstall("specialtysystem_lightning");
	return true;
}

function specialtysystem_lightning_feats() {
	global $session;
	$rounds=e_rand(0,$session['user']['level']/3)+2;
	$feats=array(
				array(
					"name"=>"Lightning Bolt",
					"buffname"=>"shikai1",
					"cost"=>"1",
					"buff"=>array(
						"startmsg"=>"`2`iRaikiri!`i`nYou blast a bolt of lightning against {badguy}.",
						"name"=>"`2Raikiri",
						"rounds"=>1,
						"minioncount"=>1,
						"effectmsg"=>"{badguy} takes some serious damage ({damage} points)...!",
						"minbadguydamage"=>round($session['user']['intelligence']*3.5+$session['user']['level']+sqrt($session['user']['dragonkills'])),
						"maxbadguydamage"=>round($session['user']['intelligence']*4.5+$session['user']['level']+sqrt($session['user']['dragonkills'])),
						"schema"=>"module-specialtysystem_lightning",
						),
					),
			);
	return $feats;

}

function specialtysystem_lightning_fightnav(){
	global $session;

	$specname="lightning";
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
	if ($zan->get_raw_type()!=7) return;
	
	$setheadline=0;
	if ($uses > 0) {

		$feats=specialtysystem_lightning_feats();

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

function specialtysystem_lightning_apply($skillname){
	global $session;
	require_once("modules/specialtysystem/functions.php");
	
	require_once("modules/zanpakutou/func.php");
	$zan=get_zanpakutou();
	if (!$zan->is_released()) {
		output("`\$You cannot use this ability as your Zanpakuto is still not released in at least Shikai form!`n`n");
		return;
	}
	
	$feats=specialtysystem_lightning_feats();
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
	specialtysystem_incrementuses("specialtysystem_lightning",httpget('cost'));
	return;
}

function specialtysystem_lightning_dohook($hookname,$args){
	switch ($hookname) {
	case "specialtysystem-register":
		$args[]=array(
			"spec_name"=>'Lightning Zanpakutō',
			"spec_colour"=>'`2',
			"spec_shortdescription"=>'-internal-', //will not be displayed to public
			"spec_longdescription"=>'-internal-',
			"modulename"=>'specialtysystem_lightning',
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

function specialtysystem_lightning_run(){
}
?>
