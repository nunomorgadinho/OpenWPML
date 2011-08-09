<?php
/* This file includes a set of funcitons that can be used by WP plugins developers to make their plugins interract with WPML */  

/* constants */
define('WPML_API_SUCCESS' , 0);
define('WPML_API_ERROR' , 99);
define('WPML_API_INVALID_LANGUAGE_CODE' , 1);
define('WPML_API_INVALID_TRID' , 2);
define('WPML_API_LANGUAGE_CODE_EXISTS' , 3);
define('WPML_API_CONTENT_NOT_FOUND' , 4);
define('WPML_API_TRANSLATION_NOT_FOUND' , 5);
define('WPML_API_INVALID_CONTENT_TYPE' , 6);
define('WPML_API_CONTENT_EXISTS' , 7);
define('WPML_API_FUNCTION_ALREADY_DECLARED', 8);
define('WPML_API_CONTENT_TRANSLATION_DISABLED', 9);

define('WPML_API_GET_CONTENT_ERROR' , 0);

define('WPML_API_MAGIC_NUMBER', 6);
define('WPML_API_ASIAN_LANGUAGES', 'zh-hans|zh-hant|ja|ko');
define('WPML_API_COST_PER_WORD', 0.07);


function _wpml_api_allowed_content_type($content_type){
    $reserved_types = array(
        'post'      => 1, 
        'page'      => 1, 
        'tax_post_tag'       => 1, 
        'tax_category'  => 1,
        'comment'   => 1
    );
    return !isset($reserved_types[$content_type]) && preg_match('#([a-z0-9_\-])#i', $content_type);
}

/**
 * Add translatable content to the WPML translations table
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param string $language_code Content language code. (defaults to current language)
 * @param int $trid Content trid - if a translation in a different language already exists.
 * 
 * @return int error code
 *  */
function wpml_add_translatable_content($content_type, $content_id, $language_code = false, $trid = false){
    global $sitepress, $wpdb;
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    if($trid){
        $trid_type   = $wpdb->get_var("SELECT element_type FROM {$wpdb->prefix}icl_translations WHERE trid='{$trid}'");
        if(!$trid_type || $trid_type != $content_type){
            return WPML_API_INVALID_TRID;
        }
    }
    
    if($wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE element_type='".$wpdb->escape($content_type)."' AND element_id='{$content_id}'")){
        return WPML_API_CONTENT_EXISTS;
    }
    
    $t = $sitepress->set_element_language_details($content_id, $content_type, $trid, $language_code);        
    
    if(!$t){
        return WPML_API_ERROR;
    }else{
        return WPML_API_SUCCESS;
    }
    
}



/**
 * Update translatable content in the WPML translations table
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param string $language_code Content language code.
 *  
 * @return int error code
 *  */
function wpml_update_translatable_content($content_type, $content_id, $language_code){
    global $sitepress, $wpdb;
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if(!$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }
    
    $translations = $sitepress->get_element_translations($trid);
    if(isset($translations[$language_code]) && !$translations[$language_code]->element_id != $content_id){
        return WPML_API_LANGUAGE_CODE_EXISTS;
    }
            
    $t = $sitepress->set_element_language_details($content_id, $content_type, $trid, $language_code);        
    
    if(!$t){
        return WPML_API_ERROR;
    }else{
        return WPML_API_SUCCESS;
    }
    
}

/**
 * Update translatable content in the WPML translations table
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param string $language_code Content language code. (when ommitted - delete all translations associated with the respective content)
 *  
 * @return int error code
 *  */
function wpml_delete_translatable_content($content_type, $content_id, $language_code = false){
    global $sitepress, $wpdb;
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_INVALID_CONTENT_TYPE;
    }

    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }
    
    if($language_code){
        $translations = $sitepress->get_element_translations($trid);
        if(!isset($translations[$language_code])){
            return WPML_API_TRANSLATION_NOT_FOUND;
        }
        
    }
    
    $sitepress->delete_element_translation($trid, $content_type, $language_code);
            
    return WPML_API_SUCCESS;
}

/**
 * Get trid value for a specific piece of content
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 *    
 * @return int trid or 0 for error
 *  */
