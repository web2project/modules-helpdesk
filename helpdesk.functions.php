<?php /* HELPDESK $Id$ */
if (!defined('W2P_BASE_DIR')) {
    die('You should not access this file directly');
}

function getAllowedUsers($companyid=0,$activeOnly=0)
{
  global $HELPDESK_CONFIG, $AppUI, $m;

    //populate user list with all users from permitted companies
  $q = new w2p_Database_Query;
  $q->addQuery('user_id, CONCAT(contact_first_name, \' \',contact_last_name) as fullname');
  $q->addTable('users');
  $q->addJoin('contacts','','user_contact = contact_id');
  $q->addWhere(getCompanyPerms('user_company', PERM_EDIT, $HELPDESK_CONFIG['the_company']) .' OR ' .getCompanyPerms('contact_company', PERM_EDIT, $HELPDESK_CONFIG['the_company'] ));

  $q->addOrder('contact_last_name, contact_first_name');
  $users =  $q->loadHashList();

    //Filter inactive users
    if ($activeOnly) {
        $perms =& $AppUI->acl();
        $cnt=0;
        $userids=array_keys($users);
            foreach ($userids as $row) {
                if ($perms->isUserPermitted($row) == false) {
                        // echo "Inactive!!!!".$row."<br>";
                 unset($users[$row]);
            }
            //$cnt++;
        }
    }

    return $users;
}
//KZHAO  8-8-2006

// eliminate non-user's companies if needed
function getAllowedCompanies($companyId=0)
{
  global $AppUI;
  $company = new CCompany();

  $allowedCompanies = $company->getAllowedRecords( $AppUI->user_id, 'company_id,company_name', 'company_name' );

  if ($companyId!=0 && $companyId!=$HELPDESK_CONFIG['the_company']) {
    $compIds=array_keys($allowedCompanies);
    foreach ($compIds as $row) {
        if($row!=$companyId)
            unset($allowedCompanies[$row]);
      }
  }

  return $allowedCompanies;
}

function getAllowedProjects($list = 0, $activeOnly = 0)
{
    global $AppUI, $HELPDESK_CONFIG;

    $project = new CProject();

    return $project->getAllowedProjects($AppUI->user_id, $activeOnly);
}
// Add a parameter for active projects-- Kang
function getAllowedProjectsForJavascript($activeonly = 0)
{
    global $HELPDESK_CONFIG, $AppUI;

    $allowedProjects = getAllowedProjects();
    $projects = array();

    foreach ($allowedProjects as $project) {
        $projects[] = "[{$project['project_company']},{$project['project_id']},'"
                . addslashes($project['project_name']) . "']";

    }

  return $projects;
}

//----------------------------------------------
//Kang--retrieve a list of tasks for helpdesk items
// Note: may need more access control here
function getAllowedTasksForJavascript($project_ids,$activeOnly=1)
{
    global $HELPDESK_CONFIG, $AppUI;
    $tasks = array();
    if (!isset($project_ids) || !is_array($project_ids) || !count($project_ids)) {
        return;
    }
    $q = new w2p_Database_Query;
    $q->addQuery('task_id, task_name, task_project');
    $q->addTable('tasks');
    $q->addWhere('task_project IN (' . implode(',',$project_ids) .') ');
    if ($activeOnly) {
        $q->addWhere('task_status=0 AND task_percent_complete!=100.00');
    }
    $q->addOrder('task_name');
    $allowedTask = $q->loadList();

    foreach ($allowedTask as $row) {
        $tasks[]="[{$row['task_project']},{$row['task_id']},'"
           . addslashes($row['task_name'])."']";
    }

    return $tasks;
}


/* Function to build a where clasuse that will restrict the list of Help Desk
 * items to only those viewable by a user. The viewable items include
 * 1. Items the user created
 * 2. Items that are assigned to the user
 * 3. Items where the user is the requestor
 * 4. Items of a company you have permissions for
 */
