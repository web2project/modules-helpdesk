<?php /* HELPDESK $Id$ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

//KZHAO 10-24-2006
// Use mutlipart header and send emails in two formats
include_once("helpdesk.functions.php");
include_once("./modules/helpdesk/config.php");

// Define log types
define("NEW_ITEM_LOG", 1);
define("STATUS_LOG", 2);
define("STATUSTASK_LOG", 3);
define("TASK_LOG", 4);
define("NEW_WATCHER_LOG", 5);

// Pull in some standard arrays
$ict = w2PgetSysVal( 'HelpDeskCallType' );
$ics = w2PgetSysVal( 'HelpDeskSource' );
$ios = w2PgetSysVal( 'HelpDeskService' );
$iap = w2PgetSysVal( 'HelpDeskApplic' );
$ipr = w2PgetSysVal( 'HelpDeskPriority' );
$isv = w2PgetSysVal( 'HelpDeskSeverity' );
$ist = w2PgetSysVal( 'HelpDeskStatus' );
$isa = w2PgetSysVal( 'HelpDeskAuditTrail' );

$field_event_map = array(
//0=>Created
  1=>"item_title",            //Title
  2=>"item_requestor",        //Requestor Name
  3=>"item_requestor_email",  //Requestor E-mail
  4=>"item_requestor_phone",  //Requestor Phone
  5=>"item_assigned_to",      //Assigned To
  6=>"item_notify",           //Notify by e-mail
  7=>"item_company_id",       //Company
  8=>"item_project_id",       //Project
  9=>"item_calltype",         //Call Type
  10=>"item_source",          //Call Source
  11=>"item_status",          //Status
  12=>"item_priority",        //Priority
  13=>"item_severity",        //Severity
  14=>"item_service",         //Service - operating System, ...
  15=>"item_application",     //Application
  16=>"item_summary",         //Summary
  17=>"item_deadline" 	      //Deadline
 // 18=>Deleted
);

// Help Desk class
class CHelpDesk extends w2p_Core_BaseObject {
    public $item_id = NULL;
    public $item_title = NULL;
    public $item_summary = NULL;

    public $item_calltype = NULL;
    public $item_source = NULL;
    public $item_service = NULL;
    public $item_application = NULL;
    public $item_priority = NULL;
    public $item_severity = NULL;
    public $item_status = NULL;
    public $item_project_id = NULL;
    public $item_company_id = NULL;

    public $item_assigned_to = NULL;
    public $item_notify = 0;
    public $item_requestor = NULL;
    public $item_requestor_id = NULL;
    public $item_requestor_email = NULL;
    public $item_requestor_phone = NULL;
    public $item_requestor_type = NULL;

    public $item_created_by = NULL;
    public $item_created = NULL;
    public $item_modified = NULL;
    //public $item_updated = NULL;
    public $item_deadline =NULL;

    public function __construct() {
        parent::__construct('helpdesk_items', 'item_id','helpdesk');
    }

    public function check() {
        $errorArray = array();
        $baseErrorMsg = get_class($this) . '::store-check failed - ';

        return $errorArray;
    }

    public function store() {
        $stored = false;

        $errorMsgArray = $this->check();

        if (count($errorMsgArray) > 0) {
            return $errorMsgArray;
        }

        $this->item_summary = strip_tags($this->item_summary);

        //if type indicates a contact or a user, then look up that phone and email
        //for those entries
        switch ($this->item_requestor_type) {
            case '0'://it's not a user or a contact
                break;
            case '1'://it's a system user
                $q = $this->_getQuery();
                $q->addTable('users','u');
                $q->addQuery('u.user_id as id');

                $q->addQuery("CONCAT(c.contact_first_name,' ', c.contact_last_name) as name");
                $q->addQuery("contact_email as email, contact_phone as phone");
                $q->addJoin('contacts','c','u.user_contact = c.contact_id');
                $q->addWhere('u.user_id='.$this->item_requestor_id);
                break;
            case '2': //it's only a contact
                $q = $this->_getQuery();
                $q->addTable('contacts','c');
                $q->addQuery("CONCAT(c.contact_first_name,' ', c.contact_last_name) as name");
                $q->addQuery("contact_email as email, contact_phone as phone");
                $q->addWhere('contact_id='.$this->item_requestor_id);
                break;
            default:
                break;
        }
        // get requestor's information
        if(isset($q)) {
            $result = $q->loadHash();
            $q->clear();
            $this->item_requestor_email = $result['email'];
            $this->item_requestor_phone = $result['phone'];
            $this->item_requestor = $result['name'];
        }

        /*
         * TODO: I don't like the duplication on each of these two branches, but I
         *   don't have a good idea on how to fix it at the moment...
         *
         * TODO: Each of these branches should have a permissions check
         *   included. Review one fo the core core classes - like CLink - for
         *   the standard structure.
         */
    	$stored = false;

        $q = $this->_getQuery();
        $this->item_modified  = $q->dbfnNowWithTZ();
        if ($this->item_id) {
            if (($msg = parent::store())) {
                return $msg;
            }
            $stored = true;
        }
        if (0 == $this->item_id) {
            $this->item_created   = $q->dbfnNowWithTZ();
            if (($msg = parent::store())) {
                return $msg;
            }
            $stored = true;
        }
        return $stored;
    }

    public function lookup_contact($contact_id) {
        $contact = new CContact();
        $contact->load($contact_id);

        return $contact->contact_first_name.' '.$contact->contact_last_name.'||'.$contact->contact_phone.'||'.$contact->contact_email.'||';
    }

    public function delete() {
		// This section will grant every request to delete an HDitem
		$k = $this->_tbl_key;
		if ($oid) {
			$this->$k = intval( $oid );
		}
		//load the item first so we can get the item_title for history
		$this->load($this->item_id);
		addHistory($this->_tbl, $this->$k, 'delete', $this->item_title, $this->item_project_id);
		$result = null;

        $q = $this->_getQuery();
		$q->setDelete($this->_tbl);
		$q->addWhere("$this->_tbl_key = '".$this->$k."'");
		if (!$q->exec()) {
			$result = db_error();
		}
		$q->clear();
		$q->setDelete('helpdesk_item_status');
		$q->addWhere("status_item_id = '".$this->item_id."'");
		if (!$q->exec()) {
			$result .= db_error();
		}
		$q->clear();
		$q->setDelete('helpdesk_item_watchers');
		$q->addWhere("item_id = '".$this->item_id."'");
		if (!$q->exec()) {
			$result .= db_error();
		}
		$q->clear();
		$q->setDelete('task_log');
		$q->addWhere("task_log_help_desk_id = '".$this->item_id."'");
		if (!$q->exec()) {
			$result .= db_error();
		}
		$q->clear();
		return $result;	
  }
  
