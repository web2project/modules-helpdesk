<?php /* $Id: tasklogs.php 156 2008-04-11 15:47:40Z pedroix $ $URL: https://web2project.svn.sourceforge.net/svnroot/web2project/trunk/modules/reports/reports/tasklogs.php $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

/**
 * Generates a report of the task logs for given dates
 */
//error_reporting( E_ALL );
global $AppUI, $cal_sdf;
$AppUI->loadCalendarJS();

$perms = &$AppUI->acl();
if (!$perms->checkModule('task_log', 'view')) {
    $AppUI->redirect(ACCESS_DENIED);
}
$do_report = w2PgetParam($_GET, 'do_report', 0);
$log_all = w2PgetParam($_GET, 'log_all', 0);
$log_pdf = w2PgetParam($_GET, 'log_pdf', 0);
$log_ignore = w2PgetParam($_GET, 'log_ignore', 0);
$log_userfilter = w2PgetParam($_GET, 'log_userfilter', '0');

$log_start_date = w2PgetParam($_GET, 'log_start_date', 0);
$log_end_date = w2PgetParam($_GET, 'log_end_date', 0);

// create Date objects from the datetime fields
$start_date = intval($log_start_date) ? new w2p_Utilities_Date($log_start_date) : new w2p_Utilities_Date();
$end_date = intval($log_end_date) ? new w2p_Utilities_Date($log_end_date) : new w2p_Utilities_Date();

if (!$log_start_date) {
	$start_date->subtractSpan(new Date_Span('14,0,0,0'));
}
$end_date->setTime(23, 59, 59);

// Lets check cost codes
$q = new w2p_Database_Query;
$q->addTable('billingcode');
$q->addQuery('billingcode_id, billingcode_name');

$task_log_costcodes[0] = $AppUI->_('None');
$rows = $q->loadList();
echo db_error();
$nums = 0;
if ($rows) {
	$nums = count($rows);
}
foreach ($rows as $row) {
	$task_log_costcodes[$row['billingcode_id']] = $row['billingcode_name'];
}

