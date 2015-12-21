<?php /* HELPDESK $Id$ */ 
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

include_once 'helpdesk.functions.php';
/* This file will write a php config file to be included during execution of
 * all helpdesk files which require the configuration options. */

// Deny all but system admins
if (!canView('system')) {
	$AppUI->redirect(ACCESS_DENIED);
}

@include_once( "./functions/admin_func.php" );

$CONFIG_FILE = "./modules/helpdesk/config.php";

// get a list of permitted companies
$company = new CCompany();
$companies = $company->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 'company_name');
$companies = arrayMerge(array('0' => ''), $companies);

// Get all active users -- KZHAO
$activeUsers=getAllowedUsers(0,1);

$assignUsers=arrayMerge( array(-1=>'', 0 => 'Current User' ), $activeUsers);

$utypes = w2PgetSysVal('UserType'); 

//define user type list
$user_types = arrayMerge( $utypes, array( '-1' => $AppUI->_('None') ) );

/* All config options, their descriptions and their default values are defined
* here. Add new config options here. Type can be "checkbox", "text", "radio" or
* "select". If the type is "radio," it must include a set of buttons. If it's
* "select" then be sure to include a 'list' entry with the options.  if the key
* starts with headingXXX then it will just display the contents on the value.
* This is used for grouping.
*/
$config_options = array(
	"heading1" => $AppUI->_('Paging Options'),
	"items_per_page" => array(
		"description" => $AppUI->_('Helpdesk Items Per Page'),
		"value" => 30,
		'type' => 'text'
	),
	"status_log_items_per_page" => array(
		"description" => $AppUI->_('Helpdesk Logs Per Page'),
		"value" => 15,
		'type' => 'text'
	),
	"pages_per_side" => array(
		"description" => $AppUI->_('Helpdesk Pages Per Side'),
		"value" => 5,
		'type' => 'text'
	),
	"heading2" => $AppUI->_('Permission Options'),
	"the_company" => array(
		"description" => $AppUI->_('Helpdesk Host Company'),
		"value" => '',
		'type' => 'select',
		'list' => $companies
	),
	"no_company_editable" => array(
		"description" => $AppUI->_('Allow Helpdesk Items without Company'),
		"value" => '0',
		'type' => 'radio',
    		'buttons' => array (1 => $AppUI->_('Yes'),
                        0 => $AppUI->_('No'))
	),
	'minimum_edit_level' => array(
		'description' => $AppUI->_('Helpdesk Min Level'),
		'value' => 9,
		'type' => 'select',
		'list' => @$user_types
	),
	"use_project_perms" => array(
		"description" => $AppUI->_('Helpdesk Use Project Perms'),
		"value" => '0',
		'type' => 'radio',
    		'buttons' => array (1 => $AppUI->_('Yes'),
                        0 => $AppUI->_('No'))
	),
	'minimum_report_level' => array(
		'description' => $AppUI->_('Helpdesk Min Rep Level'),
		'value' => 9,
		'type' => 'select',
		'list' => @$user_types
	),
  // KZHAO: 2-6-2007
  // Change/add default settings
	"heading3" => $AppUI->_('New Item Default Selections'),
  
  // MiraKlim 18.05.2009 -added format for new HD item name
	"new_hd_item_title_prefix" => array(
		"description" => $AppUI->_('Default item title prefix'),
		"value" => 'HD-%05d',
		'type' => 'text'
	),
	"default_assigned_to_current_user" => array(
		"description" => $AppUI->_('Default Assigned To'),
		"value" => '',
    'type' => 'select',
    'list' => $assignUsers       
	),
	"default_company_current_company" => array(	 
		"description" => $AppUI->_('Default Company'),
		"value" => 1,
		'type' => 'radio',
		'buttons' => array (1 => $AppUI->_('Current User\'s Company'),
                                    0 => $AppUI->_('None'))
	),
  "default_watcher" => array(
      "description" => $AppUI->_('Use Default Watcher(s)'),
      "value" => 0,
      'type' => 'radio',
      'buttons' => array (1 => $AppUI->_('Yes'),
                          0 => $AppUI->_('No'))
  ),
	"default_watcher_list" => array(	 
		"description" => $AppUI->_('Default Watchers'),
		"value" => 0,
		'type' => 'multiselect',
		'list' => $activeUsers
	),
  
	"heading4" => $AppUI->_('Search Fields On Item List'),
	"search_criteria_search" => array(
		"description" => $AppUI->_('Title/Summary Search'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_call_type" => array(
		"description" => $AppUI->_('Call Type'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_company" => array(
		"description" => $AppUI->_('Company'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_status" => array(
		"description" => $AppUI->_('Status'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_call_source" => array(
		"description" => $AppUI->_('Call Source'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_project" => array(
		"description" => $AppUI->_('Project'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_assigned_to" => array(
		"description" => $AppUI->_('Assigned To'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_priority" => array(
		"description" => $AppUI->_('Priority'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_application" => array(
		"description" => $AppUI->_('Application'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_requestor" => array(
		"description" => $AppUI->_('Requestor'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_severity" => array(
		"description" => $AppUI->_('Severity'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_service" => array(
		"description" => $AppUI->_('Service'),
		"value" => 1,
		'type' => 'checkbox'
	),
	//KZHAO 9-12-2006
	// Configurable fields for email notification
	"heading5" => $AppUI->_('Notification Email Options'),
	"default_notify_by_email" => array(
		"description" => $AppUI->_('Email notification as default for new item'),
		"value" => 1,
		'type' => 'radio',
		'buttons' => array (1 => $AppUI->_('Yes'),
                		    0 => $AppUI->_('No'))
	),
	"task_watchers_notification" => array(
		"description" => $AppUI->_('Watchers notification'),
		"value" => 1,
		'type' => 'radio',
		'buttons' => array (1 => $AppUI->_('Always'),
                		    0 => $AppUI->_('Status change'))
	),
	"task_requestor_notification" => array(
		"description" => $AppUI->_('Requestor notification'),
		"value" => 1,
		'type' => 'radio',
		'buttons' => array (1 => $AppUI->_('Always'),
                		    0 => $AppUI->_('Status change'))
	),
	"notity_email_address" => array(
          "description" => $AppUI->_('New unassigned items notification address'),
	        "value" => 'support@yourcompany.com',
	        'type' => 'text'
	),
							
	"email_subject" => array(
	        "description" => $AppUI->_('Email subject (trailed by ticket number)'),
	        "value" => 'The Company registered your recent request',
	        'type' => 'text'
	),
	"email_header" => array(
	        "description" => $AppUI->_('Email header'),
	        "value" => 'The Company Ticket Management Registry',
	        'type' => 'text'
	)								
	
);

//if this is a submitted page, overwrite the config file.
if(w2PgetParam( $_POST, "Save", '' )!=''){

	if (is_writable($CONFIG_FILE)) {
		if (!$handle = fopen($CONFIG_FILE, 'w')) {
			$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('cannot be opened'), UI_MSG_ERROR );
			exit;
		}

		if (fwrite($handle, "<?php //Do not edit this file by hand, it will be overwritten by the configuration utility. \n") === FALSE) {
			$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('cannot be written to'), UI_MSG_ERROR );
			exit;
		} else {
			foreach ($config_options as $key=>$value){
			  if(substr($key,0,7)=='heading') continue;

				$val="";
				switch($value['type']){
					case 'checkbox': 
						$val = isset($_POST[$key])?"1":"0";
						break;
					case 'text': 
						$val = isset($_POST[$key])?$_POST[$key]:"";
						break;
					case 'select': 
						$val = isset($_POST[$key])?$_POST[$key]:"0";
						break;
          case 'multiselect':
            if(isset($_POST[$key])){
                  foreach ($_POST[$key] as $idx=>$watcher){
                      $val .= $watcher.",";
                  }
                  $val=trim($val,',');
            }
            else $val=0;
            break;
					case 'radio':
						$val = $_POST[$key];
						break;
					default:
						break;
				}
				
				fwrite($handle, "\$HELPDESK_CONFIG['".$key."'] = '".$val."';\n");
			}

			fwrite($handle, "?>\n");
			$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('has been successfully updated'), UI_MSG_OK );
			fclose($handle);
			require( $CONFIG_FILE );
		}
	} else {
		$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('is not writable'), UI_MSG_ERROR );
	}
} else if(w2PgetParam( $_POST, $AppUI->_('cancel'), '' )!=''){
	$AppUI->redirect("m=system&a=viewmods");
}

//$HELPDESK_CONFIG = array();
require_once( $CONFIG_FILE );

//Read the current config values from the config file and update the array.
foreach ($config_options as $key=>$value){
	if(isset($HELPDESK_CONFIG[$key])){

		$config_options[$key]['value']=$HELPDESK_CONFIG[$key];
	}
}

// setup the title block
$titleBlock = new w2p_Theme_TitleBlock( 'Configure Help Desk Module', 'helpdesk.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=system", "System Admin" );
$titleBlock->addCrumb( "?m=system&a=viewmods", "Modules" );
$titleBlock->show();

?>

<script language = 'javascript' type='text/javascript'>
  function enableWatcherList(show){
      var useDefWatcher=document.getElementById('default_watcher');
      var WatcherList=document.getElementById('default_watcher_list[]');
      if(show){
         document.getElementById('default_watcher_list[]').disabled=false;
         document.getElementById('default_watcher_list[]').className='text';
      }
      else{
         document.getElementById('default_watcher_list[]').disabled=true;
         document.getElementById('default_watcher_list[]').className='disabledText';
      }
  }
</script> 

<form method="post">
<table class="std">
<?php
$useDefWatcher=0;
foreach ($config_options as $key=>$value){
?>
	<tr>
		<?php
    // the key starts with hr, then just display the value
	  if(substr($key,0,7)=='heading'){ ?>
		  <th align="center" colspan="2"><?php echo $value?></th>
		<?php } else { ?>
		<td align="right"><?php echo $value['description']?></td>
		<td><?php
      switch($value['type']){
        case 'checkbox': ?>
          <input type="checkbox" name="<?php echo $key?>" <?php echo $value['value']?"checked=\"checked\"":""?>>
          <?php
          break;
        case 'text': ?>
          <input type="text" name="<?php echo $key?>" value="<?php echo $value['value']?>" size=32>
          <?php
          break;
        case 'select': 
          print arraySelect( $value["list"], $key, 'class="text" size="1" id="' . $key . '" ' . $value["events"], $value["value"] );
          break;
        case 'multiselect':
          ?>
          <select name=<?php echo $key.'[]'; 
                             if($useDefWatcher)
                                echo " class='text'";
                             else
                                echo " class='disabledText' Disabled ";
                              ?> 
                              multiple="multiple" size="5" bgcolor=#ddd id=<?php echo $key.'[]'; ?>>
          <?php
            // organize string '118,72,2,68' into array 
            $selected=explode(',',$value["value"]);
            foreach ( $value["list"] as $k => $v ) {
                echo "\n\t<option value=\"".$k."\"".(in_array($k, $selected) ? " selected=\"selected\"" : '').">" .  $v  . "</option>";
            }
          ?>
          </select>
          <?php
          break;
        case 'radio':
          if($value['value']) $useDefWatcher=1;
          else $useDefWatcher=0;
          foreach ($value['buttons'] as $v => $n) {
            if($key=='default_watcher'){ ?>
                <label><input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>" 
                        value=<?php echo $v; ?> 
                        <?php echo (($value['value'] == $v)?"checked":""); ?> <?php echo $value['events']; ?> onClick=enableWatcherList(<?php echo $v; ?>)> <?php echo $n;?></label> 
            <?php }
            else {?>
                <label><input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>" value=<?php echo $v; ?> <?php echo (($value['value'] == $v)?"checked":""); ?> <?php echo $value['events']; ?>> <?php echo $n;?></label>
          <?php }
          }
          break;
        default:
          break;
      }
		?></td>
		<?php
			}
		?>
	</tr>
<?php	
}
?>
	<tr>
		<td colspan="2" align="right"><input type="Submit" name="Cancel" value="<?php echo $AppUI->_('back')?>">
                                  <input type="Submit" name="Save" value="<?php echo $AppUI->_('save')?>"></td>
	</tr>
</table>
</form>
