-- Upgrade script for helpdesk module
-- Use this script only if you have already installed helpdesk module into dotproject.
-- Kang 12/19/2006
-- The LinuxBox Corp. www.linuxbox.com
-- Ann Arbor, MI

ALTER TABLE helpdesk_items ADD item_updated datetime DEFAULT NULL;
ALTER TABLE helpdesk_items ADD item_deadline datetime DEFAULT NULL;

