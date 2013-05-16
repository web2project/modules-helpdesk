<?php /* HELPDESK $Id: index.php 240 2011-04-02 17:52:06Z eureka2 $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

// check permissions for this module
$canReadModule = canView( $m );
if (!$canReadModule) {
	$AppUI->redirect( "m=public&a=access_denied" );
}
$AppUI->savePlace();

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'HelpDeskIdxTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'HelpDeskIdxTab' ) !== NULL ? $AppUI->getState( 'HelpDeskIdxTab' ) : 0;

// Setup the title block
$titleBlock = new CTitleBlock( 'Help Desk', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );

if (hditemCreate()) {
  $titleBlock->addCell(
    '<input type="submit" class="button" value="'.$AppUI->_('new item').'" />', '',
    '<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
  );
}

$titleBlock->addCrumb( "?m=helpdesk", 'home' );
$titleBlock->addCrumb( "?m=helpdesk&a=list", 'list' );
$titleBlock->addCrumb( "?m=helpdesk&a=reports", 'reports' );

$titleBlock->show();

$item_perms = getItemPerms();

$q = new w2p_Database_Query; 
$q->addQuery('COUNT(item_id)'); 
$q->addTable('helpdesk_items');
$q->addWhere($item_perms);
$numtotal = $q->loadResult ();

/*
 * Unassigned = 0
 * Open = 1
 * Closed = 2
 * On hold = 3
 * ....
 */

$item_perms = getItemPerms();
$q = new w2p_Database_Query; 
$q->addQuery('COUNT(DISTINCT(item_id))'); 
$q->addTable('helpdesk_items');
$q->addWhere('item_assigned_to=' . $AppUI->user_id . ' AND (item_status != 2)');
$nummine = $q->loadResult ();

$q = new w2p_Database_Query; 
$q->addQuery('COUNT(DISTINCT(item_id))'); 
$q->addTable('helpdesk_items');
$q->addJoin('helpdesk_item_status','his','helpdesk_items.item_id = his.status_item_id');
$q->addWhere('item_status = 1 AND status_code = 0 ');
$q->addWhere($item_perms . ' AND (TO_DAYS(NOW()) - TO_DAYS(status_date) = 0)');
$numopened = $q->loadResult ();

$q = new w2p_Database_Query; 
$q->addQuery('COUNT(DISTINCT(item_id))'); 
$q->addTable('helpdesk_items');
$q->addJoin('helpdesk_item_status','his','helpdesk_items.item_id = his.status_item_id');
$q->addWhere('item_status = 2 AND status_code = 11 ');
$q->addWhere($item_perms . ' AND (TO_DAYS(NOW()) - TO_DAYS(status_date) = 0)');
$numclosed = $q->loadResult();

?>
<table cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
	<td width="80%" valign="top">
  <?php
  // Tabbed information boxes
  $tabBox = new CTabBox( '?m=helpdesk', W2P_BASE_DIR . '/modules/helpdesk/', $tab );
  $tabBox->add( 'vw_idx_stats', $AppUI->_('Help Desk Items')." ($numtotal)" );
  $tabBox->add( 'vw_idx_my', $AppUI->_('My Open')." ($nummine)" );
  $tabBox->add( 'vw_idx_new', $AppUI->_('Opened Today')." ($numopened)" );
  $tabBox->add( 'vw_idx_closed', $AppUI->_('Closed Today')." ($numclosed)" );
  $tabBox->add( 'vw_idx_watched', "Watched Tickets" );
  $tabBox->show();
  ?>
	</td>
</tr>
</table>