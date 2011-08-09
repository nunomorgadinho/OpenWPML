<?php
if((!isset($sitepress_settings['existing_content_language_verified']) || !$sitepress_settings['existing_content_language_verified']) || 2 > count($sitepress->get_active_languages())){
    return;
}
$active_languages = $sitepress->get_active_languages();              
$locales = $sitepress->get_locale_file_names();
$theme_localization_stats = get_theme_localization_stats();
$plugin_localization_stats = get_plugin_localization_stats();
?>

<div class="wrap">
    <div id="icon-options-general" class="icon32 icon32_adv"><br /></div>
    <h2><?php _e('Theme and plugins localization', 'sitepress') ?></h2>    

    <h3><?php _e('Select how to localize the theme','sitepress'); ?></h3>
    <p><?php _e("If your theme's texts are wrapped in gettext calls, WPML can help you display it multilingual.",'sitepress'); ?></p>
    <form id="icl_theme_localization_type" method="post" action="">
    <input type="hidden" name="icl_ajx_action" value="icl_save_theme_localization_type" />
    <ul>
        <li><label><input type="radio" name="icl_theme_localization_type" value="0" <?php if($sitepress_settings['theme_localization_type']==0):?>checked="checked"<?php endif; ?> /> <?php _e("No localization.<br /><i>The theme's texts will not be localized.</i>", 'sitepress') ?></label></li>
        <li><label><input type="radio" name="icl_theme_localization_type" value="1" <?php if($sitepress_settings['theme_localization_type']==1):?>checked="checked"<?php endif; ?> /> <?php _e("Translate the theme by WPML.<br /><i>WPML will add the theme's texts to the string translation page, where you can enter translations.</i>", 'sitepress') ?></label></li>
        <li><label><input type="radio" name="icl_theme_localization_type" value="2" <?php if($sitepress_settings['theme_localization_type']==2):?>checked="checked"<?php endif; ?> /> <?php _e("Using a .mo file in the theme directory.<br /><i>Include the theme's .mo files in the theme's folder and WPML will load the right file for each language.</i>", 'sitepress') ?></label></li>
    </ul>
    <p>
        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />        
    </p>
    <img src="<?php echo ICL_PLUGIN_URL ?>/res/img/question-green.png" width="29" height="29" alt="need help" align="left" /><p style="margin-top:14px;">&nbsp;<a href="http://wpml.org/?page_id=2717"><?php _e('Theme localization instructions', 'sitepress')?> &raquo;</a></p>
    </form>
    
    <?php if($sitepress_settings['theme_localization_type'] > 0):?>
    <br />
    <div id="icl_tl">
    <h3><?php _e('Language locale settings', 'sitepress') ?></h3>
    <p><?php _e('Select the locale to use for each language. The locale for the default language is set in your wp_config.php file.', 'sitepress') ?></p>
    <form id="icl_theme_localization" name="icl_theme_localization" method="post" action="">
    <input type="hidden" name="icl_post_action" value="save_theme_localization" />    
    <div id="icl_theme_localization_wrap"><div id="icl_theme_localization_subwrap">    
    <table id="icl_theme_localization_table" class="widefat" cellspacing="0">
    <thead>
    <tr>
    <th scope="col"><?php echo __('Language', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Code', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Locale file name', 'sitepress') ?></th>        
    <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), LANGDIR) ?></th>        
    <?php if($sitepress_settings['theme_localization_type']==2):?>
    <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), '/wp-content/themes/' . get_option('template')) ?></th>        
    <?php endif; ?>
    </tr>        
    </thead>        
    <tfoot>
    <tr>
    <th scope="col"><?php echo __('Language', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Code', 'sitepress') ?></th>
    <th scope="col"><?php echo __('Locale file name', 'sitepress') ?></th>        
    <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), LANGDIR) ?></th>        
    <?php if($sitepress_settings['theme_localization_type']==2):?>
    <th scope="col"><?php printf(__('MO file in %s', 'sitepress'), '/wp-content/themes/' . get_option('template')) ?></th>        
    <?php endif; ?>
    </tr>        
    </tfoot>
    <tbody>
    <?php foreach($active_languages as $lang): ?>
    <tr>
    <td scope="col"><?php echo $lang['display_name'] ?></td>
    <td scope="col"><?php echo $lang['code'] ?></td>
    <td scope="col">
        <input type="text" size="10" name="locale_file_name_<?php echo $lang['code']?>" value="<?php echo $locales[$lang['code']]?>" />.mo
    </td> 
    <td>
        <?php if(@is_readable(ABSPATH . LANGDIR . '/' . $locales[$lang['code']] . '.mo')): ?>
        <span class="icl_valid_text"><?php echo __('File exists.', 'sitepress') ?></span>                
		<?php elseif($lang['code'] != 'en' ): ?>
        <span class="icl_error_text"><?php echo __('File not found!', 'sitepress') ?></span>
        <?php endif; ?>
    </td>
    <?php if($sitepress_settings['theme_localization_type']==2):?>       
    <td>
        <?php if(@is_readable(TEMPLATEPATH . '/' . $locales[$lang['code']] . '.mo')): ?>
        <span class="icl_valid_text"><?php echo __('File exists.', 'sitepress') ?></span>                
        <?php elseif($lang['code'] != 'en' ): ?>
        <span class="icl_error_text"><?php echo __('File not found!', 'sitepress') ?></span>
        <?php endif; ?>        
    </td>              
    <?php endif; ?> 
    </tr>
    <?php endforeach; ?>                                                          
    </tbody>        
    </table>
    <?php if($sitepress_settings['theme_localization_type']==2):?>       
    <p>
        <?php echo __('Enter the theme\'s textdomain value:', 'sitepress')?>
        <input type="text" name="icl_domain_name" value="<?php echo $sitepress_settings['gettext_theme_domain_name'] ?>" />
        <?php if(!$sitepress_settings['gettext_theme_domain_name']): ?>
        <span class="icl_error_text"><?php echo __('Theme localization is not enabled because you didn\'t enter a text-domain.', 'sitepress'); ?></span>
        <?php endif ?>
    </p>
    <?php endif; ?>
    </div>
    </div>
    <p>
        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />
        <span class="icl_ajx_response" id="icl_ajx_response_fn"></span>
    </p>
    </form>
    <br /><br />
    </div> 
    <?php endif; ?>
    
    <?php if($sitepress_settings['theme_localization_type'] == 1):?>
        
        <div class="updated fade">
            <p><i><?php _e('Re-scanning the plugins or the themes will reset the strings tracked in the code or the HTML source', 'sitepress') ?></i></p>
        </div>
        
        <h3><?php _e('Strings in the theme', 'sitepress'); ?></h3>
        <div id="icl_strings_in_theme_wrap">
        
        <?php if($theme_localization_stats): ?>
        <p><?php _e('The following strings were found in your theme.', 'sitepress'); ?></p>
        <table id="icl_strings_in_theme" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th scope="col"><?php echo __('Domain', 'sitepress') ?></th>
                    <th scope="col"><?php echo __('Translation status', 'sitepress') ?></th>
                    <th scope="col" style="text-align:right"><?php echo __('Count', 'sitepress') ?></th>
                    <th scope="col">&nbsp;</th>
                </tr>
            </thead>  
            <tbody>
                <?php foreach($sitepress_settings['st']['theme_localization_domains'] as $tl_domain): ?>
                <?php 
                    $_tmpcomp = $theme_localization_stats[$tl_domain ? 'theme ' . $tl_domain : 'theme']['complete'];
                    $_tmpinco = $theme_localization_stats[$tl_domain ? 'theme ' . $tl_domain : 'theme']['incomplete'];
                ?>
                <tr>
                    <td rowspan="3"><?php echo $tl_domain ? $tl_domain : '<i>' . __('no domain','sitepress') . '</i>'; ?></td>
                    <td><?php echo __('Fully translated', 'sitepress') ?></td>
                    <td align="right"><?php echo $_tmpcomp; ?></td>
                    <td rowspan="3" align="right" style="padding-top:10px;">
                        <a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH) ?>/menu/string-translation.php&amp;context=<?php echo $tl_domain ? 'theme%20' . $tl_domain : 'theme' ?>" class="button-secondary"><?php echo __("View all the theme's texts",'sitepress')?></a>
                        <?php if($_tmpinco): ?>
                        <a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH) ?>/menu/string-translation.php&amp;context=<?php echo $tl_domain ? 'theme%20' . $tl_domain : 'theme' ?>&amp;status=0" class="button-primary"><?php echo __("View strings that need translation",'sitepress')?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo __('Not translated or needs update', 'sitepress') ?></td>
                    <td align="right"><?php echo $_tmpinco ?></td>
                </tr>
                <tr style="background-color:#f9f9f9;">
                    <td><strong><?php echo __('Total', 'sitepress') ?></strong></td>
                    <td align="right"><strong><?php echo $_tmpcomp + $_tmpinco; if(1 < count($sitepress_settings['st']['theme_localization_domains'])) { if(!isset($_tmpgt)) $_tmpgt = 0; $_tmpgt += $_tmpcomp + $_tmpinco; } ?></strong></td>
                </tr>            
                <?php endforeach  ?>
            </tbody>
            <?php if(1 < count($sitepress_settings['st']['theme_localization_domains'])): ?>
                <tfoot>
                    <tr>                
                        <th scope="col"><?php echo __('Total', 'sitepress') ?></th>
                        <th scope="col">&nbsp;</th>
                        <th scope="col" style="text-align:right"><?php echo $_tmpgt ?></th>
                        <th scope="col">&nbsp;</th>
                    </tr>
                </tfoot>                              
            <?php endif; ?>
        </table>
        <?php else: ?>
        <p><?php echo __("To translate your theme's texts, click on the button below. WPML will scan your theme for texts and let you enter translations.", 'sitepress') ?></p>
        <?php endif; ?>
        
        </div>
                
        <p>
        <label>
        <input type="checkbox" id="icl_load_mo_themes" value="1" checked="checked" />            
        <?php _e('Load translations if found in the .mo files. (it will not override existing translations)', 'sitepress')?></label> 
        </p>
                
        <p>
        <input id="icl_tl_rescan" type="button" class="button-primary" value="<?php echo __("Scan the theme for strings",'sitepress')?>" />
        <img class="icl_ajx_loader" src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="display:none;" alt="" />
        </p>        
        <div id="icl_tl_scan_stats"></div>  
        
        <br />
        
        <h3><?php _e('Strings in the plugins', 'sitepress'); ?></h3>
        <?php 
        $plugins = get_plugins();
        $active_plugins = get_option('active_plugins'); 
        $mu_plugins = wp_get_mu_plugins();
        foreach($mu_plugins as $p){
            $pfile = basename($p);
            $plugins[$pfile] = array('Name' => 'MU :: ' . $pfile);
            $mu_plugins_base[$pfile] = true;
        }
        $wpmu_sitewide_plugins = (array) maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
        ?>
        <form id="icl_tl_rescan_p" action="">
            <div id="icl_strings_in_plugins_wrap">
                <table id="icl_strings_in_plugins" class="widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th scope="col" class="column-cb check-column"><input type="checkbox" /></th>
                            <th scope="col"><?php echo __('Plugin', 'sitepress') ?></th>
                            <th scope="col"><?php echo __('Active', 'sitepress') ?></th>
                            <th scope="col"><?php echo __('Translation status', 'sitepress') ?><div style="float:right"><?php echo __('Count', 'sitepress') ?></div></th>
                            <th scope="col">&nbsp;</th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </thead>  
                    <tfoot>
                        <tr>
                            <th scope="col" class="column-cb check-column"><input type="checkbox" /></th>
                            <th scope="col"><?php echo __('Plugin', 'sitepress') ?></th>
                            <th scope="col"><?php echo __('Active', 'sitepress') ?></th>
                            <th scope="col"><?php echo __('Translation status', 'sitepress') ?><div style="float:right"><?php echo __('Count', 'sitepress') ?></div></th>
                            <th scope="col">&nbsp;</th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </tfoot>                              
                    <tbody>
                        <?php foreach($plugins as $file=>$plugin): ?>
                        <?php   
                            $plugin_id = (false !== strpos($file, '/')) ? dirname($file) : $file;
                            $plugin_id = 'plugin ' . $plugin_id;
                            if(isset($plugin_localization_stats[$plugin_id]['complete'])){
                                $_tmpcomp = $plugin_localization_stats[$plugin_id]['complete'];
                                $_tmpinco = $plugin_localization_stats[$plugin_id]['incomplete'];
                                $_tmptotal = $_tmpcomp + $_tmpinco;
                                $_tmplink = true;
                            }else{
                                $_tmpcomp = $_tmpinco = $_tmptotal =  __('n/a', 'sitepress');
                                $_tmplink = false;
                            }
                            $is_mu_plugin = false;
                            if(in_array($file, $active_plugins)){
                                $plugin_active_status = __('Yes', 'sitepress');    
                            }elseif(isset($wpmu_sitewide_plugins[$file])){
                                $plugin_active_status = __('Network', 'sitepress');    
                            }elseif(isset($mu_plugins_base[$file])){
                                $plugin_active_status = __('MU', 'sitepress');    
                                $is_mu_plugin = true;
                            }else{
                                $plugin_active_status = __('No', 'sitepress');    
                            } 
                            
                            
                        ?>
                        <tr>
                            <td><input type="checkbox" value="<?php echo $file ?>" name="<?php if($is_mu_plugin):?>mu-plugin[]<?php else:?>plugin[]<?php endif; ?>" /></td>
                            <td><?php echo $plugin['Name'] ?></td>
                            <td align="center"><?php echo $plugin_active_status ?></td>
                            <td>
                                <table width="100%" cellspacing="0">
                                    <tr>
                                        <td><?php echo __('Fully translated', 'sitepress') ?></td>                    
                                        <td align="right"><?php echo $_tmpcomp ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo __('Not translated or needs update', 'sitepress') ?></td>
                                        <td align="right"><?php echo $_tmpinco  ?></td>
                                    </tr>
                                    <tr>
                                        <td style="border:none"><strong><?php echo __('Total', 'sitepress') ?></strong></td>
                                        <td style="border:none" align="right"><strong><?php echo $_tmptotal; ?></strong></td>
                                    </tr>            
                                </table>
                            </td>
                            <td align="right" style="padding-top:10px;">
                                <?php if($_tmplink): ?>
                                    <p><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH) ?>/menu/string-translation.php&amp;context=<?php echo $plugin_id ?>" class="button-secondary"><?php echo __("View all the plugin's texts",'sitepress')?></a></p>
                                    <?php if($_tmpinco): ?>
                                    <p><a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH) ?>/menu/string-translation.php&amp;context=<?php echo $plugin_id ?>&amp;status=0" class="button-primary"><?php echo __("View strings that need translation",'sitepress')?></a></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p><i><?php _e('Select and use the button below to scan for strings', 'sitepress')?></i></p>
                                <?php endif; ?>
                            </td>                     
                        </tr>
                        <?php endforeach  ?>
                    </tbody>
                </table>        
            </div>    
            
            <p>
            <label>
            <input type="checkbox" name="icl_load_mo" value="1" checked="checked" />            
            <?php _e('Load translations if found in the .mo files. (it will not override existing translations)', 'sitepress')?></label> 
            </p>
            <p>
            <input type="submit" class="button-primary" value="<?php echo __("Scan the selected plugins for strings",'sitepress')?>" />
            <img class="icl_ajx_loader_p" src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="display:none;" alt="" />
            </p>
        
        
        </form>
        
        <div id="icl_tl_scan_stats_p"></div>  
        
        
    <?php endif; ?>
    
    
    <?php do_action('icl_menu_footer'); ?>
               
</div>
