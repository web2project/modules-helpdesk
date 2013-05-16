<?php /* HELPDESK $Id: setup.php,v 1.47 2005/10/07 16:09:10 pedroix Exp $ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly');
}

/* Help Desk module definitions */
$config = array();
$config['mod_name'] = 'HelpDesk';
$config['mod_version'] = '0.8';
$config['mod_directory'] = 'helpdesk';
$config['mod_setup_class'] = 'CSetupHelpDesk';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Help Desk';
$config['mod_ui_icon'] = 'helpdesk.png';
$config['mod_description'] = 'Help Desk is a bug, feature request, complaint and suggestion tracking centre';
$config['mod_config'] = true;
$config['mod_main_class'] = 'CHelpDesk'; // the name of the PHP class used by the module
//This will allow permissions to be applied to this module based on the following database criteria
$config['permissions_item_table'] = 'helpdesk_items';
$config['permissions_item_label'] = 'item_title';
$config['permissions_item_field'] = 'item_id';

if (@$a == 'setup') {
print w2PshowModuleConfig( $config );
}

class CSetupHelpDesk {

    public function install() {
        $success = true;
        $bulk_sql[] = "
            CREATE TABLE helpdesk_items (
            `item_id` int(11) unsigned NOT NULL auto_increment,
            `item_title` varchar(64) NOT NULL default '',
            `item_summary` text,
            `item_calltype` int(3) unsigned NOT NULL default '0',
            `item_source` int(3) unsigned NOT NULL default '0',
            `item_service` varchar(48) NOT NULL default '',
            `item_application` varchar(48) NOT NULL default '',
            `item_priority` int(3) unsigned NOT NULL default '0',
            `item_severity` int(3) unsigned NOT NULL default '0',
            `item_status` int(3) unsigned NOT NULL default '0',
            `item_assigned_to` int(11) NOT NULL default '0',
            `item_created_by` int(11) NOT NULL default '0',
            `item_notify` int(1) DEFAULT '1' NOT NULL ,
            `item_requestor` varchar(48) NOT NULL default '',
            `item_requestor_id` int(11) NOT NULL default '0',
            `item_requestor_email` varchar(128) NOT NULL default '',
            `item_requestor_phone` varchar(30) NOT NULL default '',
            `item_requestor_type` tinyint NOT NULL default '0',
            `item_created` datetime default NULL,
            `item_modified` datetime default NULL,
            `item_parent` int(10) unsigned NOT NULL default '0',
            `item_project_id` int(11) NOT NULL default '0',
            `item_company_id` int(11) NOT NULL default '0',
            `item_task_id` int(11) default '0',
            `item_updated` datetime default NULL,
            `item_deadline` datetime default NULL,
            PRIMARY KEY (item_id)
            ) ENGINE=MyISAM";

        $bulk_sql[] = "
            CREATE TABLE `helpdesk_item_status` (
            `status_id` int NOT NULL AUTO_INCREMENT,
            `status_item_id` int NOT NULL,
            `status_code` tinyint NOT NULL,
            `status_date` timestamp NOT NULL,
            `status_modified_by` int NOT NULL,
            `status_comment` text,
            PRIMARY KEY (`status_id`)
            ) ENGINE=MyISAM";

        $bulk_sql[] = "
            CREATE TABLE helpdesk_item_watchers (
            `item_id` int(11) NOT NULL default '0',
            `user_id` int(11) NOT NULL default '0',
            `notify` char(1) NOT NULL default ''
            ) ENGINE=MyISAM ";

        $bulk_sql[] = "
            ALTER TABLE `files` ADD COLUMN `file_helpdesk_item` int(11) DEFAULT 0";

        $bulk_sql[] = "
            ALTER TABLE `task_log` ADD `task_log_help_desk_id` int(11) NOT NULL default '0' AFTER `task_log_task`";

        foreach ($bulk_sql as $s) {
            try {
                db_exec($s);
            } catch (Exception $exc) {
                //do nothing
            }
        }

        $sk = new CSysKey( 'HelpDeskList', 'Enter values for list', '0', "\n", '|' );
        $sk->store();

        $sv = new CSysVal( $sk->syskey_id, 'HelpDeskPriority', "0|Not Specified\n1|Low\n2|Medium\n3|High" );
        $sv->store();

        $sv = new CSysVal( $sk->syskey_id, 'HelpDeskSeverity', "0|Not Specified\n1|No Impact\n2|Low\n3|Medium\n4|High\n5|Critical" );
        $sv->store();

        $sv = new CSysVal( $sk->syskey_id, 'HelpDeskCallType', "0|Not Specified\n1|Incident\n2|Feature Request\n3|Account Request\n4|Complaint\n5|Bug\n6|User Support" );
        $sv->store();

        $sv = new CSysVal( $sk->syskey_id, 'HelpDeskSource', "0|Not Specified\n1|E-Mail\n2|Phone\n3|Fax\n4|In Person\n5|E-Lodged\n6|WWW" );
        $sv->store();

        $sv = new CSysVal( $sk->syskey_id, 'HelpDeskService', "0|Not Applicable\n1|Linux\n2|Unix\n3|Solaris\n4|Windows 2000\n5|Windows XP\n999|Other" );
        $sv->store();

        $sv = new CSysVal( $sk->syskey_id, 'HelpDeskApplic', "0|Not Applicable" );
        $sv->store();

        $sv = new CSysVal( $sk->syskey_id, 'HelpDeskStatus', "0|Unassigned\n1|Open\n2|Closed\n3|On Hold\n4|Testing" );
        $sv->store();

        $sv = new CSysVal( $sk->syskey_id, 'HelpDeskAuditTrail', "0|Created\n1|Title\n2|Requestor Name\n3|Requestor E-mail\n4|Requestor Phone\n5|Assigned To\n6|Notify by e-mail\n7|Company\n8|Project\n9|Call Type\n10|Call Source\n11|Status\n12|Priority\n13|Severity\n14|Service\n15|Application\n16|Summary\n17|Deadline\n18|Deleted" );
        $sv->store();

        global $AppUI;
        $perms = $AppUI->acl();
        return $perms->registerModule('Help Desk', 'helpdesk');
    }

