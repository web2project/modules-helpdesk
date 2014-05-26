<?php /* HELPDESK $Id: vw_idx_my.php,v 1.3 2004/08/03 03:27:05 cyberhorse Exp $*/
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

require_once($w2Pconfig['root_dir'] . "/modules/helpdesk/helpdesk.functions.php");
require_once($w2Pconfig['root_dir'] . "/modules/helpdesk/vw_idx_handler.php");

global $AppUI, $project_id, $deny, $canRead, $canEdit, $w2Pconfig, $showCompany, $company_id;
$showCompany = false;
if (canView('helpdesk')) {
        if (canEdit('helpdesk')) {
                echo '<a href="./index.php?m=helpdesk&a=addedit&project_id=' . $project_id . '&company_id=' . $company_id . '">' . $AppUI->_('Add Issue') . '</a>';
                echo w2PshowImage('stock_attach-16.png', 16, 16, '', '', $m);
        }
vw_idx_handler(3);
}
