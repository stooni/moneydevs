<?php
// CHECK REPOSITORY
// since @2.0

// TO DO:
// add check if FTP Details are actually filled out
?>
<p><strong>Here's a list of Snapshots in your repository:</strong></p>
<?php

// set up variables
$host = get_option('snapshot_ftp_host');
$user = get_option('snapshot_ftp_user');
$pass = get_option('snapshot_ftp_pass');
$subdir = get_option('snapshot_ftp_subdir');

// set up basic connection
$conn_id = ftp_connect($host);

// login with username and password
$login_result = ftp_login($conn_id, $user, $pass);

// get contents of the current directory
$contents = ftp_nlist($conn_id, "$subdir/*.tar");

// output $contents
// var_dump($contents);

?><ol></em>

<?php foreach ($contents as $key => $value) {
echo '<li>' . substr($value, (strlen($subdir)+1)) . '</li>';
}
?>
</ol>
<br />
<em>This section helps you keep track of your backups. </em><br />
<em>I'll add more functions here in future versions of the Plugin.</em>

<?php
echo "<br />";


ftp_close($conn_id);
?>
