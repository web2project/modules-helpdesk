<?php /* $Id: tasklist.php 156 2008-04-11 15:47:40Z pedroix $ $URL: https://web2project.svn.sourceforge.net/svnroot/web2project/trunk/modules/reports/reports/tasklist.php $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}
/**
 *
* Generates a report of the helpdesk logs for given dates including task logs
* Based on the original report tasklist.php by jcgonz
*/
global $AppUI, $cal_sdf;
$AppUI->loadCalendarJS();

//error_reporting( E_ALL );
$do_report = w2PgetParam($_POST, 'do_report', 0);
$log_all = w2PgetParam($_POST, 'log_all', 0);
$log_pdf = w2PgetParam($_POST, 'log_pdf', 0);
$log_ignore = w2PgetParam($_POST, 'log_ignore', 0);
$days = w2PgetParam($_POST, 'days', 30);

$list_start_date = w2PgetParam($_POST, 'list_start_date', 0);
$list_end_date = w2PgetParam($_POST, 'list_end_date', 0);

$period = w2PgetParam($_POST, 'period', 0);
$period_value = w2PgetParam($_POST, 'pvalue', 1);
if ($period) {
	$today = new w2p_Utilities_Date();
	$ts = $today->format(FMT_TIMESTAMP_DATE);
	if (strtok($period, ' ') == $AppUI->_('Next')) {
		$sign = + 1;
	} else { //if(...)
		$sign = -1;
	}

	$day_word = strtok(' ');
	if ($day_word == $AppUI->_('Day')) {
		$days = $period_value;
	} elseif ($day_word == $AppUI->_('Week')) {
		$days = 7 * $period_value;
	} elseif ($day_word == $AppUI->_('Month')) {
		$days = 30 * $period_value;
	}

	$start_date = new w2p_Utilities_Date($ts);
	$end_date = new w2p_Utilities_Date($ts);

	if ($sign > 0) {
		$end_date->addSpan(new Date_Span("$days,0,0,0"));
	} else {
		$start_date->subtractSpan(new Date_Span("$days,0,0,0"));
	}

	$do_report = 1;

} else {
	// create Date objects from the datetime fields
	$start_date = intval($list_start_date) ? new w2p_Utilities_Date($list_start_date) : new w2p_Utilities_Date();
	$end_date = intval($list_end_date) ? new w2p_Utilities_Date($list_end_date) : new w2p_Utilities_Date();
}

if (!$list_start_date) {
	$start_date->subtractSpan(new Date_Span('14,0,0,0'));
}
$end_date->setTime(23, 59, 59);

