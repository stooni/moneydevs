<?php
echo "<h2>Download latest Snapshot</h2>";
?>
<p>You can download your most recent Snapshot from this server.</p>
<p><strong><a href="
<?php echo content_url().'/uploads/'. get_option('snapshot_latest'); ?>">
<?php echo get_option('snapshot_latest'); ?></a></strong></p>
<?php
// get size of latest file
$snapshot_latest = WP_CONTENT_DIR.'/uploads/'. get_option('snapshot_latest');
echo "<p><em>The size of this snapshot is " . round((filesize($snapshot_latest)/1024/1024), 2)." MB and was created on ". 
date ("F d Y \a\\t H:i:s.", filemtime($snapshot_latest))."</em></p>";
?>