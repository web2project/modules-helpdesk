<?php /* $Id$ */
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

GLOBAL $AppUI, $hditem, $ist, $ict, $HELPDESK_CONFIG,$cal_sdf;
$item_id = w2PgetParam( $_GET, 'item_id', 0 );
$AppUI->loadCalendarJS();

$users = getAllowedUsers();

$task_log_id = intval( w2PgetParam( $_GET, 'task_log_id', 0 ) );
$log = new CTask_Log();
if ($task_log_id) {
    $log->load( $task_log_id );

    //Prevent users from editing other ppls timecards.
    // KZHAO  11-30-2006
    // Problem: the $HELPDESK_CONFIG['minimum_edit_level'] is based on pre-defined user types in dP/functions/admin_func.php
    // the user types are not consistent with the user type defined for actual users...
    // Solution: use hard-coded admin user type 7 here
    if($HELPDESK_CONFIG['minimum_edit_level']>=$AppUI->user_type || $AppUI->user_type==7)
        $can_edit_task_logs=true;

    if (!$can_edit_task_logs) {
        if ($log->task_log_creator!= $AppUI->user_id) {
            $AppUI->redirect(ACCESS_DENIED);
        }
    }
} else {
    $log->task_log_help_desk_id = $item_id;
    $log->task_log_name = $hditem['item_title'];
}

//if ($canEdit) {
// Task Update Form
    $df = $AppUI->getPref( 'SHDATEFORMAT' );
    $log_date = new w2p_Utilities_Date( $log->task_log_date );
?>

<script language="JavaScript">
function updateStatus(obj)
{
  var f = document.editFrm;

  if (obj.options[obj.selectedIndex].value>0) {
    if (f.item_status.selectedIndex==0) {
        f.item_status.selectedIndex=1;
    }
  }
}

<!-- TIMER RELATED SCRIPTS -->
    // please keep these lines on when you copy the source
    // made by: Nicolas - http://www.javascript-page.com
    // adapted by: Juan Carlos Gonzalez jcgonz@users.sourceforge.net

    var timerID       = 0;
    var tStart        = null;
    var total_minutes = -1;

    public function UpdateTimer()
    {
       if (timerID) {
          clearTimeout(timerID);
          clockID  = 0;
       }
       // One minute has passed
     total_minutes = total_minutes+1;
       document.getElementById('timerStatus').innerHTML = '( '+total_minutes+' <?php echo $AppUI->_('minutes elapsed'); ?> )';

       // Lets round hours to two decimals
       var total_hours   = Math.round( (total_minutes / 60) * 100) / 100;
       document.editFrm.task_log_hours.value = total_hours;

       timerID = setTimeout('UpdateTimer()', 60000);
    }

    public function timerStart()
    {
        if (!timerID) { // this means that it needs to be started
            timerSet();
            button = document.getElementById('timerStartStopButton');
            button.innerHTML = '<?php echo $AppUI->_('Stop'); ?>';
            UpdateTimer();
        } else { // timer must be stoped
            button = document.getElementById('timerStartStopButton');
            button.innerHTML = '<?php echo $AppUI->_('Start'); ?>';
            document.getElementById('timerStatus').innerHTML = '';
            timerStop();
        }
    }

    public function timerStop()
    {
       if (timerID) {
          clearTimeout(timerID);
          timerID  = 0;
          total_minutes = total_minutes-1;
       }
    }

    public function timerReset()
    {
        document.editFrm.task_log_hours.value = '0.00';
        total_minutes = -1;
    }

    public function timerSet()
    {
        total_minutes = Math.round(document.editFrm.task_log_hours.value * 60) -1;
    }