?>
<script language="javascript">
function setDate( frm_name, f_date ) {
	fld_date = eval( 'document.' + frm_name + '.' + f_date );
	fld_real_date = eval( 'document.' + frm_name + '.' + 'list_' + f_date );
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

<form name="editFrm" action="index.php?m=helpdesk&a=reports" method="post">
  <input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
  <input type="hidden" name="report_type" value="<?php echo $report_type;?>" />
<?php
if (function_exists('styleRenderBoxTop')) {
	echo styleRenderBoxTop();
}
?>
<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">

<tr>
        <td align="right"><?php echo $AppUI->_('Default Actions'); ?>:</td>
        <td nowrap="nowrap" colspan="2">
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Month'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Week'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Day'); ?>" />
        </td>
        <td nowrap="nowrap">
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Day'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Week'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Month'); ?>" />
        </td>
        <td colspan="3"><input class="text" type="field" size="2" name="pvalue" value="1" /> - value for the previous buttons</td>
</tr>
<tr>

	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('For period'); ?>:</td>
	<td nowrap="nowrap">
		<input type="hidden" name="list_start_date" id="list_start_date" value="<?php echo $start_date ? $start_date->format(FMT_TIMESTAMP_DATE) : ''; ?>" />
		<input type="text" name="start_date" id="start_date" onchange="setDate('editFrm', 'start_date');" value="<?php echo $start_date ? $start_date->format($df) : ''; ?>" class="text" />
		<a href="javascript: void(0);" onclick="return showCalendar('start_date', '<?php echo $df ?>', 'editFrm', null, true)">
			<img src="<?php echo w2PfindImage('calendar.gif'); ?>" width="24" height="12" alt="<?php echo $AppUI->_('Calendar'); ?>" border="0" />
		</a>
	</td>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('to'); ?></td>

	<td nowrap="nowrap">
		<input type="hidden" name="list_end_date" id="list_end_date" value="<?php echo $end_date ? $end_date->format(FMT_TIMESTAMP_DATE) : ''; ?>" />
		<input type="text" name="end_date" id="end_date" onchange="setDate('editFrm', 'end_date');" value="<?php echo $end_date ? $end_date->format($df) : ''; ?>" class="text" />
		<a href="javascript: void(0);" onclick="return showCalendar('end_date', '<?php echo $df ?>', 'editFrm', null, true)">
			<img src="<?php echo w2PfindImage('calendar.gif'); ?>" width="24" height="12" alt="<?php echo $AppUI->_('Calendar'); ?>" border="0" />
		</a>
	</td>

	<td nowrap="nowrap">
		<input type="checkbox" name="log_all" id="log_all" <?php if ($log_all)
	echo 'checked="checked"' ?> />
		<label for="log_all"><?php echo $AppUI->_('Log All'); ?></label>
	</td>
	<td nowrap="nowrap">
		<input type="checkbox" name="log_pdf" id="log_pdf" <?php if ($log_pdf)
	echo 'checked="checked"' ?> />
		<label for="log_pdf"><?php echo $AppUI->_('Make PDF'); ?></label>
	</td>

	<td align="right" width="50%" nowrap="nowrap">
		<input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('submit'); ?>" />
	</td>
</tr>

</table>
</form>

<?php
if ($do_report) {

  $q = new w2p_Database_Query;
  $q->addTable('helpdesk_items','hi');
  $q->addTable('users', 'ru');
  $q->addTable('contacts','rc');
  $q->addTable('users','au');
  $q->addTable('contacts','ac');
  $q->addQuery('hi.*, concat(rc.contact_first_name, " ", rc.contact_last_name) requested_by,
                concat(ac.contact_first_name, " " , ac.contact_last_name) assigned_to');

  if ($project_id) {
  	$q->addWhere('(hi.item_project_id = ' . $project_id . ' OR hi.item_project_id = 0)');
	  $q->addWhere('ru.user_id = hi.item_requestor_id');
  } else {
  	$q->addWhere('ru.user_id = hi.item_requestor_id');
  }
  $q->addWhere('rc.contact_id = ru.user_contact');
  $q->addWhere('au.user_id = hi.item_assigned_to');
  $q->addWhere('ac.contact_id = au.user_contact');

	if (!$log_all) {
    $q->addWhere('hi.item_created >= "' . $start_date->format(FMT_DATETIME_MYSQL) . '"');
    $q->addWhere('hi.item_created <= "' . $end_date->format(FMT_DATETIME_MYSQL) . '"');
	}

	$obj =&new CTask;
	$allowedTasks = $obj->getAllowedSQL($AppUI->user_id);
	if (count($allowedTasks)) {
    $q->addWhere(implode(" AND ", $allowedTasks));
	}
	$q->addOrder('hi.item_id');
	$Task_List = $q->exec();
		
	if (function_exists('styleRenderBoxBottom')) {
		echo styleRenderBoxBottom();
	}
	echo '<br />';
	if (function_exists('styleRenderBoxTop')) {
		echo styleRenderBoxTop();
	}

	echo "Call Log entries for ticket created from " . $start_date->format( $df ) . ' to ' . $end_date->format( $df ), 9 ;

	echo "<table cellspacing=\"1\" cellpadding=\"4\" border=\"0\" class=\"tbl\">";
	echo "<tr>";
    echo "<th>Number</th>";
    echo "<th>Created On</th>";
    echo "<th>Created By</th>";
    echo "<th>Title</th>";
    echo "<th width=200>Summary</th>";
  	echo "<th>Assigned To</th>";
	  echo "<th>Status</th>";
	  echo "<th>Priority</th>";
  echo "</tr>";
	
	$pdfdata = array();
	$columns = array(
		"<b>".$AppUI->_('Number')."</b>",
		"<b>".$AppUI->_('Created On')."</b>",
		"<b>".$AppUI->_('Created By')."</b>",
		"<b>".$AppUI->_('Title')."</b>",
		"<b>".$AppUI->_('Summary')."</b>",
		"<b>".$AppUI->_('Assigned To')."</b>",
		"<b>".$AppUI->_('Status')."</b>",
		"<b>".$AppUI->_('Priority')."</b>",
	);
	while ($Tasks = db_fetch_assoc($Task_List)){
		$log_date = new w2p_Utilities_Date( $Tasks['item_created'] );
    $q = new w2p_Database_Query; 
    $q->addQuery('TRIM(SUBSTRING_INDEX(SUBSTRING(sysval_value, LOCATE(\''.
                  $Tasks['item_status'] . '|\', sysval_value) + 1), \'\\n\', 1)) item_status_desc');
    $q->addTable('sysvals');
    $q->addWhere('sysval_title =\'HelpDeskStatus\'');
    $Log_Status = $q->loadHash();
		if (substr($Log_Status['item_status_desc'], 0, 6) != 'Closed') {
      $q = new w2p_Database_Query; 
      $q->addQuery('TRIM(SUBSTRING_INDEX(SUBSTRING(sysval_value, LOCATE(\''.
                    $Tasks['item_status'] . '|\', sysval_value) + 1), \'\\n\', 1)) item_priority_desc');
      $q->addTable('sysvals');
      $q->addWhere('sysval_title =\'HelpDeskPriority\'');
      $Log_Priority = $q->loadHash();
  		$str =  "<tr valign=\"top\">";
  		$str .= "<td align=\"right\">".$Tasks['item_id']."</td>";
  		$str .= "<td>".$log_date->format( $df )."</td>";
  		$str .= "<td>".$Tasks['requested_by']."</td>";
  		$str .= "<td>".$Tasks['item_title']."</td>";
  		$str .= "<td>".$Tasks['item_summary']."</td>";
  		$str .= "<td>".$Tasks['assigned_to']."</td>";
  		$str .= "<td>".$Log_Status['item_status_desc']."</td>";
  		$str .= "<td>".$Log_Priority['item_priority_desc']."</td>";
  		$str .= "</tr>";
  		echo $str;
  
  		$pdfdata[] = array(
  			$Tasks['item_id'],
  			$log_date->format( $df ),
  			$Tasks['requested_by'],
  			$Tasks['item_title'],
  			$Tasks['item_summary'],
  			$Tasks['assigned_to'],
  			$Log_Status['item_status_desc'],
  			$Log_Priority['item_priority_desc'],
  		);
  
      $q = new w2p_Database_Query; 
      $q->addQuery('tl.task_log_date, tl.task_log_description, concat(rc.contact_first_name, " ", rc.contact_last_name) created_by');
      $q->addTable('task_log','tl');
      $q->addTable('users','ru');
      $q->addTable('contacts','rc');
      $q->addWhere('tl.task_log_help_desk_id = "' . $Tasks['item_id'] . '"');
      $q->addWhere('ru.user_id = tl.task_log_creator');
      $q->addWhere('rc.contact_id = ru.user_contact');
      $q->addOrder('tl.task_log_id');
  		$Task_Log_Query = $q->exec();
  		$Row_Count = 1;
      while ($Task_Log = db_fetch_assoc($Task_Log_Query)){
    		$log_date = new w2p_Utilities_Date( $Task_Log['task_log_date'] );
    		$str =  "<tr valign=\"top\">";
  	  	$str .= "<td align=\"right\">".$Tasks['item_id']."/".$Row_Count."</td>";
  		  $str .= "<td>".$log_date->format( $df )."</td>";
    		$str .= "<td>".$Task_Log['created_by']."</td>";
  	  	$str .= "<td>"."</td>";
  		  $str .= "<td>".$Task_Log['task_log_description']."</td>";
    		$str .= "<td>"."</td>";
  	  	$str .= "<td>"."</td>";
    		$str .= "<td>"."</td>";
  	  	$str .= "</tr>";
    		echo $str;
    
    		$pdfdata[] = array(
  	  		$Tasks['item_id']."/".$Row_Count,
    			$log_date->format( $df ),
  	  		$Task_Log['created_by'],
    			"",
  	  		$Task_Log['task_log_description'],
    			"",
  	  		"",
  		  	"",
    		);
    		$Row_Count++;
  		} // while LOG
		} // if !Closed
	} //while Task

	echo "</table>";
if ($log_pdf) {
	// make the PDF file
    $q = new w2p_Database_Query; 
    $q->addTable('projects');
    $q->addQuery('project_name');
		$q->addWhere('project_id=' . (int)$project_id);
    $pname = $q->loadResult();

		$font_dir = W2P_BASE_DIR . '/lib/ezpdf/fonts';
		$temp_dir = W2P_BASE_DIR . '/files/temp';
		$base_url  = w2PgetConfig( 'base_url' );

		require ($AppUI->getLibraryClass('ezpdf/class.ezpdf'));

		$pdf = &new Cezpdf($paper = 'A4', $orientation = 'landscape');
		$pdf->ezSetCmMargins(1, 2, 1.5, 1.5);
		$pdf->selectFont($font_dir . '/Helvetica.afm');

		$pdf->ezText(w2PgetConfig('company_name'), 12);
		// $pdf->ezText( w2PgetConfig( 'company_name' ).' :: '.w2PgetConfig( 'page_title' ), 12 );		

		$date = new w2p_Utilities_Date();
		$pdf->ezText("\n" . $date->format($df), 8);

		$pdf->selectFont( "$font_dir/Helvetica-Bold.afm" );
		$pdf->ezText( "\n" . $AppUI->_('Helpdesk Report'), 12 );
		if ($project_id != 0) {
			$pdf->ezText($pname, 15);
		}
		if ($log_all) {
			$pdf->ezText( "All open entries", 9 );
		} else {
			$pdf->ezText( "Call Log entries for ticket created from ".$start_date->format( $df ).' to '.$end_date->format( $df ), 9 );
		}
		$pdf->ezText( "\n" );
		$pdf->selectFont( "$font_dir/Helvetica.afm" );
		//$columns = null; This is already defined above... :)
		$title = null;
		$options = array(
			'showLines' => 2,
			'showHeadings' => 1,
			'fontSize' => 9,
			'rowGap' => 4,
			'colGap' => 5,
			'xPos' => 50,
			'xOrientation' => 'right',
			'width'=>'750',
			'shaded'=> 0,
			'cols'=>array(0=>array('justification'=>'right','width'=>150),
					2=>array('justification'=>'left','width'=>95),
					3=>array('justification'=>'center','width'=>75),
					4=>array('justification'=>'center','width'=>75),
					5=>array('justification'=>'center','width'=>200))
		);

		$pdf->ezTable( $pdfdata, $columns, $title, $options );

		if ($fp = fopen( "$temp_dir/temp$AppUI->user_id.pdf", 'wb' )) {
			fwrite( $fp, $pdf->ezOutput() );
			fclose( $fp );
			echo "<a href=\"$base_url/files/temp/temp$AppUI->user_id.pdf\" target=\"pdf\">";
			echo $AppUI->_( "View PDF File" );
			echo "</a>";
		} else {
			echo "Could not open file to save PDF.  ";
			if (!is_writable( $temp_dir )) {
				"The files/temp directory is not writable.  Check your file system permissions.";
			}
		}
	}
}
?>
</table>