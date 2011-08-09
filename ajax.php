<?php
// included from Sitepress::ajax_setup
//

global $wpdb;


if (!isset($_POST['unit-test'])) {
    @header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
    header("Cache-Control: no-cache, must-revalidate"); 
    header("Expires: Sat, 16 Aug 1980 05:00:00 GMT"); 
}

switch($_REQUEST['icl_ajx_action']){
    case 'health_check':
        // force error
        // header('Status: 500'); echo 'Boooo!';        
        $iclsettings['ajx_health_checked'] = 1;
        $this->save_settings($iclsettings);
        break;
    case 'set_active_languages':
        $resp = array();
        $old_active_languages_count = count($this->get_active_languages($lang_codes));
        $lang_codes = explode(',',$_POST['langs']);
        if($this->set_active_languages($lang_codes)){                    
            $resp[0] = 1;
            $active_langs = $this->get_active_languages();
            $iclresponse ='';
            $default_categories = $this->get_default_categories();            
            $default_category_main = $wpdb->get_var("SELECT name FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tx ON t.term_id=tx.term_id
                WHERE term_taxonomy_id='{$default_categories[$this->get_default_language()]}' AND taxonomy='category'");            
            $default_category_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id={$default_categories[$this->get_default_language()]} AND element_type='tax_category'");
            foreach($active_langs as $lang){
                $is_default = ($this->get_default_language()==$lang['code']);
                $iclresponse .= '<li ';
                if($is_default) $iclresponse .= 'class="default_language"';
                $iclresponse .= '><label><input type="radio" name="default_language" value="' . $lang['code'] .'" ';
                if($is_default) $iclresponse .= 'checked="checked"';
                $iclresponse .= '>' . $lang['display_name'];
                if($is_default) $iclresponse .= '('. __('default','sitepress') . ')';
                $iclresponse .= '</label></li>';                
                
                if(!in_array($lang['code'],array_keys($default_categories))){
                   // Create category for language
                   // add it to defaults                   
                   $tr_cat = $default_category_main . ' @' . $lang['code'];
                   $tr_cat_san = sanitize_title_with_dashes($default_category_main . '-' . $lang['code']); 
                   $term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->terms} WHERE name='{$tr_cat}'");
                   if(!$term_id){
                       $wpdb->query("INSERT INTO {$wpdb->terms}(name, slug) VALUES('{$tr_cat}','{$tr_cat_san}') ON DUPLICATE KEY UPDATE slug = CONCAT(slug,'".rand(1,1000)."')");
                       $term_id = mysql_insert_id();                       
                   }
                   $term_taxonomy_id = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id={$term_id} AND taxonomy='category'");
                   if(!$term_taxonomy_id){
                        $wpdb->query("INSERT INTO {$wpdb->term_taxonomy}(term_id, taxonomy) VALUES('{$term_id}','category')") ;
                        $term_taxonomy_id = mysql_insert_id();                        
                   }
                   $default_categories[$lang['code']] = $term_taxonomy_id;                   
                   $wpdb->query("INSERT INTO {$wpdb->prefix}icl_translations(element_id,element_type,trid,language_code,source_language_code) 
                    VALUES('{$term_taxonomy_id}','tax_category','{$default_category_trid}','{$lang['code']}','{$this->get_default_language()}')");
                }
            } 
            $this->set_default_categories($default_categories) ;                        
            $iclresponse .= $default_blog_category;
            $resp[1] = $iclresponse;
            // response 1 - blog got more than 2 languages; -1 blog reduced to 1 language; 0 - no change            
            if(count($lang_codes) > 1){
                if(!$iclsettings['setup_complete']){
                    $resp[2] = -2; //don't refresh the page and enable 'next'
                }else{
                    $resp[2] = 1;
                }
            }elseif($old_active_languages_count > 1 && count($lang_codes) < 2){
                if(!$iclsettings['setup_complete']){
                    $resp[2] = -3; //don't refresh the page and disable 'next'
                }else{
                    $resp[2] = -1;
                }
            }else{
                if(!$iclsettings['setup_complete']){
                    $resp[2] = -3; //don't refresh the page and disable 'next'
                }else{
                    $resp[2] = 0;
                }
            }  
            if(count($active_langs) > 1){
                $iclsettings['dont_show_help_admin_notice'] = true;
                $this->save_settings($iclsettings);
            }
        }else{
            $resp[0] = 0;
        }
        
        if(!$iclsettings['setup_complete']){
            $iclsettings['setup_wizard_step'] = 3;
            $this->save_settings($iclsettings);
        }
        
        echo join('|',$resp);
        do_action('icl_update_active_languages');
        break;
    case 'set_default_language':
        $previous_default = $this->get_default_language();
        if($response = $this->set_default_language($_POST['lang'])){
            echo '1|'.$previous_default.'|';
        }else{
            echo'0||' ;
        }
        if(1 === $response){
            echo __('Wordpress language file (.mo) is missing. Keeping existing display language.', 'sitepress');
        }
        break;
    case 'save_language_pairs':                
        $this->save_language_pairs();
        
        $ret = update_icl_account();
        if($ret){
            echo '1| ('. __('Not updated on ICanLocalize: ', 'sitepress') . $ret . ')';
            break;
        }
        
        // success, return status of language pairs from the website
        
        $iclsettings = $this->get_settings();
        $this->get_icl_translator_status($iclsettings);
        $this->save_settings($iclsettings);

        $langs = $iclsettings['language_pairs'];
        
        $active_languages = $this->get_active_languages();
        
        $result = '';
        foreach ($langs as $from_lang => $targets) {
            foreach($targets as $to_lang => $to_status) {
                if ($to_status) {
                    $result .= $from_lang . '~' . $to_lang . '~' . $this->get_language_status_text($from_lang, $to_lang) . "\n";
                }
            }
        }
        echo "1|" . $result;
        break;
    case 'save_site_description':                
        
        $iclsettings['icl_site_description'] = $_POST['icl_description'];
        $this->save_settings($iclsettings);
        $ret = update_icl_account();
        if($ret){
            echo '1| ('. __('Not updated on ICanLocalize: ', 'sitepress') . $ret . ')';
            break;
        }
        
        echo '1|' . __('Your site description has been saved.', 'sitepress');
        break;
    case 'do_account_transfer':
        
        if ($_POST['icl_new'] != '1') {
            $_POST['user']['email'] = $_POST['user']['email2'];
        }
        
        if ($_POST['user']['email'] == '') {
            echo '0|' . __('Please enter an email address');
            break;
        }
        if (!$this->transfer_icl_account($_POST['icl_new'] == '1')) {
            echo '0|' . $_POST['icl_form_errors'];
        } else {
            $iclsettings['icl_account_email'] = $_POST['user']['email'];
            $iclsettings['support_icl_account_email'] = $_POST['user']['email'];
            $this->save_settings($iclsettings);
            
            if ($_POST['icl_new'] == '1') {
                echo '1|' . sprintf(__('An account for %s has been created at ICanLocalize. This site has been transferred to the new account.', 'sitepress'), $_POST['user']['email']);
            } else {
                echo '1|' . sprintf(__('This site has been transferred to the ICanLocalize account of %s.', 'sitepress'), $_POST['user']['email']);
            }
        }
        break;
    
    case 'toggle_content_translation':
        $redir = '';
        $iclsettings['enable_icl_translations'] = $_POST['new_val'];
        if ($iclsettings['enable_icl_translations'] == 0) {
            $settings = $this->get_settings();
            
            if (!$settings['content_translation_setup_complete']) {
                // the wizard wasn't complete so set back to step 1.
                $iclsettings['content_translation_languages_setup'] = false;
                $iclsettings['content_translation_setup_wizard_step'] = 1;
            }
        }

        // finalize wizard if this is the last step        
        if(isset($_POST['wizard']) && $_POST['wizard']){
            $iclsettings['setup_wizard_step'] = 0;
            $iclsettings['setup_complete'] = 1;
            $active_languages = $this->get_active_languages();
            $default_language = $this->get_default_language();
            foreach($active_languages as $al){
                if($al != $default_language){
                    if($this->_validate_language_per_directory($al)){
                        $iclsettings['language_negotiation_type'] = 1;
                    }            
                }
            }            
            if($_POST['new_val']){
                $redir = 'admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/content-translation.php';
            }else{
                $iclsettings['promote_wpml'] = 1;
            }
        }
        
        $this->save_settings($iclsettings);
        echo '1|'.$redir;
        break;
    case 'icl_tdo_options':
        $iclsettings['translated_document_status'] = intval($_POST['icl_translated_document_status']);
        $this->save_settings($iclsettings);
        echo '1|';
       break;
    case 'icl_plugins_texts':
        update_option('icl_plugins_texts_enabled', $_POST['icl_plugins_texts_enabled']);
        echo '1|';
        break;
    case 'icl_save_language_negotiation_type':
        $iclsettings['language_negotiation_type'] = $_POST['icl_language_negotiation_type'];
        if($_POST['language_domains']){
            $iclsettings['language_domains'] = $_POST['language_domains'];
        }        
        $this->save_settings($iclsettings);
        echo 1;
        break;
    case 'icl_save_language_switcher_options':
        if(isset($_POST['icl_language_switcher_sidebar'])){
            global $wp_registered_widgets, $wp_registered_sidebars;
            $swidgets = wp_get_sidebars_widgets();            
            if(empty($swidgets)){
                $sidebars = array_keys($wp_registered_sidebars);    
                foreach($sidebars as $sb){
                    $swidgets[$sb] = array();
                }
            }
            foreach($swidgets as $k=>$v){
                $key = array_search('language-selector',$swidgets[$k]);
                if(false !== $key && $k !== $_POST['icl_language_switcher_sidebar']){
                    unset($swidgets[$k][$key]);
                }elseif($k==$_POST['icl_language_switcher_sidebar'] && !in_array('language-selector',$swidgets[$k])){
                    $swidgets[$k] = array_reverse($swidgets[$k], false);
                    array_push($swidgets[$k],'language-selector');
                    $swidgets[$k] = array_reverse($swidgets[$k], false);
                }
            }            
            wp_set_sidebars_widgets($swidgets);
        }
        $iclsettings['icl_lso_link_empty'] = intval($_POST['icl_lso_link_empty']);
        $iclsettings['icl_lso_flags'] = intval($_POST['icl_lso_flags']);
        $iclsettings['icl_lso_native_lang'] = intval($_POST['icl_lso_native_lang']);
        $iclsettings['icl_lso_display_lang'] = intval($_POST['icl_lso_display_lang']);
        
        if(!$iclsettings['setup_complete']){
            $iclsettings['setup_wizard_step'] = 3;
            $iclsettings['setup_complete'] = 1;
        }

        
        /*
        if(!$iclsettings['setup_complete']){
            $iclsettings['setup_wizard_step'] = 0;
            $iclsettings['setup_complete'] = 1;
            $active_languages = $this->get_active_languages();
            $default_language = $this->get_default_language();
            foreach($active_languages as $al){
                if($al != $default_language){
                    if($this->_validate_language_per_directory($al)){
                        $iclsettings['language_negotiation_type'] = 1;
                    }            
                    break;
                }
            }            
        }
        */
        
        if(isset($_POST['icl_lang_sel_config'])){
            $iclsettings['icl_lang_sel_config'] = $_POST['icl_lang_sel_config'];
        }
        
        if(isset($_POST['icl_lang_sel_footer_config'])){
            $iclsettings['icl_lang_sel_footer_config'] = $_POST['icl_lang_sel_footer_config'];
        }
        
        if (isset($_POST['icl_lang_sel_type']))
            $iclsettings['icl_lang_sel_type'] = $_POST['icl_lang_sel_type'];
        
        if (isset($_POST['icl_lang_sel_footer']))
            $iclsettings['icl_lang_sel_footer'] = 1;
        else $iclsettings['icl_lang_sel_footer'] = 0;
        
        if (isset($_POST['icl_post_availability']))
            $iclsettings['icl_post_availability'] = 1;
        else $iclsettings['icl_post_availability'] = 0;
        
        if (isset($_POST['icl_post_availability_position']))
            $iclsettings['icl_post_availability_position'] = $_POST['icl_post_availability_position'];
        
        if (isset($_POST['icl_post_availability_text']))
            $iclsettings['icl_post_availability_text'] = $_POST['icl_post_availability_text'];
        
        $iclsettings['icl_widget_title_show'] = (isset($_POST['icl_widget_title_show'])) ? 1 : 0;
        $iclsettings['icl_additional_css'] = $_POST['icl_additional_css'];
        
        if(!$iclsettings['icl_lso_flags'] && !$iclsettings['icl_lso_native_lang'] && !$iclsettings['icl_lso_display_lang']){
            echo '0|';
            echo __('At least one of the language switcher style options needs to be checked', 'sitepress');    
        }else{
            $this->save_settings($iclsettings);    
            echo 1;
        }                
        break;   
    case 'icl_admin_language_options':
        $iclsettings['admin_default_language'] = $_POST['icl_admin_default_language'];
        $this->save_settings($iclsettings);
        $this->icl_locale_cache->clear();
        echo 1; 
        break;    
    case 'icl_lang_more_options':
        $iclsettings['hide_translation_controls_on_posts_lists'] = !$_POST['icl_translation_controls_on_posts_lists'];
        $this->save_settings($iclsettings);
        echo 1; 
        break;
    case 'icl_blog_posts':
        $iclsettings['show_untranslated_blog_posts'] = $_POST['icl_untranslated_blog_posts'];
        $this->save_settings($iclsettings);
        echo 1; 
        break;                
    case 'icl_page_sync_options':
        $iclsettings['sync_page_ordering'] = intval($_POST['icl_sync_page_ordering']);        
        $iclsettings['sync_page_parent'] = intval($_POST['icl_sync_page_parent']);            
        $iclsettings['sync_page_template'] = intval($_POST['icl_sync_page_template']);            
        $iclsettings['sync_comment_status'] = intval($_POST['icl_sync_comment_status']);            
        $iclsettings['sync_ping_status'] = intval($_POST['icl_sync_ping_status']);            
        $iclsettings['sync_sticky_flag'] = intval($_POST['icl_sync_sticky_flag']);            
        $iclsettings['sync_private_flag'] = intval($_POST['icl_sync_private_flag']);            
        $iclsettings['sync_delete'] = intval($_POST['icl_sync_delete']);            
        $this->save_settings($iclsettings);
        echo 1; 
        break;        
    case 'language_domains':
        $active_languages = $this->get_active_languages();
        $default_language = $this->get_default_language();
        $iclsettings = $this->get_settings();
        $language_domains = $iclsettings['language_domains'];        
        echo '<table class="language_domains">';
        foreach($active_languages as $lang){
            $home = get_option('home');
            if($lang['code']!=$default_language){
                if(isset($language_domains[$lang['code']])){
                    $sugested_url = $language_domains[$lang['code']];
                }else{
                    $url_parts = parse_url($home);                    
                    $exp = explode('.' , $url_parts['host']);                    
                    if(count($exp) < 3){
                        $sugested_url = $url_parts['scheme'] . '://' . $lang['code'] . '.' . $url_parts['host'] . $url_parts['path'];    
                    }else{
                        array_shift($exp);                        
                        $sugested_url = $url_parts['scheme'] . '://' . $lang['code'] . '.' . join('.' , $exp) . $url_parts['path'];    
                    }            
                }
            }
            
            echo '<tr>';
            echo '<td>' . $lang['display_name'] . '</td>';
            if($lang['code']==$default_language){
                echo '<td id="icl_ln_home">' . $home . '</td>';
                echo '<td>&nbsp;</td>';
                echo '<td>&nbsp;</td>';
            }else{
                echo '<td><input type="text" id="language_domain_'.$lang['code'].'" name="language_domains['.$lang['code'].']" value="'.$sugested_url.'" size="40" /></td>';
                echo '<td id="icl_validation_result_'.$lang['code'].'"><label><input class="validate_language_domain" type="checkbox" name="validate_language_domains[]" value="'.$lang['code'].'" checked="checked" /> ' . __('Validate on save', 'sitepress') . '</label></td><td><span id="ajx_ld_'.$lang['code'].'"></span></td>';
            }                        
            echo '</tr>';
        }
        echo '</table>';
        break;
    case 'validate_language_domain':
        if(false === strpos($_POST['url'],'?')){$url_glue='?';}else{$url_glue='&';}
        $url = $_POST['url'] . $url_glue . '____icl_validate_domain=1';
        $client = new WP_Http();
        $response = $client->request($url, 'timeout=15');
        if(!is_wp_error($response) && ($response['response']['code']=='200') && ($response['body'] == '<!--'.get_option('home').'-->')){
            echo 1;
        }else{
            echo 0;
        }                
        break;
    case 'icl_navigation_form':   
        $iclsettings = $this->get_settings();
        $iclsettings['modules']['cms-navigation']['page_order'] = $_POST['icl_navigation_page_order'];
        $iclsettings['modules']['cms-navigation']['show_cat_menu'] = $_POST['icl_navigation_show_cat_menu'];
        if($_POST['icl_navigation_cat_menu_title']){
            $iclsettings['modules']['cms-navigation']['cat_menu_title'] = stripslashes($_POST['icl_navigation_cat_menu_title']);
            icl_register_string('WPML', 'Categories Menu', stripslashes($_POST['icl_navigation_cat_menu_title']));
        }        
        $iclsettings['modules']['cms-navigation']['cat_menu_page_order'] = $_POST['icl_navigation_cat_menu_page_order'];
        $iclsettings['modules']['cms-navigation']['cat_menu_contents'] = $_POST['icl_blog_menu_contents'];
        $iclsettings['modules']['cms-navigation']['heading_start'] = stripslashes($_POST['icl_navigation_heading_start']);
        $iclsettings['modules']['cms-navigation']['heading_end'] = stripslashes($_POST['icl_navigation_heading_end']);

        $iclsettings['modules']['cms-navigation']['cache'] = $_POST['icl_navigation_caching'];

        $iclsettings['modules']['cms-navigation']['breadcrumbs_separator'] = stripslashes($_POST['icl_breadcrumbs_separator']);
        
        $this->save_settings($iclsettings);
        
        // clear the cms navigation caches
        $this->icl_cms_nav_offsite_url_cache->clear();
        @mysql_query("TRUNCATE {$wpdb->prefix}icl_cms_nav_cache");
        
        echo '1|';
        break;

    case 'icl_clear_nav_cache':
        // clear the cms navigation caches
        $this->icl_cms_nav_offsite_url_cache->clear();
        @mysql_query("TRUNCATE {$wpdb->prefix}icl_cms_nav_cache");
        echo '1|';
        
            
    case 'send_translation_request':
        global $iclTranslationManagement, $current_user;   
        $post_ids = explode(',',$_POST['post_ids']);
        $target_languages = explode('#', $_POST['target_languages']);
        $post_types = $_POST['icl_post_type'];
        
        get_currentuserinfo();
        $translator_id = isset($_POST['translator_id']) ? $_POST['translator_id'] : $current_user->ID;
        foreach($post_ids as $post_id){            
            
            if(isset($_POST['tn_note_'.$post_id]) && trim($_POST['tn_note_'.$post_id])){
                update_post_meta($post_id, '_icl_translator_note', $_POST['tn_note_'.$post_id]);
            }
            foreach($target_languages as $to_lang){
                $from_lang = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", 
                    $post_id, 'post_'.$post_types[$post_id]));
                $data = array(
                    'translate_from'    => $from_lang,
                    'translate_to'      => array($to_lang=>1),
                    'iclpost'           => array($post_id),
                    'service'           => 'icanlocalize',
                    'iclnonce'          => wp_create_nonce('pro-translation-icl')
                );
                $jd = $iclTranslationManagement->send_jobs($data);
                $resp[] = array(
                    'post_id' => $post_id,
                    'status'  => !empty($jd)
                );
            }
            /*
            $resp[] = array(
                'post_id'=>$post_id, 
                'status'=>$ICL_Pro_Translation->send_post($post_id, $target_languages, $translator_id)
            );
            */
        }
        echo json_encode($resp);
        break;
    case 'get_translator_status':
        if(!$this->icl_account_configured()) break;

        $iclsettings = $this->get_settings();
        
        if(isset($_POST['cache'])) {
            $last_call = $iclsettings['last_get_translator_status_call'];
            if ($time - $last_call < 24 * 60 * 60) {
                break;
          }
        }
        
        $iclsettings['last_get_translator_status_call'] = time();
        $this->get_icl_translator_status($iclsettings);
        $this->save_settings($iclsettings);
                
        echo json_encode($iclsettings['icl_lang_status']);
        break;
    
    case 'get_language_status_text':
    
        if(!$this->icl_account_configured()) break;

        $iclsettings = $this->get_settings();
        
        if(!isset($_POST['cache'])) {
            $iclsettings = $this->get_settings();
            $this->get_icl_translator_status($iclsettings);
            $this->save_settings($iclsettings);
        }
            
        echo '1|' . $_POST['id'] . '|' . $this->get_language_status_text($_POST['from_lang'], $_POST['to_lang']);
        break;
    
    case 'set_post_to_date':
        $nid = (int) $_POST['post_id'];
        $md5 = $wpdb->get_var("SELECT md5 FROM {$wpdb->prefix}icl_node WHERE nid={$nid}");
        $wpdb->query("UPDATE {$wpdb->prefix}icl_content_status SET md5 = '{$md5}' WHERE nid='{$nid}'");
        echo __('Needs update','sitepress');
        echo '|';
        echo __('Complete','sitepress');
        break;    
    
    case 'icl_st_save_translation':
        $icl_st_complete = isset($_POST['icl_st_translation_complete'])?$_POST['icl_st_translation_complete']:ICL_STRING_TRANSLATION_NOT_TRANSLATED;
        if ( get_magic_quotes_gpc() ){
            $_POST = stripslashes_deep( $_POST );         
        }
        echo icl_add_string_translation($_POST['icl_st_string_id'], $_POST['icl_st_language'], stripslashes($_POST['icl_st_translation']), $icl_st_complete);
        echo '|';
        global $icl_st_string_translation_statuses;
        echo $icl_st_string_translation_statuses[icl_update_string_status($_POST['icl_st_string_id'])];
        break;
    case 'icl_st_delete_strings':
        $arr = explode(',',$_POST['value']);
        __icl_unregister_string_multi($arr);
        break;
    case 'icl_st_send_strings':
        $arr = explode(',',$_POST['strings']);
        icl_translation_send_strings($arr, explode('#',$_POST['languages']));
        echo '1';
        break;    
    case 'icl_st_send_strings_all':
        icl_translation_send_untranslated_strings(explode(',',$_POST['languages']));
        echo '1';
        break;    
    case 'icl_save_theme_localization_type':
        $icl_tl_type = (int)$_POST['icl_theme_localization_type'];
        $iclsettings['theme_localization_type'] = $icl_tl_type;
        if($icl_tl_type==1){            
            icl_st_scan_theme_files();
        }
        $this->save_settings($iclsettings);
        echo '1|'.$icl_tl_type;
        break;
    case 'icl_tl_rescan':
        $scan_stats = icl_st_scan_theme_files();                
        
        if($_POST['icl_load_mo']){
            $mo_files = icl_st_get_mo_files(TEMPLATEPATH);
            foreach((array)$mo_files as $m){
                $i = preg_match('#[-]?([a-z_]+)\.mo$#i', $m, $matches);
                if($i && $lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE locale='".$matches[1]."'")){
                    $tr_pairs = icl_st_load_translations_from_mo($m);
                    foreach($tr_pairs as $original=>$translation){
                        foreach($this->settings['st']['theme_localization_domains'] as $tld){
                            $string_id = icl_get_string_id($original, 'theme ' . $tld);                            
                            if($string_id){
                                break;
                            }
                        }                        
                        if(!$wpdb->get_var{"SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE string_id={$string_id} AND language='{$lang}'"}){
                            icl_add_string_translation($string_id, $lang, $translation, ICL_STRING_TRANSLATION_COMPLETE);
                        }
                    }
                }
            }
        }
        
        echo '1|'.$scan_stats;
        break;
    case 'icl_tl_rescan_p':
        set_time_limit(0);
        if(preg_replace('#M$#', '', ini_get('memory_limit')) < 128) ini_set('memory_limit', '128M');        
        $plugins = array();
        foreach((array)$_POST['plugin'] as $plugin){
            $plugins[] = array('file'=>$plugin, 'mu'=>0); // regular plugins
        }
        foreach((array)$_POST['mu-plugin'] as $plugin){
            $plugins[] = array('file'=>$plugin, 'mu'=>1); //mu plugins
        }    
        $scan_stats = '';
        foreach($plugins as $p){
            $plugin = $p['file'];
            
            if(false !== strpos($plugin, '/') && !$p['mu']){
                $plugin = dirname($plugin);
            }
            if($p['mu']){
                $plugin_path = WPMU_PLUGIN_DIR . '/' . $plugin;    
            }else{
                $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;    
            }
            
            $scan_stats .= icl_st_scan_plugin_files($plugin_path);                
            
            if($_POST['icl_load_mo'] && !$p['mu']){
                $mo_files = icl_st_get_mo_files($plugin_path);
                foreach($mo_files as $m){
                    $i = preg_match('#[-]([a-z_]+)\.mo$#i', $m, $matches);
                    if($i && $lang = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_locale_map WHERE locale='".$matches[1]."'")){
                        $tr_pairs = icl_st_load_translations_from_mo($m);
                        foreach($tr_pairs as $original=>$translation){
                            $string_id = icl_get_string_id($original, 'plugin ' . basename($plugin_path));                            
                            if(!$wpdb->get_var{"SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE string_id={$string_id} AND language='{$lang}'"}){
                                icl_add_string_translation($string_id, $lang, $translation, ICL_STRING_TRANSLATION_COMPLETE);
                            }
                        }
                    }
                }
            }
            
        }
        echo '1|' . $scan_stats;
        break;
    
    case 'save_ct_user_pref':
        $users = $wpdb->get_col("SELECT id FROM {$wpdb->users}");
        foreach($users as $uid){
            if(isset($_POST['icl_enable_comments_translation'][$uid])){
                update_usermeta($uid, 'icl_enable_comments_translation', 1);
            }else{
                delete_usermeta($uid, 'icl_enable_comments_translation');
            }
            if(isset($_POST['icl_enable_replies_translation'][$uid])){
                update_usermeta($uid, 'icl_enable_replies_translation', 1);
            }else{
                delete_usermeta($uid, 'icl_enable_replies_translation');
            }            
        }
        echo '1|';
        break;
    case 'get_original_comment':
        $comment_id = $_POST['comment_id'];
        $trid = $this->get_element_trid($comment_id, 'comment');
        $res = $wpdb->get_row("SELECT element_id, language_code FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}' AND element_type='comment' AND element_id <> {$comment_id} ");
        $original_cid = $res->element_id;
        $comment = $wpdb->get_row("SELECT * FROM {$wpdb->comments} WHERE comment_ID={$original_cid}");
        $comment->language_code = $res->language_code;
        if($res->language_code == $IclCommentsTranslation->user_language){
            $comment->translated_version = 1;
        }else{
            $comment->translated_version = 0;
            $comment->anchor_text = __('Back to translated version', 'sitepress');
        }        
        echo json_encode($comment);
        break;
    case 'dismiss_help':
        $iclsettings['dont_show_help_admin_notice'] = true;
        $this->save_settings($iclsettings);
        break;
    case 'dismiss_page_estimate_hint':
        $iclsettings['dismiss_page_estimate_hint'] = !$this->settings['dismiss_page_estimate_hint'];
        $this->save_settings($iclsettings);
        break;        
    case 'toggle_pt_controls':
        $iclsettings['hide_professional_translation_controls'] = $_POST['value'];
        $this->save_settings($iclsettings);
        break;                
    case 'dismiss_upgrade_notice':
        $iclsettings['hide_upgrade_notice'] = implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3));
        $this->save_settings($iclsettings);
        break;        
    case 'setup_got_to_step1':
        $iclsettings['existing_content_language_verified'] = 0;
        $iclsettings['setup_wizard_step'] = 1;
        @mysql_query("TRUNCATE TABLE {$wpdb->prefix}icl_translations");
        $this->save_settings($iclsettings);
        break;
    case 'setup_got_to_step2':
        $iclsettings['setup_wizard_step'] = 2;
        $this->save_settings($iclsettings);
        break;
    case 'setup_got_to_step3':
        $iclsettings['setup_wizard_step'] = 3;
        $this->save_settings($iclsettings);
        break;
    case 'toggle_show_translations':
        $iclsettings = $this->get_settings();
        $iclsettings['show_translations_flag'] = intval(!$iclsettings['show_translations_flag']);
        $this->save_settings($iclsettings);    
        break;
    case 'icl_messages': 
        $iclsettings = $this->get_settings();
        
        if($iclsettings['site_id'] && $iclsettings['access_key'] && empty($iclsettings['icl_anonymous_user'])){
            $iclq = new ICanLocalizeQuery($iclsettings['site_id'], $iclsettings['access_key']);       

            $output = '';

            if (isset($_POST['refresh']) && $_POST['refresh'] == 1) {
                $reminders = $iclq->get_reminders(true);
            } else {
                $reminders = $iclq->get_reminders();
            }
            
            $count = 0;
            foreach($reminders as $r) {
                $message = $r->message;
                $message = str_replace('[', '<', $message);
                $message = str_replace(']', '>', $message);
                $url = $r->url;
                $anchor_pos = strpos($url, '#');
                if ($anchor_pos !== false) {
                    $url = substr($url, 0, $anchor_pos);
                }
                
                if(false !== strpos($url,'?')){
                    $url_glue = '&';
                }else{
                    $url_glue = '?accesskey='.$this->settings['access_key'] . '&compact=1';
                }
                $output .= $message . ' - ' . $this->create_icl_popup_link(ICL_API_ENDPOINT. $url . $url_glue . '&message_id=' . $r->id. '&TB_iframe=true') . __('View', 'sitepress') . '</a>';

                if ($r->can_delete == '1') {
                    $on_click = 'dismiss_message(' . $r->id . ');';
                    
                    $output .= ' - <a href="#" onclick="'. $on_click . '">Dismiss</a>';
                }
                $output .= '<br />';
                
                $count += 1;
                if ($count > 5) {
                    break;
                }
                
            }
            
            if ($output != '') {
                $reminder_count = sizeof($reminders);
                if ($reminder_count == 1){
                    $reminder_text = __('Show 1 reminder', 'sitepress');
                } else {
                    $reminder_text = sprintf(__('Show %d reminders', 'sitepress'), $reminder_count);
                }                
                $resp = array('messages'=>$reminder_count, 'reminder_text' => $reminder_text, 'output'=>$output);
            } else {
                $resp = array('messages'=>0);
            }
        }else{
            $resp = array('messages'=>0);
        }
        echo json_encode($resp);    
        break;

    case 'icl_delete_message':
        $iclsettings = $this->get_settings();
        $iclq = new ICanLocalizeQuery($iclsettings['site_id'], $iclsettings['access_key']);
        $iclq->delete_message($_POST['message_id']);
        break;
    case 'icl_show_reminders':
        $iclsettings['icl_show_reminders'] = $_POST['state']=='show'?1:0;
        $this->save_settings($iclsettings);
        break;
    
    case 'icl_help_links':
        $iclsettings = $this->get_settings();
        $iclq = new ICanLocalizeQuery($iclsettings['site_id'], $iclsettings['access_key']);
        $links = $iclq->get_help_links();
        $lang = $iclsettings['admin_default_language'];
        if (!isset($links['resources'][$lang])) {
            $lang = 'en';
        }
        
        if (isset($links['resources'][$lang])) {
            $output = '<ul>';
            foreach( $links['resources'][$lang]['resource'] as $resource) {
                if (isset($resource['attr'])) {
                    $title = $resource['attr']['title'];
                    $url = $resource['attr']['url'];
                    $icon = $resource['attr']['icon'];
                    $icon_width = $resource['attr']['icon_width'];
                    $icon_height = $resource['attr']['icon_height'];
                } else {
                    $title = $resource['title'];
                    $url = $resource['url'];
                    $icon = $resource['icon'];
                    $icon_width = $resource['icon_width'];
                    $icon_height = $resource['icon_height'];
                }
                $output .= '<li>';
                if ($icon) {
                    $output .= '<img style="vertical-align: bottom; padding-right: 5px;" src="' . $icon . '"';
                    if ($icon_width) {
                        $output .= ' width="' . $icon_width . '"';
                    }
                    if ($icon_height) {
                        $output .= ' height="' . $icon_height . '"';
                    }
                    $output .= '>';
                }
                $output .= '<a href="' . $url . '">' . $title . '</a></li>';
            
            }
            $output .= '</ul>';
            echo '1|' . $output;
        } else {
            echo '0|';
        }
        break;

    case 'icl_show_sidebar':
        $iclsettings['icl_sidebar_minimized'] = $_POST['state']=='hide'?1:0;
        $this->save_settings($iclsettings);
        break;
    
    case 'icl_promote':
        $iclsettings['promote_wpml'] = $_POST['icl_promote']=='true'?1:0;
        $this->save_settings($iclsettings);
        break;        
    
    case 'save_translator_note':
        update_post_meta($_POST['post_id'], '_icl_translator_note', $_POST['note']);
        break;
    
    case 'icl_st_more_options':
        foreach($_POST['icl_st'] as $k=>$v){
            $iclsettings['st'][$k] = $v;
        }
        $this->save_settings($iclsettings);
        echo 1;
        break;
        
    case 'affiliate_info_check':
        $iclq = new ICanLocalizeQuery($this->settings['site_id'], $this->settings['access_key']);       
        if($iclq->test_affiliate_info($_POST['icl_affiliate_id'], $_POST['icl_affiliate_key'])){
            $error = array('error'=>0);
        }else{
            $error = array('error'=>1);
        }
        echo json_encode($error);
        break;
        
    case 'icl_hide_languages':
        $iclsettings['hidden_languages'] = $_POST['icl_hidden_languages'];        
        $this->settings['hidden_languages'] = array(); //reset current value
        $active_languages = $this->get_active_languages();
        if(!empty($iclsettings['hidden_languages'])){
             if(1 == count($iclsettings['hidden_languages'])){
                 $out = sprintf(__('%s is currently hidden to visitors.', 'sitepress'), 
                    $active_languages[$iclsettings['hidden_languages'][0]]['display_name']);
             }else{
                 foreach($iclsettings['hidden_languages'] as $l){
                     $_hlngs[] = $active_languages[$l]['display_name'];
                 }                                 
                 $hlangs = join(', ', $_hlngs);
                 $out = sprintf(__('%s are currently hidden to visitors.', 'sitepress'), $hlangs);                 
             }
             $out .= ' ' . sprintf(__('You can enable its/their display for yourself, in your <a href="%s">profile page</a>.', 'sitepress'),
                                            'profile.php#wpml');
        } else {
            $out = __('All languages are currently displayed.', 'sitepress'); 
        }            
        $this->save_settings($iclsettings);    
        echo '1|'.$out;
        break;
        
    case 'icl_adjust_ids':
        $iclsettings['auto_adjust_ids'] = intval($_POST['icl_adjust_ids']);        
        $this->save_settings($iclsettings);    
        echo '1|';        
        break;
    
    case 'icl_save_sl_options':
        $iclsettings['modules']['absolute-links']['enabled'] = $this->settings['modules']['absolute-links']['enabled'];
        $iclsettings['modules']['absolute-links']['sticky_links_widgets'] = intval($_POST['icl_sticky_links_widgets']);
        $iclsettings['modules']['absolute-links']['sticky_links_strings'] = intval($_POST['icl_sticky_links_strings']);
        $this->save_settings($iclsettings);    
        echo '1|';        
        break;
    case 'icl_torubleshooting_more_options':
        $iclsettings['troubleshooting_options'] = $_POST['troubleshooting_options'];        
        $this->save_settings($iclsettings);
        echo '1|';        
        break;
    case 'icl_st_option_writes_form':
        if(!empty($_POST['icl_admin_options'])){
            icl_register_admin_options($_POST['icl_admin_options']);
            echo '1|';        
        }else{
            echo '0' . __('No strings selected', 'sitepress');
        }
        break;
    case 'icl_st_ow_export':
        // filter empty options out
        do{
            list($_POST['icl_admin_options'], $empty_found) = _icl_st_filter_empty_options_out($_POST['icl_admin_options']);
        }while($empty_found);
        
        if(!empty($_POST['icl_admin_options'])){
            
            foreach($_POST['icl_admin_options'] as $k => $opt){
                if(!$opt){
                    unset($_POST['icl_admin_options'][$k]);
                }
            }
            
            $message = __('Include the following PHP in your code. <em>functions.php</em> would be a good place.', 'sitepress')
            . "<textarea wrap=\"soft\">&lt;?php
if (function_exists('wpml_register_admin_strings')) {
    wpml_register_admin_strings('".serialize($_POST['icl_admin_options'])."');
}
?&gt;</textarea>";
        }else{
            $error = 1;
            $message = __('Error: no strings selected', 'sitepress');
        }
        echo json_encode(array('error'=>0, 'message'=>$message));
        break;
    case 'reset_languages':
        require_once(ICL_PLUGIN_PATH . '/inc/lang-data.inc');
        
        $active = $wpdb->get_col("SELECT code FROM {$wpdb->prefix}icl_languages WHERE active = 1");
        
        mysql_query("TRUNCATE TABLE `{$wpdb->prefix}icl_languages`");
        mysql_query("TRUNCATE TABLE `{$wpdb->prefix}icl_languages_translations`");
        mysql_query("TRUNCATE TABLE `{$wpdb->prefix}icl_flags`");

        foreach($langs_names as $key=>$val){
            if(strpos($key,'Norwegian Bokm')===0){ $key = 'Norwegian Bokmål'; $lang_codes[$key] = 'nb';} // exception for norwegian
            $default_locale = isset($lang_locales[$lang_codes[$key]]) ? $lang_locales[$lang_codes[$key]] : '';
            @$wpdb->insert($wpdb->prefix . 'icl_languages', array('english_name'=>$key, 'code'=>$lang_codes[$key], 'major'=>$val['major'], 'active'=>0, 'default_locale'=>$default_locale));
        }        
        
        //restore active
        $wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET active=1 WHERE code IN('".join("','",$active)."')");
        
        foreach($langs_names as $lang=>$val){        
            if(strpos($lang,'Norwegian Bokm')===0){ $lang = 'Norwegian Bokmål'; $lang_codes[$lang] = 'nb';}
            foreach($val['tr'] as $k=>$display){        
                if(strpos($k,'Norwegian Bokm')===0){ $k = 'Norwegian Bokmål';}
                if(!trim($display)){$display = $lang;}
                if(!($wpdb->get_var("SELECT id FROM {$table_name} WHERE language_code='{$lang_codes[$lang]}' AND display_language_code='{$lang_codes[$k]}'"))){
                    $wpdb->insert($wpdb->prefix . 'icl_languages_translations', 
                    array('language_code'=>$lang_codes[$lang], 'display_language_code'=>$lang_codes[$k], 'name'=>$display));
                }
            }    
        } 
        $wpdb->update($wpdb->prefix.'icl_flags', array('from_template'=>0));       
        
        $codes = $wpdb->get_col("SELECT code FROM {$wpdb->prefix}icl_languages");
        foreach($codes as $code){
            if(!$code || $wpdb->get_var("SELECT lang_code FROM {$wpdb->prefix}icl_flags WHERE lang_code='{$code}'")) continue;
            if(!file_exists(ICL_PLUGIN_PATH.'/res/flags/'.$code.'.png')){
                $file = 'nil.png';
            }else{
                $file = $code.'.png';
            }    
            $wpdb->insert($wpdb->prefix.'icl_flags', array('lang_code'=>$code, 'flag'=>$file, 'from_template'=>0));
        }
        
        icl_cache_clear();
        break;
    case 'icl_support_update_ticket':
        if (isset($_POST['ticket'])) {
            $temp = str_replace('icl_support_ticket_', '', $_POST['ticket']);
            $temp = explode('_', $temp);
            $id = (int)$temp[0];
            $num = (int)$temp[1];
            if ($id && $num) {
                if (isset($iclsettings['icl_support']['tickets'][$id])) {
                    $iclsettings['icl_support']['tickets'][$id]['messages'] = $num;
                    $this->save_settings($iclsettings);
                }
            }
        }
        break;
    case 'icl_custom_tax_sync_options':
        foreach($_POST['icl_sync_tax'] as $k=>$v){
            $iclsettings['taxonomies_sync_option'][$k] = $v;
            if($v){
                $this->verify_taxonomy_translations($k);        
            }            
        }
        $this->save_settings($iclsettings);
        echo '1|';
        break;
    case 'icl_custom_posts_sync_options':    
        foreach($_POST['icl_sync_custom_posts'] as $k=>$v){            
            $iclsettings['custom_posts_sync_option'][$k] = $v;
            if($v){
                $this->verify_post_translations($k);                
            }            
        }
        $this->save_settings($iclsettings);
        echo '1|';
        break;
    
    break;

    case 'hide_affiliate_message':
        $this->save_settings(array('hide_affiliate_message' => 0));
        break;
            
    default:
        do_action('icl_ajx_custom_call', $_REQUEST['icl_ajx_action'], $_REQUEST);
}    

if (!isset($_POST['unit-test'])) {
    exit;
}
  
?>
