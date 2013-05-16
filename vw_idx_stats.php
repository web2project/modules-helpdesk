<?php /* HELPDESK $Id: vw_idx_stats.php 340 2007-02-19 15:57:59Z kang $*/
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

global $m, $ict, $ist;

$stats = array();

$item_perms = getItemPerms();

foreach ($ict as $k => $v) {
  $q = new w2p_Database_Query; 
  $q->addQuery('item_status, count(item_id)');
  $q->addTable('helpdesk_items');
  $q->addWhere('item_calltype=' . '\'' . $k . '\' ');
  $q->addGroup('item_status');
  $stats[$k] = $q->loadHashList();
}

?>
<table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">
<tr>
	<th colspan="2"><?php echo $AppUI->_('Type')?></th>
<?php
	$s = '';
	foreach ($ist as $k => $v) {
		$s .= "<th width=\"12%\"><a href=\"?m=helpdesk&a=list&item_calltype=-1&item_status=$k\" class=\"hdr\">"
        . $AppUI->_($v)
        . "</a></th>";
	}
	echo $s;

	$s = '';
	foreach ($ict as $kct => $vct) {
		$s .= '<tr>';
		$s .= '<td width="15">'
        . w2PshowImage ('ct'.$kct.'.png', 15, 17, $vct,'',$m)
        . '</td>';
		$s .= "<td nowrap><a href=\"?m=helpdesk&a=list&item_calltype=$kct&item_status=-1\">"
        . $AppUI->_($vct)
        . "</a></td>";

		foreach ($ist as $kst => $vst) {
			$s .= "<td align=\"center\"><a href=\"?m=helpdesk&a=list&item_calltype={$kct}&item_status=$kst\">"
          . @$stats[$kct][$kst]
          . "</a></td>";
		}

		$s .= '</tr>';
	}
	echo $s;
?>
</table>