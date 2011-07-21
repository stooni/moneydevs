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
   	
<p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Add Directory') ?>" />
  <br />
</p>
</form>
<hr />