function getItemPerms()
{
  global $HELPDESK_CONFIG, $AppUI;

  $permarr = array();
  //pull in permitted companies
  $allowedCompanies = getAllowedCompanies();
  $allowedProjects = getAllowedProjects();
  //if there are none listed, make sure that sql returns nothing
  if (!$allowedCompanies) {
    return "0=1";
  }

  foreach ($allowedCompanies as $k=>$v) {
    $companyIds[] = $k;
  }
  $companyIds = implode(",", $companyIds);
  $permarr[] = "(item_company_id in ("
               .$companyIds
               .")  OR item_created_by="
               .$AppUI->user_id
               .") ";
  //it's assigned to the current user
  $permarr[] = "item_assigned_to=".$AppUI->user_id;
  //it's requested by a user and that user is you
  $permarr[] = " (item_requestor_type=1 AND item_requestor_id=".$AppUI->user_id.') ' ;

  if ($HELPDESK_CONFIG['use_project_perms']) {
        $projectIds = array_keys($allowedProjects);
  } else {
        foreach ($allowedProjects as $p) {
            $projectIds[] = $p['project_id'];
        }
  }
  if (count($projectIds)) {
    $projarr[] = " AND item_project_id in (0,".implode(", ", $projectIds).")";
  } else {
    $projarr[] = " AND item_project_id in (0)";
  }

  $sql = '('.implode("\n OR ", $permarr).')'.implode('',$projarr);

  return $sql;
}

// Function to build a where clause to be appended to any sql that will narrow
// down the returned data to only permitted company data
function getCompanyPerms($mod_id_field,$perm_type=NULL,$the_company=NULL)
{
    GLOBAL $AppUI, $perms, $m;

    //pull in permitted companies
    $allowedCompanies = getAllowedCompanies();
    //if there are none listed, make sure that sql returns nothing
    if (!$allowedCompanies) {
        return "0=1";
    }

    $allowedCompanies = array_keys($allowedCompanies);

    if (is_numeric($the_company)) {
        $allowedCompanies[] = $the_company;
    }

    return "($mod_id_field in (".implode(",", $allowedCompanies)."))";
}

function hditemReadable($hditem)
{
  return hditemPerm($hditem, PERM_READ);
}

function hditemEditable($hditem)
{
  return hditemPerm($hditem, PERM_EDIT);
}

function hditemPerm($hditem, $perm_type)
{
  global $HELPDESK_CONFIG, $AppUI, $m;

  $perms = & $AppUI->acl();
  $created_by = $hditem['item_created_by'];
  $company_id = isset($hditem['item_company_id'])?$hditem['item_company_id']:'';
  $assigned_to = isset($hditem['item_assigned_to'])?$hditem['item_assigned_to']:'';
  $requested_by = isset($hditem['item_requestor_id'])?$hditem['item_requestor_id']:'';

  switch ($perm_type) {
    case PERM_READ:
      $company_perm = $perms->checkModuleItem('companies', 'view', $company_id);
      break;
    case PERM_EDIT:
      // If the item is not assigned to a company, figure out if we can edit it
      if ($company_id == 0) {
        if ($HELPDESK_CONFIG['no_company_editable']) {
          $company_perm = 1;
        } else {
          $company_perm = 0;
        }
      } else {
      $company_perm = $perms->checkModuleItem('companies', 'view', $company_id);
      }
      break;
    default:
      die ("Wrong permission type was passed");
  }

  /* User is allowed if
    1. He has the company permission
    2. He is the creator
    3. He is the assignee
    4. He is the requestor
  */


  if($company_perm ||
     ($created_by == $AppUI->user_id) ||
     ($assigned_to == $AppUI->user_id) ||
     ($requested_by == $AppUI->user_id)) {
    return true;
  } else {
    return false;
  }
}

function hditemCreate()
{
  global $m, $AppUI;

  $perms = & $AppUI->acl();
  if ($perms->checkModule($m, 'add'))
        return true;

  return false;
}

function dump($var)
{
  print "<pre>";
  print_r($var);
  print "</pre>";
}

// Added by KZHAO: 8-4-2006
// convert mysql date format into PHP date format
function get_mysql_to_epoch($sqldate)
{
    list( $year, $month, $day, $hour, $minute, $second )= split( '([^0-9])', $sqldate );
    //echo $year.",".$month.",".$day;
    return date( 'U', mktime( $hour, $minute, $second, $month, $day, $year) );
}