function setDate(frm_name, f_date)
{
    fld_date = eval( 'document.' + frm_name + '.' + f_date );
    fld_real_date = eval( 'document.' + frm_name + '.' + 'task_' + f_date );
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

<table cellspacing="1" cellpadding="2" border="0" width="100%">
<form name="editFrm" action="?m=helpdesk&a=view&item_id=<?php echo $item_id; ?>" method="post">
    <input type="hidden" name="uniqueid" value="<?php echo uniqid(""); ?>" />
    <input type="hidden" name="dosql" value="do_item_aed" />
    <input type="hidden" name="item_id" value="<?php echo $item_id; ?>" />
    <input type="hidden" name="task_log" value="1" />
    <input type="hidden" name="task_log_id" value="<?php echo $log->task_log_id; ?>" />
    <input type="hidden" name="task_log_help_desk_id" value="<?php echo $item_id; ?>" />
    <input type="hidden" name="task_log_creator" value="<?php echo $AppUI->user_id; ?>" />
    <input type="hidden" name="task_log_name" value="Update :<?php echo $log->task_log_name; ?>" />
<tr>
    <td nowrap="nowrap">
        <?php echo $AppUI->_('Date'); ?><br />
        <input type="hidden" name="task_log_date" id="task_log_date" value="<?php echo $log_date ? $log_date->format(FMT_TIMESTAMP_DATE) : ''; ?>" />
        <input type="text" name="log_date" id="log_date" onchange="setDate('editFrm', 'log_date');" value="<?php echo $log_date ? $log_date->format($df) : ''; ?>" class="text" />
        <a href="javascript: void(0);" onclick="return showCalendar('log_date', '<?php echo $df ?>', 'editFrm', null, true)">
            <img src="<?php echo w2PfindImage('calendar.gif'); ?>" width="24" height="12" alt="<?php echo $AppUI->_('Calendar'); ?>" border="0" />
        </a>
    </td>
    <td><?php echo $AppUI->_('Summary'); ?>:<br />
        <input type="text" class="text" name="task_log_name" value="<?php echo $log->task_log_name; ?>" maxlength="255" size="60" />
    </td>
</tr>
<tr>
  <td><?php echo $AppUI->_('Status')?>:<br />
      <?php echo arraySelect( $ist, 'item_status', 'size="1" class="text" id="medium"',@$hditem["item_status"], true )?>
  </td>
    <td rowspan="4">
    <?php echo $AppUI->_('Description'); ?>:<br />
        <textarea name="task_log_description" class="textarea" cols="50" rows="7"><?php echo $log->task_log_description; ?></textarea>
    </td>
</tr>
<tr>
    <td><?php echo $AppUI->_('Assigned to'); ?>:<br />
      <?php echo arraySelect( arrayMerge( array( 0 => '' ), $users), 'item_assigned_to', 'size="1" class="text" id="iat" onchange="updateStatus(this)"',
                          @$hditem["item_assigned_to"] ); ?>
    </td>
</tr>
<tr>
    <td><?php echo $AppUI->_('Call Type'); ?>:<br />
      <?php echo arraySelect( $ict, 'item_calltype', 'size="1" class="text" id="ict"',@$hditem["item_calltype"], true ); ?>
    </td>
</tr>
<tr>
    <td nowrap="nowrap">
  <br />
    <?php echo "&nbsp;&nbsp;" . $AppUI->_('Hours Worked'); ?>
    <input type="text" style="text-align:right;" class="text" name="task_log_hours" value="<?php echo $log->task_log_hours; ?>" maxlength="8" size="4" />
    <a class="button" href="javascript:;" onclick="javascript:timerStart()"><span id="timerStartStopButton"><?php echo $AppUI->_('Start'); ?></span></a>
        <a class="button" href="javascript:;" onclick="javascript:timerReset()"><span id="timerResetButton"><?php echo $AppUI->_('Reset'); ?></span></a>
        <span id='timerStatus'></span>
    </td>
</tr>
<tr>
    <td colspan="2" valign="bottom" align="right">
        <input type="submit" class="button" value="<?php echo $AppUI->_($task_log_id?'update task log':'create log'); ?>" onclick="" />
    </td>
</tr>

</form>
</table>
