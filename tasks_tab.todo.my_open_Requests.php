<?php /* HELPDESK $Id: vw_idx_my.php,v 1.3 2004/08/03 03:27:05 cyberhorse Exp $*/
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

require_once($w2Pconfig['root_dir'] . "/modules/helpdesk/helpdesk.functions.php");
require_once($w2Pconfig['root_dir'] . "/modules/helpdesk/vw_idx_handler.php");

// Show my items
vw_idx_handler(2);