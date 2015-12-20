<?php /* HELPDESK $Id: export.php v 0.1*/
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

include_once 'helpdesk.functions.php';

//KZHAO  10-24-2006
global $HELPDESK_CONFIG, $w2Pconfig;
$item_id = (int) w2PgetParam($_GET, 'item_id', 0);

// Pull data
$q = new w2p_Database_Query;
$q->addQuery('*');
$q->addTable('helpdesk_items');
$q->addWhere('item_id = ' . $item_id);
$hditem = $q->loadHash();

// Check permissions for this record
if ($item_id) {
    // Already existing item
    $canEdit = $perms->checkModule($m, 'edit') && hditemEditable($hditem);
} else {
    echo "Cannot find the item id!";

    return;
}

if (!$canEdit) {
    $AppUI->redirect(ACCESS_DENIED);
}

//KZHAO 10-24-2006
//Load helpdesk item
$org_hditem = new CHelpDesk();
$org_hditem->load( $item_id );

//Check required information before export
if (!@$hditem["item_project_id"]) {
     $AppUI->setMsg( "Project must be specified for this item before exporting to task!" , UI_MSG_ERROR );
         $AppUI->redirect("m=helpdesk&a=view&item_id=$item_id");
}
//KZHAO  7-10-2007
// Item with associated task cannot be exported
if (@$hditem["item_task_id"]) {
     $AppUI->setMsg( "Item with associated task cannot be exported to another task!" , UI_MSG_ERROR );
   $AppUI->redirect("m=helpdesk&a=view&item_id=$item_id");
}
//KZHAO 10-24-2006
// Check status
if ($ist[@$hditem["item_status"]]=="Closed") {
         $AppUI->setMsg( "Closed helpdesk items cannot be exported to tasks!" , UI_MSG_ERROR );
         $AppUI->redirect("m=helpdesk&a=view&item_id=$item_id");
}

if (!@$hditem["item_assigned_to"] && $HELPDESK_CONFIG['default_assigned_to_current_user']) {
  @$hditem["item_assigned_to"] = $AppUI->user_id;
  @$hditem["item_status"] = 1;
}

if (!@$hditem["item_company_id"] && $HELPDESK_CONFIG['default_company_current_company']) {
  @$hditem["item_company_id"] = $AppUI->user_company;
}
// Setup the title block

$df = $AppUI->getPref('SHDATEFORMAT');
$tf = $AppUI->getPref('TIMEFORMAT');
$item_date = new w2p_Utilities_Date( $hditem["item_created"] );
$deadline_date = new w2p_Utilities_Date( $hditem["item_deadline"] );
$tc = $item_date->format( "$df $tf" );

$dateNow = new w2p_Utilities_Date();
$dateNowSQL = $dateNow->format( FMT_DATETIME_MYSQL );

$newTask = new CTask();
$ref_task ="This task was created from Helpdesk item #".$item_id.".\n";
$ref_task.= "-----------------------\n";

if (@$hditem["item_priority"]==0 || @$hditem["item_priority"]==2) {
    $taskPrio=0;
} elseif (@$hditem["item_priority"]==1) {
    $taskPrio=-1;
} else {
    $taskPrio=1;
}

$hditem["item_deadline"] = (isset($hditem["item_deadline"])) ? $hditem["item_deadline"] : $dateNowSQL;

$taskInfo= array( "task_id"=>0,
    "task_name"=> @$hditem["item_title"],
    "task_project"=> @$hditem["item_project_id"],
    "task_start_date"=> $dateNowSQL,
    "task_end_date"=>@$hditem["item_deadline"],
    "task_priority"=>$taskPrio,
    "task_owner"=> $AppUI->user_id,
    "task_creator"=>$AppUI->user_id,
    "task_description"=> $ref_task.@$hditem["item_summary"],
    "task_contacts" => @$hditem["item_requestor_id"],
    "task_related_url"=> $w2Pconfig['base_url']."/index.php?m=helpdesk&a=view&item_id=".$item_id
);

echo "<br><br>";
$result= $newTask->bind( $taskInfo);
if (!$result) {
    $AppUI->setMsg( $newTask->getError(), UI_MSG_ERROR );
    $AppUI->redirect();
}
$result = $newTask->store();

if (is_array($result)) {
    $AppUI->setMsg( $msg, UI_MSG_ERROR );
    $AppUI->redirect(); // Store failed don't continue?
} else {
    $ref_hd ="This helpdesk item has been exported to task #".$newTask->task_id.".\n";
    $ref_hd.="Link: ".$w2Pconfig['base_url']."/index.php?m=tasks&a=view&task_id=".$newTask->task_id."\n";
    $ref_hd.="---------------------------\n";
    $org_hditem->item_status=2;
    $org_hditem->item_updated=$dateNowSQL;
    $org_hditem->item_summary=$ref_hd.$org_hditem->item_summary;
    $result = $org_hditem->store();
    if (is_array($result)) {
        $AppUI->setMsg( $msg, UI_MSG_ERROR );
        $AppUI->redirect();
    }
    $newTask->updateAssigned($hditem["item_assigned_to"], array($hditem["item_assigned_to"] => 100));

    $AppUI->setMsg( 'Task added!', UI_MSG_OK);
    $AppUI->redirect("m=helpdesk&a=view&item_id=$item_id&tab=0");
}
