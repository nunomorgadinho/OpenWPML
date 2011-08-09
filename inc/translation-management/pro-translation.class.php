<?php

// deprecated - used for string translations
define ( 'CMS_TARGET_LANGUAGE_CREATED', 0);
define ( 'CMS_TARGET_LANGUAGE_ASSIGNED', 1);
define ( 'CMS_TARGET_LANGUAGE_TRANSLATED', 2);
define ( 'CMS_TARGET_LANGUAGE_DONE', 3);


class ICL_Pro_Translation{
    
    private $tmg;
    protected static $__asian_languages = array('ja', 'ko', 'zh-hans', 'zh-hant', 'mn', 'ne', 'hi', 'pa', 'ta', 'th');
    
    function __construct(){
        global $iclTranslationManagement;
        $this->tmg =& $iclTranslationManagement;
        
        add_filter('xmlrpc_methods',array($this, 'custom_xmlrpc_methods'));
        add_action('post_submitbox_start', array($this, 'post_submitbox_start'));
        
        add_action('icl_ajx_custom_call', array($this, 'ajax_calls'), 10, 2);
        
        add_action('icl_hourly_translation_pickup', array($this, 'pool_for_translations'));
        
    }
    
    function ajax_calls($call, $data){
        global $sitepress_settings, $sitepress;
        switch($call){
            case 'set_pickup_mode':
                $method = intval($data['icl_translation_pickup_method']);
                $iclsettings['translation_pickup_method'] = $method;
                $sitepress->save_settings($iclsettings);
                
                $data['site_id'] = $sitepress_settings['site_id'];
                $data['accesskey'] = $sitepress_settings['access_key'];
                $data['create_account'] = 0;
                $data['pickup_type'] = $method;
                
                $icl_query = new ICanLocalizeQuery();                
                $res = $icl_query->updateAccount($data);
                
                if($method == ICL_PRO_TRANSLATION_PICKUP_XMLRPC){
                    wp_clear_scheduled_hook('icl_hourly_translation_pickup');    
                }else{
                    wp_schedule_event(time(), 'hourly', 'icl_hourly_translation_pickup');    
                }
                
                echo json_encode(array('message'=>'OK'));
                break;
            case 'pickup_translations':
                if($sitepress_settings['translation_pickup_method']==ICL_PRO_TRANSLATION_PICKUP_POLLING){
                    $fetched = $this->pool_for_translations();
                    echo json_encode(array('message'=>'OK', 'fetched'=> urlencode('&nbsp;' . sprintf(__('Fetched %d translations.', 'sitepress'), $fetched))));
                }else{
                    echo json_encode(array('error'=>__('Manual pick up is disabled.')));
                }
                break;
        }
    }
    
    function send_post($post_id, $target_languages, $translator_id = 0){
        global $sitepress, $sitepress_settings, $wpdb, $iclTranslationManagement;
        
        // don't wait for init
        if(empty($this->tmg->settings)){
            $iclTranslationManagement->init();    
        }
        
        
        $err = false;
        
        $post = get_post($post_id);
                
        if(!$post){
            return false;
        }
        
        $orig_lang = $sitepress->get_language_for_element($post_id, 'post_' . $post->post_type);
        $__ld = $sitepress->get_language_details($orig_lang);
        $orig_lang_for_server = $this->server_languages_map($__ld['english_name']);
                
        if(empty($target_languages)) return false;
        
        // Make sure the previous request is complete.
        // Only send if it needs update
        foreach($target_languages as $target_lang){
            
            if($target_lang == $orig_lang) continue;
            
            $translation =  $this->tmg->get_element_translation($post_id, $target_lang, 'post_' . $post->post_type);
            
            if(empty($translation)){ // translated the first time
                $tdata = array(
                    'translate_to' => array($target_lang=>1),
                    'post'          => array($post_id),
                    'translator'    => $translator_id,
                    'service'       => 'icanlocalize'
                );
                $this->tmg->send_jobs($tdata);
                $translation =  $this->tmg->get_element_translation($post_id, $target_lang, 'post_' . $post->post_type);
            }
            
            if($translation->needs_update || $translation->status == ICL_TM_NOT_TRANSLATED || $translation->status == ICL_TM_WAITING_FOR_TRANSLATOR){
                
                $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);
                if($post->post_type=='page'){
                    $post_url       = get_option('home') . '?page_id=' . ($post_id);
                }else{
                    $post_url       = get_option('home') . '?p=' . ($post_id);
                }

                // TAGS
                // ***************************************************************************
                foreach(wp_get_object_terms($post_id, 'post_tag') as $tag){
                    $post_tags[$tag->term_taxonomy_id] = $tag->name;
                }   
                
                if(is_array($post_tags)){
                    //only send tags that don't have a translation
                    foreach($post_tags as $term_taxonomy_id=>$pc){
                        $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$term_taxonomy_id}' AND element_type='tax_post_tag'");
                        foreach($target_languages as $lang){
                            $not_translated = false;
                            if($trid != $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE language_code='{$lang}' AND trid='{$trid}' AND element_id IS NOT NULL")){
                                $not_translated = true;
                                break;
                            }                
                        }
                        if($not_translated){
                            $tags_to_translate[$term_taxonomy_id] = $pc; 
                        }            
                    }              
                    sort($post_tags, SORT_STRING);
                } 
                
                // CATEGORIES 
                // ***************************************************************************
                foreach(wp_get_object_terms($post_id, 'category') as $cat){
                    $post_categories[$cat->term_taxonomy_id] = $cat->name;
                }      
                                                              
                if(is_array($post_categories)){
                    //only send categories that don't have a translation
                    foreach($post_categories as $term_taxonomy_id=>$pc){
                        $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$term_taxonomy_id}' AND element_type='tax_category'");
                        foreach($target_languages as $lang){                            
                            $not_translated = false;
                            if($trid != $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE language_code='{$lang}' AND trid='{$trid}' AND element_id IS NOT NULL")){
                                $not_translated = true;
                                break;
                            }                
                        }
                        if($not_translated){
                            $categories_to_translate[$term_taxonomy_id] = $pc; 
                        }            
                    }  
                    sort($post_categories, SORT_STRING);
                }
                
                // CUSTOM TAXONOMIES
                // ***************************************************************************                                
                $taxonomies = $wpdb->get_col("
                    SELECT DISTINCT tx.taxonomy 
                    FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->term_relationships} tr ON tx.term_taxonomy_id = tr.term_taxonomy_id
                    WHERE tr.object_id = {$post_id}
                ");
                foreach($taxonomies as $t){
                    if($sitepress_settings['taxonomies_sync_option'][$t] == 1){
                        $object_terms = $wpdb->get_results("
                            SELECT x.term_taxonomy_id, t.name 
                            FROM {$wpdb->terms} t 
                                JOIN {$wpdb->term_taxonomy} x ON t.term_id=x.term_id
                                JOIN {$wpdb->term_relationships} r ON x.term_taxonomy_id = r.term_taxonomy_id
                            WHERE x.taxonomy = '{$t}' AND r.object_id = $post_id
                            ");
                        foreach($object_terms as $trm){
                            $trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations 
                                WHERE element_id='{$trm->term_taxonomy_id}' AND element_type='tax_{$t}'");
                            foreach($target_languages as $lang){
                                $not_translated = false;
                                if($trid != $wpdb->get_var("
                                        SELECT trid FROM {$wpdb->prefix}icl_translations WHERE l.english_name='{$lang}' AND trid='{$trid} AND element_id IS NOT NULL'
                                ")){
                                    $not_translated = true;
                                    break;
                                }                
                            }
                            if($not_translated){
                                $taxonomies_to_translate[$t][$trm->term_taxonomy_id] = $trm->name; 
                            }            
                        }      
                    }
                }                
                
             
                $__ld = $sitepress->get_language_details($target_lang);
                $target_for_server = $this->server_languages_map($__ld['english_name']);
             
                $data['url']                = htmlentities($post_url);
                $data['contents']['title']  = array(
                                                    'translate'=>1,
                                                    'data'=>base64_encode($post->post_title),
                                                    'format'=>'base64'
                                                    );
                if(!empty($post->post_excerpt))
                $data['contents']['excerpt']  = array(
                                                    'translate'=>1,
                                                    'data'=>base64_encode($post->post_excerpt),
                                                    'format'=>'base64'
                                                    );
                $data['contents']['body']     = array(
                                                    'translate'=>1,
                                                    'data'=>base64_encode($post->post_content),
                                                    'format'=>'base64'
                                                    );
                $data['contents']['original_id']  = array(
                                                    'translate'=>0,
                                                    'data'=>$post_id
                                                    );
                $data['target_languages']         = array($target_for_server);   
                
                $custom_fields = array();
                foreach((array)$iclTranslationManagement->settings['custom_fields_translation'] as $cf => $op){
                    if ($op == 2) {
                        $custom_fields[] = $cf;
                    }
                }
                
                foreach($custom_fields as $cf){
                    $custom_fields_value = get_post_meta($post_id, $cf, true);
                    if ($custom_fields_value != '') {
                        $data['contents']['field-'.$cf] = array(
                            'translate' => 1,
                            'data' => base64_encode($custom_fields_value),
                            'format' => 'base64',
                        );
                        $data['contents']['field-'.$cf.'-name'] = array(
                            'translate' => 0,
                            'data' => $cf,
                        );
                        $data['contents']['field-'.$cf.'-type'] = array(
                            'translate' => 0,
                            'data' => 'custom_field',
                        );
                    }
                }
                

                if(is_array($categories_to_translate)){
                    $data['contents']['categories'] = array(
                            'translate'=>1,
                            'data'=> implode(',', array_map(create_function('$e', 'return \'"\'.base64_encode($e).\'"\';'), $categories_to_translate)),
                            'format'=>'csv_base64'
                        );    
                    $data['contents']['category_ids'] = array(
                            'translate'=>0,
                            'data'=> implode(',', array_keys($categories_to_translate)),
                            'format'=>''
                        );                
                }
                
                if(is_array($tags_to_translate)){
                    $data['contents']['tags'] = array(
                            'translate'=>1,
                            'data'=> implode(',', array_map(create_function('$e', 'return \'"\'.base64_encode($e).\'"\';'), $tags_to_translate)),
                            'format'=>'csv_base64'
                        );                
                    $data['contents']['tag_ids'] = array(
                            'translate'=>0,
                            'data'=> implode(',', array_keys($tags_to_translate)),
                            'format'=>''
                        );                            
                }
                
                if(is_array($taxonomies_to_translate)){
                    foreach($taxonomies_to_translate as $k=>$v){
                        $data['contents'][$k] = array(
                                'translate'=>1,
                                'data'=> implode(',', array_map(create_function('$e', 'return \'"\'.base64_encode($e).\'"\';'), $v)),
                                'format'=>'csv_base64'
                            );                
                        $data['contents'][$k.'_ids'] = array(
                                'translate'=>0,
                                'data'=> implode(',', array_keys($v)),
                                'format'=>''
                            );                            
                    }
                }
                
                if($post->post_status=='publish'){
                    $permlink = $post_url;
                }else{
                    $permlink = false;
                }
                
                $note = get_post_meta($post_id, '_icl_translator_note', true);                
                
                // if this is an old request having a old request_id, include that
                if($wpdb->prefix.'icl_content_status' == $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}icl_content_status'")){
                    $prev_rid = $wpdb->get_var($wpdb->prepare("SELECT MAX(rid) FROM {$wpdb->prefix}icl_content_status WHERE nid=%d", $post_id));
                    if(!empty($prev_rid)){
                        $data['previous_cms_request_id'] = $prev_rid;
                    }
                }
                
                $xml = $iclq->build_cms_request_xml($data, $orig_lang_for_server);                 
                $cms_id = sprintf('%s_%d_%s_%s', $post->post_type, $post->ID, $orig_lang, $target_lang);
                $args = array(
                    'cms_id'        => $cms_id,
                    'xml'           => $xml,
                    'title'         => $post->post_title,
                    'to_languages'   => array($target_for_server),
                    'orig_language' => $orig_lang_for_server,
                    'permlink'      => $permlink,
                    'translator_id' => $translator_id,
                    'note'          => $note
                );
                
                $res = $iclq->send_request($args);
                if($res > 0){
                    $this->tmg->update_translation_status(array(
                        'translation_id'=>$translation->translation_id,
                        'status' => ICL_TM_IN_PROGRESS,
                        'needs_update' => 0
                    ));
                }else{
                    $_prevstate = $wpdb->get_var($wpdb->prepare("SELECT _prevstate FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation->translation_id));
                    if(!empty($_prevstate)){
                        $_prevstate = unserialize($_prevstate);
                        $wpdb->update($wpdb->prefix . 'icl_translation_status', 
                                                array(
                                                    'status'                => $_prevstate['status'], 
                                                    'translator_id'         => $_prevstate['translator_id'], 
                                                    'status'                => $_prevstate['status'], 
                                                    'needs_update'          => $_prevstate['needs_update'], 
                                                    'md5'                   => $_prevstate['md5'], 
                                                    'translation_service'   => $_prevstate['translation_service'], 
                                                    'translation_package'   => $_prevstate['translation_package'], 
                                                    'timestamp'             => $_prevstate['timestamp'], 
                                                    'links_fixed'           => $_prevstate['links_fixed'] 
                                                ), 
                                                array('translation_id'=>$translation->translation_id)
                                            ); 
                    }else{
                        $wpdb->update($wpdb->prefix . 'icl_translation_status', 
                            array('status'=>ICL_TM_NOT_TRANSLATED, 'needs_update'=>0), array('translation_id'=>$translation->translation_id));                         
                    }
                    $err = true;
                }
            } // if needs translation
        } // foreach target lang
        return $err ? false : $res; //last $ret
    }
    
