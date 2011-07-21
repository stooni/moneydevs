 <?php
 
function cockbollocks() {
	$trouble = "Hello";
	return $trouble;
}

function snapshot_test_ftp() {

// now let's see if we can connect to the FTP repo
// set up variables
$host = get_option('snapshot_ftp_host');
$user = get_option('snapshot_ftp_user');
$pass = get_option('snapshot_ftp_pass');
$subdir = get_option('snapshot_ftp_subdir');
$remotefile = $subdir . '/' . $filename;

// @since 2.0
// chkecing FTP Details
// this is a re-write of the Prefilght Checks

// connect to host
$conn = ftp_connect($host);

if (!$conn)
{
  $trouble = "I could not connect to your FTP server.<br />Please check your FTP Host and try again.";
  return $trouble;
}
// can we log in?
$result = ftp_login($conn, $user, $pass);
if (!$result) {
$trouble = "I could connect to the FTP server but I could not log in.<br />Please check your credentials and try again.";
  return $trouble;
}
// and does the remote directory exist?
$success = ftp_chdir($conn, $subdir);
if (!$success) {
$trouble = "I can connect to the FTP server, but I cannot change into the FTP subdirectory you specified. <br />Is the path correct? Does the directory exist? Is it wrritable?<br />Please check using an FTP client like FileZilla.";
  return $trouble;
}

// and is it writeable?

// got til here? Wow - everything must be fine then
$trouble = 'OK';

// lose this connection
ftp_close($conn);
return $trouble;

} // end of function


?>