    public function remove() {
        $success = true;

        $bulk_sql[] = "DROP TABLE helpdesk_items";
        $bulk_sql[] = "DROP TABLE helpdesk_item_status";
        $bulk_sql[] = "DROP TABLE helpdesk_item_watchers";
        $bulk_sql[] = "ALTER TABLE `task_log` DROP COLUMN `task_log_help_desk_id`";
        $bulk_sql[] = "ALTER TABLE `files` DROP COLUMN `file_helpdesk_item`";

        foreach ($bulk_sql as $s) {
            try {
                db_exec($s);
            } catch (Exception $exc) {
                //do nothing
            }
        }

        $q = new w2p_Database_Query;
        $q->addQuery('syskey_id');
        $q->addTable('syskeys');
        $q->addWhere('syskey_name = \'HelpDeskList\'');
        $id = $q->loadResult();

        unset($bulk_sql);

        $bulk_sql[] = "DELETE FROM syskeys WHERE syskey_id = $id";
        $bulk_sql[] = "DELETE FROM sysvals WHERE sysval_key_id = $id";

        foreach ($bulk_sql as $s) {
            try {
                db_exec($s);
            } catch (Exception $exc) {
                //do nothing
            }
        }

        $q = new w2p_Database_Query;
        $q->setDelete('modules');
        $q->addWhere("mod_directory = 'helpdesk'");
        $q->exec();

        return $success;
    }

    public function upgrade($old_version) {
        $success = true;

        switch ($old_version) {
            case 0.2:
                // Version 0.3 features new permissions
                break;
            case 0.3:
                // Version 0.31 includes new watchers functionality
                $sql = "
                    CREATE TABLE helpdesk_item_watchers (
                    `item_id` int(11) NOT NULL default '0',
                    `user_id` int(11) NOT NULL default '0',
                    `notify` char(1) NOT NULL default ''
                    ) TYPE=MyISAM";
                db_exec($sql);
                if (db_error()) {
                    return false;
                }
            case 0.31:
                $sql = "ALTER TABLE files ADD COLUMN file_helpdesk_item int(11) DEFAULT 0;";
                db_exec($sql);
                $sql = "
                    ALTER TABLE `helpdesk_items`
                    CHANGE `item_os` `item_service` varchar(48) NOT NULL default '',
                    ADD `item_updated` datetime default NULL,
                    ADD `item_deadline` datetime default NULL,
                    ADD `item_task_id` int(11) default '0'";

                db_exec($sql);
                if (db_error()) {
                    return false;
                }

                $sql = "SELECT `item_id` FROM helpdesk_items";
                $rows = db_loadList( $sql );
                $sql = '';
                foreach ($rows as $row) {
                    $sql = "SELECT MAX(status_date) status_date FROM helpdesk_item_status WHERE status_item_id =".$row['item_id'];
                    $sdrow = db_loadList( $sql );

                    $sql = '';
                    $sql = "UPDATE `helpdesk_items`
                    SET `item_updated`='".$sdrow[0]['status_date']."'
                    WHERE `item_id`=".$row['item_id'];
                    db_exec($sql);
                    if (db_error()) {
                        return false;
                    }
                }
                break;
            case 0.4:
                $sql = "ALTER TABLE `helpdesk_items`
                    CHANGE `item_os` `item_service` varchar(48) NOT NULL default '',
                    ADD `item_deadline` datetime default NULL,
                    ADD `item_task_id` int(11) default '0'";
                //???: Should this query actually get applied? - caseydk
                break;
            case 0.6:
                $sql = "ALTER TABLE `helpdesk_items`
                CHANGE `item_os` `item_service` varchar(48) NOT NULL default '',
                ADD `item_task_id` int(11) default '0'";
                db_exec($sql);
                if (db_error()) {
                    return false;
                }
                break;
            case 0.8:
                $sql = "UPDATE `modules` SET `mod_main_class` = 'CHelpDesk'
                    WHERE `mod_main_class` = 'CHelpDeskItem";
                db_exec($sql);
                if (db_error()) {
                    return false;
                }
                break;
            default:
                $success = 0;
        }

        global $AppUI;
        $perms = $AppUI->acl();
        return $perms->registerModule('Help Desk', 'helpdesk');
    }

    public function configure() {
        global $AppUI;

        $AppUI->redirect("m=helpdesk&a=configure");

        return true;
    }
}