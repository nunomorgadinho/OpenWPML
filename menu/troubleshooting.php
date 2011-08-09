<?php 

/* DEBUG ACTION */
if(isset($_GET['debug_action']) && $_GET['nonce']==wp_create_nonce($_GET['debug_action'])){
    ob_end_clean();
    switch($_GET['debug_action']){
        case 'reset_pro_translation_configuration':
            $sitepress_settings = get_option('icl_sitepress_settings');
            
            $sitepress_settings['content_translation_languages_setup'] = false;
            $sitepress_settings['content_translation_setup_complete'] = false;        
            unset($sitepress_settings['content_translation_setup_wizard_step']);
            unset($sitepress_settings['site_id']);
            unset($sitepress_settings['access_key']);
            unset($sitepress_settings['translator_choice']);
            unset($sitepress_settings['icl_lang_status']);
            unset($sitepress_settings['icl_balance']);
            unset($sitepress_settings['icl_support_ticket_id']);
            unset($sitepress_settings['icl_current_session']);
            unset($sitepress_settings['last_get_translator_status_call']);
            unset($sitepress_settings['last_icl_reminder_fetch']);
            unset($sitepress_settings['icl_account_email']);

            update_option('icl_sitepress_settings', $sitepress_settings);
            
            mysql_query("TRUNCATE TABLE {$wpdb->prefix}icl_core_status");
            mysql_query("TRUNCATE TABLE {$wpdb->prefix}icl_content_status");
            mysql_query("TRUNCATE TABLE {$wpdb->prefix}icl_string_status");
            mysql_query("TRUNCATE TABLE {$wpdb->prefix}icl_node");
            mysql_query("TRUNCATE TABLE {$wpdb->prefix}icl_reminders");
            
            echo "<script type=\"text/javascript\">location.href='admin.php?page=". 
                basename(ICL_PLUGIN_PATH).'/menu/troubleshooting.php&message=' . __('PRO translation was reset.', 'sitepress')."'</script>";
            exit;
        case 'ghost_clean':
            
            // clean the icl_translations table 
            $orphans = $wpdb->get_col("
                SELECT t.translation_id 
                FROM {$wpdb->prefix}icl_translations t 
                LEFT JOIN {$wpdb->posts} p ON t.element_id = p.ID 
                WHERE t.element_id IS NOT NULL AND t.element_type LIKE 'post\\_%' AND p.ID IS NULL
            ");   
            if(!empty($orphans)){
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id IN (".join(',',$orphans).")");
            }
            
            $orphans = $wpdb->get_col("
                SELECT t.translation_id 
                FROM {$wpdb->prefix}icl_translations t 
                LEFT JOIN {$wpdb->term_taxonomy} p ON t.element_id = p.term_taxonomy_id 
                WHERE t.element_id IS NOT NULL AND t.element_type LIKE 'tax\\_%' AND p.term_taxonomy_id IS NULL");   
            if(!empty($orphans)){
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id IN (".join(',',$orphans).")");
            }
            
            global $wp_taxonomies;
            if (is_array($wp_taxonomies)) {
                foreach ($wp_taxonomies as $t => $v) {
                    $orphans = $wpdb->get_col("
                SELECT t.translation_id 
                FROM {$wpdb->prefix}icl_translations t 
                LEFT JOIN {$wpdb->term_taxonomy} p 
                ON t.element_id = p.term_taxonomy_id 
                WHERE t.element_type = 'tax_{$t}' 
                AND p.taxonomy <> '{$t}'
                    ");
                    if (!empty($orphans)) {
                        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id IN (".join(',',$orphans).")");
                    }
                }
            } 
            
            // remove ghost translations
            // get unlinked rids
            $rids = $wpdb->get_col("SELECT rid FROM {$wpdb->prefix}icl_translation_status WHERE translation_id NOT IN (SELECT translation_id FROM {$wpdb->prefix}icl_translations)");
            $jids = $wpdb->get_col("SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rids IN (".join(',', $rids).")");
            $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id IN (".join(',', $jids).")");
            $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translate_job WHERE job_id IN (".join(',', $jids).")");
            $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translation_status WHERE rid IN (".join(',', $rids).")");
            
            // remove any duplicates in icl_translations
            $trs = $wpdb->get_results("SELECT element_id, GROUP_CONCAT(translation_id) AS tids FROM {$wpdb->prefix}icl_translations 
                WHERE element_id > 0 AND element_type LIKE 'post\\_%' GROUP BY element_id");
            foreach($trs as $r){
                $exp = explode(',', $r->tids);                
                if(count($exp) > 1){
                    $maxtid = max($exp);
                    foreach($exp as $e){
                        if($e != $maxtid){
                            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d", $e));
                        }                        
                    }        
                }
            }
            
            
            exit;       
            break;        
        case 'icl_sync_jobs':
        
            $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);                                
            $requests = $iclq->cms_requests_all();        
            if(!empty($requests))
            foreach($requests as $request){
                $source_language = ICL_Pro_Translation::server_languages_map($request['language_name'], true);
                $target_language = ICL_Pro_Translation::server_languages_map($request['target']['language_name'], true);
                
                $source_language = $wpdb->get_var($wpdb->prepare("SELECT code FROM {$wpdb->prefix}icl_languages WHERE english_name=%s", $source_language));
                $target_language = $wpdb->get_var($wpdb->prepare("SELECT code FROM {$wpdb->prefix}icl_languages WHERE english_name=%s", $target_language));
                
                // only handle old-style cms_id values
                if(!is_numeric($request['cms_id'])) continue;
                
                $tr  = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d", $request['cms_id']));   
                if(empty($tr)){
                    $trs = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $request['cms_id']));
                    if(!empty($trs)){
                        $tpack = unserialize($trs->translation_package);
                        $original_id = $tpack['contents']['original_id']['data'];
                        list($trid, $element_type) = $wpdb->get_row("
                                SELECT trid, element_type 
                                FROM {$wpdb->prefix}icl_translations 
                                WHERE element_id={$original_id}
                                AND element_type LIKE 'post\\_%'
                            ", ARRAY_N);
                        if($trid){
                            $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE trid={$trid} AND language_code='{$target_language}'");
                            $recover = array(
                                'translation_id' => $request['cms_id'],
                                'element_type'   => $element_type,
                                //'element_id'     => this is NULL
                                'trid'           => $trid,
                                'language_code'  => $target_language,     
                                'source_language_code' => $source_language
                            );
                            $wpdb->insert($wpdb->prefix . 'icl_translations', $recover);
                        }
                    }
                }
            }        
            
            // Do a check to see if the icl_translation_status is consistant.
            // There was a problem with the cancel logic leaving it in a status where
            // Translations couldn't be sent.
            
            global $iclTranslationManagement;

            $res = $wpdb->get_results($wpdb->prepare("
                SELECT rid, status, needs_update, md5, translation_package
                FROM {$wpdb->prefix}icl_translation_status"
                ));
            foreach($res as $row){
                if ($row->status == ICL_TM_NOT_TRANSLATED || $row->needs_update == 1) {
                    
                    $tpack = unserialize($row->translation_package);
                    $original_id = $tpack['contents']['original_id']['data'];
                
                    $post_md5 = $iclTranslationManagement->post_md5($original_id);
                    
                    if ($post_md5 == $row->md5) {
                        // The md5 shouldn't be the same if it's not translated or needs update.
                        // Add a dummy md5 and mark it as needs_update.
                        $data = array('needs_update' => 1, 'md5' => 'XXXX');
                        $wpdb->update($wpdb->prefix.'icl_translation_status', $data, array('rid'=>$row->rid));
                    }
                }
            }
                
            exit;
            //break; 
            
          case 'icl_cms_id_fix':  
            $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);                                
            
            $p = $wpdb->get_row("SELECT t.* FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_translation_status s ON t.translation_id=s.translation_id
                WHERE t.element_type LIKE 'post\\_%' AND t.source_language_code IS NOT NULL AND s.translation_service='icanlocalize' LIMIT {$_REQUEST['offset']}, 1");
            if(!empty($p)){
                
                $original_id = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", $p->trid));
                if($p->element_type=='post_page'){
                    $permalink   = get_option('home') . '?page_id=' . $original_id;
                }else{
                    $permalink   = get_option('home') . '?p=' . $original_id;    
                }
                $_lang_details = $sitepress->get_language_details($p->source_language_code);
                $from_language = ICL_Pro_Translation::server_languages_map($_lang_details['english_name']); 
                $_lang_details = $sitepress->get_language_details($p->language_code);
                $to_language = ICL_Pro_Translation::server_languages_map($_lang_details['english_name']); 
                $cms_id = sprintf('%s_%d_%s_%s', preg_replace('#^post_#','',$p->element_type), $original_id, $p->source_language_code, $p->language_code);
                
                $ret = $iclq->update_cms_id(compact('permalink', 'from_language', 'to_language', 'cms_id'));                    
                
                if($ret != $cms_id && $iclq->error()){
                    echo json_encode(array('errors'=>1, 'message'=>$iclq->error(), 'cont'=>0));
                }else{
                    echo json_encode(array('errors'=>0, 'message'=>'OK', 'cont'=>1));
                }
                
            }else{
                echo json_encode(array('errors'=>0, 'message'=>__('Done', 'sitepress'), 'cont'=>0));
            }
            
            exit;
            //break; 

        case 'icl_cleanup':
            global $sitepress, $wpdb, $wp_post_types;
            $post_types = array_keys($wp_post_types);
            foreach($post_types as $pt){
                $types[] = 'post_' . $pt;
            }
            /*
             * Messed up on 2.0 upgrade
             */
            // fix source_language_code
            // all source documents must have null
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}icl_translations SET source_language_code = NULL
                WHERE element_type IN('".join("','", $types)."') AND source_language_code = '' AND language_code='%s'", $sitepress->get_default_language()));
            // get translated documents with missing source language
            $res = $wpdb->get_results($wpdb->prepare("
                SELECT translation_id, trid, language_code
                FROM {$wpdb->prefix}icl_translations
                WHERE (source_language_code = '' OR source_language_code IS NULL)
                    AND element_type IN('".join("','", $types)."')
                    AND language_code <> %s
                    ", $sitepress->get_default_language()
                ));
            foreach($res as $row){
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}icl_translations SET source_language_code = '%s' WHERE translation_id=%d", $sitepress->get_default_language(), $row->translation_id));
            }
            break;
    }
}
/* DEBUG ACTION */

