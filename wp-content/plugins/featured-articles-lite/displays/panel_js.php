<?php 
/**
 * @package Featured articles Lite - Wordpress plugin
 * @author Constantin Boiangiu ( constantin[at]php-help.ro )
 * @version 2.3
 */
?>
<div class="misc-pub-section">
    <label for="effectDuration" title="<?php _e('Enter the number of seconds the transition effect should take when sliding')?>"><?php _e('Effect duration:');?></label>
    <input id="effectDuration" type="text" name="effectDuration" value="<?php echo $options['_fa_lite_js']['effectDuration']; ?>" class="small-text FA_number float" /> seconds (numeric)<br />
</div>
<div class="misc-pub-section">
<label for="fadeDist" title="<?php _e('Choose the distance to start the sliding effect from (in pixels)');?>"><?php _e('Fade distance:');?></label>
<input type="text" id="fadeDist" name="fadeDist" value="<?php echo $options['_fa_lite_js']['fadeDist']; ?>" class="small-text FA_number" /> <?php _e('pixels (numeric)');?><br />
</div>
<div class="misc-pub-section">
<label for="" title="<?php _e('Choose between top sliding or left sliding entry point')?>"><?php _e('Slides entering from:');?></label>
<input type="radio" id="fadeLeft" name="fadePosition" value="left"<?php if( $options['_fa_lite_js']['fadePosition'] == 'left' ):?> checked="checked"<?php endif;?> /> <label for="fadeLeft">left</label>
<input type="radio" id="fadeTop" name="fadePosition" value="top"<?php if( $options['_fa_lite_js']['fadePosition'] == 'top' ):?> checked="checked"<?php endif;?> /> <label for="fadeTop">top</label><br />
</div>
<div class="misc-pub-section">
<label for="stopSlideOnClick" title="<?php _e('When autoslide in effect, it will stop if user clicks navigation');?>"><?php _e('Navigation click stops auto sliding:');?></label>
<input type="checkbox" id="stopSlideOnClick" name="stopSlideOnClick"<?php if($options['_fa_lite_js']['stopSlideOnClick']) echo ' checked="checked"';?> value="1" /><br />
</div>
<div class="misc-pub-section">
<label for="mouseWheelNav" title="<?php _e('Enable/disable mouse wheel navigation in slider');?>"><?php _e('Enable mouse wheel navigation:');?></label>
<input type="checkbox" id="mouseWheelNav" name="mouseWheelNav"<?php if($options['_fa_lite_js']['mouseWheelNav']) echo ' checked="checked"';?> value="1" /><br />
</div>
<div class="misc-pub-section misc-pub-section-last">
<label for="autoSlide"><?php _e('Autoslide:');?></label>
<input type="checkbox" id="autoSlide" name="autoSlide"<?php if($options['_fa_lite_js']['autoSlide']) echo ' checked="checked"';?> value="1" /> <?php _e('every');?> <input type="text" name="slideDuration" value="<?php echo $options['_fa_lite_js']['slideDuration']; ?>" class="small-text FA_number float" /> <?php _e('sec.');?>
</div>

