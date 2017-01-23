<?php

//taken a deep look into City - Amwayr from Billie Kennedy

function lasnoches_getmoduleinfo(){
	$info = array(
		"name"=>"Hueco Mundo",
		"version"=>"1.0",
		"author"=>"`2Oliver Brendel",
		"category"=>"Cities",
		"download"=>"",
		"requires"=>array(
			"cities"=>"1.0|Eric Stevens, part of the core download",
		),
		"settings"=>array(
			"Hokage Village Settings,title",
			"villagename"=>"Name for the village|`4L`)as `7N`\$oches",
			"showforest"=>"Is the forest available from here?,bool|0",
			"travelfrom"=>"Where can you travel from,location|".getsetting("villagename", LOCATION_FIELDS),
			"travelto"=>"Where can you travel to,location|".getsetting("villagename", LOCATION_FIELDS),
			"mindk"=>"How many dks does a player have to have for access?,int|20",
		),
		"prefs"=>array(
			),
	);
	return $info;
}

function lasnoches_install(){
	module_addhook("villagetext");
	module_addhook("village");
	module_addhook("travel");
	module_addhook("validlocation");
	module_addhook("moderate");
	module_addhook("changesetting");
	module_addhook("mountfeatures");
	module_addhook("scrylocation");
	return true;
}

function lasnoches_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	return true;
}