// KZHAO: get how long ago
function get_time_ago($mysqltime)
{
        global $AppUI;
    $wrong=0;
    $timestamp=get_mysql_to_epoch($mysqltime);

        $elapsed_seconds = time() - $timestamp;
    // KZHAO  8-10-2006
    // dealing with time in the future
    if ($elapsed_seconds<0) {
        return ("N/A");
    } elseif ($elapsed_seconds < 60) { // seconds ago
        if ($elapsed_seconds) {
            $interval = $elapsed_seconds;
         } else {
            $interval = 1;
        }
        $output = "sec.";
    } elseif ($elapsed_seconds < 3600) { // minutes ago
        $interval = round($elapsed_seconds / 60);
        $output = "min.";
    } elseif ($elapsed_seconds < 86400) { // hours ago
            $interval = round($elapsed_seconds / 3600);
            $output = "hr.";
    } elseif ($elapsed_seconds < 604800) { // days ago
            $interval = round($elapsed_seconds / 86400);
            $output = "day";
    } elseif ($elapsed_seconds < 2419200) { // weeks ago
            $interval = round($elapsed_seconds / 604800);
            $output = "week";
    } elseif ($elapsed_seconds < 29030400) { // months ago
        $interval = round($elapsed_seconds / 2419200);
        $output = " month";
    } else { // years ago
        $interval = round($elapsed_seconds / 29030400);
            $output = "year";
    }

    if ($interval > 1) {
        $output .= "s";
    }
        $output = " ".$AppUI->_($output);
    $output .= " ".$AppUI->_('ago');
    $output = $interval.$output;

    return($output);
}
//KZHAO  8-10-2006
// handle the deadline
function get_due_time($mysqltime, $listView=0)
{
        global $AppUI;
    $ago=1;
    $color="000000";
    $color_soon="ff0000";// red
    $color_days="990066";//pink
    $color_weeks="cc6600";// brown
    $color_months="339900";//green
    $color_long="66ff00";//
    $timestamp=get_mysql_to_epoch($mysqltime);

        $elapsed_seconds = time() - $timestamp;
    // KZHAO  8-10-2006
    // dealing with time in the future
    if ($elapsed_seconds<0) {
        $elapsed_seconds=$timestamp-time();
        $ago=0;
    }

    if ($elapsed_seconds < 60) { // seconds ago
        $interval = $elapsed_seconds;
        $output = "sec.";
        $color=$color_soon;
    } elseif ($elapsed_seconds < 3600) { // minutes ago
        $interval = round($elapsed_seconds / 60);
        $output = "min.";
        $color=$color_soon;
    } elseif ($elapsed_seconds < 86400) { // hours ago
            $interval = round($elapsed_seconds / 3600);
            $output = "hr.";
        $color=$color_soon;
    } elseif ($elapsed_seconds < 604800) { // days ago
            $interval = round($elapsed_seconds / 86400);
            $output = "day";
        if($interval<=3)
            $color=$color_soon; //red
        else
            $color=$color_days;//orange
    } elseif ($elapsed_seconds < 2419200) { // weeks ago
            $interval = round($elapsed_seconds / 604800);
            $output = "week";
         $color=$color_weeks;
    } elseif ($elapsed_seconds < 29030400) { // months ago
        $interval = round($elapsed_seconds / 2419200);
        $output = " month";
        $color=$color_months;
    } else { // years ago
        $interval = round($elapsed_seconds / 29030400);
            $output = "year";
         $color=$color_long;
    }

    if ($interval > 1) {
        $output .= "s";
    }

        $output = " ".$AppUI->_($output);
    //Only display time for list view
    if ($listView) {
        if($ago)
            $output =$interval." ".$output." ".$AppUI->_('ago');
        else
            $output = "<font color=#".$color.">".$interval.$output."</font>";

    } else {
        if ($ago) {
                $output .= " ".$AppUI->_('ago');
                $output = "Deadline is ".$interval.$output;
        } else {
            $output = "<font color=#".$color.">Due in <strong>".$interval.$output."</strong></font>";
        //$output ="Due in "
        }
    }

        return($output);
}

function linkLinks($data)
{
    $data = strip_tags($data);
    $search_email = '/([\w-]+([.][\w_-]+){0,4}[@][\w_-]+([.][\w-]+){1,3})/';
    $search_http = '/(http(s)?:\/\/[^\s]+)/i';
    $data = preg_replace($search_email,"<a href=\"mailto:$1\">$1</a>",$data);
    $data = preg_replace($search_http,"<a href=\"$1\" target=\"_blank\">$1</a>",$data);

    return $data;
}
