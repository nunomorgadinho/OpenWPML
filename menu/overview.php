<?php 
if($sitepress_settings['existing_content_language_verified']){
    $active_languages = $sitepress->get_active_languages();    
    $default_language = $sitepress->get_default_language();
    foreach($active_languages as $lang){
        if($default_language != $lang['code']){$default = '';}else{$default = ' ('.__('default','sitepress').')';}
        $alanguages_links[] = $lang['display_name'] . $default;
    }    
    
    if(2 <= count($sitepress->get_active_languages())){
        $strings_need_update = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}icl_strings WHERE status <> 1");            
    }
}
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap">
    
        <div id="icon-options-general" class="icon32 icon32_adv"><br /></div>
        <h2><?php echo __('WPML Overview', 'sitepress') ?></h2>    
        
        <p><?php printf(__('WPML makes it possible to run full multilingual websites with WordPress. You are using <b>WPML %s</b>.', 'sitepress'), ICL_SITEPRESS_VERSION)?></p>
        
        <?php do_action('icl_page_overview_top'); ?>
        
        <h3><?php _e('Multilingual', 'sitepress') ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th width="15%"><?php _e('Section', 'sitepress') ?></th>
                    <th><?php _e('Description', 'sitepress') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/languages.php"><?php _e('Languages', 'sitepress')?></a></td>
                    <td>                        
                        <?php if(!$sitepress_settings['existing_content_language_verified']): ?>          
                        <p><b><?php _e('Your site\'s languages are not set up yet.', 'sitepress'); ?></b></p>              
                        <?php else: ?>
                        <p>
                            <?php _e('Currently configured languages:', 'sitepress')?> 
                            <b><?php echo join(', ', (array)$alanguages_links)?></b>
                        </p>
                        <?php endif; ?>
                        <p>
                            <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/languages.php' ?>"><?php echo __('Configure languages', 'sitepress') ?></a>
                        </p>
                    </td>
                </tr>  

                <?php if(2 <= count($sitepress->get_active_languages())) :?>
                
                <tr>
                    <td><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/theme-localization.php"><?php _e('Theme and plugins localization', 'sitepress')?></a></td>
                    <td>
                        <p>
                            <?php 
                            echo __('Current configuration', 'sitepress');
                            echo '<br /><strong>';
                            switch($sitepress_settings['theme_localization_type']){
                                case '1': echo __('Translate the theme by WPML', 'sitepress'); break;
                                case '2': echo __('Using a .mo file in the theme directory', 'sitepress'); break;
                                default: echo __('No localization', 'sitepress'); 
                            }
                            echo '</strong>';
                            ?>
                        </p>                                                                              
                        <p><a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/theme-localization.php' ?>"><?php echo __('Manage theme and plugins localization', 'sitepress'); ?></a></p>                     
                    </td>
                </tr>                            
                
                <tr>
                    <td><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/string-translation.php"><?php _e('String translation', 'sitepress')?></a></td>
                    <td>
                        <p><?php echo __('String translation allows you to enter translation for texts such as the site\'s title, tagline, widgets and other text not contained in posts and pages.', 'sitepress')?></p>
                        <?php if($strings_need_update==1): ?>          
                        <p><b><?php printf(__('There is <a href="%s"><b>1</b> string</a> that needs to be updated or translated. ', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php&amp;status=0')?></b></p>                                      
                        <?php elseif($strings_need_update): ?>          
                        <p><b><?php printf(__('There are <a href="%s"><b>%s</b> strings</a> that need to be updated or translated. ', 'sitepress'), 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php&amp;status=0' ,$strings_need_update)?></b></p>              
                        <?php else: ?>
                        <p>
                            <?php echo __('All strings are up to date.', 'sitepress'); ?>
                        </p>
                        <?php endif; ?>
                        <p>
                        <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/string-translation.php' ?>"><?php echo __('Translate strings', 'sitepress') ?></a>
                        </p>                                            
                    </td>
                </tr>
                
                <tr>
                    <td><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/translation-management.php&sm=mcsetup"><?php _e('Translation synchronization', 'sitepress')?></a></td>
                    <td><?php _e('Controls how to synchronize between contents in different languages.','sitepress') ?></td>
                </tr>
                
                <tr>
                    <td><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/comments-translation.php"><?php _e('Comments translation', 'sitepress')?></a></td>
                    <td>
                        <?php _e('WPML can translate comments that visitors leave you in languages that you don\'t speak and translate back your replies.','sitepress') ?>
                        <?php if(!$sitepress->icl_account_configured() || !$sitepress->get_icl_translation_enabled()): ?>
                        <br />
                        <?php printf(__('To translate your replies, you need to enable <a href="%s">professional translation</a>.','sitepress'),'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/content-translation.php')?>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <td><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/content-translation.php"><?php _e('Professional translation', 'sitepress')?></a></td>
                    <td>
                        <p><b><?php _e('ICanLocalize can translate your site\'s contents professionally.', 'sitepress'); ?></b></p>
                        <p><?php _e('WPML will send the documents that need translation to ICanLocalize and then create the translated posts and pages.','sitepress'); ?></p>
                        
                        <p><a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/content-translation.php' ?>"><?php echo __('Manage professional translation', 'sitepress') ?></a>
                        </p>
                    </td>
                </tr>
                
                <?php endif; //if(2 <= count($sitepress->get_active_languages())) ?>
                
            </tbody>
        </table>
        
        <br />
        <h3><?php _e('CMS navigation', 'sitepress') ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th width="15%"><?php _e('Section', 'sitepress') ?></th>
                    <th><?php _e('Description', 'sitepress') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php if($sitepress_settings['modules']['cms-navigation']['enabled']):?><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/navigation.php"><?php endif;?>
                        <?php _e('Navigation', 'sitepress')?>
                        <?php if($sitepress_settings['modules']['cms-navigation']['enabled']):?></a><?php endif;?>
                    </td>
                    <td>
                        <p>
                            <?php echo __('WPML provides advanced menus and navigation to go with your WordPress website, including drop-down menus, breadcrumbs and sidebar navigation.', 'sitepress')?>
                        </p>
                        <?php if(!$sitepress_settings['modules']['cms-navigation']['enabled']):?>
                        <p><b><?php echo __('CMS Navigation is disabled.','sitepress') ?></b></p>
                        <p><a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/overview.php&amp;enable-cms-navigation=1' ?>"><?php echo __('Enable CMS navigation', 'sitepress') ?></a></p>
                        <?php else: ?>
                        <p><b><?php echo __('CMS Navigation is enabled.','sitepress') ?></b></p>
                        <p>
                            <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/navigation.php' ?>"><?php echo __('Configure navigation', 'sitepress') ?></a>
                            <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/overview.php&amp;enable-cms-navigation=0' ?>"><?php echo __('Disable CMS navigation', 'sitepress') ?></a>
                        </p>        
                        <?php endif; ?>            
                    </td>
                </tr>            
                <tr>
                    <td>
                        <?php if($sitepress_settings['modules']['absolute-links']['enabled']):?><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/absolute-links.php"><?php endif; ?>
                        <?php _e('Sticky links', 'sitepress')?>
                        <?php if($sitepress_settings['modules']['absolute-links']['enabled']):?></a><?php endif; ?>
                    </td>
                    <td>
                        <p><?php echo __('With Sticky Links, WPML can automatically ensure that all links on posts and pages are up-to-date, should their URL change.', 'sitepress'); ?></p>
                    
                        <?php if($sitepress_settings['modules']['absolute-links']['enabled']):?>
                        <p><b><?php echo __('Sticky links are enabled.','sitepress') ?></b></p>
                        <p>
                            <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/absolute-links.php' ?>"><?php echo __('Configure sticky links', 'sitepress') ?></a>
                            <a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/overview.php&amp;icl_enable_alp=0' ?>"><?php echo __('Disable sticky links', 'sitepress') ?></a>
                        </p>                    
                        
                        <?php else: ?>
                        <p><b><?php echo __('Sticky links are disabled.','sitepress') ?></b></p>
                        <p><a class="button secondary" href="<?php echo 'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/overview.php&amp;icl_enable_alp=1' ?>"><?php echo __('Enable sticky links', 'sitepress') ?></a></p>
                        <?php endif; ?>
                    </td>
                </tr>                            
            </tbody>
        </table>
        
        <br />
        <p><?php echo(sprintf(__('For advanced access or to completely uninstall WPML and remove all language information, use the <a href="%s">troubleshooting</a> page.','sitepress'),'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/troubleshooting.php')); ?></p>
        
        <?php do_action('icl_menu_footer'); ?>
    
</div>
