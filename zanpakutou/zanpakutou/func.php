<?php

function get_zanpakutou($target=false) {
	global $session;
	require_once("modules/zanpakutou.php");
	if (!is_numeric($target)) $target=false; //discussable
	if ($target===false) $target = $session['user']['acctid'];
	$zan=get_module_pref('zanpakutou','zanpakutou',$target);
	$zanpakutou=unserialize(stripslashes($zan));
	if (!is_a($zanpakutou,"zanpakutou")) {
		$zanpakutou=new zanpakutou;
	}
	return $zanpakutou;
}

function set_zanpakutou($zanpakutou,$target=false) {
	global $session;
	if ($target===false) $target = $session['user']['acctid'];
	$zan=serialize($zanpakutou);
	$test_zan=unserialize($zan);
	if (!is_a($test_zan,'zanpakutou')) {
		output("`\$Something went wrong - I could not save your Zanpakutou. Pls make a screenshot and report this...`n`n");
		set_module_pref('zanpakutou_error',$zan,'zanpakutou',$target);
	}	 else {
		$old=get_module_pref('zanpakutou','zanpakutou',$target);
		set_module_pref('zanpakutou_backup',$old,'zanpakutou',$target);
		set_module_pref('zanpakutou',$zan,'zanpakutou',$target);
	}

}

?>
