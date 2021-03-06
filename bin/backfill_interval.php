<?php

require_once(dirname(__FILE__).'/config.php');
require_once(WWW_DIR.'/lib/nntp.php');
require_once(WWW_DIR.'/lib/Tmux.php');
require_once(dirname(__FILE__).'/../test/ColorCLI.php');
require_once(dirname(__FILE__).'/../test/functions.php');

$c = new ColorCLI();
if (!isset($argv[1])) {
	exit($c->error("This script is not intended to be run manually, it is called from backfill_threaded.py."));
} else if (isset($argv[1])) {
	// Create the connection here and pass
	$nntp = new NNTP();
	if ($nntp->doConnect() !== true) {
		exit($c->error("Unable to connect to usenet."));
	}

	$pieces = explode(' ', $argv[1]);
	if (isset($pieces[1]) && $pieces[1] == 1) {
		$functions = new Functions();
		$functions->backfillAllGroups($nntp, $pieces[0]);
	} else if (isset($pieces[1]) && $pieces[1] == 2) {
		$tmux = new Tmux();
		$count = $tmux->get()->backfill_qty;
		$functions = new Functions();
		$functions->backfillPostAllGroups($nntp, $pieces[0], $count, $type = '');
	}
		$nntp->doQuit();
}
