<?php 
    $troptions = icl_st_scan_options_strings();
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32 icon32_adv"><br /></div>
    <h2><?php echo __('String translation', 'sitepress') ?></h2>    
    
    <?php if(!empty($troptions)): ?>
    <div id="icl_st_option_writes">
    <p><?php _e('This table shows all the admin texts that WPML  found.', 'sitepress'); ?></p>
    <p><?php printf(__('The fields with <span%s>cyan</span> background are text fields and the fields with <span%s>gray</span> background are numeric.', 'sitepress'),' class="icl_st_string"',' class="icl_st_numeric"'); ?></p>
    <p><?php printf(__("Choose the fields you'd like to translate and click on the 'Apply' button. Then, use WPML's <a%s>String translation</a> to translate them.", 'sitepress'),' href="admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/string-translation.php&context=admin_options_'.get_option('template').'"'); ?></p>    
    
    <p>
        <input type="button" class="button" id="icl_st_ow_export" value="<?php _e('Export selected strings as PHP code to be added to the theme code', 'sitepress'); ?>" />
        <input type="button" class="button-primary" id="icl_st_ow_export_close" value="<?php _e('Close', 'sitepress')?>" />
        <img class="ajax_loader" src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="display:none" width="16" height="16" />
    </p>
    <p id="icl_st_ow_export_out"></p>
    
    <form name="icl_st_option_writes_form" id="icl_st_option_write_form">    
        
    <?php foreach($troptions as $option_name=>$option_value): ?>
    <?php echo icl_st_render_option_writes($option_name, $option_value); ?>
    <br clear="all" />
    <?php endforeach; ?>    
    <span id="icl_st_options_write_success" class="hidden updated message fade"><?php printf(__('The selected strings can now be translated using the <a%s>string translation</a> screen', 'sitepress'), ' href="admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/string-translation.php&context=admin_options_'.get_option('template').'"');?></span>
    <span id="icl_st_options_write_confirm" class="hidden"><?php _e('You have removed some of the texts that are translated already. The translations will be lost.','sitepress')?></span>
    <p class="submit">
        <input type="submit" value="<?php _e('Apply', 'sitepress');?>" />
        <span class="icl_ajx_response" id="icl_ajx_response"></span>
    </p>
    
    
    
    </form>
    </div>
    <?php else: ?>
    <div align="center"><?php _e('No options found. Make sure you saved your theme options at least once. <br />Some themes only add these to the wp_options table after the user explicitly saves over the theme defaults', 'sitepress') ?></div>
    <?php endif; ?>
    
</div>