<?php     
    require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
    $active_languages = $sitepress->get_active_languages();            
    $languages = $sitepress->get_languages();            
    if(!$sitepress_settings['modules']['absolute-links']['enabled']){
        $total_posts_pages_processed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_alp_processed'");
    }
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap">
    <div id="icon-options-general" class="icon32" style="background: transparent url(<?php echo ICL_PLUGIN_URL ?>/res/img/icon.png) no-repeat"><br /></div>
    <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>    
    
    <h3><?php echo __('About Sticky Links', 'sitepress') ?></h3>    
    
    <p><?php echo __('WPML can turn internal links to posts and pages into sticky links. What this means is that links to pages and posts will automatically update if their URL changes. There are many reasons why page URL changes:', 'sitepress'); ?></p>
    <ul style="list-style:disc;margin-left:20px;">
        <li><?php echo __('The slug changes.', 'sitepress'); ?></li>
        <li><?php echo __('The page parent changes.', 'sitepress'); ?></li>
        <li><?php echo __('Permlink structure changes.', 'sitepress'); ?></li>
    </ul>
    <p><?php echo __('If you select to enable sticky links, internal links to pages and posts will never break. When the URL changes, all links to it will automatically update.', 'sitepress'); ?></p>
    <p><?php echo __('When you edit a page (while sticky links are enabled) you will notice that links in that page change to the default WordPress links. This is a normal thing. Visitors will not see these &#8220;strange&#8221; links. Instead they will get links to the full URL.', 'sitepress'); ?></p>
    
    <h3><?php _e('Options', 'sitepress')?></h3>
    <form name="icl_save_sl_options" id="icl_save_sl_options" action="" method="post">
    <ul>
        <li>
            <label><input type="checkbox" name="icl_sticky_links_widgets" value="1" 
            <?php if($sitepress_settings['modules']['absolute-links']['sticky_links_widgets']):?>checked="checked"<?php endif;?>  />
            &nbsp;<?php _e('Turn links in text widgets to Sticky', 'sitepress')?></label>
        </li>
        <li>
            <label><input type="checkbox" name="icl_sticky_links_strings" value="1"
            <?php if($sitepress_settings['modules']['absolute-links']['sticky_links_strings']):?>checked="checked"<?php endif;?>  />
            &nbsp;<?php _e("Turn links in WPML's string Translation to Sticky", 'sitepress')?></label>
        </li>
    </ul>
    <p>
        <input class="button" name="save" value="<?php echo __('Apply','sitepress') ?>" type="submit" />
        <span class="icl_ajx_response" id="icl_ajx_response2"></span>
    </p>    
    </form>
    
    <span style="position:absolute;" id="icl_ajax_loader_alp"></span>    
    
    <div id="icl_alp_wrap">
    <?php if($sitepress_settings['modules']['absolute-links']['enabled']):?>    
    <?php $iclAbsoluteLinks->management_page_content(); ?>    
    <?php else: ?>
    <?php 
        $total_posts_pages_processed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_alp_processed'");
        if($total_posts_pages_processed > 0){
            $fr = array('[a]','[/a]');
            $to = array('<a href="#revert-links">','</a>');
            echo str_replace($fr, $to, sprintf(__('Some links (%s) were converted to absolute. You can [a]return them to their original values[/a]', 'sitepress'),$total_posts_pages_processed));
        }
    ?>
    <?php endif; ?>
    </div>
    
    <br /> 
    <p><input id="icl_<?php if($sitepress_settings['modules']['absolute-links']['enabled']): ?>disable<?php else:?>enable<?php endif?>_absolute_links" type="button" class="button-primary" value="<?php echo $sitepress_settings['modules']['absolute-links']['enabled'] ? __('Disable Sticky links','sitepress') : __('Enable Sticky links','sitepress') ?>" /></p>
    <span id="icl_toggle_ct_confirm_message" style="display:none"><?php echo __('Are you sure you want to disable Sticky links?','sitepress'); ?></span>
    <span id="icl_overview_url" style="display:none"><?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/overview.php'; ?></span>
    <br /> 
    
    <?php do_action('icl_menu_footer'); ?>
    
</div>