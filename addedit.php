<?php /* HELPDESK $Id$ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

include_once("helpdesk.functions.php");
global $HELPDESK_CONFIG;
$AppUI->loadCalendarJS(); 

$item_id = (int) w2PgetParam($_GET, 'item_id', 0);

$projects = getAllowedProjectsForJavascript(1);
$allowedProjects=getAllowedProjects(0,1);
$proj_ids=array();
foreach($allowedProjects as $proj){
    $proj_ids[]=$proj['project_id'];
}
$tasks = getAllowedTasksForJavascript($proj_ids,1);

$helpdesk = new CHelpDesk();
$helpdesk->load($item_id);
$q = new w2p_Database_Query; 
$q->addQuery('*');
$q->addTable('helpdesk_items');
$q->addWhere('item_id = ' .$item_id);
$hditem = $q->loadHash();

// Check permissions for this record
if ($item_id) {
  // Already existing item
  $canEdit = $perms->checkModule($m, 'edit') && hditemEditable($hditem); 
} else {
  $canEdit = $perms->checkModule($m, 'add');
}


if(!$canEdit) {
    $AppUI->redirect( "m=public&a=access_denied" );
}
// Use new default 'assigned to' ---KZHAO
if(!@$hditem["item_assigned_to"]) {
    if($HELPDESK_CONFIG['default_assigned_to_current_user']=='-1') {
        @$hditem["item_assigned_to"] = 0;
        if(!@$hditem["item_status"]) {
            @$hditem["item_status"]=0;
        }
    } elseif($HELPDESK_CONFIG['default_assigned_to_current_user']=='0') {
        @$hditem["item_assigned_to"] = $AppUI->user_id;
        if(!@$hditem["item_status"]) {
            @$hditem["item_status"]=1;
        }
    } else {
        @$hditem["item_assigned_to"] = $HELPDESK_CONFIG['default_assigned_to_current_user'];
        if(!@$hditem["item_status"]) {
            @$hditem["item_status"]=1;
        }
    }
}

if(!@$hditem["item_company_id"] && $HELPDESK_CONFIG['default_company_current_company']){
  @$hditem["item_company_id"] = $AppUI->user_company;
}

$itemtitleprefix = $HELPDESK_CONFIG['new_hd_item_title_prefix'];

// KZHAO : 8-8-2006
// get current user's company id and use it to filter users

$q = new w2p_Database_Query; 
$q->addQuery('DISTINCT cn.contact_company,cp.company_name');
$q->addTable('contacts','cn');
$q->addTable('users','u');
$q->addJoin('companies','cp','cn.contact_company=cp.company_id');
$q->addWhere('u.user_id=' . $AppUI->user_id . ' AND u.user_contact=cn.contact_id ');
$allowedComp = $q->loadHashList();
if(!count($allowedComp)) {
    echo "ERROR: No company found for current user!!<br>";
    $compId=0;
} elseif(count($allowedComp)==1) {
    $tmp=array_keys($allowedComp);
    $compId=$tmp[0];
    if ($HELPDESK_CONFIG['default_company_current_company']) {
        $allowedCompanies = arrayMerge( $allowedComp, array( 0 => '' ));
    } else {
        $allowedCompanies = arrayMerge( array( 0 => '' ), $allowedComp );
    }
    $allowedCompanies = arrayMerge( $allowedCompanies, getAllowedCompanies() );
} else {
    echo "ERROR: Multiple companies found for current user!!!<br>";
    $compId=0;
}
// Determine whether current user is a client
if($compId!=$HELPDESK_CONFIG['the_company']) {
    $client_disable=' disabled ';
} else {
    $client_disable=' ';
}

// setup contact list for javascript
$q = new w2p_Database_Query; 
$q->addQuery('c.contact_id,u.user_id, contact_email, contact_phone, 
    CONCAT_WS(\' \',c.contact_first_name, c.contact_last_name) as full_name');
$q->addTable('contacts','c');
$q->addJoin('users','u','c.contact_id=u.user_contact');

$list = $q->loadList();
foreach($list as $row){
  $contacts[] = "[{$row['contact_id']},{$row['user_id']},'" . addslashes($row['full_name']) . "', '" . addslashes($row['contact_email']) . "','" . addslashes($row['contact_phone']) . "']"; 
}                
$users = getAllowedUsers($compId,1);

//Use new watcher list --KZHAO
if($item_id) { 
    // if editing an existing helpdesk item, get its watchers from database
    $q = new w2p_Database_Query;
    $q->addQuery('helpdesk_item_watchers.user_id, contact_email, 
        CONCAT(contact_first_name, \' \',contact_last_name) as name');
    $q->addTable('helpdesk_item_watchers');
    $q->addJoin('users','','helpdesk_item_watchers.user_id = users.user_id');
    $q->addJoin('contacts','c','user_contact = contact_id');

    $q->addWhere('item_id = ' . $item_id );
    $q->addOrder('contact_last_name, contact_first_name');
    $watchers = $q->loadHashList();
} else { // for a new item, check default
    $q = new w2p_Database_Query;
    $q->addQuery('max(item_id)');
    $q->addTable('helpdesk_items');
    $new_item_id = $q->loadResult()+1;
    if($HELPDESK_CONFIG['default_watcher'] && $HELPDESK_CONFIG['default_watcher_list']){
        $watchers = explode(',',$HELPDESK_CONFIG['default_watcher_list']);
    }
}

// Setup the title block
$ttl = $item_id ? 'Editing Help Desk Item' : 'Adding Help Desk Item';

$titleBlock = new w2p_Theme_TitleBlock( $ttl, 'helpdesk.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=helpdesk", 'home' );
$titleBlock->addCrumb( "?m=helpdesk&a=list", 'list');

if ($item_id) {
  $titleBlock->addCrumb( "?m=helpdesk&a=view&item_id=$item_id", 'view this item' );
}

$titleBlock->show();

if ($item_id) { 
  $df = $AppUI->getPref('SHDATEFORMAT');
  $tf = $AppUI->getPref('TIMEFORMAT');
  $item_date = new w2p_Utilities_Date( $hditem["item_created"] );
  $deadline_date = intval($hditem["item_deadline"]) ?
        new w2p_Utilities_Date($hditem["item_deadline"]) : new w2p_Utilities_Date();
  $tc = $item_date->format( "$df $tf" );
} else {
  $item_date = new w2p_Utilities_Date();
  $deadline_date = new w2p_Utilities_Date();
  $item_date = $item_date->format( FMT_DATETIME_MYSQL );
  $hditem["item_created"] = $item_date;
}

?>
<script src="./lib/jquery/jquery.js" type="text/javascript"></script>
<script language="javascript" type="text/javascript">
function submitIt() {
  var f   = document.frmHelpDeskItem;
  var msg = '';

  if ( f.item_title.value.length < 1 ) {
    msg += "\n<?php echo $AppUI->_('Title'); ?>";
    f.item_title.focus();
  }

  if( f.item_requestor.value.length < 1 ) {
    msg += "\n<?php echo $AppUI->_('Requestor'); ?>";
    f.item_requestor.focus();
  }

  if( f.item_summary.value.length < 1 ) {
    msg += "\n<?php echo $AppUI->_('Summary'); ?>";
    f.item_summary.focus();
  }

  //concat all the multiselect values together for easier retrieval on the back end.
  var watchers = "";
  var list = f.watchers_select;
  for (var i=0, n = list.options.length; i < n; i++) {
    var user = list.options[i];
    if(user.selected)
    	watchers += user.value + ",";
  }
  if(watchers.length>0){
  	f.watchers.value = watchers.substring(0,watchers.length-1);
  }
  
  if( msg.length > 0) {
    alert('<?php echo $AppUI->_('helpdeskSubmitError', UI_OUTPUT_JS); ?>:' + msg);
  } else {
    f.submit();
  }
} 

function popContactDialog() {
<?php 
    print "\nvar company_id = ";
    print $compId . ";";
?>
    var selected_contacts_id = $('item_requestor_id').value;
// J: fix error populating ticket
    if (selected_contacts_id == undefined){selected_contacts_id=""};
//
    window.open('./index.php?m=public&a=contact_selector&dialog=1&call_back=setRequestor&selected_contacts_id='+selected_contacts_id+'&company_id='+company_id, 'contacts','height=600,width=400,resizable,scrollbars=yes');
}

var oldRequestor = '';

<?php 
	print "\nvar contacts = new Array(";
	if($contacts){
		print implode(",",$contacts );
	}
	print ");"; 
?>

// Callback function for the generic selector
function setRequestor( key, uid ) {
    var f = document.frmHelpDeskItem;
    var keyrray = key.split(',');
    f.item_requestor.value = '';
    f.item_requestor_email.value = '';
    f.item_requestor_phone.value = '';

    if (key=='') {
        keyrray[0] = <?php echo $AppUI->user_id; ?>;
    }
    f.item_requestor_id.value = keyrray[0];

    //do lookup
    $.ajax({
        type: 'POST',
        url: 'index.php?m=helpdesk',
        data: { dosql: 'do_contact_lookup',
                suppressHeaders: '1', contact_id: keyrray[0]},
        success: function(response) {
            var values = response.split('||');
            f.item_requestor.value = values[0];
            f.item_requestor_phone.value = values[1];
            f.item_requestor_email.value = values[2];
        }
    });
}

  
function updateStatus(obj){
  var f = document.frmHelpDeskItem;

  if(obj.options[obj.selectedIndex].value>0){
    if(f.item_status.selectedIndex==0){
    	f.item_status.selectedIndex=1;
    }
  }
}

<?php 
	$ua = $_SERVER['HTTP_USER_AGENT'];
	$isMoz = strpos( $ua, 'Gecko' ) !== false;

	print "\nvar projects = new Array(";
	if($projects){
		print implode(",",$projects );		
	}
	print ");"; 

	print "\nvar tasks = new Array(";
	if($tasks) {
		print implode(",",$tasks );
	}
	print ");"; 
	
?>

// Dynamic project list handling functions
function emptyList( list ) {
<?php 
	if ($isMoz) { 
?>
	 list.options.length = 0;
<?php 
 	} else {
?>
	 while( list.options.length > 0 )
		list.options.remove(0);
<?php } ?>

}

function addToList( list, text, value ) {
<?php if ($isMoz) { ?>
  list.options[list.options.length] = new Option(text, value);
<?php } else { ?>
  var newOption = document.createElement("OPTION");
  newOption.text = text;
  newOption.value = value;
  list.add( newOption, 0 );
<?php } ?>

}

function changeList( listName, source, target ) {
  var f = document.frmHelpDeskItem;
  var list = eval( "f."+listName );
  // Clear the options
  emptyList( list );
  // Refill the list based on the target
  // Add a blank first to force a change
   addToList( list, '', '0' );

   for (var i=0, n = source.length; i < n; i++) {
    if( source[i][0] == target ) {
      addToList( list, source[i][2], source[i][1] );
    }
  }
}

// Select an item in the list by target value
function selectList( listName, target ) {
  var f = document.frmHelpDeskItem;
  var list = eval( 'f.'+listName );

  for (var i=0, n = list.options.length; i < n; i++) {
    if( list.options[i].value == target ) {
      list.options.selectedIndex = i;
      return;
    }
  }
 
}
				  
<!-- TIMER RELATED SCRIPTS -->

function setDate( frm_name, f_date ) {
	fld_date = eval( 'document.' + frm_name + '.' + f_date );
	fld_real_date = eval( 'document.' + frm_name + '.' + 'item_' + f_date );
	if (fld_date.value.length>0) {
      if ((parseDate(fld_date.value))==null) {
            alert('The Date/Time you typed does not match your prefered format, please retype.');
            fld_real_date.value = '';
            fld_date.style.backgroundColor = 'red';
        } else {
        	fld_real_date.value = formatDate(parseDate(fld_date.value), 'yyyyMMdd');
        	fld_date.value = formatDate(parseDate(fld_date.value), '<?php echo $cal_sdf ?>');
            fld_date.style.backgroundColor = '';
  		}
	} else {
      	fld_real_date.value = '';
	}
}	
</script>
<!-- END OF TIMER RELATED SCRIPTS --> 

<form name="frmHelpDeskItem" action="?m=helpdesk" method="post" enctype="multipart/form-data">
    <input type="hidden" name="dosql" value="do_item_aed" />
    <input name="del" type="hidden" value="0" />
    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>" />
    <input type="hidden" name="item_requestor_type" value="<?php echo @$hditem["item_requestor_type"]; ?>" />
    <input type="hidden" name="item_requestor_id" id="item_requestor_id" value="<?php echo @$hditem["item_requestor_id"]; ?>" />
    <input type="hidden" name="item_created" value="<?php echo @$hditem["item_created"]; ?>" />
    <?php if (!$item_id): ?>
    <input type="hidden" name="item_created_by" value="<?php echo $AppUI->user_id; ?>" />
    <?php endif; ?>
<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
  <tr>
  <td valign="top" width="50%">
    <table cellspacing="0" cellpadding="2" border="0">
    <?php if ($item_id): ?>
    <tr>
      <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Date Created'); ?>:</td>
      <td width="100%"><strong><?php echo $tc; ?></strong></td>
    </tr>
    <?php endif; ?>
    <tr>
      <td align="right"><font color="red"><label for="it">* <?php echo $AppUI->_('Title'); ?>:</label></font></td>
      <td valign="top"><input type="text" class="text" id="it" name="item_title"
      <?php if ($item_id): ?>
            value="<?php echo @$hditem["item_title"]; ?>" maxlength="64"
      <?php else: ?>
            value="<?php printf($itemtitleprefix ,$new_item_id); ?>" maxlength="64" 
      <?php endif; ?> /></td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><font color="red"><label for="ir">* <?php echo $AppUI->_('Requestor'); ?>:</label></font></td>
      <td valign="top" nowrap="nowrap">
        <input type="text" class="text" id="ir" name="item_requestor"
        value="<?php echo @$hditem["item_requestor"]; ?>" maxlength="64"
        onChange="if (this.value!=oldRequestor) {
                    document.frmHelpDeskItem.item_requestor_id.value = 0;
                    oldRequestor = this.value;
                  }" />
      <input type="button" class="button" 
      		value="<?php echo $AppUI->_('Contacts'); ?>" onclick="popContactDialog();" />
     
      </td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><label for="ire">&dagger; <?php echo $AppUI->_('Requestor E-mail'); ?>:</label></td>
      <td valign="top"><input type="text" class="text" id="ire"
                              name="item_requestor_email"
                              value="<?php echo @$hditem["item_requestor_email"]; ?>"
                              maxlength="64" /></td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><label for="irp">&dagger; <?php echo $AppUI->_('Requestor Phone'); ?>:</label></td>
      <td valign="top"><input type="text" class="text" id="irp"
                              name="item_requestor_phone"
                              value="<?php echo @$hditem["item_requestor_phone"]; ?>"
                              maxlength="30" /></td>
    </tr>

    <tr>
      <td align="right"><label for="c"><?php echo $AppUI->_('Company'); ?>:</label></td>
      <td><?php echo arraySelect( $allowedCompanies, 'item_company_id', 'size="1" class="text" id="c" onchange="changeList(\'item_project_id\',projects, this.options[this.selectedIndex].value)"',
                          @$hditem["item_company_id"] ); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="p"><?php echo $AppUI->_('Project'); ?>:</label></td>
      <td><select name="item_project_id" size="1" class="text" id="p" onchange="changeList('item_task_id', tasks, this.options[this.selectedIndex].value)" value="<?php echo @$hditem["item_project_id"]; ?>">
          </select></td>
    </tr>

    <tr>
      <td align="right"><label for="p"><?php echo $AppUI->_('Task'); ?>:</label></td>
      <td><select name="item_task_id" size="1" class="text" id="t" value="<?php echo @$hditem["item_task_id"]; ?>"></select></td>
    </tr>

    <tr>
      <td align="right" valign="top"><label for="iat"><?php echo $AppUI->_('Assigned To'); ?>:</label></td>
      <td><?php 
            echo arraySelect( arrayMerge( array( 0 => '' ), $users), 'item_assigned_to', 'size="1" class="text" id="iat" ' . $client_disable . ' onchange="updateStatus(this)"', @$hditem["item_assigned_to"] ); 
    	?></td>
    </tr>

    <?php   if($item_id) {
    		//existing item
		if($hditem['item_notify']) $emailNotify=1;
		else $emailNotify=0;
	    }
	    else {
		$emailNotify=$HELPDESK_CONFIG['default_notify_by_email'];
	    }
	
    ?>
    <tr>
       <td align="right" valign="top"><label for="iat"><?php echo $AppUI->_('Email Notification'); ?>:</label>
       </td>  
       <td>
     	    <input type="radio" name="item_notify" value="1" id="ina" 
	    		<?php if($emailNotify) echo "checked";
                echo $client_disable; ?> />
		<label for="ina"><?php echo $AppUI->_( 'Yes' ); ?></label>
	    <input type="radio" name="item_notify" value="0" id="inn" 
	    		<?php if(!$emailNotify) echo "checked";
                echo $client_disable; ?> />
	       <label for="inn"><?php echo $AppUI->_( 'No' ); ?></label>
       </td>
    </tr>
    </table>
  </td>
  <td valign="top" width="50%">
    <table cellspacing="0" cellpadding="2" border="0">
    <tr>
      <td align="right" nowrap="nowrap"><label for="ict"><?php echo $AppUI->_('Call Type'); ?>:</label></td>
      <td><?php echo arraySelect( $ict, 'item_calltype', 'size="1" class="text" id="ict"',
                          @$hditem["item_calltype"], true ); ?></td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><label for="ics"><?php echo $AppUI->_('Call Source'); ?>:</label></td>
      <td><?php echo arraySelect( $ics, 'item_source', 'size="1" class="text" id="ics"',
                          @$hditem["item_source"], true); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="ist"><?php echo $AppUI->_('Status'); ?>:</label></td>
      <td><?php echo arraySelect( $ist, 'item_status', 'size="1" class="text" id="ist"' . $client_disable ,
                          @$hditem["item_status"], true ); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="ipr"><?php echo $AppUI->_('Priority'); ?>:</label></td>
      <td><?php echo arraySelect( $ipr, 'item_priority', 'size="1" class="text" id="ipr"',
                          @$hditem["item_priority"], true ); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="isv"><?php echo $AppUI->_('Severity'); ?>:</label></td>
      <td><?php echo arraySelect( $isv, 'item_severity', 'size="1" class="text" id="isv"',
                          @$hditem["item_severity"], true ); ?></td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><label for="ios"><?php echo $AppUI->_('Service'); ?>:</label></td>
      <td><?php echo arraySelect( $ios, 'item_service', 'size="1" class="text" id="ios"',
                          @$hditem["item_service"], true); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="iap"><?php echo $AppUI->_('Application'); ?>:</label></td>
      <td><?php echo arraySelect( $iap, 'item_application', 'size="1" class="text" id="iap"',
                          @$hditem["item_application"], true); ?></td>
    </tr>
    <tr>
      <!--
      <td align="right" nowrap="nowrap"><label for="idl"><?php echo $AppUI->_('Deadline'); ?>:</label></td>
      <td valign="top"><input type="text" class="text" id="idl"
                              name="item_deadline"
                              value="<?php echo "NA"/*@$hditem["item_deadline"]*/; ?>"
			      size="4"
                              maxlength="4" /><?php echo $AppUI->_('day(s) from today'); ?> 
      </td>
      -->
      <td align="right" nowrap="nowrap"><label for="idl"><?php echo $AppUI->_('Deadline'); ?>:</label> </td>
      <td>
      	<input type="hidden" name="item_deadline" value="
      	<?php 
      		if($item_id && $hditem["item_deadline"]!=NULL) 
      			echo $deadline_date->format( FMT_DATETIME_MYSQL ); 
      		else echo "N/A";
      	?>">	
    	  <input type="text" name="deadline" id="deadline" onchange="setDate('frmHelpDeskItem', 'log_date');" 
        value=
        "<?php if($item_id && $hditem['item_deadline']!=NULL) echo $deadline_date->format( $df ); else echo "Not Specified";?>" 
        class="text" disabled="disabled">
        <a href="javascript: void(0);" onclick="return showCalendar('deadline', '<?php echo $df ?>', 'frmHelpDeskItem', null, true)">
    			<img src="<?php echo w2PfindImage('calendar.gif'); ?>" width="24" height="12" alt="<?php echo $AppUI->_('Calendar'); ?>" border="0" />
	      </a>
      </td>
    </tr>
    <tr>
      <td align="right"><label for="iap"><?php echo $AppUI->_('Hours Worked'); ?>:</label></td>
      <td> 
         <input type="text" style="text-align:right" class="text" name="task_log_hours" id="task_log_hours" value="<?php echo $log->task_log_hours; ?>" maxlength="8" size="4" /> 
      </td>
    </tr>
    </table>
  </td>