function wpml_get_content_trid($content_type, $content_id){
    global $sitepress;
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return WPML_API_GET_CONTENT_ERROR; //WPML_API_INVALID_CONTENT_TYPE;
    }
    
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    
    if(!$trid){
        return WPML_API_GET_CONTENT_ERROR;
    }else{
        return $trid;
    } 
} 

/**
 * Detects the current language and returns the language relevant content id. optionally it can return the original id if a translation is not found 
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param bool $return_original return the original id when translation not found.
 *    
 * @return int trid or 0 for error
 *  */

function wpml_get_content($content_type, $content_id, $return_original = true){
    global $sitepress, $wpdb;
    
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    
    if(!$trid){
        return WPML_API_GET_CONTENT_ERROR;
    }else{
        if($content_id <= 0){
            return $content_id;
        } 
        if($content_type=='category' || $content_type=='post_tag' || $content_type=='tag'){
            $content_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id= %d AND taxonomy='{$content_type}'",$content_id));
        }
        if($content_type=='post_tag'){
            $icl_element_type = 'tax_post_tag';
        }elseif($content_type=='category'){
            $icl_element_type = 'tax_category';
        }elseif($content_type=='page'){
            $icl_element_type = 'post';
        }else{
            $icl_element_type = $content_type;
        }
        
        $trid = $sitepress->get_element_trid($content_id, $icl_element_type);
        $translations = $sitepress->get_element_translations($trid, $icl_element_type);
        
        if(isset($translations[ICL_LANGUAGE_CODE]->element_id)){
            $ret_element_id = $translations[ICL_LANGUAGE_CODE]->element_id;
            if($element_type=='category' || $element_type=='post_tag'){
                $ret_element_id = $wpdb->get_var($wpdb->prepare("SELECT t.term_id FROM {$wpdb->term_taxonomy} tx JOIN {$wpdb->terms} t ON t.term_id = tx.term_id WHERE tx.term_taxonomy_id = %d AND tx.taxonomy='{$content_type}'", $ret_element_id));            
            }
        }else{
            $ret_element_id = $return_original ? $content_id : null;
        }
        
        return $ret_element_id;
    } 
}

/**
 * Get translations for a certain piece of content 
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param bool $return_original return the original id when translation not found.
 *    
 * @return int trid or error code
 *  */
function wpml_get_content_translations($content_type, $content_id, $skip_missing = true){
    global $sitepress;
        
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_TRANSLATION_NOT_FOUND;
    }
    
    $translations = $sitepress->get_element_translations($trid, $content_type, $skip_missing);
    
    $tr = array();
    foreach($translations as $k=>$v){
        $tr[$k] = $v->element_id;
    }
    
    return $tr;
}

/**
 *  Returns a certain translation for a piece of content 
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 * @param int $content_id Content ID.
 * @param bool $language_code 
 *    
 * @return error code or array('lang'=>element_id)
 *  */
function wpml_get_content_translation($content_type, $content_id, $language_code){
    global $sitepress;
        
    $trid = $sitepress->get_element_trid($content_id, $content_type);
    if(!$trid){
        return WPML_API_CONTENT_NOT_FOUND;
    }
        
    $translations = $sitepress->get_element_translations($trid, $content_type, true);
    
    if(!isset($translations[$language_code])){
        return WPML_API_TRANSLATION_NOT_FOUND;
    }else{
        return array($language_code => $translations[$language_code]->element_id);
    }
    
}

/**
 *  Returns the list of active languages
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 *    
 * @return array
 *  */
function wpml_get_active_languages(){
    global $sitepress;
    $langs = $sitepress->get_active_languages();        
    return $langs;
}

/**
 *  Returns the default language
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 *    
 * @return string
 *  */
function wpml_get_default_language(){
    global $sitepress;
    return $sitepress->get_default_language();
}


/**
 *  Get current language
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 * 
 * @return string
 *  */
function wpml_get_current_language(){
    global $sitepress;
    return $sitepress->get_current_language();
}

/**
 *  Get contents of a specific type
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type Content type.
 *    
 * @return int or array
 *  */