$icl_tables = array(
    $wpdb->prefix . 'icl_languages',
    $wpdb->prefix . 'icl_languages_translations',
    $wpdb->prefix . 'icl_translations',
    $wpdb->prefix . 'icl_translation_status',    
    $wpdb->prefix . 'icl_translate_job',    
    $wpdb->prefix . 'icl_translate',    
    $wpdb->prefix . 'icl_locale_map',
    $wpdb->prefix . 'icl_flags',
    //$wpdb->prefix . 'icl_content_status',
    //$wpdb->prefix . 'icl_core_status',
    //$wpdb->prefix . 'icl_node',
    $wpdb->prefix . 'icl_strings',
    $wpdb->prefix . 'icl_string_translations',
    $wpdb->prefix . 'icl_string_status',
    $wpdb->prefix . 'icl_string_positions',
    $wpdb->prefix . 'icl_cms_nav_cache',
    $wpdb->prefix . 'icl_message_status',
    $wpdb->prefix . 'icl_reminders',    
);

if( (isset($_POST['icl_reset_allnonce']) && $_POST['icl_reset_allnonce']==wp_create_nonce('icl_reset_all'))){
    if($_POST['icl-reset-all']=='on'){
        foreach($icl_tables as $icl_table){
            mysql_query("DROP TABLE " . $icl_table);
        }
        delete_option('icl_sitepress_settings');
        delete_option('icl_sitepress_version');
        delete_option('_icl_cache');
        delete_option('WPLANG');                
        deactivate_plugins(basename(ICL_PLUGIN_PATH) . '/sitepress.php');
        $ra = get_option('recently_activated');
        $ra[basename(ICL_PLUGIN_PATH) . '/sitepress.php'] = time();
        update_option('recently_activated', $ra);        
        echo '<script type="text/javascript">location.href=\''.admin_url('plugins.php?deactivate=true').'\'</script>';
    }
}                                    


