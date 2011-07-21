<?php
echo "<h2>Send package to FTP site</h2>";

// set up variables
$host = get_option('snapshot_ftp_host');
$user = get_option('snapshot_ftp_user');
$pass = get_option('snapshot_ftp_pass');
$subdir = get_option('snapshot_ftp_subdir');
$remotefile = $subdir.'/'.$filename;
$localfile = WP_CONTENT_DIR .'/uploads/' . $filename;

// connect to host
$conn = ftp_connect($host);

// @since 1.6
// new passive FTP connection to avoid timeouts
// thanks to Kara for this code ;-)

if (!$conn) {
  echo 'Could not connect to ftp server. This will be local backup.<br />';
}
else {
echo "Connected to $host.<br />";
// log in to host
$result = @ftp_login($conn, $user, $pass);
if (!$result) {
 echo "Could not log on as $user. This will be local backup.<br />";
}
else {
echo "Logged in as $user<br />";
// Switch to passive mode
ftp_pasv($conn, true);
// upload file
echo 'Uploading package to FTP repository...<br />';
if (!$success = ftp_put($conn, $remotefile, $localfile, FTP_BINARY)) {
 echo 'Error: Could not upload file. This will be local backup.<br />';
} 
else {
   echo 'File was uploaded successfully <br />';
             }
      }
}
// close connection to host
ftp_quit($conn);

// echo "... Done!";

?>