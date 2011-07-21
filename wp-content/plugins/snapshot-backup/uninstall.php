<?php
// Snapshot Backup uninstall script
// deletes all database options when plugin is removed
// @since 1.6
// 
// if uninstall is not called from WordPress then exit
if (!defined('WP_UNINSTALL_PLUGIN')) exit();

// delete all options
    delete_option ('snapshot_ftp_host');
    delete_option ('snapshot_ftp_user');
    delete_option ('snapshot_ftp_pass');
    delete_option ('snapshot_ftp_subdir');
	delete_option ('snapshot_ftp_prefix');
	delete_option ('snapshot_add_dir1');
	delete_option ('snapshot_latest');
	delete_option ('snapshot_auto_interval');
	delete_option ('snapshot_auto_email');
	delete_option ('snapshot_repo_amount');

// Thanks for using Snapshot Backup
// If you'd like to try again someday check out http://wpguru.tv where it lives and grows
?>