<?php //included from menu translation-management.php ?>
<?php
      
    $cposts = array();
    $icl_post_types = $sitepress->get_translatable_documents(true);    
    
    foreach($icl_post_types as $k=>$v){
        if(!in_array($k, array('post','page'))){
            $cposts[$k] = $v;        
        }
    }
    
    foreach($cposts as $k=>$cpost){
        if(!isset($sitepress_settings['custom_posts_sync_option'][$k])){
            $cposts_sync_not_set[] = $cpost->labels->name;
        }    
    }    
    if(!empty($cposts_sync_not_set)){
        $notice = '<div class="updated below-h2"><p>';
        $notice .= sprintf(__("You haven't set your synchronization preferences for these custom posts: %s. Default value was selected.", 'sitepress'), 
            '<i>'.join('</i>, <i>', $cposts_sync_not_set) . '</i>');
        $notice .= '</p></div>';
    }
    
    global $wp_taxonomies;
    $ctaxonomies = array_diff(array_keys((array)$wp_taxonomies), array('post_tag','category', 'nav_menu', 'link_category'));    
    
    foreach($ctaxonomies as $ctax){
        if(!isset($sitepress_settings['taxonomies_sync_option'][$ctax])){
            $tax_sync_not_set[] = $wp_taxonomies[$ctax]->label;
        }    
    }
    if(!empty($tax_sync_not_set)){
        $notice .= '<div class="updated below-h2"><p>';
        $notice .= sprintf(__("You haven't set your synchronization preferences for these taxonomies: %s. Default value was selected.", 'sitepress'), 
            '<i>'.join('</i>, <i>', $tax_sync_not_set) . '</i>');
        $notice .= '</p></div>';
    }
    
    $cf_keys_limit = 1000; // jic
    $cf_keys = $wpdb->get_col( "
        SELECT meta_key
        FROM $wpdb->postmeta
        GROUP BY meta_key
        ORDER BY meta_key
        LIMIT $cf_keys_limit" );
    
    $cf_keys_exceptions = array('_edit_last', '_edit_lock', '_wp_page_template', '_wp_attachment_metadata', '_icl_translator_note');
    // '_wp_attached_file'
    
    $cf_keys = array_diff($cf_keys, $cf_keys_exceptions);
    $cf_keys = array_unique(array_merge($cf_keys, $iclTranslationManagement->settings['custom_fields_readonly_config']));
    
    if ( $cf_keys )
        natcasesort($cf_keys);
    
    $cf_settings = $iclTranslationManagement->settings['custom_fields_translation'];  
    $cf_settings_ro = (array)$iclTranslationManagement->settings['custom_fields_readonly_config'];  
    $doc_translation_method = intval($iclTranslationManagement->settings['doc_translation_method']);
    
?>

    <?php if(isset($notice)) echo $notice ?>
        
    <div style="width:50%;float:left;margin-right:12px;">
    <form id="icl_doc_translation_method" name="icl_doc_translation_method" action="">        
    <table class="widefat">
        <thead>
            <tr>
                <th colspan="2"><?php _e('How to translate posts and pages', 'sitepress');?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: none;">
                    <ul>
                        <li><label><input type="radio" name="t_method" value="<?php echo ICL_TM_TMETHOD_MANUAL ?>" <?php if($doc_translation_method==ICL_TM_TMETHOD_MANUAL): ?>checked="checked"<?php endif; ?> /> 
                            <?php _e('Create translations manually', 'sitepress')?></label></li>
                        <li><label><input type="radio" name="t_method" value="<?php echo ICL_TM_TMETHOD_EDITOR ?>" <?php if($doc_translation_method==ICL_TM_TMETHOD_EDITOR): ?>checked="checked"<?php endif; ?> /> 
                            <?php _e('Use the translation editor', 'sitepress')?></label></li>
                        <li><label><input type="radio" name="t_method" value="<?php echo ICL_TM_TMETHOD_PRO ?>" <?php if($doc_translation_method==ICL_TM_TMETHOD_PRO): ?>checked="checked"<?php endif; ?> /> 
                            <?php _e('Send to professional translation', 'sitepress')?></label></li>
                    </ul>
                    <input type="submit" class="button-secondary" value="<?php _e('Save', 'sitepress')?>" />
                    <span class="icl_ajx_response" id="icl_ajx_response_dtm"></span>
                    <p><a href="http://wpml.org/?page_id=3416" target="_blank">Learn more about the different translation options</a></p>
                </td>    
            </tr>
        </tbody>
    </table>
    </form>
    </div>
    
    <div style="width:49%;float:left;">
    <form name="icl_tdo_options" id="icl_tdo_options" action="">
    <table class="widefat">
        <thead>
            <tr>
                <th colspan="2"><?php _e('Translated documents options', 'sitepress') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: none;"><?php _e('Document status', 'sitepress')?></td>
                <td style="border: none;">
                    <ul>
                        <li>
                            <p>
                            <label><input type="radio" name="icl_translated_document_status" value="0" 
                                <?php if(!$sitepress_settings['translated_document_status']): ?>checked="checked"<?php endif;?> /> 
                                <?php echo __('Draft', 'sitepress') ?>
                            </label>&nbsp;
                            <label><input type="radio" name="icl_translated_document_status" value="1" 
                                <?php if($sitepress_settings['translated_document_status']): ?>checked="checked"<?php endif;?> /> 
                                <?php echo __('Same as the original document', 'sitepress') ?>
                            </label>     
                            </p>
                            <i><?php echo __("Choose if translations should be published when received. Note: If Publish is selected, the translation will only be published if the original node is published when the translation is received.", 'sitepress') ?></i>
                        </li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border: none;">
                    <input type="submit" class="button-secondary" value="<?php _e('Save', 'sitepress')?>" />
                    <span class="icl_ajx_response" id="icl_ajx_response_tdo"></span>
                </td>
            </tr>
        </tbody>
    </table>
    </form>
    </div>
    
    <br clear="all" />
    <br>
    
    <div style="width:50%;float:left;margin-right:12px;">
    <form id="icl_page_sync_options" name="icl_page_sync_options" action="">        
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Posts and pages synchronization', 'sitepress');?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: none;">
                    <br />                    
                    <p>
                        <label><input type="checkbox" id="icl_sync_page_ordering" name="icl_sync_page_ordering" <?php if($sitepress_settings['sync_page_ordering']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize page order for translations', 'sitepress') ?></label>                        
                    </p>
                    <p>
                        <label><input type="checkbox" id="icl_sync_page_parent" name="icl_sync_page_parent" <?php if($sitepress_settings['sync_page_parent']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Set page parent for translation according to page parent of the original language', 'sitepress') ?></label>                        
                    </p>
                    <p>
                        <label><input type="checkbox" name="icl_sync_page_template" <?php if($sitepress_settings['sync_page_template']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize page template', 'sitepress') ?></label>                        
                    </p>                    
                    <p>
                        <label><input type="checkbox" name="icl_sync_comment_status" <?php if($sitepress_settings['sync_comment_status']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize comment status', 'sitepress') ?></label>                        
                    </p>                    
                    <p>
                        <label><input type="checkbox" name="icl_sync_ping_status" <?php if($sitepress_settings['sync_ping_status']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize ping status', 'sitepress') ?></label>                        
                    </p>                                        
                    <p>
                        <label><input type="checkbox" name="icl_sync_sticky_flag" <?php if($sitepress_settings['sync_sticky_flag']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize sticky flag', 'sitepress') ?></label>                        
                    </p>                                                            
                    <p>
                        <label><input type="checkbox" name="icl_sync_private_flag" <?php if($sitepress_settings['sync_private_flag']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('Synchronize private flag', 'sitepress') ?></label>                        
                    </p>                    
                    <p style="border-top:solid 1px #ddd;font-size:2px">&nbsp;</p>
                    <p>
                        <label><input type="checkbox" name="icl_sync_delete" <?php if($sitepress_settings['sync_delete']): ?>checked="checked"<?php endif; ?> value="1" />
                        <?php echo __('When deleting a post, delete translations as well', 'sitepress') ?></label>                        
                    </p>                                                                                                                                                                
                    <p>
                        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />
                        <span class="icl_ajx_response" id="icl_ajx_response_mo"></span>
                    </p>                    
                </td>
            </tr>
        </tbody>
    </table>
    </form>                
    </div>
    
    <div style="width:49%;float:left;">
    <form id="icl_translation_pickup_mode" name="icl_translation_pickup_mode" action="">        
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Translation pickup mode', 'sitepress');?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="border: none;" id="icl_tm_pickup_wrap">
                    <p><?php _e('How should the site receive completed translations from ICanLocalize?', 'sitepress'); ?></p>
                    <p><label>
                        <input type="radio" name="icl_translation_pickup_method" value="<?php echo ICL_PRO_TRANSLATION_PICKUP_XMLRPC ?>"<?php
                            if($sitepress_settings['translation_pickup_method']==ICL_PRO_TRANSLATION_PICKUP_XMLRPC) echo ' checked="checked"';
                        ?>/>&nbsp;
                        <?php _e('ICanLocalize will deliver translations automatically using XML-RPC', 'sitepress'); ?>
                    </label></p>
                    <p><label>
                        <input type="radio" name="icl_translation_pickup_method" value="<?php echo ICL_PRO_TRANSLATION_PICKUP_POLLING ?>"<?php
                            if($sitepress_settings['translation_pickup_method']==ICL_PRO_TRANSLATION_PICKUP_POLLING) echo ' checked="checked"';
                        ?>/>&nbsp;
                        <?php _e('The site will fetch translations manually', 'sitepress'); ?>
                    </label></p> 
                    <p>
                        <input class="button" name="save" value="<?php echo __('Save','sitepress') ?>" type="submit" />
                        <span class="icl_ajx_response" id="icl_ajx_response_tpm"></span>
                    </p>    
                    
                    <?php $ICL_Pro_Translation->get_icl_manually_tranlations_box(''); // shows only when translation polling is on and there are translations in progress ?>
                                                                                               
                </td>
            </tr>
        </tbody>
    </table>   
    </form> 
    </div>
    <br clear="all" />
    
    <div class="updated below-h2">
        <p style="line-height: 14px"><?php _e("WPML can read a configuration file that tells it what needs translation in themes and plugins. The file is named wpml-config.xml and it's placed in the root folder of the plugin or theme.", 'sitepress'); ?></p>
        <p><a href="http://wpml.org/?page_id=5526"><?php _e('Learn more', 'sitepress') ?></a></p>
    </div>
    
    <form id="icl_cf_translation" name="icl_cf_translation" action="">        
    <table class="widefat">
        <thead>
            <tr>
                <th colspan="2"><?php _e('Custom fields translation', 'sitepress');?></th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($cf_keys)): ?>
            <tr>
                <td colspan="2" style="border: none;">
                    <?php _e('No custom fields found. It is possible that they will only show up here after you add more posts after installing a new plugin.', 'sitepress'); ?>
                </td>
            </tr>
            <?php else: foreach($cf_keys as $cf_key): ?>
            <?php 
                $rdisabled = in_array($cf_key, $cf_settings_ro) ? 'disabled="disabled"' : '';
                if($rdisabled && $cf_settings[$cf_key]==0) continue;
            ?>
            <tr>
                <td><?php echo $cf_key ?></td>
                <td align="right">
                    <label><input type="radio" name="cf[<?php echo base64_encode($cf_key) ?>]" value="0" <?php echo $rdisabled ?>
                        <?php if($cf_settings[$cf_key]==0):?>checked="checked"<?php endif;?> />&nbsp;<?php _e("Don't translate", 'sitepress')?></label>&nbsp;
                    <label><input type="radio" name="cf[<?php echo base64_encode($cf_key) ?>]" value="1" <?php echo $rdisabled ?>
                        <?php if($cf_settings[$cf_key]==1):?>checked="checked"<?php endif;?> />&nbsp;<?php _e("Copy from original to translation", 'sitepress')?></label>&nbsp;
                    <label><input type="radio" name="cf[<?php echo base64_encode($cf_key) ?>]" value="2" <?php echo $rdisabled ?>
                        <?php if($cf_settings[$cf_key]==2):?>checked="checked"<?php endif;?> />&nbsp;<?php _e("Translate", 'sitepress')?></label>&nbsp;
                </td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2" style="border: none;">
                    <p>
                        <input type="submit" class="button" value="<?php _e('Save', 'sitepress') ?>" />
                        <span class="icl_ajx_response" id="icl_ajx_response_cf"></span>
                    </p>    
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </form>                    
    <br />
        
    <?php if(!empty($cposts)): ?>    
    <form id="icl_custom_posts_sync_options" name="icl_custom_posts_sync_options" action="">        
    <table class="widefat">
        <thead>
            <tr>
                <th width="60%"><?php _e('Custom posts', 'sitepress');?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($cposts as $k=>$cpost): ?>
            <?php 
                $rdisabled = isset($iclTranslationManagement->settings['custom_types_readonly_config'][$k]) ? 'disabled="disabled"':'';
            ?>
            <tr>
                <td><?php echo $cpost->labels->name; ?></td>
                <td>
                    <label><input type="radio" name="icl_sync_custom_posts[<?php echo $k ?>]" value="1" <?php echo $rdisabled; 
                        if($sitepress_settings['custom_posts_sync_option'][$k]==1) echo ' checked="checked"'
                    ?> />&nbsp;<?php _e('Translate', 'sitepress') ?></label>&nbsp;
                    <label><input type="radio" name="icl_sync_custom_posts[<?php echo $k ?>]" value="0" <?php echo $rdisabled;
                        if($sitepress_settings['custom_posts_sync_option'][$k]==0) echo ' checked="checked"'
                    ?> />&nbsp;<?php _e('Do nothing', 'sitepress') ?></label>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2">
                <p>
                    <input type="submit" class="button" value="<?php _e('Save', 'sitepress') ?>" />
                    <span class="icl_ajx_response" id="icl_ajx_response_cp"></span>
                </p>
                </td>
            </tr>
        </tbody>
    </table>
    </form>        
    <?php endif; ?>     
    
    <?php if(!empty($ctaxonomies)): ?>
    <form id="icl_custom_tax_sync_options" name="icl_custom_tax_sync_options" action="">        
    <table class="widefat">
        <thead>
            <tr>
                <th width="60%"><?php _e('Custom taxonomies', 'sitepress');?></th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($ctaxonomies as $ctax): ?>
            <?php 
                $rdisabled = isset($iclTranslationManagement->settings['taxonomies_readonly_config'][$ctax]) ? 'disabled="disabled"':'';
            ?>            
            <tr>
                <td><?php echo $wp_taxonomies[$ctax]->label; ?></td>
                <td>
                    <label><input type="radio" name="icl_sync_tax[<?php echo $ctax ?>]" value="1" <?php echo $rdisabled; 
                        if($sitepress_settings['taxonomies_sync_option'][$ctax]==1) echo ' checked="checked"'
                    ?> />&nbsp;<?php _e('Translate', 'sitepress') ?></label>&nbsp;
                    <label><input type="radio" name="icl_sync_tax[<?php echo $ctax ?>]" value="0" <?php echo $rdisabled; 
                        if($sitepress_settings['taxonomies_sync_option'][$ctax]==0) echo ' checked="checked"'
                    ?> />&nbsp;<?php _e('Do nothing', 'sitepress') ?></label>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="2">
                <p>
                    <input type="submit" class="button" value="<?php _e('Save', 'sitepress') ?>" />
                    <span class="icl_ajx_response" id="icl_ajx_response_ct"></span>
                </p>
                </td>
            </tr>
        </tbody>
    </table>
    </form>        
    <?php endif; ?>     
    <br clear="all" />    
        
    <?php if(!empty($iclTranslationManagement->admin_texts_to_translate)): ?>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Admin Strings to Translate', 'sitepress');?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <?php foreach($iclTranslationManagement->admin_texts_to_translate as $option_name=>$option_value): ?>
                    <?php $iclTranslationManagement->render_option_writes($option_name, $option_value); ?>
                    <?php endforeach ?>
                    <br />
                    <p><a class="button-secondary" href="<?php echo admin_url('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/string-translation.php') ?>"><?php _e('Edit translatable strings', 'sitepress') ?></a></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>
    
