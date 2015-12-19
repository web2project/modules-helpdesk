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

class CSetupHelpDesk extends w2p_System_Setup
{
    public function install()
    {
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

        $q = $this->_getQuery();
        $i = 0;
        $priorities = array('Not Specified', 'Low', 'Normal', 'High');
        foreach ($priorities as $priority) {
            $q->clear();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1);
            $q->addInsert('sysval_title', 'HelpDeskPriority');
            $q->addInsert('sysval_value', $priority);
            $q->addInsert('sysval_value_id', $i);
            $q->exec();
            $i++;
        }

        $i = 0;
        $severities = array('Not Specified', 'No Impact', 'Low', 'Medium', 'High', 'Critical');
        foreach ($severities as $severity) {
            $q->clear();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1);
            $q->addInsert('sysval_title', 'HelpDeskSeverity');
            $q->addInsert('sysval_value', $severity);
            $q->addInsert('sysval_value_id', $i);
            $q->exec();
            $i++;
        }

        $i = 0;
        $types = array('Not Specified', 'Incident', 'Feature Request', 'Account Request', 'Complaint', 'Bug', 'User Support');
        foreach ($types as $type) {
            $q->clear();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1);
            $q->addInsert('sysval_title', 'HelpDeskCallType');
            $q->addInsert('sysval_value', $type);
            $q->addInsert('sysval_value_id', $i);
            $q->exec();
            $i++;
        }

        $i = 0;
        $sources = array('Not Specified', 'E-Mail', 'Phone', 'Fax', 'In Person', 'E-Lodged', 'WWW');
        foreach ($sources as $source) {
            $q->clear();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1);
            $q->addInsert('sysval_title', 'HelpDeskSource');
            $q->addInsert('sysval_value', $source);
            $q->addInsert('sysval_value_id', $i);
            $q->exec();
            $i++;
        }

        $i = 0;
        $services = array('Not Specified', 'Linux', 'Unix', 'Solaris', 'Windows 2000', 'Windows XP', 'Other');
        foreach ($services as $service) {
            $q->clear();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1);
            $q->addInsert('sysval_title', 'HelpDeskService');
            $q->addInsert('sysval_value', $service);
            $q->addInsert('sysval_value_id', $i);
            $q->exec();
            $i++;
        }

        $i = 0;
        $statuses = array('Unassigned', 'Open', 'Closed', 'On Hold', 'Testing');
        foreach ($statuses as $status) {
            $q->clear();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1);
            $q->addInsert('sysval_title', 'HelpDeskStatus');
            $q->addInsert('sysval_value', $status);
            $q->addInsert('sysval_value_id', $i);
            $q->exec();
            $i++;
        }

        $i = 0;
        $audittrails = array('Created', 'Title', 'Requestor Name', 'Requestor E-mail', 'Requestor Phone', 'Assigned To',
                            'Notify by e-mail', 'Company', 'Project', 'Call Type', 'Call Source', 'Status', 'Priority',
                            'Severity', 'Service', 'Application', 'Summary', 'Deadline', 'Deleted');
        foreach ($audittrails as $audittrail) {
            $q->clear();
            $q->addTable('sysvals');
            $q->addInsert('sysval_key_id', 1);
            $q->addInsert('sysval_title', 'HelpDeskAuditTrail');
            $q->addInsert('sysval_value', $audittrail);
            $q->addInsert('sysval_value_id', $i);
            $q->exec();
            $i++;
        }

        global $AppUI;
        $perms = $AppUI->acl();

        return $perms->registerModule('Help Desk', 'helpdesk');
    }

    public function remove()
    {
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
        $id = (int) $q->loadResult();

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

    public function upgrade($old_version)
    {
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

    public function configure()
    {
        global $AppUI;

        $AppUI->redirect("m=helpdesk&a=configure");

        return true;
    }
}