?>
<script language="javascript">
function setDate( frm_name, f_date ) {
	fld_date = eval( 'document.' + frm_name + '.' + f_date );
	fld_real_date = eval( 'document.' + frm_name + '.' + 'log_' + f_date );
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

<form name="editFrm" action="" method="GET">
<input type="hidden" name="m" value="helpdesk" />
<input type="hidden" name="a" value="reports" />
<input type="hidden" name="company_id" value="<?php echo $company_id; ?>" />
<input type="hidden" name="project_id" value="<?php echo $project_id; ?>" />
<input type="hidden" name="report_type" value="<?php echo $report_type; ?>" />

<?php
if (function_exists('styleRenderBoxTop')) {
	echo styleRenderBoxTop();
}
?>
<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">

<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('For period'); ?>:</td>
	<td nowrap="nowrap">
		<input type="hidden" name="log_start_date" id="log_start_date" value="<?php echo $start_date ? $start_date->format(FMT_TIMESTAMP_DATE) : ''; ?>" />
		<input type="text" name="start_date" id="start_date" onchange="setDate('editFrm', 'start_date');" value="<?php echo $start_date ? $start_date->format($df) : ''; ?>" class="text" />
		<a href="javascript: void(0);" onclick="return showCalendar('start_date', '<?php echo $df ?>', 'editFrm', null, true)">
			<img src="<?php echo w2PfindImage('calendar.gif'); ?>" width="24" height="12" alt="<?php echo $AppUI->_('Calendar'); ?>" border="0" />
		</a>
	</td>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('to'); ?></td>

	<td nowrap="nowrap">
		<input type="hidden" name="log_end_date" id="log_end_date" value="<?php echo $end_date ? $end_date->format(FMT_TIMESTAMP_DATE) : ''; ?>" />
		<input type="text" name="end_date" id="end_date" onchange="setDate('editFrm', 'end_date');" value="<?php echo $end_date ? $end_date->format($df) : ''; ?>" class="text" />
		<a href="javascript: void(0);" onclick="return showCalendar('end_date', '<?php echo $df ?>', 'editFrm', null, true)">
			<img src="<?php echo w2PfindImage('calendar.gif'); ?>" width="24" height="12" alt="<?php echo $AppUI->_('Calendar'); ?>" border="0" />
		</a>
	</td>

	<td nowrap="nowrap">
		<?php echo $AppUI->_('User'); ?>:
        <select name="log_userfilter" class="text" style="width: 200px">

	<?php
  if ($log_userfilter == 0)
	  echo '<option value="0" selected="selected">' . $AppUI->_('All users');
  else
	  echo '<option value="0">All users';
    if (($log_userfilter_users = w2PgetUsersList())) {
    	foreach ($log_userfilter_users as $row) {
		    $selected = '';
    		if ($log_userfilter == $row['user_id']) {
		  	$selected = ' selected="selected"';
  		}
	  	echo '<option value="' . $row['user_id'] . '"' . $selected . '>' . $row['contact_first_name'] . ' ' . $row['contact_last_name'];
  	}
  }

	?>

		</select>
	</td>

	<td nowrap="nowrap">
		<input type="checkbox" name="log_all" <?php if ($log_all)
	echo "checked" ?> />
		<?php echo $AppUI->_('Log All'); ?>
	</td>

	<td nowrap="nowrap">
		<input type="checkbox" name="log_pdf" <?php if ($log_pdf)
	echo "checked" ?> />
		<?php echo $AppUI->_('Make PDF'); ?>
	</td>

	<td nowrap="nowrap">
		<input type="checkbox" name="log_ignore" />
		<?php echo $AppUI->_('Ignore 0 hours'); ?>
	</td>

	<td align="right" width="50%" nowrap="nowrap">
		<input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('submit'); ?>" />
	</td>
</tr>

</table>
</form>

<?php
if ($do_report) {

	if (function_exists('styleRenderBoxBottom')) {
		echo $AppUI->getTheme()->styleRenderBoxBottom();
	}
	echo '<br />';
	if (function_exists('styleRenderBoxTop')) {
		echo styleRenderBoxTop();
	}

  $q = new w2p_Database_Query;
  $q->addTable('task_log','t');
  $q->addQuery('t.*, item_id, CONCAT_WS(\' \',contact_first_name,contact_last_name) AS creator, billingcode_value, ROUND((billingcode_value * t.task_log_hours), 2) AS amount');
  $q->addJoin('helpdesk_items','ts','ts.item_id = task_log_help_desk_id');
  $q->addJoin('users','u','user_id = task_log_creator');
  $q->addJoin('contacts','','user_contact = contact_id');
  $q->addJoin('projects','','project_id = item_project_id');
  $q->addJoin('companies','c','c.company_id = item_company_id');
  $q->addJoin('billingcode','','billingcode_id = task_log_costcode');
  $q->addWhere('task_log_task = 0 AND task_log_help_desk_id > 0');

	if ($project_id)
		$q->addWhere('project_id = ' . (int)$project_id);

	if ($company_id)
		$q->addWhere('c.company_id = ' . (int)$company_id);

	if (!$log_all) {
    $q->addWhere('task_log_date >= \''.$start_date->format( FMT_DATETIME_MYSQL ).'\'');
    $q->addWhere('task_log_date <= \''.$end_date->format( FMT_DATETIME_MYSQL ).'\'');
	}

	if ($log_ignore) {
    $q->addWhere('task_log_hours > 0');
 	}

	if ($log_userfilter) {
		$q->addWhere('task_log_creator = ' . (int)$log_userfilter);
 	}

	$proj = &new CProject;
	$allowedProjects = $proj->getAllowedSQL($AppUI->user_id, 'task_project');
	if (count($allowedProjects)) {
		$q->addWhere(implode(' AND ', $allowedProjects));
	}

  $q->addOrder('task_log_date');
  $logs = $q->loadList();

	echo db_error();
?>
	<table cellspacing="1" cellpadding="4" border="0" class="tbl">
	<tr>
		<th><?php echo $AppUI->_('Date');?></th>
		<th><?php echo $AppUI->_('Created by');?></th>
		<th><?php echo $AppUI->_('Item');?></th>
		<th><?php echo $AppUI->_('Summary');?></th>
		<th><?php echo $AppUI->_('Description');?></th>
		<th><?php echo $AppUI->_('Cost Code');?></th>
		<th><?php echo $AppUI->_('Hours');?></th>
		<th><?php echo $AppUI->_('Cost');?></th>
		<th><?php echo $AppUI->_('Amount');?></th>
	</tr>
<?php
	$hours = 0.00;
	$tamount = 0.00;
	$pdfdata = array();

        foreach ($logs as $log) {
		$date = new w2p_Utilities_Date( $log['task_log_date'] );
		$hours += $log['task_log_hours'];
		$tamount += $log['amount'];

		$pdfdata[] = array(
			$date->format( $df ),
			$log['creator'],
			$log['item_id'],
			$log['task_log_name'],
			$log['task_log_description'],
			$task_log_costcodes[$log['task_log_costcode']],
			sprintf( "%.2f", $log['task_log_hours'] ),
			sprintf( "%.2f", $log['billingcode_value'] ),
			sprintf( "%.2f", $log['amount'] ),
		);
?>
	<tr>
		<td><?php echo $date->format( $df );?></td>
		<td><?php echo $log['creator'];?></td>
		<td align="center"><a href="index.php?m=helpdesk&a=view&item_id=<?php echo $log['item_id'];?>"><?php echo $log['item_id'];?></a></td>
		<td>
			<a href="index.php?m=helpdesk&a=view&tab=1&item_id=<?php echo $log['task_log_help_desk_id'];?>&task_log_id=<?php echo $log['task_log_id'];?>"><?php echo $log['task_log_name'];?></a>
		</td>
		<td><?php
// dylan_cuthbert: auto-transation system in-progress, leave these lines for time-being
            $transbrk = "\n[translation]\n";
			$descrip = str_replace( "\n", "<br />", $log['task_log_description'] );
			$tranpos = strpos( $descrip, str_replace( "\n", "<br />", $transbrk ) );
			if ( $tranpos === false) echo $descrip;
			else
			{
				$descrip = substr( $descrip, 0, $tranpos );
				$tranpos = strpos( $log['task_log_description'], $transbrk );
				$transla = substr( $log['task_log_description'], $tranpos + strlen( $transbrk ) );
				$transla = trim( str_replace( "'", '"', $transla ) );
				echo $descrip."<div style='font-weight: bold; text-align: right'><a title='$transla' class='hilite'>[".$AppUI->_("translation")."]</a></div>";
			}
// dylan_cuthbert; auto-translation end
			?></td>
		<td><?php echo $task_log_costcodes[$log['task_log_costcode']];?></td>
		<td align="right"><?php printf( "%.2f", $log['task_log_hours'] );?></td>
		<td align="right"><?php printf( "%.2f", $log['billingcode_value'] );?></td>
		<td align="right"><?php printf( "%.2f", $log['amount'] );?></td>
	</tr>
<?php
	}
	$pdfdata[] = array(
		'',
		'',
		'',
		'',
		'',
		$AppUI->_('Report Totals').':',
		sprintf( "%.2f", $hours ),
		'',
		sprintf( "%.2f", $tamount ),
	);
?>
	<tr>
		<td align="right" colspan="6"><?php echo $AppUI->_('Report Totals');?>:</td>
		<td align="right"><?php printf( "%.2f", $hours );?></td>
		<td>&nbsp;</td>
		<td align="right"><?php printf( "%.2f", $tamount );?></td>
	</tr>
	</table>
<?php
	if ($log_pdf) {
	// make the PDF file
		if ($project_id) {
			$q = new w2p_Database_Query;
			$q->addTable('projects');
			$q->addQuery('project_name');
			$q->addWhere('project_id=' . (int)$project_id);
			$pname = 'Project: ' . $q->loadResult();
		} else {
			$pname = 'All Companies and All Projects';
		}
		echo db_error();

		if ($company_id) {
			$q = new w2p_Database_Query;
			$q->addTable('companies');
			$q->addQuery('company_name');
			$q->addWhere('company_id=' . (int)$company_id);
			$cname = 'Company: ' . $q->loadResult();
		} else {
			$cname = 'All Companies and All Projects';
		}
		echo db_error();

		if ($log_userfilter) {
			$q = new w2p_Database_Query;
			$q->addTable('contacts');
			$q->addQuery('CONCAT(contact_first_name, \' \', contact_last_name)');
			$q->addJoin('users', '', 'user_contact = contact_id', 'inner');
			$q->addWhere('user_id =' . (int)$log_userfilter);
			$uname = 'User: ' . $q->loadResult();
		} else {
			$uname = 'All Users';
		}
		echo db_error();

		$font_dir = w2PgetConfig('root_dir') . '/lib/ezpdf/fonts';
		$temp_dir = w2PgetConfig('root_dir') . '/files/temp';
		$base_url = w2PgetConfig('base_url');
		require ($AppUI->getLibraryClass('ezpdf/class.ezpdf'));

		$pdf = &new Cezpdf();
		$pdf->ezSetCmMargins(1, 2, 1.5, 1.5);
		$pdf->selectFont($font_dir . '/Helvetica.afm');

		$pdf->ezText(w2PgetConfig('company_name'), 12);

		$date = new w2p_Utilities_Date();
		$pdf->ezText("\n" . $date->format($df), 8);

		$pdf->selectFont($font_dir . '/Helvetica-Bold.afm');
		$pdf->ezText("\n" . $AppUI->_('Task Log Report'), 12);

		if ($company_id) {
			$pdf->ezText($cname, 10);
		} else {
			$pdf->ezText($pname, 10);
		}
		$pdf->ezText($uname, 10);

		if ($log_all) {
			$pdf->ezText( "All Helpdesk task log entries", 9 );
		} else {
			$pdf->ezText( "Helpdesk task log entries from ".$start_date->format( $df ).' to '.$end_date->format( $df ), 9 );
		}
		$pdf->ezText( "\n\n" );

		$title = 'Helpdesk Task Logs';

	        $pdfheaders = array(
        		$AppUI->_('Date',UI_OUTPUT_JS),
        		$AppUI->_('Creator',UI_OUTPUT_JS),
		        $AppUI->_('Item',UI_OUTPUT_JS),
        		$AppUI->_('Summary',UI_OUTPUT_JS),
        		$AppUI->_('Description',UI_OUTPUT_JS),
	        	$AppUI->_('Cost Code',UI_OUTPUT_JS),
        		$AppUI->_('Hours',UI_OUTPUT_JS),
        		$AppUI->_('Cost',UI_OUTPUT_JS),
        		$AppUI->_('Amount',UI_OUTPUT_JS)
        	);

		$options = array(
			'showLines' => 1,
			'fontSize' => 7,
			'rowGap' => 1,
			'colGap' => 1,
			'xPos' => 50,
			'xOrientation' => 'right',
			'width'=>'500',
			'cols'=>array(
					0=>array('justification'=>'center','width'=>45),
					1=>array('justification'=>'left','width'=>65),
					2=>array('justification'=>'center','width'=>25),
					3=>array('justification'=>'left','width'=>95),
					4=>array('justification'=>'left','width'=>95),
					5=>array('justification'=>'center','width'=>50),
					6=>array('justification'=>'right','width'=>35),
					7=>array('justification'=>'right','width'=>35),
					8=>array('justification'=>'right','width'=>50)
        	)
		);

		$pdf->ezTable( $pdfdata, $pdfheaders, $title, $options );

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