</tr>

<tr><td colspan="2">
<table cellspacing="0" cellpadding="0" border="0">
<tr>
  <td align="left"><font color="red"><label for="summary">* <?php echo $AppUI->_('Summary'); ?>:</label></font>
  </td>
  <td>&nbsp;&nbsp;</td>
  <td><label for="watchers"><?php echo $AppUI->_('Watchers'); ?>:</label></td>
</tr>

<tr>
  <td valign="top">
    <textarea id="summary" cols="75" rows="12" class="textarea"
              name="item_summary"><?php echo @$hditem["item_summary"]; ?></textarea>
  </td>
  <td>&nbsp;&nbsp;</td>
      <td>
      <select name="watchers_select" size="14" id="watchers_select" multiple="multiple" 
      <?php if($is_client) echo "disabled class=disabledText";
            else echo "class=text";
      ?>
      >
      <?php
	      foreach($users as $id => $name){
		echo "<option value=\"{$id}\"";
    // Two situations -- KZHAO
		if($item_id && array_key_exists($id,$watchers))
			echo " selected";
    elseif(!$item_id && $watchers && in_array($id, $watchers))
      echo " selected";
		echo ">{$name}</option>";
	      }
      ?></select>
      <input type="hidden" name="watchers" value="" /></td>
</tr>

