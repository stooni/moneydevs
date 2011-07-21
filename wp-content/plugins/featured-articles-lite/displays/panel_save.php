<?php 
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */
?>
<div id="submitpost" class="submitbox">
    <div class="misc-pub-section">
        <div class="misc-pub-section">
            <label for="section_title" title="<?php _e('Title will be displayed above the slider.');?>"><strong><?php _e('Section title:');?></strong></label>
            <input type="text" id="section_title" name="section_title" value="<?php echo $options['_fa_lite_aspect']['section_title']; ?>" style="margin:5px 0px; width:100%;" /><br />
            <input type="checkbox" name="section_display" id="section_display" value="1" <?php if($options['_fa_lite_aspect']['section_display']): ?> checked="checked"<?php endif;?> /> <label for="section_display" title="<?php _e('Choose whether to display the slider title or not');?>"><?php _e('Title is visible');?> </label>
        </div>
        <div class="misc-pub-section misc-pub-section-last">
            <label for="" title="<?php _e('Select slider theme');?>"><strong><?php _e('Select a theme: ');?></strong></label>
            <select name="active_theme" style="width:100%;">
            <?php foreach ($themes as $theme):?>
            <option value="<?php echo $theme;?>"<?php if($options['_fa_lite_theme']['active_theme'] == $theme):?> selected="selected"<?php endif;?> /><?php echo ucfirst(str_replace('_', ' ', $theme));?></option>
            <?php endforeach;?>
            </select>
        </div>
    </div>
    <div id="major-publishing-actions">
        <?php if( $slider_id ): ?>        
        <div id="delete-action">
        	<a href="<?php echo wp_nonce_url( $current_page.'&amp;noheader=true&amp;delete='.$slider_id );?>" class="submitdelete deletion">Delete</a>
        </div>
        <?php endif;?>
        <div id="publishing-action">
        	<input type="submit" value="<?php echo $slider_id ? 'Update':'Save';?>" accesskey="p" tabindex="5" id="publish" class="button-primary" name="save">
        </div>
        <div class="clear"></div>
    </div>
</div>