?>
<div class="wrap">
    <div id="icon-options-general" class="icon32 icon32_adv" style="background: transparent url(<?php echo ICL_PLUGIN_URL; ?>/res/img/icon_adv.png) no-repeat;"><br /></div>
    <h2><?php echo __('Troubleshooting', 'sitepress') ?></h2>    
    <?php if(isset($_GET['message'])):?>
    <div class="updated message fade"><p>
    <?php echo $_GET['message'];?>
    </p></div>
    <?php endif?>
    <?php
    /*
    foreach($icl_tables as $icl_table){
        echo '<a href="#'.$icl_table.'_anch">'.$icl_table.'</a> | ';
    }
    */
    echo '<a href="#wpml-settings">'.__('WPML Settings', 'sitepress').'</a>';
    
    /* 
    foreach($icl_tables as $icl_table){
        echo '<h3  id="'.$icl_table.'_anch" onclick="jQuery(\'#'.$icl_table.'\').toggle(); jQuery(\'#'.$icl_table.'_arrow_up\').toggle(); jQuery(\'#'.$icl_table.'_arrow_dn\').toggle();" style="cursor:pointer">'.$icl_table.'&nbsp;&nbsp;<span id="'.$icl_table.'_arrow_up" style="display:none">&uarr;</span><span id="'.$icl_table.'_arrow_dn">&darr;</span></h3>';        
        if(strtolower($wpdb->get_var("SHOW TABLES LIKE '{$icl_table}'")) != strtolower($icl_table)){
            echo '<p class="error">'.__('Not found!', 'sitepress').'</p>';
        }else{
            $results = $wpdb->get_results("DESCRIBE {$icl_table}", ARRAY_A);
            $keys = array_keys($results[0]);
            ?>
            <table class="widefat">
                <thead>
                    <tr>
                    <?php foreach($keys as $k): ?><th width="<?php echo floor(100/count($keys)) ?>%"><?php echo $k ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($results as $r):?>
                    <tr>
                        <?php foreach($keys as $k): ?><td><?php echo $r[$k] ?></td><?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            <tbody>
            </table>
            <?php
            echo '<span id="'.$icl_table.'" style="display:none">';    
            $results = $wpdb->get_results("SELECT * FROM {$icl_table}", ARRAY_A);
            echo '<textarea style="font-size:10px;width:100%" wrap="off" rows="8" readonly="readonly">';
            $inc = 0;
            foreach((array)$results as $res){
                if($inc==0){
                    $columns = array_keys($res);
                    $columns = array_map('__custom_csv_escape', $columns);
                    echo implode(",", $columns) . PHP_EOL;;
                }
                $inc++;
                $res = array_map('__custom_csv_escape', $res);
                echo implode(",", $res) . PHP_EOL;
            }
            echo '</textarea>';
            echo '</span>';        
        }        
        
    }
    
    function __custom_csv_escape($s){
        $s = "&#34;". str_replace('"','&#34;',addslashes($s)) . "&#34;";
        return $s;
    } 
    */
                            
    echo '<br /><hr /><h3 id="wpml-settings"> ' . __('WPML settings', 'sitepress') . '</h3>';
    echo '<textarea style="font-size:10px;width:100%" wrap="off" rows="16" readonly="readonly">';
    ob_start();
    print_r($sitepress->get_settings());
    $ob = ob_get_contents();
    ob_end_clean();
    echo htmlspecialchars($ob);
    echo '</textarea>';
    
    ?> 
    
    <script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery('#icl_torubleshooting_more_options').submit(iclSaveForm);
    })
    </script>
    <br clear="all" /><br />
    <div class="icl_cyan_box" >           
    <h3><?php _e('More options', 'sitepress')?></h3>
    <form name="icl_torubleshooting_more_options" id="icl_torubleshooting_more_options" action="">
    <label><input type="checkbox" name="troubleshooting_options[raise_mysql_errors]" value="1" <?php 
        if($sitepress_settings['troubleshooting_options']['raise_mysql_errors']): ?>checked="checked"<?php endif; ?>/>&nbsp;<?php 
        _e('Raise mysql errors on XML-RPC calls', 'sitepress')?></label>
    <br />
    <label><input type="checkbox" name="troubleshooting_options[http_communication]" value="1" <?php 
            if($sitepress_settings['troubleshooting_options']['http_communication']): ?>checked="checked"<?php endif; ?>/>&nbsp;<?php 
            _e('Communicate with ICanLocalize using HTTP instead of HTTPS', 'sitepress')?></label>        
                
    <p>
        <input class="button" name="save" value="<?php echo __('Apply','sitepress') ?>" type="submit" />
        <span class="icl_ajx_response" id="icl_ajx_response"></span>
    </p>    
    </form>
    </div>
    
    <br clear="all" /><br />
    <script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery('#icl_remove_ghost').click(function(){
                jQuery(this).attr('disabled', 'disabled');
                jQuery(this).after(icl_ajxloaderimg);
                jQuery.post(location.href + '&debug_action=ghost_clean&nonce=<?php echo wp_create_nonce('ghost_clean'); ?>', function(){                    
                    jQuery('#icl_remove_ghost').removeAttr('disabled');
                    alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
                    jQuery('#icl_remove_ghost').next().fadeOut();
                    
                });
            })            
            jQuery('#icl_sync_jobs').click(function(){
                jQuery(this).attr('disabled', 'disabled');
                jQuery(this).after(icl_ajxloaderimg);
                jQuery.post(location.href + '&debug_action=icl_sync_jobs&nonce=<?php echo wp_create_nonce('icl_sync_jobs'); ?>', function(){                    
                    jQuery('#icl_sync_jobs').removeAttr('disabled');
                    alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
                    jQuery('#icl_sync_jobs').next().fadeOut();
                    
                });
            })
            jQuery('#icl_cleanup').click(function(){
                jQuery(this).attr('disabled', 'disabled');
                jQuery(this).after(icl_ajxloaderimg);
                jQuery.post(location.href + '&debug_action=icl_cleanup&nonce=<?php echo wp_create_nonce('icl_cleanup'); ?>', function(){
                    jQuery('#icl_cleanup').removeAttr('disabled');
                    alert('<?php echo esc_js(__('Done', 'sitepress')) ?>');
                    jQuery('#icl_cleanup').next().fadeOut();

                });
            })
            
            function _icl_sync_cms_id(offset){
                jQuery('#icl_cms_id_fix_prgs_cnt').html(offset+1);
                jQuery.ajax({
                    type: "POST", 
                    url: location.href + '&debug_action=icl_cms_id_fix&nonce=<?php echo wp_create_nonce('icl_cms_id_fix'); ?>&offset='+offset, 
                    data: 'debug_action=icl_cms_id_fix&nonce=<?php echo wp_create_nonce('icl_cms_id_fix'); ?>&offset='+offset,
                    dataType: 'json',
                    success: function(msg){
                            if(msg.errors > 0){
                                alert(msg.message);
                                jQuery('#icl_cms_id_fix').removeAttr('disabled');                            
                                jQuery('#icl_cms_id_fix').next().fadeOut();
                                jQuery('#icl_cms_id_fix_prgs').fadeOut();                                
                            }else{
                                offset++;
                                if(msg.cont){
                                    _icl_sync_cms_id(offset);    
                                }else{
                                    alert(msg.message);    
                                    jQuery('#icl_cms_id_fix').removeAttr('disabled');                            
                                    jQuery('#icl_cms_id_fix').next().fadeOut();
                                    jQuery('#icl_cms_id_fix_prgs').fadeOut();
                                }
                            }
                        }
                });
            }
            
            jQuery('#icl_cms_id_fix').click(function(){
                jQuery(this).attr('disabled', 'disabled');
                jQuery(this).after(icl_ajxloaderimg);                
                jQuery('#icl_cms_id_fix_prgs').fadeIn();                
                _icl_sync_cms_id(0);
            })
            
            
        })
    </script>
    <div class="icl_cyan_box" >           
    <h3><?php _e('Clean up', 'sitepress')?></h3>
    <p class="error" style="padding:6px;"><?php _e('Please make backup of your database before using this.', 'sitepress') ?></p>    
    <p>
    <input id="icl_remove_ghost" type="button" class="button-secondary" value="<?php _e('Remove ghost entries from the translation tables', 'sitepress')?>" /><br />
    <small style="margin-left:10px;">Removes entries from the WPML tables that are not linked properly. Cleans the table off entries left over upgrades, bug fixes or undetermined factors.</small>
    </p>
    <?php if($sitepress_settings['site_id'] && $sitepress_settings['access_key']):?>
    <p>
    <input id="icl_sync_jobs" type="button" class="button-secondary" value="<?php _e('Synchronize translation jobs with ICanLocalize', 'sitepress')?>" /><br />
    <small style="margin-left:10px;">Fixes links between translation entries in the database and ICanLocalize.</small>
    </p>
    <p>
    <input id="icl_cms_id_fix" type="button" class="button-secondary" value="<?php _e('CMS ID fix', 'sitepress')?>" />    
    <span id="icl_cms_id_fix_prgs" style="display: none;"><?php printf('fixing %s/%d', '<span id="icl_cms_id_fix_prgs_cnt">0</span>', $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}icl_translations t JOIN {$wpdb->prefix}icl_translation_status s ON t.translation_id=s.translation_id WHERE t.element_type LIKE 'post\\_%' AND t.source_language_code IS NOT NULL AND s.translation_service='icanlocalize'")) ?></span><br />
    <small style="margin-left:10px;">Updates translation in progress with new style identifiers for documents. The new identifiers depend on the document being translated and the languages so it's not possible to get out of sync when translations are being deleted locally.</small>
    </p>
    <?php endif; ?>
    <p>
    <input id="icl_cleanup" type="button" class="button-secondary" value="<?php _e('General clean up', 'sitepress')?>" /><br />
    <small style="margin-left:10px;">Sets source language to NULL in the icl_translations table. </small>
    </p>
    

    </div>    
      
    <br clear="all" /><br />
    <div class="icl_cyan_box" >       
    <h3><?php _e('Reset PRO translation configuration', 'sitepress')?></h3>
    <p class="error" style="padding:6px;"><?php _e("Resetting your ICanLocalize account will interrupt any translation jobs that you have in progress. Only use this function if your ICanLocalize account doesn't include any jobs, or if the account was deleted.", 'sitepress'); ?></p>
    <a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH)?>/menu/troubleshooting.php&amp;debug_action=reset_pro_translation_configuration&amp;nonce=<?php echo wp_create_nonce('reset_pro_translation_configuration')?>" class="button"><?php _e('Reset PRO translation configuration', 'sitepress');?></a>
    </div>
    
    <br clear="all" /><br />
    <div class="icl_cyan_box" >       
    <h3><?php _e('Database dump', 'sitepress')?></h3>
    <a class="button" href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/troubleshooting.php&amp;icl_action=dbdump"><?php _e('Download', 'sitepress') ?></a>
    </div>
    
    <br clear="all" /><br />
    <div class="icl_cyan_box" >    
    <a name="icl-connection-test"></a>
    <h3><?php _e('ICanLocalize connection test', 'sitepress')?></h3>
    <?php if(isset($_GET['icl_action']) && $_GET['icl_action']=='icl-connection-test'): ?>
    <?php 
        $icl_query = new ICanLocalizeQuery();        
        if(isset($_GET['data'])){
            $user = unserialize(base64_decode($_GET['data']));
        }else{                
            $user['create_account'] = 1;
            $user['anon'] = 1;
            $user['platform_kind'] = 2;
            $user['cms_kind'] = 1;
            $user['blogid'] = $wpdb->blogid?$wpdb->blogid:1;
            $user['url'] = get_option('siteurl');
            $user['title'] = get_option('blogname');
            $user['description'] = $sitepress_settings['icl_site_description'];
            $user['is_verified'] = 1;                
           if(defined('ICL_AFFILIATE_ID') && defined('ICL_AFFILIATE_KEY')){
                $user['affiliate_id'] = ICL_AFFILIATE_ID;
                $user['affiliate_key'] = ICL_AFFILIATE_KEY;
            }
            $user['interview_translators'] = $sitepress_settings['interview_translators'];
            $user['project_kind'] = 2;
            $user['pickup_type'] = intval($sitepress_settings['translation_pickup_method']);
            $notifications = 0;
            if ( $sitepress_settings['icl_notify_complete']){
                $notifications += 1;
            }
            if ( $sitepress_settings['alert_delay']){
                $notifications += 2;
            }
            $user['notifications'] = $notifications;
            $user['ignore_languages'] = 0;
            $user['from_language1'] = isset($_GET['lang_from']) ? $_GET['lang_from'] : 'English';            
            $user['to_language1'] = isset($_GET['lang_to']) ? $_GET['lang_to'] : 'French';
        }
        
        define('ICL_DEB_SHOW_ICL_RAW_RESPONSE', true);
        $resp = $icl_query->createAccount($user);                
        echo '<textarea style="width:100%;height:400px;font-size:9px;">';
        if (defined('ICL_API_ENDPOINT')) {
            echo ICL_API_ENDPOINT . "\r\n\r\n";
        }
        echo __('Data', 'sitepress') . "\n----------------------------------------\n" .
            print_r($user, 1) . 
            __('Response', 'sitepress') . "\n----------------------------------------\n" .
            print_r($resp, 1) . 
        '</textarea>';
                
    ?>
        
    <?php endif; ?>
    <a class="button" href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/troubleshooting.php&ts=<?php echo time()?>&icl_action=icl-connection-test#icl-connection-test"><?php _e('Connect', 'sitepress') ?></a>
    </div>
        
    
    <br clear="all" /><br />
    <div class="icl_cyan_box" >       
    <?php    
    echo '<h3 id="wpml-settings"> ' . __('Reset', 'sitepress') . '</h3>';
    echo '<form method="post" onsubmit="return confirm(\''.__('Are you sure you want to reset all languages data? This operation cannot be reversed.', 'sitepress').'\')">';
    wp_nonce_field('icl_reset_all','icl_reset_allnonce');
    echo '<p class="error" style="padding:6px;">' . __("All translations you have sent to ICanLocalize will be lost if you reset WPML's data. They cannot be recovered later.", 'sitepress') 
        . '</p>';
    echo '<label><input type="checkbox" name="icl-reset-all" onchange="if(this.checked) jQuery(\'#reset-all-but\').removeAttr(\'disabled\'); else  jQuery(\'#reset-all-but\').attr(\'disabled\',\'disabled\');" /> ' . __('I am about to reset all language data.', 'sitepress') . '</label><br /><br />';
    echo '<input id="reset-all-but" type="submit" disabled="disabled" class="button-primary" value="'.__('Reset all language data and deactivate WPML', 'sitepress').'" />';    
    echo '</form>';
    ?>
    </div>
    
    <?php do_action('icl_menu_footer'); ?>
</div>