function wpml_get_contents($content_type, $language_code = false){
    global $sitepress, $wpdb;
    
    if($language_code && !$sitepress->get_language_details($language_code)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    if(!$language_code){
        $language_code = $sitepress->get_current_language();
    }
    
    $contents = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='".$wpdb->escape($content_type)."' AND language_code='{$language_code}'");
    return $contents;
    
}

/**
 *  Returns google translation for given string
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $string String
 * @param string $from_language Language to translate from
 * @param string $to_language Language to translate into
 *    
 * @return int (error code) or string
 *  */
function wpml_machine_translation($string, $from_language, $to_language){
    global $sitepress;
    
    if(!$sitepress->get_language_details($from_language) || !$sitepress->get_language_details($to_language)){
        return WPML_API_INVALID_LANGUAGE_CODE; 
    }
    
    return IclCommentsTranslation::machine_translate($from_language, $to_language, $string);    
}

/**
 *  Sends piece of content (string) to professional translation @ ICanLocalize
 *  
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $string String
 * @param string $from_language Language to translate from
 * @param int $content_id Content ID
 * @param string $content_type Content Type
 * @param string $to_language Language to translate into
 *    
 * @return int request id
 *  */
function wpml_send_content_to_translation($string, $content_id, $content_type, $from_language, $to_language){
    global $sitepress, $sitepress_settings, $wpdb;
    
    if(!$sitepress->get_icl_translation_enabled()){
        return 0; //WPML_API_CONTENT_TRANSLATION_DISABLED
    }
    
    if(!_wpml_api_allowed_content_type($content_type)){
        return 0; //WPML_API_INVALID_CONTENT_TYPE
    }
    
    if(!$sitepress->get_language_details($from_language) || !$sitepress->get_language_details($to_language)){
        return 0; // WPML_API_INVALID_LANGUAGE_CODE
    }
    
    $from_lang = $sitepress->get_language_details($from_language);
    $to_lang   = $sitepress->get_language_details($to_language);    
    $from_lang_server = apply_filters('icl_server_languages_map', $from_lang['english_name']);
    $to_lang_server = apply_filters('icl_server_languages_map', $to_lang['english_name']);
    
    $iclq = new ICanLocalizeQuery($sitepress_settings['site_id'], $sitepress_settings['access_key']);    
    
    $rid = $iclq->cms_create_message($string, $from_lang_server, $to_lang_server);
    
    if($rid > 0){
        // does this comment already exist in the messages status queue?
        $msid = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_message_status WHERE object_type='{$content_type}' AND object_id={$content_id}");
        if($msid){
            $wpdb->update($wpdb->prefix.'icl_message_status', 
                array('rid'=>$rid, 'md5' => md5($string), 'status' => MESSAGE_TRANSLATION_IN_PROGRESS),
                array('id' => $msid)
                );
        }else{
            $wpdb->insert($wpdb->prefix.'icl_message_status', array(
                'rid'           => $rid,
                'object_id'     => $content_id,
                'from_language' => $from_language,
                'to_language'   => $to_language,
                'md5'           => md5($string),
                'object_type'   => $content_type,
                'status'        => MESSAGE_TRANSLATION_IN_PROGRESS
            ));
        }
    }  
    
    return $rid;  
}

/**
 * Registers a callback for when a translation is received from the server.
 * The callback parameters are int $request_id, string $content, string $language
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $content_type
 * @param string $callback
 *    
 * @return error code (0 on success)
 *  */
function wpml_add_callback_for_received_translation($content_type, $callback){
    global $wpml_add_message_translation_callbacks;
    $wpml_add_message_translation_callbacks[$content_type][] = $callback;
    return 0;    
}

/**
 * Returns the number of the words that will be sent to translation and a cost estimate
 * @since 1.3
 * @package WPML
 * @subpackage WPML API
 *
 * @param string $string
 * @param string $language - should be specified when the language is one of zh-hans|zh-hant|ja|ko
 *    
 * @return array (count, cost)
 *  */
function wpml_get_word_count($string, $language = false){
    
    $asian_languages = explode('|', WPML_API_ASIAN_LANGUAGES);
    
    if($language && in_array($language, $asian_languages)){
        $count = ceil(mb_strlen($string)/WPML_API_MAGIC_NUMBER);
    }else{
        $count = count(explode(' ', $string));
    }
    
    $cost  = $count * WPML_API_COST_PER_WORD;
    
    $ret = array('count'=>$count, 'cost'=>$cost);
        
    return $ret;
    
}
?>
