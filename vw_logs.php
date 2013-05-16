<?php /* HELPDESK $Id$ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

global $AppUI, $format, $perms, $m;
$item_id = (int) w2PgetParam( $_GET, 'item_id', 0 );

// Lets check cost codes
$q = new w2p_Database_Query;
$q->addTable('billingcode');
$q->addWhere('billingcode_status=0');
$q->addWhere("company_id='$proj->project_company'"." OR company_id='0'");
$q->addOrder('billingcode_name');

$task_log_costcodes[0]=$AppUI->_('None');
$ptrc = $q->exec();
echo db_error();
$nums = 0;
if ($ptrc)
	$nums=db_num_rows($ptrc);
for ($x=0; $x < $nums; $x++) {
        $row = db_fetch_assoc( $ptrc );
        $task_log_costcodes[$row["billingcode_id"]] = $row["billingcode_name"];
}

?>
<script language="JavaScript">
function delIt2(id) {
	if (confirm( "<?php echo $AppUI->_('doDelete', UI_OUTPUT_JS).' '.$AppUI->_('Task Log', UI_OUTPUT_JS).'?';?>" )) {
		document.frmDelete2.task_log_id.value = id;
		document.frmDelete2.submit();
	}
}
</script>

<table border="0" cellpadding="2" cellspacing="1" width="100%" class="tbl">
<form name="frmDelete2" action=<?php echo '"./index.php?m=helpdesk&a=view&tab=0&item_id=' . $item_id . '"'; ?> method="post">
	<input type="hidden" name="dosql" value="do_dellog" />
	<input type="hidden" name="del" value="1" />
	<input type="hidden" name="task_log_id" value="0" />
	<input type="hidden" name="task_log_help_desk_id" value=<?php echo '"' . $item_id . '"'; ?> />
</form>

<tr>
	<th></th>
	<th><?php echo $AppUI->_('Date');?></th>
	<th width="100"><?php echo $AppUI->_('Summary');?></th>
	<th width="100"><?php echo $AppUI->_('User');?></th>
	<th width="100"><?php echo $AppUI->_('Hours');?></th>
	<th width="100"><?php echo $AppUI->_('Cost Code');?></th>
	<th width="100%"><?php echo $AppUI->_('Comments');?></th>
	<th></th>
</tr>

<?php

$q = new w2p_Database_Query; 
$q->addQuery('task_log.*, user_username');
$q->addTable('task_log');
$q->addJoin('users','','user_id = task_log_creator');
$q->addWhere('task_log_help_desk_id = '. $item_id);
$q->addOrder('task_log_date');
$logs = $q->loadList();

$s = '';
$hrs = 0;


$q = new w2p_Database_Query; 
$q->addQuery('item_company_id,item_created_by');
$q->addTable('helpdesk_items');
$q->addWhere('item_id = '.$item_id);
$hditem = $q->loadHash();

  $canEdit = hditemEditable($hditem);
  $canEdit = $perms->checkModule($m, 'edit') && hditemEditable($hditem);  

foreach ($logs as $row) {
	$task_log_date = intval( $row['task_log_date'] ) ? new w2p_Utilities_Date( $row['task_log_date'] ) : null;

	$s .= '<tr bgcolor="white" valign="top">';
	$s .= "\n\t<td>";
	if ($canEdit) {
		$s .= "\n\t\t<a href=\"?m=helpdesk&a=view&item_id=$item_id&tab=1&task_log_id=".@$row['task_log_id']."\">"
			. "\n\t\t\t". w2PshowImage( 'stock_edit-16.png', 16, 16, '' )
			. "\n\t\t</a>";
	}
	$s .= "\n\t</td>";
 	$s .= '<td nowrap="nowrap">'.($task_log_date ? $task_log_date->format( $format ) : '-').'</td>';
	$s .= '<td width="30%">'.@$row["task_log_name"].'</td>';
	$s .= '<td width="100">'.$row["user_username"].'</td>';
	$s .= '<td width="100" align="right">'.sprintf( "%.2f", $row["task_log_hours"] ) . '</td>';
	$s .= '<td width="100">'.$task_log_costcodes[$row["task_log_costcode"]].'</td>';
	$s .= '<td>';

// dylan_cuthbert: auto-transation system in-progress, leave these lines
	$transbrk = "\n[translation]\n";
	$descrip = str_replace( "\n", "<br />", $row['task_log_description'] );
	$tranpos = strpos( $descrip, str_replace( "\n", "<br />", $transbrk ) );
	if ( $tranpos === false) $s .= $descrip;
	else
	{
		$descrip = substr( $descrip, 0, $tranpos );
		$tranpos = strpos( $row['task_log_description'], $transbrk );
		$transla = substr( $row['task_log_description'], $tranpos + strlen( $transbrk ) );
		$transla = trim( str_replace( "'", '"', $transla ) );
		$s .= $descrip."<div style='font-weight: bold; text-align: right'><a title='$transla' class='hilite'>[".$AppUI->_("translation")."]</a></div>";
	}
// end auto-translation code

	$s .= '</td><td>';
			
	if ($canEdit) {
		$s .= '<a href="javascript:delIt2(' . $row['task_log_id'] . ');" title="' . $AppUI->_('delete log') . '">' . w2PshowImage('icons/stock_delete-16.png', 16, 16, '') . '</a>';
	}
	$s .= '</td></tr>'; 
	$hrs += (float)$row["task_log_hours"];
}
$s .= '<tr bgcolor="white" valign="top">';
$s .= '<td colspan="4" align="right">' . $AppUI->_('Total Hours') . ' =</td>';
$s .= '<td align="right">' . sprintf( "%.2f", $hrs ) . '</td>';
$s .= '<td align="right" colspan="3"><form action="?m=helpdesk&a=view&tab=1&item_id=' . $item_id . '" method="post">';
if ($canEdit) {
	$s .= '<input type="submit" class="button" value="' . $AppUI->_('new log') . '">';
} 
$s .= '</form></td></tr>';
echo $s;
?>
</table>