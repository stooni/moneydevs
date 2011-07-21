<h2>Reading out the Database</h2>
<?php
/* BEFORE WE START
We should use his opportunity to check for a writeable directory
perhaps create one under /wp-content/snapshots/
*/
echo 'This could take a moment...<br>';
// generate filename from system time
$dbsnapshotfile = 'snapshot-db-'.date('Ymd-Gi').'.sql';

/*********************************************
I'm retiring the previous scrip in this place
due to incompatibilities with certain servers.

The Script by David B Walsh - is still online here:
http://davidwalsh.name/backup-mysql-database-php
*/

/* Database Readout via mysqldump
** @since 1.5
*/
$dbfilename = 'snapshot-db-'.$filetime.'.sql';
$dbfilepath = WP_CONTENT_DIR .'/uploads/'.$dbfilename;
$dumpstring = 'mysqldump -h '.DB_HOST.' -u '. DB_USER .' -p'. DB_PASSWORD .' '. DB_NAME .' > '. $dbfilepath;
// echo '<br>'.$dbfilepath.'<br>'.$dumpstring.'<br>';

$output = shell_exec($dumpstring);
// echo "<pre>$output</pre>";
// echo 'End of Database thing<br>';

echo 'Done!';
?>