    public function server_languages_map($language_name, $server2plugin = false){    
        if(is_array($language_name)){
            return array_map(array($this, 'icl_server_languages_map'), $language_name);
        }
        $map = array(
            'Norwegian Bokmål' => 'Norwegian',
            'Portuguese, Brazil' => 'Portuguese',
            'Portuguese, Portugal' => 'Portugal Portuguese'
        );
        if($server2plugin){
            $map = array_flip($map);
        }    
        if(isset($map[$language_name])){
            return $map[$language_name];
        }else{
            return $language_name;    
        }
    }    
    
    function custom_xmlrpc_methods($methods){
        
        $icl_methods['icanlocalize.update_status_by_cms_id'] = array($this, 'get_translated_document');
        
        // for migration to 2.0.0
        $icl_methods['icanlocalize.set_translation_status'] =  array($this,'_legacy_set_translation_status'); 
        
        //$icl_methods['icanlocalize.set_translation_status'] =  array($this,'get_translated_string'); // use for strings - old method
        
        //$icl_methods['icanlocalize.list_posts'] = array($this, '_list_posts');
        //$icl_methods['icanlocalize.translate_post'] = array($this, '_remote_control_translate_post');
        
        $icl_methods['icanlocalize.test_xmlrpc'] = array($this, '_test_xmlrpc');
        $icl_methods['icanlocalize.cancel_translation_by_cms_id'] = array($this, '_xmlrpc_cancel_translation');
        
        // for migration to 2.0.0
        $icl_methods['icanlocalize.cancel_translation'] = array($this, '_legacy_xmlrpc_cancel_translation');
        
        $icl_methods['icanlocalize.notify_comment_translation'] =  array($this, '_xmlrpc_add_message_translation');    
        
        
        $methods = $methods + $icl_methods;    
        if(defined('XMLRPC_REQUEST') && XMLRPC_REQUEST){
            preg_match('#<methodName>([^<]+)</methodName>#i', $GLOBALS['HTTP_RAW_POST_DATA'], $matches);
            $method = $matches[1];    
            if(in_array($method, array_keys($icl_methods))){  
                //error_reporting(E_NONE);                
                //ini_set('display_errors', '0');        
                $old_error_handler = set_error_handler(array($this, "_translation_error_handler"),E_ERROR|E_USER_ERROR);
            }
        }
        return $methods;
        
    }
    
