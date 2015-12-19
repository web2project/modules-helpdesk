<?php /* PROJECTS $Id: reports.php,v 1.1 2005/11/10 21:59:02 pedroix Exp $ */
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

$project_id = (int) w2PgetParam( $_REQUEST, "project_id", 0 );
$report_type = w2PgetParam( $_REQUEST, "report_type", '' );

// check permissions for this record
$perms =& $AppUI->acl();

$canRead = $perms->checkModule( $m, 'view' );
if (!$canRead) {
    $AppUI->redirect(ACCESS_DENIED);
}

$can_view_reports = $HELPDESK_CONFIG['minimum_report_level']>=$AppUI->user_type;
if (!$can_view_reports) {
    $AppUI->redirect(ACCESS_DENIED);
}

$obj = new CProject();
$q = new w2p_Database_Query;
$q->addQuery('pr.project_id, pr.project_status, pr.project_name, pr.project_description, pr.project_short_name');
$q->addTable('projects','pr');
$q->addGroup('pr.project_id');
$q->addOrder('pr.project_short_name');

$obj->setAllowedSQL($AppUI->user_id, $q);

$project_list=array("0"=> $AppUI->_("All", UI_OUTPUT_RAW) );
$ptrc = $q->exec();
$nums=db_num_rows($ptrc);
echo db_error();
for ($x=0; $x < $nums; $x++) {
        $row = db_fetch_assoc( $ptrc );
        if ($row["project_id"] == $project_id)
            $display_project_name='('.$row["project_short_name"].') '.$row["project_name"];
        $project_list[$row["project_id"]] = '('.$row["project_short_name"].') '.$row["project_name"];
}
$q->clear();

if (! $suppressHeaders) {
?>
<script language="javascript">

function changeIt(obj)
{
        var f=document.changeMe;
        f.submit();
}
</script>

<?php
}
// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

$reports = $AppUI->readFiles( w2PgetConfig( 'root_dir' )."/modules/$m/reports", "\.php$" );

// setup the title block
if (! $suppressHeaders) {
    $titleBlock = new w2p_Theme_TitleBlock( 'Helpdesk Reports', 'applet3-48.png', $m, "$m.$a" );
    $titleBlock->addCrumb( "?m=helpdesk", "home" );
    $titleBlock->addCrumb( "?m=helpdesk&a=list", "list" );
    if ($report_type) {
        $titleBlock->addCrumb( "?m=helpdesk&a=reports&project_id=$project_id", "reports" );
    }
    $titleBlock->show();
}

$report_type_var = w2PgetParam($_GET, 'report_type', '');
if (!empty($report_type_var))
    $report_type_var = '&report_type=' . $report_type;

if (! $suppressHeaders) {
if (!isset($display_company_name)) {
    if (!isset($display_project_name)) {
        $display_project_name = "None";
    } else {
        echo $AppUI->_('Selected Project') . ": <b>".$display_project_name."</b>";
        $display_company_name = "None";
    }
} else {
    echo $AppUI->_('Selected Company') . ": <b>".$display_company_name."</b>";
}
?>
<form name="changeMe" action="./index.php?m=helpdesk&a=reports<?php echo $report_type_var; ?>" method="post">
<table width="100%" cellspacing="0" cellpadding="4" border="0" class="std">
<tr>
<td><?php echo $AppUI->_('Projects') . ':';?></td>
<td><?php echo arraySelect( $project_list, 'project_id', 'size="1" class="text" onchange="changeIt(this);"', $project_id, false );?></td>
</tr>
<tr>
    <td colspan="2">
<?php
}
if ($report_type) {
    $report_type = $AppUI->checkFileName( $report_type );
    $report_type = str_replace( ' ', '_', $report_type );
    require "$baseDir/modules/$m/reports/$report_type.php";
} else {
    echo "<table>";
    echo "<tr><td><h2>" . $AppUI->_( 'Reports Available' ) . "</h2></td></tr>";
    foreach ($reports as $v) {
        $type = str_replace( ".php", "", $v );
        $desc_file = str_replace( ".php", ".{$AppUI->user_locale}.txt", $v );
        $desc = @file( "$baseDir/modules/$m/reports/$desc_file");

        echo "\n<tr>";
        echo "\n	<td><a href=\"index.php?m=$m&a=reports&project_id=$project_id&report_type=$type";
        if (isset($desc[2]))
            echo "&" . $desc[2];
        echo "\">";
        echo @$desc[0] ? $desc[0] : $v;
        echo "</a>";
        echo "\n</td>";
        echo "\n<td>" . (@$desc[1] ? "- $desc[1]" : '') . "</td>";
        echo "\n</tr>";
    }
    echo "</table>";
}
?>
        </td>
    </tr>
</table>
</form>