////////////////////////////////////////////////////////////////////////////////

// MiraKlim - for compatibility with unmodified files.class.php  
  function notify($type, $log_id, $newhdi=0) {
     $this->notifymsg($type, 'Notify called'); 
  }

  
  function notifymsg($type, $log_msg, $email_list=null) {
    global $w2Pconfig, $HELPDESK_CONFIG;
    if (!isset($email_list)) {
      $email_list = array();   
      if (($type!=TASK_LOG) || $HELPDESK_CONFIG['task_watchers_notification'] ) {
        // Pull up the email address of everyone on the watch list 
        // this list does not include the assignee
        $q = $this->_getQuery();
      	$q->addTable('helpdesk_item_watchers','hdw');
      	$q->addQuery('contact_email');
      	$q->addJoin('users','u','hdw.user_id = u.user_id');
      	$q->addJoin('contacts','c','u.user_contact = c.contact_id');
      	$q->addWhere("hdw.item_id='".$this->item_id."'");
  
        $log_user_email = $q->loadHashList();
        $log_user_email = array_keys($log_user_email);
        foreach ($log_user_email as $user_email) {
     	    if (trim($user_email)) {
         	  $email_list[] = $user_email;
     	    }
      	}
      	$q->clear();
      }
           
      if (($type!=TASK_LOG) || $HELPDESK_CONFIG['task_requestor_notification']) {
        //add the requestor email to the list of mailing people
        $email_list[] = $this->item_requestor_email;
        
        //add the assigned user email to the list of mailing people
        if (isset($this->item_assigned_to)) {
          $assigned_user_email = array();
          $q = $this->_getQuery();
          $q->addTable('users','u');
          $q->addQuery('contact_email');
      	  $q->addJoin('contacts','c','u.user_contact = c.contact_id');
          $q->addWhere('u.user_id='.$this->item_assigned_to);
          
          $log_user_email = $q->loadHashList();
          $log_user_email = array_keys($log_user_email);
          foreach ($log_user_email as $user_email) {
            if (trim($user_email) ) {
           	  $email_list[] = $user_email;
            }  
        	}
      	$q->clear();
        }
      }  
    } 	

		// Use subject and header from config.php
		$subject = $HELPDESK_CONFIG['email_subject']."---".$this->_AppUI->_('Item')."#{$this->item_id} ";
		$body .= $HELPDESK_CONFIG{'email_header'} . "\n";
		$body .= $this->_AppUI->_('Item') . "  : " . $this->item_id . "\n";
		$body .= $this->_AppUI->_('Subject') . "  : " . $this->item_title."\n";
		$body .= $this->_AppUI->_('Requestor') . "  : " . $this->item_requestor . "\n";
		$body .= $this->_AppUI->_('Link') . "  : " . $w2Pconfig['base_url']."/index.php?m=helpdesk&a=view&item_id=" . $this->item_id. "\n";

		switch ($type) {
			case STATUS_LOG:
			case NEW_ITEM_LOG:
                if($type==NEW_ITEM_LOG) {
                    $subject .= $this->_AppUI->_('Created');
                    if (!isset($this->item_status) || ($ist[$this->item_status]=='Unassigned') )  {
                        $email_list[] = $HELPDESK_CONFIG['notity_email_address'];
                    }
                } else {
                    $subject .= $this->_AppUI->_('Updated');
                }
				$body .= $this->_AppUI->_('Call Type') . "  : " . $ict[$this->item_calltype]."\n";
				$body .= $this->_AppUI->_('Status') . "  : " . $ist[$this->item_status]."\n";
				$body .= $this->_AppUI->_('Summary') . "  : \n" . $this->item_summary."\n";
				$body .= $this->_AppUI->_('Updates') . "  : \n" . $log_msg . "\n"; 		
				break;
			case TASK_LOG:
			  $subject .= $this->_AppUI->_('Task Log');
    		$body .= "\n" . $this->_AppUI->_('Task Log') . "\n" . $log_msg . "\n";
				break;
			case STATUSTASK_LOG:
			  $subject .= $this->_AppUI->_('Task+Status Log');
    		$body .= "\n" . $this->_AppUI->_('Task+Status Log') . "\n" . $log_msg . "\n";
				break;
			case NEW_WATCHER_LOG:
			  $subject .= $this->_AppUI->_('Watchers Notification');
    		$body = $this->_AppUI->_('You have been added to the watchers list for Help Desk item') . "\n" . $body . "\n" . $log_msg . "\n";
				break;
		}	
    //if there's no one in the list, skip the rest.
    if (count($email_list)>0) {
      $email_list=array_unique($email_list);

      foreach($email_list as $assigned_to_email){
  	    $mail = new w2p_Utilities_Mail();
  	    if ($mail->ValidEmail($assigned_to_email) && ($this->_AppUI->user_email!=$assigned_to_email)) {
      		// KZHAO 9-12-2006
      		$to=$assigned_to_email;
      	  if ($mail->ValidEmail($this->_AppUI->user_email)) {
    	    	$email = $this->_AppUI->user_email;
      	  } else {
            $email = $HELPDESK_CONFIG['notity_email_address'];
      	  }
  	      // Mail it
  		    $mail->Subject($subject, $locale_char_set);
  	      $mail->From($email);
  	      $mail->To($assigned_to_email);
  	      $mail->Body($body, isset( $GLOBALS['locale_char_set']) ? $GLOBALS['locale_char_set'] : "");
  	      $mail->Send();
        } 
      }
    }  
  }

    public function hook_search() {
        $search['table'] = 'helpdesk_items';
        $search['table_alias'] = 'h';
        $search['table_module'] = 'helpdesk';
        $search['table_key'] = 'h.item_id';
        $search['table_link'] = 'index.php?m=helpdesk&a=view&item_id=';
        $search['table_title'] = 'Helpdesk';
        $search['table_orderby'] = 'item_title';
        $search['table_orderby'] = 'item_title';
        $search['search_fields'] = array('item_title', 'item_summary', 
            'item_application', 'item_requestor', 'item_requestor_email',
            'task_log_name', 'task_log_description');
        $search['display_fields'] = $search['search_fields'];
        $search['table_joins'] = array(array('table' => 'task_log',
            'alias' => 'tl', 'join' => 'h.item_id = tl.task_log_help_desk_id'));

        return $search;
    }

  function log_status_changes() {
    global $ist, $ict, $ics, $ios, $iap, $ipr, $isv, $ist, $field_event_map;
    if(w2PgetParam( $_POST, "item_id")){
       $item_id = (int) w2PgetParam( $_POST, "item_id");
       $hditem = new CHelpDesk();
       $hditem->load($item_id);
      
      $count=0;
      $status_changes_summary="";
      foreach($field_event_map as $key => $value){
       if (eval("return  (isset(\$this->$value) && (\$hditem->$value != \$this->$value));")) {
          $old = $new = "";
      	  $count++;

          switch($value){
            // Create the comments here
            case 'item_assigned_to':
              $q = $this->_getQuery();
              $q->addQuery('user_id, concat(contact_first_name,\' \',contact_last_name) as user_name');
              $q->addTable('users');
              $q->addJoin('contacts','','user_contact = contact_id');
              $q->addWhere('user_id in ('.($hditem->$value?$hditem->$value:'0').
                                          ($this->$value&&$hditem->$value?', ':'').
                                          ($this->$value?$this->$value:'').')');
              $ids = $q->loadList();

              foreach ($ids as $row){
                if($row["user_id"]==$this->$value){
                  $new = $row["user_name"];
                } else if($row["user_id"]==$hditem->$value){
                  $old = $row["user_name"];
                }
              }
              break;
            case 'item_company_id':
//                $q = new w2p_Database_Query();
                $q = $this->_getQuery();
              $q->addQuery('company_id, company_name');
              $q->addTable('companies');
              $q->addWhere('company_id in ('.($hditem->$value?$hditem->$value:'').
                                          ($this->$value&&$hditem->$value?', ':'').
                                          ($this->$value?$this->$value:'').')');
              $ids = $q->loadList();

              foreach ($ids as $row){
                if($row["company_id"]==$this->$value){
                  $new = $row["company_name"];
                } 
            		else if($row["company_id"]==$hditem->$value){
                  $old = $row["company_name"];
                }
              }

              break;
            case 'item_project_id':
//                $q = new w2p_Database_Query();
                $q = $this->_getQuery();
              $q->addQuery('project_id, project_name');
              $q->addTable('projects');
              $q->addWhere('project_id in ('.($hditem->$value?$hditem->$value:'').
                                          ($this->$value&&$hditem->$value?', ':'').
                                          ($this->$value?$this->$value:'').')');
              $ids = $q->loadList();
              foreach ($ids as $row){
                if($row["project_id"]==$this->$value){
                  $new = $row["project_name"];
                } else if($row["project_id"]==$hditem->$value){
                  $old = $row["project_name"];
                }
              }
              break;
            case 'item_calltype':
              $old = $this->_AppUI->_($ict[$hditem->$value]);
              $new = $this->_AppUI->_($ict[$this->$value]);
              break;
            case 'item_source':
              $old = $this->_AppUI->_($ics[$hditem->$value]);
              $new = $this->_AppUI->_($ics[$this->$value]);
              break;
            case 'item_status':
              $old = $this->_AppUI->_($ist[$hditem->$value]);
              $new = $this->_AppUI->_($ist[$this->$value]);
              break;
            case 'item_priority':
              $old = $this->_AppUI->_($ipr[$hditem->$value]);
              $new = $this->_AppUI->_($ipr[$this->$value]);
              break;
            case 'item_severity':
              $old = $this->_AppUI->_($isv[$hditem->$value]);
              $new = $this->_AppUI->_($isv[$this->$value]);
              break;
            case 'item_service':
              $old = $this->_AppUI->_($ios[$hditem->$value]);
              $new = $this->_AppUI->_($ios[$this->$value]);
              break;
            case 'item_application':
              $old = $this->_AppUI->_($iap[$hditem->$value]);
              $new = $this->_AppUI->_($iap[$this->$value]);
              break;
            case 'item_notify':
              $old = $hditem->$value ? $this->_AppUI->_('On') : $this->_AppUI->_('Off');
              $new = $this->$value ? $this->_AppUI->_('On') : $this->_AppUI->_('Off');
              break;
            case 'item_deadline':
              $old = $hditem->$value;
              $new = $this->$value;
              if (strcmp($new,'N/A')) { 
                unset($new);
               }
              break;
            default:
              $old = trim($hditem->$value);
              $new = trim($this->$value);
              break;
	        }// end of switch

          if(!eval("return \$new == \$old;")){
            if ($new=='') {$new = ' ';}
      	    $last_status_comment = $this->log_status($key, $old, $new);
            $status_changes_summary .= $last_status_comment . "\n";
          } 
      	}//end of if
    	}//end of loop
      if ($this->item_notify && $count) {
        $this->notifymsg(STATUS_LOG, $status_changes_summary);
      }  
	    return $status_changes_summary;
    }
  }
  
  function log_status ($audit_code, $commentfrom="", $commentto="", $notify=0) {
  	global $isa ;
  	if ($commentto) {
  	  $sep = ' ';
  	  $sepend = ' ';
  	  if ($audit_code==16) {
        $sep = "------ ";
    	  $sepend = " \n";
      }    
  	  $comment = $sep . $this->_AppUI->_('changed from'). $sepend . " \"" . addslashes($commentfrom) . "\" ";
      $comment .= $sep . $this->_AppUI->_('to') . $sepend . " \"" . addslashes($commentto) . "\"";
    }
    else {
    	$comment=$commentfrom;
    }
    $sql = "
      INSERT INTO helpdesk_item_status
      (status_item_id,status_code,status_date,status_modified_by,status_comment)
      VALUES('{$this->item_id}','{$audit_code}',NOW(),'{$this->_AppUI->user_id}','$comment')
    ";
    db_exec($sql);

    if (db_error()) {
      return false;
    }
    $log_id = mysql_insert_id();

    if(($this->item_notify) && $notify==1){
      $this->notifymsg(($audit_code==0) ? NEW_ITEM_LOG : STATUS_LOG , $comment);
    }
    return $isa[$audit_code] . " " . $comment;
  }
}

/**
* Overloaded CTask Class
*/
class CHDTaskLog extends w2p_Core_BaseObject {
    public $task_log_id = NULL;
    public $task_log_task = NULL;
    public $task_log_help_desk_id = NULL;
    public $task_log_name = NULL;
    public $task_log_description = NULL;
    public $task_log_creator = NULL;
    public $task_log_hours = NULL;
    public $task_log_date = NULL;
    public $task_log_costcode = NULL;

    public function __construct() {
        parent::__construct('task_log', 'task_log_id');
    }
  
    // overload check method
    function check() {
        $this->task_log_hours = (float) $this->task_log_hours;
        return NULL;
    }
}