    function _legacy_set_translation_status($args){            
            global $sitepress_settings, $sitepress, $wpdb;        
            try{
                
                $signature   = $args[0];
                $site_id     = $args[1];
                $request_id  = $args[2];
                $original_language    = $args[3];
                $language    = $args[4];
                $status      = $args[5];
                $message     = $args[6];  

                if ($site_id != $sitepress_settings['site_id']) {
                    return 3;                                                             
                }
                
                //check signature
                $signature_chk = sha1($sitepress_settings['access_key'].$sitepress_settings['site_id'].$request_id.$language.$status.$message);
                if($signature_chk != $signature){
                    return 2;
                }
                
                $lang_code = $sitepress->get_language_code($this->server_languages_map($language, true));//the 'reverse' language filter 
                $cms_request_info = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}icl_core_status WHERE rid={$request_id} AND target='{$lang_code}'");
                
                if (empty($cms_request_info)){
                    $this->_throw_exception_for_mysql_errors();
                    return 4;
                }
                
                if ($this->_legacy_process_translated_document($request_id, $language, $args)){
                    $this->_throw_exception_for_mysql_errors();
                    return 1;
                } else {
                    $this->_throw_exception_for_mysql_errors();
                    return 6;
                }
                
            }catch(Exception $e) {
                return $e->getMessage();
            }

    } 
    
    function _legacy_process_translated_document($request_id, $language, $args){
                
        global $sitepress_settings, $wpdb, $sitepress, $iclTranslationManagement;
        $ret = false;
        $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);       
        $post_type = $wpdb->get_var($wpdb->prepare("SELECT p.post_type FROM {$wpdb->posts} p JOIN {$wpdb->prefix}icl_content_status c ON p.ID = c.nid WHERE c.rid=%d", $request_id));
        $trid = $wpdb->get_var($wpdb->prepare("
            SELECT trid 
            FROM {$wpdb->prefix}icl_translations t 
            JOIN {$wpdb->prefix}icl_content_status c ON t.element_id = c.nid AND t.element_type='post_{$post_type}' AND c.rid=%d",$request_id));
        $translation = $iclq->cms_do_download($request_id, $language);                           
                
        if($translation){
            if (icl_is_string_translation($translation)){
                $ret = $this->get_translated_string($args);
            } else {
                // we need to create a cms_id for this
                list($lang_from, $lang_to) = $wpdb->get_row($wpdb->prepare("
                    SELECT origin, target FROM {$wpdb->prefix}icl_core_status WHERE rid=%d ORDER BY id DESC LIMIT 1
                ", $request_id), ARRAY_N);
                $translation_id = $wpdb->get_var($wpdb->prepare("
                    SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d and language_code='%s'
                ", $trid, $lang_to));
                
                if(!$translation_id){
                    $wpdb->insert($wpdb->prefix.'icl_translations', array(
                        'element_type'  => 'post_' . $post_type,
                        'trid'          => $trid,
                        'language_code' => $lang_to,
                        'source_language_code' => $lang_from
                    ));
                    $translation_id = $wpdb->insert_id;
                }
                
                $original_post_id = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", $trid));
                
                $translation_package = $iclTranslationManagement->create_translation_package($original_post_id);
                $md5 = $iclTranslationManagement->post_md5($original_post_id);
                
                
                $translator_id = $wpdb->get_var($wpdb->prepare("SELECT post_author FROM {$wpdb->posts} WHERE ID=%d", $original_post_id));
                // add translation_status record        
                                
                list($rid, $update) = $iclTranslationManagement->update_translation_status(array(
                    'translation_id'        => $translation_id,
                    'status'                => 2,
                    'translator_id'         => $translator_id,
                    'needs_update'          => 0,
                    'md5'                   => $md5,
                    'translation_service'   => 'icanlocalize',
                    'translation_package'   => serialize($translation_package)
                ));
                $job_ids[] = $iclTranslationManagement->add_translation_job($rid, $translator_id, $translation_package);                                
                                
                                
                $ret = $this->add_translated_document($translation_id, $request_id);
                
                //if ($ret){
                //    $translations = $sitepress->get_element_translations($trid, 'post_'.$post_type);
                //    $iclq->report_back_permalink($request_id, $language, $translations[$sitepress->get_language_code(icl_server_languages_map($language, 1))]);
                //}
            }
            if($ret){
                $iclq->cms_update_request_status($request_id, CMS_TARGET_LANGUAGE_DONE, $language);
            } 
            
        }        
        return $ret;
    }
            
    /*
     * 0 - unknown error
     * 1 - success
     * 2 - signature mismatch
     * 3 - website_id incorrect
     * 4 - cms_id not found
     * 5 - icl translation not enabled
     * 6 - unknown error processing translation
     */    
    function get_translated_document($args){
        global $sitepress_settings, $sitepress, $wpdb;                
        try{
            
            $signature   = $args[0];
            $site_id     = $args[1];
            $request_id  = $args[2];
            $cms_id      = $args[3];            
            $status      = $args[4];
            $message     = $args[5];  
            
            
            if ($site_id != $sitepress_settings['site_id']) {
                return 3;                                                             
            }
            
            //check signature
            $signature_chk = sha1($sitepress_settings['access_key'].$sitepress_settings['site_id'].$cms_id.$status.$message);
            if($signature_chk != $signature){
                return 2;
            }
            
            // decode cms_id
            $int = preg_match('#(.+)_([0-9]+)_([^_]+)_([^_]+)#', $cms_id, $matches);
            
            $_element_type  = $matches[1];
            $_element_id    = $matches[2];
            $_original_lang = $matches[3];
            $_lang          = $matches[4];
            
            $trid = $sitepress->get_element_trid($_element_id, 'post_'. $_element_type);
            if(!$trid){
                return 4;
            }
            
            $translation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code=%s", $trid, $_lang));                    
            
            if (empty($translation)){ // if the translation was deleted re-create it
                
                $wpdb->insert($wpdb->prefix.'icl_translations', array(
                    'element_type'          => 'post_' . $_element_type,
                    'trid'                  => $trid,
                    'language_code'         => $_lang,
                    'source_language_code'  => $_original_lang
                ));

                $translation_id = $wpdb->insert_id;
                
                $md5 = $this->tmg->post_md5($_element_id);
            
                $translation_package = $this->tmg->create_translation_package($_element_id);
                     
                $translator_id = 0; //TO FIX!          
                list($rid, $update) = $this->tmg->update_translation_status(array(
                    'translation_id'        => $translation_id,
                    'status'                => ICL_TM_IN_PROGRESS,
                    'translator_id'         => $translator_id,
                    'needs_update'          => 0,
                    'md5'                   => $md5,
                    'translation_service'   => 'icanlocalize',
                    'translation_package'   => serialize($translation_package)
                ));
                $this->tmg->add_translation_job($rid, $translator_id, $translation_package);                                                
            
            }else{
                
                $translation_id = $translation->translation_id;
                
                // if the post is trashed set the element_id to null
                if('trash' == $wpdb->get_var($wpdb->prepare("SELECT post_status FROM {$wpdb->posts} WHERE ID=%d", $translation->element_id))){
                    $wpdb->query("UPDATE {$wpdb->prefix}icl_translations SET element_id = NULL WHERE translation_id={$translation->translation_id}");
                }
                
            }
            
            if ($this->add_translated_document($translation_id, $request_id) === true){
                $this->_throw_exception_for_mysql_errors();
                return 1;
            } else {
                $this->_throw_exception_for_mysql_errors();                
                return 6;
            }
            
        }catch(Exception $e) {
            return $e->getMessage();
        }
    }
    
    function add_translated_document($translation_id, $request_id){
        global $sitepress_settings, $wpdb, $sitepress;            
        
        $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);                               
        $tinfo = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d", $translation_id));                
        $_lang = $sitepress->get_language_details($tinfo->language_code);
        $translation = $iclq->cms_do_download($request_id, $this->server_languages_map($_lang['english_name']));                                 
                
        //if(icl_is_string_translation($translation)){
        //    $language_code = $wpdb->get_var($wpdb->prepare("
        //        SELECT target FROM {$wpdb->prefix}icl_core_status
        //        WHERE rid=%d", $request_id
        //    ));
        //    $ret = icl_translation_add_string_translation($request_id, $translation, $language_code);
        //}else{
            $language_code = $wpdb->get_var($wpdb->prepare("
                SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE translation_id=%d", $translation_id
            ));
            $ret = $this->save_post_translation($translation_id, $translation);    
        //}

        if($ret){
            $lang_details = $sitepress->get_language_details($language_code);
            $language_server = $this->server_languages_map($lang_details['english_name']);
            $iclq->cms_update_request_status($request_id, CMS_TARGET_LANGUAGE_DONE, $language_server);
        } 
                                  
          
        return $ret;
    }
    
    function save_post_translation($translation_id, $translation){        
        global $wpdb, $sitepress_settings, $sitepress, $wp_taxonomies;
        $taxonomies = array_diff(array_keys((array)$wp_taxonomies), array('post_tag','category'));
        
        $tinfo = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}icl_translations tr
                    JOIN {$wpdb->prefix}icl_translation_status ts ON ts.translation_id = tr.translation_id
                WHERE tr.translation_id=%d", $translation_id));                                    
        $lang_code = $tinfo->language_code;
        $trid = $tinfo->trid;

        $original_post_details = $wpdb->get_row("
            SELECT p.post_author, p.post_type, p.post_status, p.comment_status, p.ping_status, p.post_parent, p.menu_order, t.language_code
            FROM {$wpdb->prefix}icl_translations t 
            JOIN {$wpdb->posts} p ON t.element_id = p.ID AND CONCAT('post_',p.post_type) = t.element_type
            WHERE trid='{$trid}' AND p.ID = '{$translation['original_id']}'
        ");
        
        //is the original post a sticky post?
        remove_filter('option_sticky_posts', array($sitepress,'option_sticky_posts')); // remove filter used to get language relevant stickies. get them all
        $sticky_posts = get_option('sticky_posts');
        $is_original_sticky = $original_post_details->post_type=='post' && in_array($translation['original_id'], $sticky_posts);
        
               
        $this->_content_fix_image_paths_in_body($translation);        
        $this->_content_fix_relative_link_paths_in_body($translation);
        $this->_content_decode_shortcodes($translation);
        
        
        // deal with tags
        if(isset($translation['tags'])){
            $translated_tags = $translation['tags'];   
            $translated_tag_ids = explode(',', $translation['tag_ids']);
            foreach($translated_tags as $k=>$v){
                $tag_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$translated_tag_ids[$k]}' AND element_type='tax_post_tag'");
                
                // before adding the new term make sure that another tag with the same name doesn't exist. If it does append @lang                                        
                // same term name exists in a different language?
                $term_different_language = $wpdb->get_var("
                    SELECT tm.term_id 
                    FROM {$wpdb->term_taxonomy} tx
                        JOIN {$wpdb->terms} tm ON tx.term_id = tm.term_id 
                        JOIN {$wpdb->prefix}icl_translations tr ON tx.term_taxonomy_id = tr.element_id
                    WHERE tm.name='".$wpdb->escape($v)."' AND tr.element_type LIKE 'tax\\_%' AND tr.language_code <> '{$lang_code}'
                ");
                if($term_different_language){
                    $v .= ' @'.$lang_code;    
                }
                
                //tag exists? (in the current language)
                $etag = get_term_by('name', htmlspecialchars($v), 'post_tag');
                if(!$etag){
                    $etag = get_term_by('name', htmlspecialchars($v) . '@'.$lang_code, 'post_tag');
                }                
                if(!$etag){                                          
                    $tmp = wp_insert_term($v, 'post_tag');
                    if(!is_wp_error($tmp) && isset($tmp['term_taxonomy_id'])){
                        $wpdb->update($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$tag_trid, 'source_language_code'=>$original_post_details->language_code), 
                            array('element_type'=>'tax_post_tag','element_id'=>$tmp['term_taxonomy_id']));
                    }
                }else{
                    $term_taxonomy_id = $etag->term_taxonomy_id; 
                    // check whether we have an orphan translation - the same trid and language but a different element id                                                     
                    $__translation_id = $wpdb->get_var("
                        SELECT translation_id FROM {$wpdb->prefix}icl_translations 
                        WHERE   trid = '{$tag_trid}' 
                            AND language_code = '{$lang_code}' 
                            AND element_id <> '{$term_taxonomy_id}'
                    ");
                    if($__translation_id){
                        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id={$__translation_id}");    
                    }
                    
                    $tag_translation_id = $wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_id={$term_taxonomy_id} AND element_type='tax_post_tag'");                        
                    if($tag_translation_id){
                        $wpdb->update($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$tag_trid, 'source_language_code'=>$original_post_details->language_code), 
                            array('element_type'=>'tax_post_tag','translation_id'=>$tag_translation_id));                
                    }else{                                                
                        $wpdb->insert($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$tag_trid, 'element_type'=>'tax_post_tag', 'element_id'=>$term_taxonomy_id, 'source_language_code'=>$original_post_details->language_code));                                
                    }
                }        
            }
        }
        
        foreach(wp_get_object_terms($translation['original_id'] , 'post_tag') as $t){
            $original_post_tags[] = $t->term_taxonomy_id;
        }    
        if($original_post_tags){
            $tag_trids = $wpdb->get_col("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_post_tag' AND element_id IN (".join(',',$original_post_tags).")");    
            if(!empty($tag_trids))
            $tag_tr_tts = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_post_tag' AND language_code='{$lang_code}' AND trid IN (".join(',',$tag_trids).")");    
            if(!empty($tag_tr_tts))
            $translated_tags = $wpdb->get_col("SELECT t.name FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = t.term_id WHERE tx.taxonomy='post_tag' AND tx.term_taxonomy_id IN (".join(',',$tag_tr_tts).")");
        }
        
        // deal with categories
        if(isset($translation['categories'])){
            $translated_cats = $translation['categories'];   
            $translated_cats_ids = explode(',', $translation['category_ids']);    
            foreach($translated_cats as $k=>$v){
                //$v = trim(str_replace('<p>', '', $v));
                $cat_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id='{$translated_cats_ids[$k]}' AND element_type='tax_category'");
                
                // before adding the new term make sure that another tag with the same name doesn't exist. If it does append @lang                                        
                // same term name exists in a different language?
                $term_different_language = $wpdb->get_var("
                    SELECT tm.term_id 
                    FROM {$wpdb->term_taxonomy} tx
                        JOIN {$wpdb->terms} tm ON tx.term_id = tm.term_id 
                        JOIN {$wpdb->prefix}icl_translations tr ON tx.term_taxonomy_id = tr.element_id
                    WHERE tm.name='".$wpdb->escape($v)."' AND tr.element_type LIKE 'tax\\_%' AND tr.language_code <> '{$lang_code}'
                ");
                if($term_different_language){
                    $v .= ' @'.$lang_code;    
                }
                
                //cat exists?
                $ecat = get_term_by('name', htmlspecialchars($v), 'category');
                if(!$ecat){
                    $ecat = get_term_by('name', htmlspecialchars($v) . '@'.$lang_code, 'category');
                }     
                           
                if(!$ecat){                    
                    // get original category parent id
                    $original_category_parent_id = $wpdb->get_var($wpdb->prepare("SELECT parent FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d",$translated_cats_ids[$k]));                    
                    if($original_category_parent_id){                        
                        $_op_tax_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='category' AND term_id=%d",$original_category_parent_id));
                        $_op_trid   = $wpdb->get_var($wpdb->prepare("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_category' AND element_id=%d",$_op_tax_id));            
                        // get id of the translated category parent
                        $_tp_tax_id = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE language_code='{$lang_code}' AND trid=%d",$_op_trid));                         
                        if($_tp_tax_id){
                            $category_parent_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='category' AND term_taxonomy_id=%d",$_tp_tax_id));
                        }else{
                            $category_parent_id = 0;
                        }                        
                    }else{
                        $category_parent_id = 0;
                    }                    
                    $tmp = wp_insert_term($v, 'category', array('parent'=>$category_parent_id));
                    if(!is_wp_error($tmp) && isset($tmp['term_taxonomy_id'])){
                        $wpdb->update($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$cat_trid, 'source_language_code'=>$original_post_details->language_code), 
                            array('element_type'=>'tax_category','element_id'=>$tmp['term_taxonomy_id']));
                            
                        // if this is a parent category, make sure that nesting is correct for all translations
                        $orig_cat_tax_id   = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", $cat_trid));                        
                        $orig_cat_term_id  = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d AND taxonomy='category'",$orig_cat_tax_id));
                        $orig_cat_children = $wpdb->get_col($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE parent=%d",$orig_cat_term_id));                        
                        if(!empty($orig_cat_children)) foreach($orig_cat_children as $ch){
                            $_tr_child = icl_object_id($ch, 'category', false, $lang_code);
                            if($_tr_child){
                                $wpdb->update($wpdb->term_taxonomy, array('parent'=>$tmp['term_id']), array(
                                    'taxonomy'=>'category', 'term_id' => $_tr_child
                                ));
                            }
                        }                            
                    }
                }else{
                    $term_taxonomy_id = $ecat->term_taxonomy_id;
                    // check whether we have an orphan translation - the same trid and language but a different element id                                                     
                    $__translation_id = $wpdb->get_var("
                        SELECT translation_id FROM {$wpdb->prefix}icl_translations 
                        WHERE   trid = '{$cat_trid}' 
                            AND language_code = '{$lang_code}' 
                            AND element_id <> '{$term_taxonomy_id}'
                    ");
                    if($__translation_id){
                        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id={$__translation_id}");    
                    }
                    
                    $cat_translation_id = $wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_id={$term_taxonomy_id} AND element_type='tax_category'");    
                    if($cat_translation_id){
                        $wpdb->update($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$cat_trid, 'source_language_code'=>$original_post_details->language_code), 
                            array('element_type'=>'tax_category','translation_id'=>$cat_translation_id));                
                    }else{
                        $wpdb->insert($wpdb->prefix.'icl_translations', 
                            array('language_code'=>$lang_code, 'trid'=>$cat_trid, 'element_type'=>'tax_category', 'element_id'=>$term_taxonomy_id, 'source_language_code'=>$original_post_details->language_code));                                
                    }            
                }        
            }
        }
        $original_post_cats = array();    
        foreach(wp_get_object_terms($translation['original_id'] , 'category') as $t){
            $original_post_cats[] = $t->term_taxonomy_id;
        }
        if($original_post_cats){    
            $cat_trids = $wpdb->get_col("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_category' AND element_id IN (".join(',',$original_post_cats).")");
            if(!empty($cat_trids))
            $cat_tr_tts = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_category' AND language_code='{$lang_code}' AND trid IN (".join(',',$cat_trids).")");
            if(!empty($cat_tr_tts))
            $translated_cats_ids = $wpdb->get_col("SELECT t.term_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = t.term_id WHERE tx.taxonomy='category' AND tx.term_taxonomy_id IN (".join(',',$cat_tr_tts).")");
        }   
        
                
        // deal with custom taxonomies        
        if(!empty($sitepress_settings['taxonomies_sync_option'])){
            foreach($sitepress_settings['taxonomies_sync_option'] as $taxonomy=>$value){
                if($value == 1 && isset($translation[$taxonomy])){
                    $translated_taxs[$taxonomy] = $translation[$taxonomy];   
                    $translated_tax_ids[$taxonomy] = explode(',', $translation[$taxonomy.'_ids']);
                    foreach($translated_taxs[$taxonomy] as $k=>$v){
                        $tax_trid = $wpdb->get_var("
                                SELECT trid FROM {$wpdb->prefix}icl_translations 
                                WHERE element_id='{$translated_tax_ids[$taxonomy][$k]}' AND element_type='tax_{$taxonomy}'");
                        // before adding the new term make sure that another tag with the same name doesn't exist. If it does append @lang
                        // same term name exists in a different language?                        
                        $term_different_language = $wpdb->get_var("
                                SELECT tm.term_id 
                                FROM {$wpdb->term_taxonomy} tx
                                    JOIN {$wpdb->terms} tm ON tx.term_id = tm.term_id 
                                    JOIN {$wpdb->prefix}icl_translations tr ON tx.term_taxonomy_id = tr.element_id
                                WHERE tm.name='".$wpdb->escape($v)."' AND tr.element_type LIKE 'tax\\_%' AND tr.language_code <> '{$lang_code}'
                            ");
                        if($term_different_language){
                            $v .= ' @'.$lang_code;    
                        }
                            
                        //tax exists? (in the current language)
                        $etag = get_term_by('name', htmlspecialchars($v), $taxonomy);
                        if(!$etag){
                            $etag = get_term_by('name', htmlspecialchars($v) . '@'.$lang_code, $taxonomy);
                        }                
                        if(!$etag){      
                            
                            // get original category parent id
                            $original_t_parent_id = $wpdb->get_var($wpdb->prepare("SELECT parent FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d",$translated_tax_ids[$taxonomy][$k]));
                            if($original_t_parent_id){                        
                                $_op_tax_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='category' AND term_id=%d",$original_t_parent_id));
                                $_op_trid   = $wpdb->get_var($wpdb->prepare("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_category' AND element_id=%d",$_op_tax_id));            
                                // get id of the translated category parent
                                $_tp_tax_id = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE language_code='{$lang_code}' AND trid=%d",$_op_trid));                         
                                if($_tp_tax_id){
                                    $t_parent_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='category' AND term_taxonomy_id=%d",$_tp_tax_id));
                                }else{
                                    $t_parent_id = 0;
                                }                        
                            }else{
                                $t_parent_id = 0;
                            }
                                                                            
                            $tmp = wp_insert_term($v, $taxonomy);                            
                            if(!is_wp_error($tmp) && isset($tmp['term_taxonomy_id'])){
                                $wpdb->update($wpdb->prefix.'icl_translations', 
                                        array('language_code'=>$lang_code, 'trid'=>$tax_trid, 'source_language_code'=>$original_post_details->language_code), 
                                        array('element_type'=>'tax_'.$taxonomy,'element_id'=>$tmp['term_taxonomy_id']));
                                        
                                
                                // if this is a parent category, make sure that nesting is correct for all translations
                                $orig_tax_id   = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND source_language_code IS NULL", $tax_trid));                
                                $orig_term_id  = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d AND taxonomy='{$taxonomy}'", $orig_tax_id));
                                $orig_tax_children = $wpdb->get_col($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE parent=%d", $orig_term_id));                        
                                if(!empty($orig_tax_children)) foreach($orig_tax_children as $ch){
                                    $_tr_child = icl_object_id($ch, $taxonomy, false, $lang_code);
                                    if($_tr_child){
                                        $wpdb->update($wpdb->term_taxonomy, array('parent'=>$tmp['term_id']), array(
                                            'taxonomy'=>$taxonomy, 'term_id' => $_tr_child
                                        ));
                                    }
                                }
                            }
                        }else{
                            $term_taxonomy_id = $etag->term_taxonomy_id;
                            // check whether we have an orphan translation - the same trid and language but a different element id                             
                            $__translation_id = $wpdb->get_var("
                                SELECT translation_id FROM {$wpdb->prefix}icl_translations 
                                WHERE   trid = '{$tax_trid}' 
                                    AND language_code = '{$lang_code}' 
                                    AND element_id <> '{$term_taxonomy_id}'
                            ");
                            if($__translation_id){
                                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE translation_id={$__translation_id}");    
                            }
                            
                            $tax_translation_id = $wpdb->get_var("
                                SELECT translation_id FROM {$wpdb->prefix}icl_translations 
                                WHERE element_id={$term_taxonomy_id} AND element_type='tax_{$taxonomy}'");                        
                            if($tax_translation_id){
                                $wpdb->update($wpdb->prefix.'icl_translations', 
                                    array('language_code'=>$lang_code, 'trid'=>$tax_trid, 'source_language_code'=>$original_post_details->language_code), 
                                    array('element_type'=>'tax_'.$taxonomy,'translation_id'=>$tax_translation_id));                
                            }else{                                                
                                $wpdb->insert($wpdb->prefix.'icl_translations', 
                                    array('language_code'=>$lang_code, 'trid'=>$tax_trid, 'element_type'=>'tax_'.$taxonomy, 
                                        'element_id'=>$term_taxonomy_id, 'source_language_code'=>$original_post_details->language_code));                                                      
                            }
                        }        
                    }
                }
                
                foreach(wp_get_object_terms($translation['original_id'] , $taxonomy) as $t){
                    $original_post_taxs[$taxonomy][] = $t->term_taxonomy_id;
                }    
                if($original_post_taxs[$taxonomy]){
                    $tax_trids = $wpdb->get_col("SELECT trid FROM {$wpdb->prefix}icl_translations 
                        WHERE element_type='tax_{$taxonomy}' AND element_id IN (".join(',',$original_post_taxs[$taxonomy]).")");    
                    if(!empty($tax_trids))
                    $tax_tr_tts = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations 
                        WHERE element_type='tax_{$taxonomy}' AND language_code='{$lang_code}' AND trid IN (".join(',',$tax_trids).")");    
                    if(!empty($tax_tr_tts)){
                        if($wp_taxonomies[$taxonomy]->hierarchical){
                            $translated_tax_ids[$taxonomy] = $wpdb->get_col("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id IN (".join(',',$tax_tr_tts).")");
                        }else{
                            $translated_taxs[$taxonomy] = $wpdb->get_col("SELECT t.name FROM {$wpdb->terms} t 
                                JOIN {$wpdb->term_taxonomy} tx ON tx.term_id = t.term_id 
                                WHERE tx.taxonomy='{$taxonomy}' AND tx.term_taxonomy_id IN (".join(',',$tax_tr_tts).")");                    
                        }
                    }
                }
            }
        }
           
                     
    
        // handle the page parent and set it to the translated parent if we have one.
        if($original_post_details->post_parent){
            $post_parent_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_type='post_{$original_post_details->post_type}' AND element_id='{$original_post_details->post_parent}'");
            if($post_parent_trid){
                $parent_id = $wpdb->get_var("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='post_{$original_post_details->post_type}' AND trid='{$post_parent_trid}' AND language_code='{$lang_code}'");
            }            
        }
                
        
        // determine post id based on trid
        $post_id = $tinfo->element_id;
        
        if($post_id){
            // see if the post really exists - make sure it wasn't deleted while the plugin was 
            if(!$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE ID={$post_id}")){
                $is_update = false;
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE element_type='post_{$original_post_details->post_type}' AND element_id={$post_id}");
            }else{
                $is_update = true;
                $postarr['ID'] = $_POST['post_ID'] = $post_id;
            }
        }else{
            $is_update = false;
        } 
        $postarr['post_title'] = $translation['title'];
        $postarr['post_content'] = $translation['body'];
        if(is_array($translated_tags)){
            $postarr['tags_input'] = join(',',(array)$translated_tags);
        }
        if(is_array($translated_taxs)){
            foreach($translated_taxs as $taxonomy=>$values){
                $postarr['tax_input'][$taxonomy] = join(',',(array)$values);
            }
        } 
        if(is_array($translated_tax_ids)){
            $postarr['tax_input'] = $translated_tax_ids;
        }           
        if(isset($translated_cats_ids)){
            $postarr['post_category'] = $translated_cats_ids;        
        }
        $postarr['post_author'] = $original_post_details->post_author;  
        $postarr['post_type'] = $original_post_details->post_type;
        if($sitepress_settings['sync_comment_status']){
            $postarr['comment_status'] = $original_post_details->comment_status;
        }
        if($sitepress_settings['sync_ping_status']){
            $postarr['ping_status'] = $original_post_details->ping_status;
        }
        if($sitepress_settings['sync_page_ordering']){
            $postarr['menu_order'] = $original_post_details->menu_order;
        }
        if($sitepress_settings['sync_private_flag'] && $original_post_details->post_status=='private'){    
            $postarr['post_status'] = 'private';
        }
        if(!$is_update){
            $postarr['post_status'] = !$sitepress_settings['translated_document_status'] ? 'draft' : $original_post_details->post_status;
        } else {
            // set post_status to the current post status.
            $postarr['post_status'] = $wpdb->get_var("SELECT post_status FROM {$wpdb->prefix}posts WHERE ID = ".$post_id);
        }
        
        if(isset($parent_id) && $sitepress_settings['sync_page_parent']){
            $_POST['post_parent'] = $postarr['post_parent'] = $parent_id;  
            $_POST['parent_id'] = $postarr['parent_id'] = $parent_id;  
        }
        
        $_POST['trid'] = $trid;
        $_POST['lang'] = $lang_code;
        $_POST['skip_sitepress_actions'] = true;
        
        
        global $wp_rewrite;
        if(!isset($wp_rewrite)) $wp_rewrite = new WP_Rewrite();
            
        kses_remove_filters();
        
        $new_post_id = wp_insert_post($postarr);    
        
        
        // associate custom taxonomies by hand
        if ( !empty($postarr['tax_input']) ) {
            foreach ( $postarr['tax_input'] as $taxonomy => $tags ) {
                wp_set_post_terms( $new_post_id, $tags, $taxonomy );
            }
        }
        
        // set stickiness
        if($is_original_sticky && $sitepress_settings['sync_sticky_flag']){
            stick_post($new_post_id);
        }else{
            if($original_post_details->post_type=='post' && $is_update){
                unstick_post($new_post_id); //just in case - if this is an update and the original post stckiness has changed since the post was sent to translation
            }
        }
                                                                                                         
        foreach((array)$sitepress_settings['translation-management']['custom_fields_translation'] as $cf => $op){
            if ($op == 1) {
                update_post_meta($new_post_id, $cf, get_post_meta($translation['original_id'],$cf,true));
            }elseif ($op == 2 && isset($translation['field-'.$cf])) {                
                $field_translation = $translation['field-'.$cf];
                $field_type = $translation['field-'.$cf.'-type'];
                if ($field_type == 'custom_field') {
                    $field_translation = str_replace ( '&#0A;', "\n", $field_translation );                                
                    // always decode html entities  eg decode &amp; to &
                    $field_translation = html_entity_decode($field_translation);
                    update_post_meta($new_post_id, $cf, $field_translation);
                }            
            }
        }
        
        
        // set specific custom fields
        $copied_custom_fields = array('_top_nav_excluded', '_cms_nav_minihome');    
        foreach($copied_custom_fields as $ccf){
            $val = get_post_meta($translation['original_id'], $ccf, true);
            update_post_meta($new_post_id, $ccf, $val);
        }    
        
        // sync _wp_page_template
        if($sitepress_settings['sync_page_template']){
            $_wp_page_template = get_post_meta($translation['original_id'], '_wp_page_template', true);
            update_post_meta($new_post_id, '_wp_page_template', $_wp_page_template);
        }
        
        if(!$new_post_id){
            return false;
        }
        
        if(!$is_update){
            $wpdb->update($wpdb->prefix.'icl_translations', array('element_id'=>$new_post_id), array('translation_id' => $translation_id));
        }
        
        update_post_meta($new_post_id, '_icl_translation', 1);
        
        global $iclTranslationManagement;
        
        $ts = array(
            'status'=>ICL_TM_COMPLETE, 'needs_update'=>0,
            'translation_id'=>$translation_id
        );        
        
        $translator_id = $wpdb->get_var($wpdb->prepare("SELECT translator_id FROM {$wpdb->prefix}icl_translation_status WHERE translation_id=%d", $translation_id));
        if(!$translator_id){
            foreach($sitepress_settings['icl_lang_status'] as $lpair){
                if($lpair['from'] == $original_post_details->language_code && $lpair['to'] == $lang_code && isset($lpair['translators'][0]['id'])){
                    $ts['translator_id'] = $lpair['translators'][0]['id'];
                    break;
                }
            }
        }
                
        
         // update translation status 
        $iclTranslationManagement->update_translation_status($ts);
        
       
        
        // add new translation job
        
        //$translation_package = $iclTranslationManagement->create_translation_package(get_post($translation['original_id'])); 
        //$job_id = $iclTranslationManagement->add_translation_job($tinfo->rid, $tinfo->translator_id, $translation_package);
        $job_id = $iclTranslationManagement->get_translation_job_id($trid, $lang_code);
        // save the translation
        $iclTranslationManagement->save_job_fields_from_post($job_id, get_post($new_post_id));
        $iclTranslationManagement->mark_job_done($job_id);
        
        $this->_content_fix_links_to_translated_content($new_post_id, $lang_code, 'post');
        icl_st_fix_links_in_strings($new_post_id);
                
        
        // Now try to fix links in other translated content that may link to this post.
        $sql = "SELECT
                    tr.element_id
                FROM
                    {$wpdb->prefix}icl_translations tr
                JOIN
                    {$wpdb->prefix}icl_translation_status ts
                ON
                    tr.translation_id = ts.translation_id
                WHERE
                    ts.links_fixed = 0 AND tr.element_type = 'post_{$original_post_details->post_type}' AND tr.language_code = '{$lang_code}' AND tr.element_id IS NOT NULL";
        $needs_fixing = $wpdb->get_results($sql);
        foreach($needs_fixing as $id){
            if($id->element_id != $new_post_id){ // fix all except the new_post_id. We have already done this.
                $this->_content_fix_links_to_translated_content($id->element_id, $lang_code, 'post');                
            }
        }
        
        // if this is a parent page then make sure it's children point to this.
        $this->fix_translated_children($translation['original_id'], $new_post_id, $lang_code);
        
        return true;
    }    
    
    
    // old style - for strings
    function get_translated_string($args){
        global $sitepress_settings, $sitepress, $wpdb;        

        try{
            
            $signature   = $args[0];
            $site_id     = $args[1];
            $request_id  = $args[2];
            $original_language    = $args[3];
            $language    = $args[4];
            $status      = $args[5];
            $message     = $args[6];  
            
            if ($site_id != $sitepress_settings['site_id']) {
                return 3;                                                             
            }
            
            //check signature
            $signature_chk = sha1($sitepress_settings['access_key'].$sitepress_settings['site_id'].$request_id.$language.$status.$message);
            if($signature_chk != $signature){
                return 2;
            }
            
            $lang_code = $sitepress->get_language_code($this->server_languages_map($language, true));//the 'reverse' language filter 
            
            $cms_request_info = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}icl_core_status WHERE rid={$request_id} AND target='{$lang_code}'");
            
            if (empty($cms_request_info)){
                $this->_throw_exception_for_mysql_errors();
                return 4;
            }

            //return $this->process_translated_string($request_id, $language);
            
            if ($this->process_translated_string($request_id, $language) === true){
                $this->_throw_exception_for_mysql_errors();
                return 1;
            } else {
                $this->_throw_exception_for_mysql_errors();
                return 6;
            }
            
        }catch(Exception $e) {
            return $e->getMessage();
        }
    }
    
    // old style - for strings
    function process_translated_string($request_id, $language){
        global $sitepress_settings, $wpdb, $sitepress;
        $ret = false;
        $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);       
        
        $translation = $iclq->cms_do_download($request_id, $language);                                   
        
        if($translation){            
            $ret = icl_translation_add_string_translation($request_id, $translation, $sitepress->get_language_code($this->server_languages_map($language, true))); 
            if($ret){
                $iclq->cms_update_request_status($request_id, CMS_TARGET_LANGUAGE_DONE, $language);
            } 
        }   
             
        // if there aren't any other unfullfilled requests send a global 'done'               
        if(0 == $wpdb->get_var("SELECT COUNT(rid) FROM {$wpdb->prefix}icl_core_status WHERE rid='{$request_id}' AND status < ".CMS_TARGET_LANGUAGE_DONE)){
            $iclq->cms_update_request_status($request_id, CMS_REQUEST_DONE, false);
        }
        return $ret;
    }
    
    function _content_fix_image_paths_in_body(&$translation) {
        $body = $translation['body'];
        $image_paths = $this->_content_get_image_paths($body);
        
        $source_path = get_permalink($translation['original_id']);
      
        foreach($image_paths as $path) {
      
            $src_path = $this->resolve_url($source_path, $path[2]);
            if ($src_path != $path[2]) {
                $search = $path[1] . $path[2] . $path[1];
                $replace = $path[1] . $src_path . $path[1];
                $new_link = str_replace($search, $replace, $path[0]);
          
                $body = str_replace($path[0], $new_link, $body);
          
              
            }
        
        }
        $translation['body'] = $body;
    }    
    
    /*
     Decode any html encoding in shortcodes
     http://codex.wordpress.org/Shortcode_API
    */
    function _content_decode_shortcodes(&$translation) {
        $body = $translation['body'];
        
        global $shortcode_tags;
        if (isset($shortcode_tags)) {
            $tagnames = array_keys($shortcode_tags);
        $tagregexp = join( '|', array_map('preg_quote', $tagnames) );

            $regexp = '/\[('.$tagregexp.')\b(.*?)\]/s';
            
            if (preg_match_all($regexp, $body, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $body = str_replace($match[0], '[' . $match[1] . html_entity_decode($match[2]) . ']', $body);
                }
            }
            
        }
        
        $translation['body'] = $body;
    }    
    
    /**
     * get the paths to images in the body of the content
     */

    function _content_get_image_paths($body) {

      $regexp_links = array(
                          "/<img\ssrc\s*=\s*([\"\']??)([^\"]*)\".*>/siU",
                          "/&lt;script\ssrc\s*=\s*([\"\']??)([^\"]*)\".*>/siU",
                          "/<embed\ssrc\s*=\s*([\"\']??)([^\"]*)\".*>/siU",
                          );

      $links = array();

      foreach($regexp_links as $regexp) {
        if (preg_match_all($regexp, $body, $matches, PREG_SET_ORDER)) {
          foreach ($matches as $match) {
            $links[] = $match;
          }
        }
      }

      return $links;
    }


    /**
     * Resolve a URL relative to a base path. This happens to work with POSIX
     * filenames as well. This is based on RFC 2396 section 5.2.
     */
    function resolve_url($base, $url) {
            if (!strlen($base)) return $url;
            // Step 2
            if (!strlen($url)) return $base;
            // Step 3
            if (preg_match('!^[a-z]+:!i', $url)) return $url;
            $base = parse_url($base);
            if ($url{0} == "#") {
                    // Step 2 (fragment)
                    $base['fragment'] = substr($url, 1);
                    return $this->unparse_url($base);
            }
            unset($base['fragment']);
            unset($base['query']);
            if (substr($url, 0, 2) == "//") {
                    // Step 4
                    return $this->unparse_url(array(
                            'scheme'=>$base['scheme'],
                            'path'=>$url,
                    ));
            } else if ($url{0} == "/") {
                    // Step 5
                    $base['path'] = $url;
            } else {
                    // Step 6
                    $path = explode('/', $base['path']);
                    $url_path = explode('/', $url);
                    // Step 6a: drop file from base
                    array_pop($path);
                    // Step 6b, 6c, 6e: append url while removing "." and ".." from
                    // the directory portion
                    $end = array_pop($url_path);
                    foreach ($url_path as $segment) {
                            if ($segment == '.') {
                                    // skip
                            } else if ($segment == '..' && $path && $path[sizeof($path)-1] != '..') {
                                    array_pop($path);
                            } else {
                                    $path[] = $segment;
                            }
                    }
                    // Step 6d, 6f: remove "." and ".." from file portion
                    if ($end == '.') {
                            $path[] = '';
                    } else if ($end == '..' && $path && $path[sizeof($path)-1] != '..') {
                            $path[sizeof($path)-1] = '';
                    } else {
                            $path[] = $end;
                    }
                    // Step 6h
                    $base['path'] = join('/', $path);

            }
            // Step 7
            return $this->unparse_url($base);
    }

    function unparse_url($parsed){
        if (! is_array($parsed)) return false;
        $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
        $uri .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
        $uri .= isset($parsed['host']) ? $parsed['host'] : '';
        $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
        if(isset($parsed['path']))
            {
            $uri .= (substr($parsed['path'],0,1) == '/')?$parsed['path']:'/'.$parsed['path'];
            }
        $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
        $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
        return $uri;
    }

    function _content_fix_relative_link_paths_in_body(&$translation) {
        $body = $translation['body'];
        $link_paths = $this->_content_get_link_paths($body);

        $source_path = get_permalink($translation['original_id']);

        foreach($link_paths as $path) {
          
            if ($path[2][0] != "#"){
                $src_path = $this->resolve_url($source_path, $path[2]);
                if ($src_path != $path[2]) {
                    $search = $path[1] . $path[2] . $path[1];
                    $replace = $path[1] . $src_path . $path[1];
                    $new_link = str_replace($search, $replace, $path[0]);
                    
                    $body = str_replace($path[0], $new_link, $body);
                }
            }      
        }
        $translation['body'] = $body;
    }

    function _content_get_link_paths($body) {
      
        $regexp_links = array(
                            "/<a.*?href\s*=\s*([\"\']??)([^\"]*)[\"\']>(.*?)<\/a>/i",
                            );
        
        $links = array();
        
        foreach($regexp_links as $regexp) {
            if (preg_match_all($regexp, $body, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                  $links[] = $match;
                }
            }
        }
        
        return $links;
    }    
    
    public static function _content_make_links_sticky($element_id, $element_type='post', $string_translation = true) {
        if($element_type=='post'){
            // only need to do it if sticky links is not enabled.
            // create the object
            if(!$sitepress_settings['modules']['absolute-links']['enabled']){
                include_once ICL_PLUGIN_PATH . '/modules/absolute-links/absolute-links-plugin.php';
                $icl_abs_links = new AbsoluteLinksPlugin();
                $icl_abs_links->process_post($element_id);
            }
        }elseif($element_type=='string'){                
            if(!class_exists('AbsoluteLinksPlugin')){
                include_once ICL_PLUGIN_PATH . '/modules/absolute-links/absolute-links-plugin.php';
            }
            $icl_abs_links = new AbsoluteLinksPlugin(true); // call just for strings
            $icl_abs_links->process_string($element_id, $string_translation);                                        
        }
    }

    function _content_fix_links_to_translated_content($element_id, $target_lang_code, $element_type='post'){
        global $wpdb, $sitepress, $sitepress_settings, $wp_taxonomies;
        self::_content_make_links_sticky($element_id, $element_type);
        
        if($element_type == 'post'){
            $post = $wpdb->get_row("SELECT * FROM {$wpdb->posts} WHERE ID={$element_id}");
            $body = $post->post_content;        
        }elseif($element_type=='string'){
            $body = $wpdb->get_var("SELECT value FROM {$wpdb->prefix}icl_string_translations WHERE id=" . $element_id);
        }    
        $new_body = $body;

        $base_url_parts = parse_url(get_option('home'));
        
        $links = $this->_content_get_link_paths($body);
        $all_links_fixed = 1;
        
        foreach($links as $link) {
            $path = $link[2];
            $url_parts = parse_url($path);
            
            if((!isset($url_parts['host']) or $base_url_parts['host'] == $url_parts['host']) and
                    (!isset($url_parts['scheme']) or $base_url_parts['scheme'] == $url_parts['scheme']) and
                    isset($url_parts['query'])) {
                $query_parts = split('&', $url_parts['query']);
                foreach($query_parts as $query){
                    
                   
                    // find p=id or cat=id or tag=id queries
                    
                    list($key, $value) = split('=', $query);
                    $translations = NULL;
                    $is_tax = false;
                    if($key == 'p'){
                        $kind = 'post_' . $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID='{$value}'");
                    } else if($key == "page_id"){
                        $kind = 'post_page';
                    } else if($key == 'cat' || $key == 'cat_ID'){
                        $kind = 'tax_category';
                        $taxonomy = 'category';
                    } else if($key == 'tag'){
                        $is_tax = true;
                        $taxonomy = 'post_tag';
                        $kind = 'tax_' . $taxonomy;                    
                        $value = $wpdb->get_var("SELECT term_taxonomy_id FROM {$wpdb->terms} t 
                            JOIN {$wpdb->term_taxonomy} x ON t.term_id = x.term_id WHERE x.taxonomy='{$taxonomy}' AND t.slug='{$value}'");
                    } else {
                        $found = false;
                        foreach($wp_taxonomies as $ktax => $tax){
                            if($tax->query_var && $key == $tax->query_var){
                                $found = true;
                                $is_tax = true;
                                $kind = 'tax_' . $ktax;                            
                                $value = $wpdb->get_var("
                                    SELECT term_taxonomy_id FROM {$wpdb->terms} t 
                                        JOIN {$wpdb->term_taxonomy} x ON t.term_id = x.term_id WHERE x.taxonomy='{$ktax}' AND t.slug='{$value}'");                            
                            }                        
                        }
                        if(!$found) continue;
                    }

                    $link_id = (int)$value;  
                    
                    if (!$link_id || $sitepress->get_language_for_element($link_id, $kind) == $target_lang_code) {
                        // link already points to the target language.
                        continue;
                    }

                    $trid = $sitepress->get_element_trid($link_id, $kind);
                    if(!$trid){
                        continue;
                    }
                    if($trid !== NULL){
                        $translations = $sitepress->get_element_translations($trid, $kind);
                    }
                    if(isset($translations[$target_lang_code])){
                        
                        // use the new translated id in the link path.
                        
                        $translated_id = $translations[$target_lang_code]->element_id;
                        
                        if($is_tax){
                            $translated_id = $wpdb->get_var("SELECT slug FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x ON t.term_id=x.term_id WHERE x.term_taxonomy_id=$translated_id");    
                        }
                        
                        // if absolute links is not on turn into WP permalinks
                        if(!$sitepress_settings['modules']['absolute-links']['enabled']){
                            ////////
                            if(preg_match('#^post_#', $kind)){
                                $replace = get_permalink($translated_id);
                            }elseif(preg_match('#^tax_#', $kind)){
                                if(is_numeric($translated_id)) $translated_id = intval($translated_id);
                                $replace = get_term_link($translated_id, $taxonomy);                                
                            }
                            $new_link = str_replace($link[2], $replace, $link[0]);
                        }else{
                            $replace = $key . '=' . $translated_id;    
                            $new_link = str_replace($query, $replace, $link[0]);
                        }
                        
                        // replace the link in the body.                        
                        $new_body = str_replace($link[0], $new_link, $new_body);
                    } else {
                        // translation not found for this.
                        $all_links_fixed = 0;
                    }
                }
            }
            
            
        }
        
        if ($new_body != $body){

            // unless sticky links is on - we convert default links to permalinks
            /*
            if(!$sitepress_settings['modules']['absolute-links']['enabled']){
                // create the object
                include_once ICL_PLUGIN_PATH . '/modules/absolute-links/absolute-links-plugin.php';
                $icl_abs_links = new AbsoluteLinksPlugin();
                
                $new_body = $icl_abs_links->show_permalinks($new_body);
            }        
            */
            
            // save changes to the database.
            if($element_type == 'post'){        
                $wpdb->update($wpdb->posts, array('post_content'=>$new_body), array('ID'=>$element_id));
                
                // save the all links fixed status to the database.
                $wpdb->query("UPDATE {$wpdb->prefix}icl_node SET links_fixed='{$all_links_fixed}' WHERE nid={$element_id}");
                
            }elseif($element_type == 'string'){
                $wpdb->update($wpdb->prefix.'icl_string_translations', array('value'=>$new_body), array('id'=>$element_id));
            }
                    
        }
        
    }
    
    function fix_translated_children($original_id, $translated_id, $lang_code){
        global $wpdb, $sitepress;

        // get the children of of original page.
        $original_children = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent = {$original_id} AND post_type = 'page'");
        foreach($original_children as $original_child){
            // See if the child has a translation.
            $trid = $sitepress->get_element_trid($original_child);
            if($trid){
                $translations = $sitepress->get_element_translations($trid);
                if (isset($translations[$lang_code])){
                    $current_parent = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE ID = ".$translations[$lang_code]->element_id);
                    if ($current_parent != $translated_id){
                        $wpdb->query("UPDATE {$wpdb->posts} SET post_parent={$translated_id} WHERE ID = ".$translations[$lang_code]->element_id);
                    }
                }
            }
        }
    }

    function fix_translated_parent($original_id, $translated_id, $lang_code){
        global $wpdb, $sitepress;

        $original_parent = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE ID = {$original_id} AND post_type = 'page'");
        if ($original_parent){
            $trid = $sitepress->get_element_trid($original_parent);
            if($trid){
                $translations = $sitepress->get_element_translations($trid);
                if (isset($translations[$lang_code])){
                    $current_parent = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE ID = ".$translated_id);
                    if ($current_parent != $translations[$lang_code]->element_id){
                        $wpdb->query("UPDATE {$wpdb->posts} SET post_parent={$translations[$lang_code]->element_id} WHERE ID = ".$translated_id);
                    }
                }
            }
        }
    }
  
    
    function _throw_exception_for_mysql_errors(){
        global $EZSQL_ERROR, $sitepress_settings;
        if($sitepress_settings['troubleshooting_options']['raise_mysql_errors']){
            if(!empty($EZSQL_ERROR)){
                foreach($EZSQL_ERROR as $k=>$v){
                    $mysql_errors[] = $v['error_str'] . ' [' . $v['query'] . ']';
                }
                throw new Exception(join("\n", $mysql_errors));
            }    
        }
    }  
    
    function _translation_error_handler($errno, $errstr, $errfile, $errline){    
        switch($errno){
            case E_ERROR:
            case E_USER_ERROR:
                throw new Exception ($errstr . ' [code:e' . $errno . '] in '. $errfile . ':' . $errline);
            case E_WARNING:
            case E_USER_WARNING:
                return true;                
                //throw new Exception ($errstr . ' [code:w' . $errno . '] in '. $errfile . ':' . $errline);    
            default: 
                return true;
        }
        
    }    
    
    function post_submitbox_start(){
        global $post, $iclTranslationManagement;
        if(!$post->ID){
            return;
        }
        
        $translations = $iclTranslationManagement->get_element_translations($post->ID, 'post_' . $post->post_type);
        $show_box = 'display:none';
        foreach($translations as $t){
            if($t->element_id == $post->ID){
                if(!empty($t->source_language_code)) return;
                else continue;
            } 
            if($t->status == ICL_TM_COMPLETE && !$t->needs_update){
                $show_box = '';
                break;
            }
        }
        
        echo '<p id="icl_minor_change_box" style="float:left;padding:0;margin:3px;'.$show_box.'">';
        echo '<label><input type="checkbox" name="icl_minor_edit" value="1" style="min-width:15px;" />&nbsp;';
        echo __('Minor edit - don\'t update translation','sitepress');        
        echo '</label>';
        echo '<br clear="all" />';
        echo '</p>';
    }   
    
    public function estimate_word_count($data, $lang_code) {
        $words = 0;
        if(isset($data->post_title)){
            if(in_array($lang_code, self::$__asian_languages)){
                $words += strlen(strip_tags($data->post_title)) / 6;
            } else {
                $words += count(explode(' ',$data->post_title));
            }
        }
        if(isset($data->post_content)){
            if(in_array($lang_code, self::$__asian_languages)){
                $words += strlen(strip_tags($data->post_content)) / 6;
            } else {
                $words += count(explode(' ',strip_tags($data->post_content)));
            }
        }        
        return (int)$words;
    } 
    
    function estimate_custom_field_word_count($post_id, $lang_code) {
        $words = 0;
        $custom_fields = array();
        foreach((array)$sitepress_settings['translation-management']['custom_fields_translation'] as $cf => $op){
            if ($op == 2) {
                $custom_fields[] = $cf;
            }
        }
        foreach($custom_fields as $cf){
            $custom_fields_value = get_post_meta($post_id, $cf, true);
            if ($custom_fields_value != "") {
                if(in_array($lang_code, self::__asian_languages)){
                    $words += strlen(strip_tags($custom_fields_value)) / 6;
                } else {
                    $words += count(explode(' ',strip_tags($custom_fields_value)));
                }
            }
        }        
        return (int)$words;
    }    
    
    public function get_translator_name($translator_id){
        global $sitepress_settings;
        static $translators;
        if(is_null($translators)){
            foreach($sitepress_settings['icl_lang_status'] as $lp){
                if(!empty($lp['translators'])){
                    foreach($lp['translators'] as $tr){
                        $translators[$tr['id']] = $tr['nickname'];                    
                    }
                }
            }
        }        
        if(isset($translators[$translator_id])){
            return $translators[$translator_id];
        }else{
            return false;
        }
    }
    
    
    function _xmlrpc_cancel_translation($args){
        global $sitepress_settings, $sitepress, $wpdb;        
        $signature = $args[0];
        $website_id = $args[1];
        $request_id = $args[2];
        $cms_id = $args[3];
        $checksum = $sitepress_settings['access_key'] . $sitepress_settings['site_id'] . $request_id . $cms_id;
        
        
        
        // decode cms_id
        $int = preg_match('#(.+)_([0-9]+)_([^_]+)_([^_]+)#', $cms_id, $matches);
        
        $_element_type  = $matches[1];
        $_element_id    = $matches[2];
        $_original_lang = $matches[3];
        $_lang          = $matches[4];
        
        $trid = $sitepress->get_element_trid($_element_id, 'post_' . $_element_type);
        
        if (sha1 ( $checksum ) == $signature) {
            $wid = $sitepress_settings['site_id'];
            if ($website_id == $wid) {
                $translation_entry = $wpdb->get_row("SELECT * 
                    FROM {$wpdb->prefix}icl_translation_status s JOIN {$wpdb->prefix}icl_translations t ON t.translation_id = s.translation_id
                    WHERE t.trid={$trid} AND t.language_code='{$_lang}'");
                    
                if (empty($translation_entry)){
                    return 4; // cms_request not found
                }
                $job_id = $wpdb->get_var($wpdb->prepare("SELECT job_id FROM {$wpdb->prefix}icl_translate_job WHERE rid=%d AND revision IS NULL", $translation_entry->rid));
                if($job_id){
                    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translate_job WHERE job_id=%d", $job_id));    
                    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}icl_translate WHERE job_id=%d", $job_id));    
                    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}icl_translate_job SET revision = NULL WHERE rid=%d ORDER BY job_id DESC LIMIT 1", $translation_entry->rid));
                }
                
                if(!empty($translation_entry->_prevstate)){
                    $_prevstate = unserialize($translation_entry->_prevstate);
                    $wpdb->update($wpdb->prefix . 'icl_translation_status', 
                        array(
                            'status'                => $_prevstate['status'], 
                            'translator_id'         => $_prevstate['translator_id'], 
                            'status'                => $_prevstate['status'], 
                            'needs_update'          => $_prevstate['needs_update'], 
                            'md5'                   => $_prevstate['md5'], 
                            'translation_service'   => $_prevstate['translation_service'], 
                            'translation_package'   => $_prevstate['translation_package'], 
                            'timestamp'             => $_prevstate['timestamp'], 
                            'links_fixed'           => $_prevstate['links_fixed'] 
                        ), 
                        array('translation_id'=>$translation_entry->translation_id)
                    ); 
                    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}icl_translation_status SET _prevstate = NULL WHERE translation_id=%d",$translation_entry->translation_id));
                }else{
                    $wpdb->update($wpdb->prefix . 'icl_translation_status', array('status'=>ICL_TM_NOT_TRANSLATED, 'needs_update'=>0), array('translation_id'=>$translation_entry->translation_id)); 
                }
                return 1;
            } else {
                return 3; // Website id incorrect
            }
        } else {
            return 2; // Signature failed
        }
      
        return 0; // Should not have got here - unknown error.
    }
    
    
    function _legacy_xmlrpc_cancel_translation($args){
        global $sitepress_settings, $sitepress, $wpdb;        
        $signature = $args[0];
        $website_id = $args[1];
        $request_id = $args[2];
        
        $accesskey = $sitepress_settings['access_key'];
        $checksum = $accesskey . $website_id . $request_id;
        
        $args['sid'] = sha1 ( $checksum );
        
        if (sha1 ( $checksum ) == $signature) {
            $wid = $sitepress_settings['site_id'];
            if ($website_id == $wid) {
        
                $cms_request_info = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}icl_core_status WHERE rid={$request_id}");
                
                if (empty($cms_request_info)){
                    return 4; // cms_request not found
                }
          
                // cms_request have been found.
                // delete it
        
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_core_status WHERE rid={$request_id}");
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_content_status WHERE rid={$request_id}");
                
                // find cms_id
                $nid = $wpdb->get_var($wpdb->prepare("SELECT nid FROM {$wpdb->prefix}icl_content_status WHERE rid=%d", $request_id));
                
                if($nid){
                    $trid = $wpdb->get_var($wpdb->prepare("
                        SELECT trid FROM {$wpdb->prefix}icl_translations 
                        WHERE element_id=%d AND post_type LIKE 'post\_%'", $nid)
                    );     

                    $translation = $wpdb->get_row($wpdb->prepare("SELECT translation_id FROM {$wpdb->prefix}icl_translations 
                        WHERE trid=%d AND language_code=%s", $trid, $cms_request_info->target)
                    );     
                    $original_element_id = $wpdb->get_var($wpdb->prepare("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code=%s", 
                        $translation->trid, $translation->source_language_code));
                    $cms_id = sprintf('%s_%d_%s_%s', preg_replace('#^post_#','', $translation->element_type, $original_element_id, $translation->source_language_code, $translation->language_code));                           }
                
                if($cms_id){
                    $args[3] = $cms_id;
                    return $this->_xmlrpc_cancel_translation($args);
                    
                }
                return 1;
                
            } else {
                return 3; // Website id incorrect
            }
        } else {
            return 2; // Signature failed
        }
      
        return 0; // Should not have got here - unknown error.
    } 
        
    
    function _test_xmlrpc(){ return true; }
    
    function _xmlrpc_add_message_translation($args){
        global $wpdb, $sitepress, $sitepress_settings, $wpml_add_message_translation_callbacks;
        $signature      = $args[0];
        $site_id        = $args[1];
        $rid            = $args[2];
        $translation    = $args[3];
        
        $signature_check = md5($sitepress_settings['access_key'] . $sitepress_settings['site_id'] . $rid);
        if($signature != $signature_check){
            return 0; // array('err_code'=>1, 'err_str'=> __('Signature mismatch','sitepress'));
        }
        
        $res = $wpdb->get_row("SELECT to_language, object_id, object_type FROM {$wpdb->prefix}icl_message_status WHERE rid={$rid}");
        if(!$res){
            return 0;
        }
        
        $to_language = $res->to_language;
        $object_id   = $res->object_id;
        $object_type   = $res->object_type;
        
        try{
            if(is_array($wpml_add_message_translation_callbacks[$object_type])){
                foreach($wpml_add_message_translation_callbacks[$object_type] as $callback){
                    if ( !is_null($callback) ) {
                        call_user_func($callback, $object_id, $to_language, $translation);    
                    } 
                }
            }                            
            $wpdb->update($wpdb->prefix.'icl_message_status', array('status'=>MESSAGE_TRANSLATION_COMPLETE), array('rid'=>$rid));
        }catch(Exception $e){
            return $e->getMessage().'[' . $e->getFile() . ':' . $e->getLine() . ']';
        }
        return 1;
        
    }
    
    function get_jobs_in_progress(){
        global $wpdb;
        $jip = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}icl_translation_status WHERE status=%d AND translation_service='icanlocalize'", ICL_TM_IN_PROGRESS));
        return $jip;
    }
    
    function get_strings_in_progress(){
        global $wpdb;
        $sip = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}icl_core_status WHERE status < %d", 3));
        return $sip;
    }    
    
    function pool_for_translations(){
        global $sitepress_settings, $sitepress, $wpdb;
        
        // Limit to once per hour
        $toffset = strtotime(current_time('mysql')) - $sitepress_settings['last_picked_up'] - 3600;
        if($toffset < 0){
            return 0;
        }
        
        $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);
        $pending = $iclq->cms_requests();
        
        $fetched = 0;
        if(!empty($pending)){
            foreach($pending as $doc){
                
                if(empty($doc['cms_id'])){ // it's a string
                    $target = $wpdb->get_var($wpdb->prepare("SELECT target FROM {$wpdb->prefix}icl_core_status WHERE rid=%d", $doc['id']));                    
                    $__ld = $sitepress->get_language_details($target);
                    $language = $this->server_languages_map($__ld['english_name']);                    
                    $ret = $this->process_translated_string($doc['id'], $language);
                    if($ret){
                        $fetched++;
                    }
                }else{
                    
                    // decode cms_id
                    $int = preg_match('#(.+)_([0-9]+)_([^_]+)_([^_]+)#', $doc['cms_id'], $matches);
                    
                    $_element_type  = $matches[1];
                    $_element_id    = $matches[2];
                    $_original_lang = $matches[3];
                    $_lang          = $matches[4];
                    
                    $trid = $sitepress->get_element_trid($_element_id, 'post_'. $_element_type);                    
                    $translation = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}icl_translations WHERE trid=%d AND language_code=%s", $trid, $_lang));                    
                    
                    $ret = $this->add_translated_document($translation->translation_id, $doc['id']);                
                    if($ret){
                        $fetched++;
                    }
                }
            }
        }
        
        $iclsettings['last_picked_up'] = strtotime(current_time('mysql'));
        $sitepress->save_settings($iclsettings);
        
        return $fetched;
    }
    
    function get_icl_manually_tranlations_box($wrap_class=""){
        global $sitepress_settings;
        
        if(isset($_GET['icl_pick_message'])){
            ?>
                <span id="icl_tm_pickup_wrap"><p><?php echo $_GET['icl_pick_message'] ?></p></div>
            <?php
        }
        if($sitepress_settings['translation_pickup_method'] == ICL_PRO_TRANSLATION_PICKUP_POLLING 
            && ($job_in_progress = $this->get_jobs_in_progress() || $this->get_strings_in_progress()))
        {
            $last_time_picked_up = !empty($sitepress_settings['last_picked_up']) ? date_i18n('Y, F jS @g:i a', $sitepress_settings['last_picked_up']) : __('never', 'sitepress'); 
            $toffset = strtotime(current_time('mysql')) - $sitepress_settings['last_picked_up'] - 3600;            
            if($toffset < 0){
                $gettdisabled = ' disabled="disabled" ';
                $waittext = '<p><i>' . sprintf(__('You can check again in %s minutes.', 'sitepress'), '<span id="icl_sec_tic">' . floor(abs($toffset)/60) . '</span>') . '</i></p>';
            }else{
                $waittext = '';
                $gettdisabled = '';
            }
            
            ?>
            <span id="icl_tm_pickup_wrap">
            
            <div class="<?php echo $wrap_class ?>">
            <p><?php printf(__('%d job(s) sent to ICanLocalize.', 'sitepress'), $job_in_progress); ?></p>
            <p><input type="button" class="button-secondary" value="<?php _e('Get completed translations', 'sitepress')?>" id="icl_tm_get_translations"<?php echo $gettdisabled ?>/><?php echo $waittext ?></p>                
            <p><?php printf(__('Last time translations were picked up: %s', 'sitepress'), $last_time_picked_up) ?></p>    
            </div></span><?php 
        }
    }
        
}  
?>
