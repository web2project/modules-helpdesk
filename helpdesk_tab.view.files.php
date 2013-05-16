<?php /* TASKS $Id: helpdesk_tab.view.files.php 5771 2008-07-15 14:41:58Z merlinyoda $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

GLOBAL $AppUI, $project_id, $task_id, $helpdesk_id, $deny, $canRead, $canEdit, $w2Pconfig, $cfObj, $m, $obj;

global $allowed_folders_ary, $denied_folders_ary, $limited;

$cfObj = new CFileFolder();
$allowed_folders_ary = $cfObj->getAllowedRecords($AppUI->user_id);
$denied_folders_ary = $cfObj->getDeniedRecords($AppUI->user_id);

$limited = ((count( $allowed_folders_ary ) < $cfObj->countFolders()) ? true : false);

if (!$limited) {
	$canEdit = true;
} elseif ($limited && array_key_exists($folder, $allowed_folders_ary)) {
	$canEdit = true;
} else {
	$canEdit = false;
}

$showProject = false;

if (getPermission('files', 'edit')) {
	echo ('<a href="./index.php?m=files&a=addedit&project_id=' . $project_id . '&file_helpdesk_item=' . $helpdesk_id  
	      . '&file_task=' . $task_id . '">' . $AppUI->_('Attach a file') . '</a>');
	echo w2PshowImage('stock_attach-16.png', 16, 16, '', '', $m);
}

$canAccess_folders = getPermission('file_folders', 'access');
if ($canAccess_folders) {
	$folder = w2PgetParam($_GET, 'folder', 0);
	require( W2P_BASE_DIR . '/modules/files/folders_table.php' );
} else if (getPermission('files', 'view')) {
	require( W2P_BASE_DIR . '/modules/files/index_table.php' );
}