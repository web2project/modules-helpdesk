<?php /* HELPDESK $Id$ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

global $HELPDESK_CONFIG, $w2Pconfig;
global $field_event_map,$ist,$ict;

$del = (int) w2PgetParam( $_POST, 'del', 0 );// the last parameter is the default value
$item_id = (int) w2PgetParam( $_POST, 'item_id', 0 );
$do_task_log = w2PgetParam( $_POST, 'task_log', 0 );
$deadline=w2PgetParam( $_POST, 'item_deadline', 0 );

$new_item = !($item_id>0);
$updated_date = new w2p_Utilities_Date();
$udate = $updated_date->format( FMT_DATETIME_MYSQL );
$notify_all = isset($_POST['item_notify']) ? w2PgetParam( $_POST,'item_notify',0) : $HELPDESK_CONFIG['default_notify_by_email'];

if($do_task_log){ // called from HD task log
	//first update the status on to current helpdesk item.
	$hditem = new CHelpDesk();
	$hditem->load( $item_id );
	$hditem->item_updated = $udate;

	$new_status = w2PgetParam( $_POST, 'item_status', 0 );
	$new_calltype = w2PgetParam( $_POST, 'item_calltype', 0 );
	$new_assignee = w2PgetParam( $_POST, 'item_assigned_to', 0 );
	$users = getAllowedUsers();
	$log_status_msg='';
	$update_item=0;

	if($new_status!=$hditem->item_status){
		$status_msg = $hditem->log_status(11,$AppUI->_($ist[$hditem->item_status]),$AppUI->_($ist[$new_status]));
    $log_status_msg .= $status_msg . "\n";
		$hditem->item_status = $new_status;
  	$update_item=1;
	}
	if($new_calltype!=$hditem->item_calltype){
		$status_msg = $hditem->log_status(9,$AppUI->_($ict[$hditem->item_calltype]),$AppUI->_($ict[$new_calltype]));
	  $log_status_msg .= $status_msg . "\n";
		$hditem->item_calltype = $new_calltype;
  	$update_item=1;
  }  
	if($new_assignee!=$hditem->item_assigned_to){
		$status_msg = $hditem->log_status(5,$AppUI->_($users[$hditem->item_assigned_to]),$AppUI->_($users[$new_assignee]));
	  $log_status_msg .= $status_msg . "\n";
		$hditem->item_assigned_to = $new_assignee;
  	$update_item=1;
  }
  if ($update_item==1) {
  	if (($msg = $hditem->store()) !== true) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
			$AppUI->redirect();
		} 
	} 

	//then create/update the task log
	$obj = new CHDTaskLog();

	if (!$obj->bind( $_POST )) {
		$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
		$AppUI->redirect();
	}

	if ($obj->task_log_date) {
	  $date = new w2p_Utilities_Date($obj->task_log_date . date('Hi')); 
		$obj->task_log_date = $date->format( FMT_DATETIME_MYSQL );
	}

	$AppUI->setMsg('Task Log');

  $obj->task_log_costcode = $obj->task_log_costcode;
  if ($msg = $obj->store() !== true) {
    $AppUI->setMsg( $msg, UI_MSG_ERROR );
    $AppUI->redirect();
  } else {
 	  $body = $AppUI->_('Summary') . " : " . $obj->task_log_name . "\n";	
		$body .= $AppUI->_('Description') . " : \n" . $obj->task_log_description . "\n";
   	if ($log_status_msg) {
      $body .= "\n" . $AppUI->_('Updates') . " : \n" . $log_status_msg;   
      $hditem->notifymsg(STATUSTASK_LOG, $body);
    }
    else {
      $hditem->notifymsg(TASK_LOG, $body);
    }
    if($AppUI->msgNo != UI_MSG_ERROR) {
      $AppUI->setMsg( @$_POST['task_log_id'] ? 'updated' : 'added', UI_MSG_OK, true );
    }
  }
	$AppUI->redirect("m=helpdesk&a=view&item_id=$item_id&tab=0");
} else {  // for creating or editting Helpdesk items

	$hditem = new CHelpDesk();
	if ( !$hditem->bind( $_POST )) {
		$AppUI->setMsg( $hditem->error, UI_MSG_ERROR );
		$AppUI->redirect();
	}

	$AppUI->setMsg( 'Help Desk Item', UI_MSG_OK );
	
	if ($del) {// to delete an item
  	$hditem->load( $item_id );
		$hditem->item_updated = $udate;
		if (($msg = $hditem->store()) !== true){ 
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
			$AppUI->redirect();
		}
		if (($msg = $hditem->delete())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
		} else {
			$AppUI->setMsg( 'deleted', UI_MSG_OK, true );
			$hditem->log_status(18,'Item '.$AppUI->_('Deleted'),1);
			$AppUI->redirect('m=helpdesk&a=list');
		}
	} else { // edit or new
		if ($new_item) {
			$item_date = new w2p_Utilities_Date();
 			$idate = $item_date->format( FMT_DATETIME_MYSQL );
			$hditem->item_created = $idate;
			$hditem->item_updated = $udate;
			$hditem->item_notify = $notify_all;
		} else {
  	  $hditem->item_notify = $notify_all;
			$hditem->item_updated = $udate;
   		$status_log_msg = $hditem->log_status_changes();
		}
		
    //KZHAO  8-10-2006
    // get the deadline for the HD item
    $dl = ((int) $deadline) ? new w2p_Utilities_Date($deadline) : new w2p_Utilities_Date();
    $dl->setTime(23,59,59);
    $hditem->item_deadline = $dl->format( FMT_DATETIME_MYSQL );

    // Kang: 3-15-2007
    // file uploading
    if (isset( $_FILES['hdfile']) && isset($_FILES['hdfile']['name']) && $_FILES['hdfile']['name']!='') {
        $file_obj = new CFile();
        $file_info=array();
        $acl =& $AppUI->acl();
        if ( ! $acl->checkModule('files', 'add')) {
            $AppUI->setMsg($AppUI->_( "noDeletePermission" ));
            $AppUI->redirect('m=public&a=access_denied');
        }
        $file_obj->_message = 'added';
        $file_info['file_version'] = 1.0;
        $file_info['file_category'] = 0;
        $file_info['file_parent'] = 0;
        $file_info['file_project'] = $hditem->item_project_id;
        if(!$new_item) {
            $file_info['file_description'] = $AppUI->_('This file is associated with helpdesk item') . ' ' .$hditem->item_id . ' ('. $hditem->item_title . ')';
            $file_info['file_helpdesk_item'] = $hditem->item_id;
        }
        $file_info['file_owner']=$AppUI->user_id;

        if (!$file_obj->bind( $file_info )) {
            $AppUI->setMsg( $file_obj->getError(), UI_MSG_ERROR );
            $AppUI->redirect();
        }

        $upload=null;
        $upload = $_FILES['hdfile'];
        if ($upload['size'] < 1) {
            if (!$file_id) {
                $AppUI->setMsg( 'Upload file size is zero. Process aborted.', UI_MSG_ERROR );
                $AppUI->redirect('m=helpdesk&a=view&item_id='.$hditem->item_id);
            }
        } else {
            // store file with a unique name
            $file_obj->file_name = $upload['name'];
            $file_obj->file_type = $upload['type'];
            $file_obj->file_size = $upload['size'];
            $file_obj->file_date = str_replace("'", '', $db->DBTimeStamp(time()));
            $file_obj->file_real_filename = uniqid( rand() );
            $res = $file_obj->moveTemp( $upload );
            if (!$res) {
                $AppUI->setMsg( 'File could not be written', UI_MSG_ERROR );
                $AppUI->redirect();
            }
        }

        if (! $file_obj->file_version_id) {
            $q  = new w2p_Database_Query;
            $q->addTable('files');
            $q->addQuery('file_version_id');
            $q->addOrder('file_version_id DESC');
            $q->setLimit(1);
            $sql = $q->prepare();
            $latest_file_version = $q->loadResult($sql);
            $file_obj->file_version_id = $latest_file_version + 1;
        }

        if (($msg = $file_obj->store()) !== true) {
            $AppUI->setMsg( $msg, UI_MSG_ERROR );
        }
        // add the link of the file to the description of the helpdesk item
        $hd_file_info="\n------------------\n" . $AppUI->_('Associated File Name:'). ' '.$file_obj->file_name;
        $hd_file_info.="  Link: ".$w2Pconfig['base_url']."/fileviewer.php?file_id=".$file_obj->file_id;
        $hd_file_info.="\n------------------\n";
        $hditem->item_summary=$hditem->item_summary . $hd_file_info;
    }// end of file uploading

  	if (($msg = $hditem->store()) !== true) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
		} else {
	    if($new_item) {// new item creation
 				$status_log_msg = $hditem->log_status(0,$AppUI->_('Ticket').' '.$AppUI->_('Created'),'',1);
 				//Lets create a log for the item creation:
 				$obj = new CHDTaskLog();
 				$new_item_log = array('task_log_id' => 0,'task_log_help_desk_id' => $hditem->item_id, 'task_log_creator' => $AppUI->user_id, 'task_log_name' => 'Item Created: '.$_POST['item_title'], 'task_log_date' => $hditem->item_created, 'task_log_description' => $_POST['item_title'], 'task_log_hours' => $_POST['task_log_hours'], 'task_log_costcode' => $_POST['task_log_costcode']);
 				if (!$obj->bind( $new_item_log )) {
 					$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
 					$AppUI->redirect();
 				}
 				if (($msg = $obj->store()) !== true) {
   				$AppUI->setMsg( $msg, UI_MSG_ERROR );
   				$AppUI->redirect();
 				}	
        // Generate the description for attached file
        if($file_obj){
          $file_obj->file_description = $AppUI->_('This file is associated with helpdesk item') . ' ' .$hditem->item_id . ' ('. $hditem->item_title . ')';
          $file_obj->file_helpdesk_item=$hditem->item_id;
          if (($msg = $file_obj->store()) !== true) {
            $AppUI->setMsg( $msg, UI_MSG_ERROR );
          }
        }
	    }
     	// KZHAO  8-7-2006
	    doWatchers(w2PgetParam( $_POST, 'watchers', 0 ), $hditem, $notify_all);
      // KZHAO  8-7-2006
      if($AppUI->msgNo != UI_MSG_ERROR) {
        $AppUI->setMsg( $new_item ? ($AppUI->_('Help Desk Item') .' '. $AppUI->_('added')) : ($AppUI->_('Help Desk Item') . ' ' . $AppUI->_('updated')) , UI_MSG_OK, false );
      }
      $AppUI->redirect('m=helpdesk&a=view&item_id='.$hditem->item_id);
		}
	}
}

// dealing with the helpdesk_item_watchers table in DB and send emails
// send emails to acknowledge that they are added to the watcher list
function doWatchers($list, $hditem, $notify_all){//KZHAO 8-7-2006
	global $AppUI;
	
	# Create the watcher list
	$watcherlist = split(',', $list);
	
  $q = new w2p_Database_Query; 
  $q->addQuery('user_id');
  $q->addTable('helpdesk_item_watchers');
  $q->addWhere('item_id=' . $hditem->item_id);
  $current_users = $q->loadHashList();
	$current_users = array_keys($current_users);

	# Delete the existing watchers as the list might have changed
	$sql = "DELETE FROM helpdesk_item_watchers WHERE item_id=" . $hditem->item_id;
	db_exec($sql);
	
	//print_r($current_users);
	//echo "!!!<br>";
	if (!$del){
		if($list){
			foreach($watcherlist as $watcher){
                $q = new w2p_Database_Query;
                $q->addQuery('user_id, c.contact_email');
                $q->addTable('users');
                $q->addJoin('contacts','c','user_contact = contact_id');
                $q->addWhere('user_id=' . $watcher);
				if($notify_all){
					$rows = $q->loadlist($sql);
					$email_list = array();
					foreach($rows as $row){
					# Send the notification that they've been added to a watch list.
						//KZHAO 8-3-2006: only when users choose to send emails
						if(!in_array($row['user_id'],$current_users)){
							$email_list[] = $row['contact_email'];
						}
					}
                    $hditem->notifymsg(NEW_WATCHER_LOG, '', $email_list);
				}

				$sql = "INSERT INTO helpdesk_item_watchers VALUES(". $hditem->item_id . "," . $watcher . ",'Y')";
				db_exec($sql);
			}
		}
	}
}