<!--Kang: file attachment-->
<tr>
  <td align="left"><?php echo $AppUI->_("Attach a file"); ?>
        <input type="File" name="hdfile" />
  </td>
</tr>
</table>
</td></tr>

<!--commented by KZHAO 7-20-2006
    code dealing with hours worked and cost code
-->
 
<tr>
  <td colspan="2">
  <br />
  <small>
    <font color="red">* <?php echo $AppUI->_('Required field'); ?></font><br />
    &dagger; <?php echo $AppUI->_('helpdeskFieldMessage'); ?>
  </small>
  <br /><br />
  </td>
</tr>

<tr>
  <td><input type="button" value="<?php echo $AppUI->_('back'); ?>" class="button" onClick="javascript:history.back(-1);" />
  </td>
  <td align="right"><input type="button" value="<?php echo $AppUI->_('submit'); ?>" class="button" onClick='submitIt()' >
  </td>
</tr>
</table>
</form>

<?php 
  /* If we have a company stored, pre-select it.
     Else, select nothing */
  if (@$hditem['item_company_id']) {
    $target = $hditem['item_company_id'];
  } else if (@$hditem['item_project_id']) {
    $target = $reverse[$hditem['item_project_id']];
  } else {
    $target = 0;
  }

  /* Select the project from the list */
  $select = @$hditem['item_project_id'] ? $hditem['item_project_id'] : 0;
  $select_task = @$hditem['item_task_id'] ? $hditem['item_task_id'] : 0;
?>

<script language="javascript">

<?php if (($compId!=$HELPDESK_CONFIG['the_company']) && (!$item_id) ) { ?>
  setRequestor ('',<?php echo $AppUI->user_id ?> );
<?php } ?>
  
selectList('item_company_id',<?php echo $target?>);
changeList('item_project_id', projects, <?php echo $target?>);
selectList('item_project_id',<?php echo $select?>);
<?php if(@$hditem['item_project_id']){ ?>
  changeList('item_task_id', tasks, <?php echo @$hditem['item_project_id']?>);
  selectList('item_task_id',<?php echo $select_task?>);
<?php } ?>

</script>
