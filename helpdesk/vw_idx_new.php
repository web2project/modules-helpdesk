<?php /* HELPDESK $Id: vw_idx_new.php 161 2006-10-26 14:10:50Z kang $*/
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

require_once 'vw_idx_handler.php';

// Show opened items
vw_idx_handler(0);
