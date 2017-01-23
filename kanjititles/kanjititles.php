<?php

function kanjititles_getmoduleinfo(){
$info = array(
	"name"=>"Kanji Titles",
	"version"=>"1.0",
	"author"=>"`2Oliver Brendel",
	"category"=>"Titles",
	"download"=>"",
	
	);
	return $info;
}

function kanjititles_install(){
	module_addhook("dragonkilltext");
	module_addhook_priority("setrace",INT_MAX);
	module_addhook("rock");
	return true;
}

function kanjititles_uninstall(){
	return true;
}

function kanjititles_dohook($hookname, $args){
	global $session;
	switch ($hookname) {
		case "setrace": case "dragonkilltext":
			$title=kanjititles_gettitle($session['user']['dragonkills'],$session['user']['race'],$session['user']['sex'],false);
			$newtitle=$title;
			require_once("lib/names.php");
			$newname = change_player_title($title);
			$session['user']['title'] = $title;
			$session['user']['name'] = $newname;

		
		break;
		case "rock":
			addnav("Tetsubo");
			addnav("Title Change to Japanese","runmodule.php?module=kanjititles&op=titles");
			break;
	}
	return $args;
}

function kanjititles_run(){
	global $session;
	$op=httpget('op');
	$name="`gT`xe`gt`xsu`tbo";
	page_header("%s",sanitize($name));
	$u=&$session['user'];
	addnav("Navigation");
	addnav("Back to the rock","rock.php");
	addnav("Actions");
	switch ($op) {
		case "changetitle":
			$title=kanjititles_gettitle($session['user']['dragonkills'],$session['user']['race'],$session['user']['sex'],true);
			$newtitle=$title;
			require_once("lib/names.php");
			debug($newtitle);debug($title);
			$newname = change_player_title($title);
			$session['user']['title'] = $title;
			$session['user']['name'] = $newname;
			output("`yAll set! Come back again...");
			break;
		case "overview":
			output("`yYou approach %s`y and ask about Japanese Titles... which are available and what they mean....`n`n",$name);
			$titles=array(
				"Senior Student"=>"下死神",
				"Shinigami"=>"死神",
				"Junior Officer"=>"後輩死神 ",
				"Senior Officer"=>"先輩死神",
				"Ranked Officer"=>"席官",
				"Fukutaicho"=>"副隊長",
				"Taicho"=>"隊長, Taichō",
				"`\$S`4ō`\$T`4aicho"=>"風影",
				);
			$wromanji=translate_inline("Rōmaji");
			$wkanji=translate_inline("Kanji");
			rawoutput("<center><table cellpadding='3' cellspacing='0' border='0' ><tr class='trhead'><td>$wromanji</td><td>$wkanji</td></tr>");
			$class='';
			foreach ($titles as $romanji=>$kanji) {
				$class=($class=='trlight'?'trdark':'trlight');
				rawoutput("<tr class='$class'><td>");
				output_notl("`@$romanji");
				rawoutput("</td><td>");
				output_notl("`2$kanji");
				rawoutput("</tr>");
			}
			rawoutput("</table>");
			addnav("Back to the titles","runmodule.php?module=kanjititles&op=titles");
			break;
		case "titles":
			output("`yYou approach %s`y and ask for Japanese Titles... you are explained that the following titles you get are in Rōmaji, like most Japanese things in the game, but you may here change it at any time to the Japanese counterparts. This does only affect your Final-Test-Title... if you have a custom title, it has a priority.`n`n`\$Do you want to change your title?",$name);
			require_once("lib/names.php");
			require_once("lib/titles.php");
			$title=kanjititles_gettitle($session['user']['dragonkills'],$session['user']['race'],$session['user']['sex'],false);
			debug($u['title']);
			debug($title);
			if ($u['title']==$title) {
				addnav("`xChange it `\$NOW`x please","runmodule.php?module=kanjititles&op=changetitle");
				output("`yPreview:`n`n`iBefore`i: %s`n`y`iAfter`i: %s",$u['title'],kanjititles_gettitle($session['user']['dragonkills'],$session['user']['race'],$session['user']['sex'],true));
			} else {
				output("Sadly, you do not have the standard title for your level... either you got a custom one by an event, or already have the Japanese one...");
			}
			addnav("Overview of available titles","runmodule.php?module=kanjititles&op=overview");
			break;
	
	}
	page_footer();
}

function kanjititles_gettitle($dk,$race,$sex=SEX_MALE,$jp=FALSE) {
	require_once("lib/titles.php");
	$titles=array(
				"Junior Student"=>"ジュニア留学",
				"Senior Student"=>"下死神",
				"Shinigami"=>"死神",
				"Junior Officer"=>"後輩死神 ",
				"Senior Officer"=>"先輩死神",
				"Ranked Officer"=>"席官",
				"Fukutaicho"=>"副隊長",
				"Taicho"=>"隊長, Taichō",
				"`\$S`4ō`\$T`4aicho"=>"風影",
				);
	$title=get_dk_title($dk,$sex);
	$ktitle='';
	$ktitle=str_replace(array_keys($titles),$titles,$title);

	if ($jp) {
		return $ktitle;
	} else {
		return $title;
	}
	

}

?>
