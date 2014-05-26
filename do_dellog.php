<?php /* HELPDESK $Id$ */ 
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

$del = w2PgetParam($_POST, 'del', 0);
$isNotNew = $_POST['task_log_id'];
$perms = &$AppUI->acl();
if ($del) {
	if (!$perms->checkModule('task_log', 'delete')) {
        $AppUI->redirect(ACCESS_DENIED);
	}
} elseif ($isNotNew) {
	if (!$perms->checkModule('task_log', 'edit')) {
        $AppUI->redirect(ACCESS_DENIED);
	}
} else {
	if (!$perms->checkModule('task_log', 'add')) {
        $AppUI->redirect(ACCESS_DENIED);
	}
}

$obj = new CHDTaskLog();
if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}
$redir='m=helpdesk&a=view&tab=0&item_id=' . $obj->task_log_help_desk_id;
$AppUI->setMsg('Task Log');
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		$AppUI->setMsg('deleted', UI_MSG_ALERT);
	}
}

$AppUI->redirect($redir);
