<?php /* HELPDESK $Id: selector.php,v 1.14 2006-12-14 12:55 Kang Exp $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

function selPermWhere( $table, $idfld ) {
	global $AppUI;

	// get any companies denied from viewing
	$sql = "SELECT $idfld"
		."\nFROM $table, permissions"
		."\nWHERE permission_user = $AppUI->user_id"
		."\n	AND permission_grant_on = '$table'"
		."\n	AND permission_item = $idfld"
		."\n	AND permission_value = 0";

	$deny = db_loadColumn( $sql );
	echo db_error();

	return "permission_user = $AppUI->user_id"
		."\nAND permission_value <> 0"
		."\nAND ("
		."\n	(permission_grant_on = 'all')"
		."\n	OR (permission_grant_on = '$table' and permission_item = -1)"
		."\n	OR (permission_grant_on = '$table' and permission_item = $idfld)"
		."\n	)"
		. (count($deny) > 0 ? "\nAND $idfld NOT IN (" . implode( ',', $deny ) . ')' : '');
}

$debug = false;
$callback = w2PgetParam( $_GET, 'callback', 0 );
$table = w2PgetParam( $_GET, 'table', 0 );
$comp=w2PgetParam($_GET, 'comp', 0);

$ok = $callback & $table;

$title = "Generic Selector";
$select = '';
$from = $table;
$where = '';
$order = '';

switch ($table) {
case 'companies':
	$title = 'Company';
	$select = 'company_id,company_name';
	$order = 'company_name';
	$table .= ", permissions";
	$where = selPermWhere( 'companies', 'company_id' );
	break;
case 'departments':
// known issue: does not filter out denied companies
	$title = 'Department';
	$company_id = w2PgetParam( $_GET, 'company_id', 0 );
	$where = "dept_company = company_id ";
	$where .= "\nAND ".selPermWhere( 'departments', 'dept_id' );

	$table .= ", companies, permissions";
	$hide_company = w2PgetParam( $_GET, 'hide_company', 0 );
	if ( $hide_company == 1 ){
		$select = "dept_id, dept_name";
	}else{
		$select = "dept_id,CONCAT_WS(': ',company_name,dept_name) AS dept_name";
	}
	if ($company_id) {
		$where .= "\nAND dept_company = $company_id";
		$order = 'dept_name';
	} else {
		$order = 'company_name,dept_name';
	}
	break;
case 'forums':
	$title = 'Forum';
	$select = 'forum_id,forum_name';
	$order = 'forum_name';
	break;
case 'projects':
	$project_company = w2PgetParam( $_GET, 'project_company', 0 );

	$title = 'Project';
	$select = 'project_id,project_name';
	$order = 'project_name';
	$where = selPermWhere( 'projects', 'project_id' );
	$where .= $project_company ? "\nAND project_company = $project_company" : '';
	$table .= ", permissions";
	break;
case 'tasks':
	$task_project = w2PgetParam( $_GET, 'task_project', 0 );

	$title = 'Task';
	$select = 'task_id,task_name';
	$order = 'task_name';
	$where = $task_project ? "task_project = $task_project" : '';
	break;
case 'users':
	$title = 'User';
	//by KZHAO
	$templist = getAllowedUsers($comp, 1);
	foreach($templist as $key=>$value){
		$list[$key]=$value;
	}
	break;
case 'contacts':
	$title = 'Contacts';
	$select = "contact_id,CONCAT_WS(' ',contact_first_name,contact_last_name)";
	$order = "CONCAT_WS(' ',contact_first_name,contact_last_name)";
	break;
default:
	$ok = false;
	break;
}

if (!$ok) {
	echo "Incorrect parameters passed\n";
	if ($debug) {
		echo "<br />callback = $callback \n";
		echo "<br />table = $table \n";
		echo "<br />ok = $ok \n";
	}
} else {
	if(!isset($list)){

    $q = new w2p_Database_Query; 
    $q->addQuery($select);
    $q->addTable($table);
    if ($where) {
      $q->addWhere($where);
    }
    $q->addOrder($order);
 		$list = arrayMerge( array( 0=>''), $q->loadHashList());

	}
	echo db_error();
?>
<script language="javascript">
	function setClose(key, val){
		window.opener.<?php echo $callback;?>(key,val);
		window.close();
	}
	function setHeight(){
		window.opener.<?php echo $callback;?>(key,val);
		window.close();
	}
</script>

<table cellspacing="0" cellpadding="3" border="0">
<tr>
	<td>
<?php
	if (count( $list ) > 1) {
		echo $AppUI->_( 'Select' ).' '.$AppUI->_( $title ).':<br />';
		foreach ($list as $key => $val) {
			echo "<a href=\"javascript:setClose('$key','".addslashes($val)."');\">$val</a><br>\n";
		}
?>
	</td>
</tr>
<tr>
	<td align="right">
		<input type="button" class="button" value="<?php echo $AppUI->_( 'cancel' );?>" onclick="window.close()" />
<?php
	} else {
		echo $AppUI->_( "no$table" );
	}
?>
	</td>
</tr>
</table>
<?php
}