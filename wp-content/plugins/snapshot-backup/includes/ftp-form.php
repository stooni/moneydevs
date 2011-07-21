<?php

    // variables for the field and option names 
    $opt_name = 'snapshot_ftp_host';
    $opt_name2 = 'snapshot_ftp_user';
    $opt_name3 = 'snapshot_ftp_pass';
    $opt_name4 = 'snapshot_ftp_subdir';
	$opt_name5 = 'snapshot_ftp_prefix';
	$opt_name6 = 'snapshot_add_dir1';
	$opt_name7 = 'snapshot_auto_interval';
	$opt_name8 = 'snapshot_auto_email';
	
    $hidden_field_name = 'snapshot_ftp_hidden';
    $hidden_field_name2 = 'snapshot_backup_hidden';
    $hidden_field_name3 = 'snapshot_check_repo';
    $data_field_name = 'snapshot_ftp_host';
    $data_field_name2 = 'snapshot_ftp_user';
    $data_field_name3 = 'snapshot_ftp_pass';
    $data_field_name4 = 'snapshot_ftp_subdir';
	$data_field_name5 = 'snapshot_ftp_prefix';
	$data_field_name6 = 'snapshot_add_dir1';
	$data_field_name7 = 'snapshot_auto_interval';
	$data_field_name8 = 'snapshot_auto_email';

    // Read in existing option value from database
    $opt_val = get_option( $opt_name );
    $opt_val2 = get_option ($opt_name2 );
    $opt_val3 = get_option ($opt_name3 );
    $opt_val4 = get_option ($opt_name4 );
	$opt_val5 = get_option ($opt_name5 );
	$opt_val6 = get_option ($opt_name6 );
	$opt_val7 = get_option ($opt_name7 );
	$opt_val8 = get_option ($opt_name8 );
	
    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name3 ]) && $_POST[ $hidden_field_name3 ] == 'Y' ) {
    // Read their posted value
    $opt_val6 = trim($_POST[ $data_field_name6 ]);
	// Save the posted value in the database
    update_option( $opt_name6, $opt_val6 );
	// Put a "settings updated" message on the screen
?>
<div class="updated"><p><strong><?php echo 'Your additional directory has been saved.'; ?></strong></p></div>
<?php
    }
/*
 * @since 1.0
 * FTP FORM SETTINGS
 */
	
    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
        // Read their posted value
    $opt_val = trim($_POST[ $data_field_name ]);
    $opt_val2 = trim($_POST[ $data_field_name2 ]);
	$opt_val3 = trim($_POST[ $data_field_name3 ]);
    $opt_val4 = trim($_POST[ $data_field_name4 ]);
	$opt_val5 = trim($_POST[ $data_field_name5 ]);
        
	// Save the posted value in the database
    update_option( $opt_name, $opt_val );
    update_option( $opt_name2, $opt_val2 );
	update_option( $opt_name3, $opt_val3 );
	update_option( $opt_name4, $opt_val4 );
	update_option( $opt_name5, $opt_val5 );

     // Put a "settings updated" message on the screen
?>
<div class="updated"><p><strong><?php _e('Your FTP details have been saved.', 'snapshot-menu' ); ?></strong></p></div>
<?php
    } // end if
	//
	// Test Connection Button
	// @since 2.0
	//
	if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Test Connection' ) {
	include plugin_dir_path( __FILE__ ) . 'test-ftp.php';
	$result = snapshot_test_ftp();
	// echo "<h2>$result</h2>";
	
	if ($result != 'OK') {	?>
    <div class="error"><p><strong>Houston, we have a problem:<br /></strong></p>
    <?php echo $result . '<br /><br />'; ?>
    </div>
    <?php } else { ?>
<div class="updated"><p><strong>Eureka - it works!</strong></p></div>
<?php	
	} // end if 
	} // end if

        ?>

<p>Enter your FTP details for your offsite backup repository. Leave these blank for local backups.</p>		
<form name="form1" method="post" action="">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<table width="700" border="0" cellspacing="10">
  <tr>
    <td>FTP Host:</td>
    <td><input type="text" name="<?php echo $data_field_name; ?>" value="<?php echo $opt_val; ?>" size="25"></td>
    <td><em>e.g. ftp.yoursite.com </em></td>
  </tr>
  <tr>
    <td>FTP User:</td>
    <td><input type="text" name="<?php echo $data_field_name2; ?>" value="<?php echo $opt_val2; ?>" size="25"></td>
    <td>&nbsp;      </td>
  </tr>
  <tr>
    <td>FTP Password:</td>
    <td><input type="password" name="<?php echo $data_field_name3; ?>" value="<?php echo $opt_val3; ?>" size="25"></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>Subdirectory:</td>
    <td><input type="text" name="<?php echo $data_field_name4; ?>" value="<?php echo $opt_val4; ?>" size="25"></td>
    <td><em>e.g. /httpdocs/backups or leave blank</em> </td>
  </tr>

  <tr>
    <td>Snapshot Prefix: </td>
    <td><input type="text" name="<?php echo $data_field_name5; ?>" value="<?php echo $opt_val5; ?>" size="25"></td>
    <td><em>filename is prefix-snapshot-date-time.tar</em></td>
  </tr>
</table>
<p><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save FTP Details') ?>" />&nbsp;
<input type="submit" name="<?php echo $hidden_field_name; ?>" class="button-secondary" value="Test Connection" />

  <br />
</p>
</form>
<hr />
<br />
<h2>Include Additional Directory </h2>
<p>You can include the full path to another directory in your Snapshots if you wish. </p>
<p>This option is useful if you have moved your wp-content folder to a location other than the default 


 (i.e. the same folder that WordPress resides in).</p>
<p>Leave  blank if you have a default installation. </p>
<?php

?>
<form name="form3" method="post" action="">
<input type="hidden" name="<?php echo $hidden_field_name3; ?>" value="Y">
<input type="text" name="<?php echo $data_field_name6; ?>" value="<?php echo $opt_val6; ?>" size="80">
<p><em>No trailing slash please - e.g. <?php echo WP_CONTENT_DIR; ?> </em></p>
   	
<p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Directory') ?>" />
  <br />
</p>
</form>