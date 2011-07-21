<?php
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */

/* set the current page url */
$current_page = menu_page_url('featured-articles-lite/permissions.php', false);

if( isset( $_POST['FA_perm'] ) && !empty($_POST['FA_perm']) ){
	if( !wp_verify_nonce($_POST['FA_perm'],'FA_permissions') ){
		die('Sorry, your action is invalid.');
	}else{
		// get wordpress roles
		$roles = $wp_roles->get_names();
		foreach( $roles as $role=>$name ){
			// administrator has default access so skip this role
			if( 'administrator' == $role ) continue;
			// add/remove editing capabilities
			if( isset( $_POST['role'][$role] ) ){
				$wp_roles->add_cap($role, FA_CAPABILITY);
			}else{
				$wp_roles->remove_cap($role, FA_CAPABILITY);
			}
		}
		wp_redirect( $current_page );	
		exit();	
	}	
}
?>
<div class="wrap">
	<div class="icon32" id="icon-users"><br></div>
    <h2 id="add-new-user">Set editing permissions</h2>
    <p>Set the user levels that can edit the slider settings (administrator can edit by default).</p>
    
    <form method="post" action="<?php echo $current_page;?>&amp;noheader=1">
        <?php wp_nonce_field('FA_permissions', 'FA_perm');?>
        <table class="edit-form">
        <tbody>
        <tr class="form-field">
            <td>            	
                <?php
					$roles = $wp_roles->get_names();
					foreach( $roles as $role=>$name ):
						if( 'administrator' == $role ){
							continue;
						}	
						$r = $wp_roles->get_role( $role );
						$checked = array_key_exists( FA_CAPABILITY, $r->capabilities ) ? ' checked="checked"' : '';							
				?>
                	<label><input type="checkbox" name="role[<?php echo $role;?>]" value="1"<?php echo $checked;?> style="width:auto;" /> <?php echo $name;?></label><br />
                 
                <?php endforeach;?>
            </td>
        </tr>
        </tbody>
        </table>
<p class="submit">
    <input type="submit" value="Set users level" class="button-primary" id="addusersub" name="adduser">
</p>        
    </form>
</div>