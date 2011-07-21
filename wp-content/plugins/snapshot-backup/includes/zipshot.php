<?php
echo "<h2>Archving your website</h2>";
echo "This could take another moment...<br />";
$savepath = WP_CONTENT_DIR . '/uploads/';

// delete previous backups
$output = exec('rm '.$savepath.'*snapshot-*.tar');
// generate filename for backup
$prefix = get_option('snapshot_ftp_prefix');
if (!$prefix == ''){
$filename = $prefix.'-snapshot-'.$filetime.'.tar';
} else {
$filename = 'snapshot-'.$filetime.'.tar';
}
// echo "<br>The filename will be $filename <br>";
// save filename to database 
update_option('snapshot_latest', $filename);
// echo "<br>saving backup in $savepath";

// create exec command out of the following components:
// $safepath, $filename, .htaccess file, root directory
$snapshotfile = 'tar -cvf ' . $savepath . $filename . ' '. ABSPATH . '.htaccess ' . ABSPATH . '*';
// let's check for any additional directories
$extradir = get_option('snapshot_add_dir1');
if ($extradir) {
	$snapshotfile = $snapshotfile . ' ' . $extradir . '/*';
}
// echo $snapshotfile . '<br />';
// execute tar at system level
$output = exec($snapshotfile);  
// echo $output . '<br />';
// get size of latest file
echo "The size of this Snapshot is " . round((filesize($savepath.$filename)/1024/1024), 2)." MB<br>";
// delete database file
$output = exec('rm '.$savepath.'snapshot-db-*.sql');

echo "Done!";
?>