function lasnoches_dohook($hookname,$args){
	global $session,$resline;
	$city = get_module_setting("villagename");
	switch($hookname){
	case "scrylocation":
		//you cannot scry to this one
		if (array_key_exists(sanitize($city),$args)) {
			$args=array_diff($args,array(sanitize($city)=>$args[sanitize($city)]));
		}
		break;
	case "travel":
break;
		$args2 = modulehook("count-travels", array('available'=>0,'used'=>0));
		$free = max(0, $args2['available'] - $args2['used']);
		$tfree=$free+$session['user']['turns'];
		$capital = getsetting("villagename", LOCATION_FIELDS);
		$hotkey = substr(sanitize($city), 0, 1);
		$scity = htmlentities(sanitize($city),ENT_COMPAT,getsetting('charset','ISO-8859-1'));
		tlschema("module-cities");
		if (($session['user']['superuser']&SU_MEGAUSER)!=SU_MEGAUSER) break;
		if ($session['user']['dragonkills'] < get_module_setting("mindk")) 
			break;
		if ($session['user']['location']!=$city){
			addnav("More Dangerous Travel");
			// Actually make the travel dangerous
			$cost=5;
			if($session['user']['location'] == get_module_setting("travelfrom")){
			addnav(array("Go to %s (%s points)", ($tfree>=$cost?$city:sanitize($city)),$cost),
					($tfree>=$cost?"runmodule.php?op=travel&module=cities&cost=5&city=$scity&d=1":""));
			}
			if($session['user']['location'] == get_module_setting("travelto") && $session['user']['location'] != get_module_setting("travelfrom")){
			addnav(array("Go to %s (%s points)", ($tfree>=$cost?$city:sanitize($city)),$cost),
					($tfree>=$cost?"runmodule.php?op=travel&module=cities&cost=5&city=$scity&d=1":""));
			}

		}
		if ($session['user']['superuser'] & SU_EDIT_USERS){
			addnav("Superuser");
			addnav(array("Go to %s (free)", $city),
					"runmodule.php?op=travel&module=cities&cost=5&city=$scity&su=1");
		}
		tlschema();
		break;
	case "changesetting":
		// Ignore anything other than villagename setting changes
		if ($args['setting']=="villagename" && $args['module']=="lasnoches") {
			if ($session['user']['location'] == $args['old']) {
				$session['user']['location'] = $args['new'];
			}
			$sql = "UPDATE " . db_prefix("accounts") . " SET location='" .
				$args['new'] . "' WHERE location='" . $args['old'] . "'";
			db_query($sql);
		}
		break;
	case "validlocation":
		if (is_module_active("cities"))
			$args[sanitize($city)]="village-lasnoches";
		break;
	case "moderate":
		if (is_module_active("cities")) {
			tlschema("commentary");
			$args["lasnoches"]=sprintf_translate("%s", $city);
			tlschema();
		}
		break;
	case "villagetext":

		if ($session['user']['location'] == sanitize($city)){
			$args['text']="`\$`c`@`bYou stand in the middle of Las Noches - the capital of the world of the Shadows and Hollows. Here, Arrancar reside and battle constantly to achieve more strength.`n`nThe place looks deserted except for the few buildings known to belong to `\$Aizen`@ as leader of the Espada.`c`n`nYou get the feeling you're surrounded by powerful beings that watch your very steps.`n`n";
            $args['schemas']['text'] = "module-lasnoches";
			$args['clock']="`n`7Having no day or night cycle, you can only guess the time to be around `&%s`7.`n";
            $args['schemas']['clock'] = "module-lasnoches";
			if (is_module_active("calendar")) {
				$args['calendar'] = "`n`2Secret voices whisper it is `&%s`2, `&%s %s %s`2.`n";
				$args['schemas']['calendar'] = "module-lasnoches";
			}
			$args['title']=array("%s", sanitize($city));
			$args['schemas']['title'] = "module-lasnoches";
			$args['sayline']="whispers";
			$args['schemas']['sayline'] = "module-lasnoches";
			$args['talk']="`n`&You sense:`n";
			$args['schemas']['talk'] = "module-lasnoches";
			$args['newest'] = "";

			//block all the multicity navs and modules. configure as needed for your server

			
			//blocknav("lodge.php");
			//blocknav("weapons.php");
			//blocknav("armor.php");
			//blocknav("clan.php");
			blocknav("train.php");
			blocknav("pvp.php");
			//blocknav("stables.php");
			//blocknav("runmodule.php?module=cities&op=travel");
			//blocknav("list.php");

			if (!get_module_setting("showforest"))
				blocknav("forest.php");



			//blocknav("bank.php");
			//blockmodule("cities");
			blockmodule("questbasics");
			blockmodule("house");
			blockmodule("klutz");
			blockmodule("abigail");
			blockmodule("crazyaudrey");
			blockmodule("zoo");
			//blockmodule("battlearena");
			blockmodule("beggarslane");
			//blocknav("clan.php");
			//blocknav("gardens.php");
			//blocknav("gypsy.php");
			//blockmodule("dwellings");
			blocknav("mercenarycamp.php");
			
			



			$args['schemas']['newest'] = "module-lasnoches";
			$args['gatenav']="Hollow Gates";
			$args['schemas']['gatenav'] = "module-lasnoches";
			$args['fightnav']="Nearby Plains";
			$args['schemas']['fightnav'] = "module-lasnoches";
			$args['marketnav']="Market Square";
			$args['schemas']['marketnav'] = "module-lasnoches";
			$args['tavernnav']="Indulgment Lane";
			$args['schemas']['tavernnav'] = "module-lasnoches";
			$args['section']="lasnoches";
			$args['infonav']="Espada Council";
			$args['schemas']['infonav'] = "module-lasnoches";
		}
		break;

	case "village":
		$from = get_module_setting("travelfrom");
		$to = get_module_setting("travelto");
		$city = sanitize($city);
		if ($session['user']['location']==$city){
			tlschema($args['schemas']['gatenav']);
			addnav($args['gatenav']);
			tlschema();
			addnav("Visit the Healing Faculty","healer.php?return=village.php");
			modulehook("eliteforest");
		}
//		if ($session['user']['acctid']==7) {
//		}
		break;
	}
	return $args;
}

function lasnoches_run(){
}

function lasnoches_freetravel() {
	$args = modulehook("count-travels", array('available'=>0,'used'=>0));
	$free = max(0, $args['available'] - $args['used']);
	return max(0,$free);
}
?>
