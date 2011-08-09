<?php
  
function icl_pt_get_texts(){
    global $wpdb;
    $active_plugins = get_option('active_plugins');    
    $enabled_plugins = (array)get_option('icl_plugins_texts_enabled');
    foreach($active_plugins as $ap){
        $aps[] = "'" . $ap . "'";
    }
    $res = $wpdb->get_results("SELECT plugin_name, attribute_name, translate FROM {$wpdb->prefix}icl_plugins_texts WHERE plugin_name IN (". join(',', $aps).") ");
    foreach($res as $r){  
        $t = $r->translate?__('translate','sitepress'):__('synchronize','sitepress');      
        $rs[$r->plugin_name][] = $r->attribute_name . ' (' . $t . ')';
    }
    foreach($active_plugins as $ap){    
        $plugin_name_short = str_replace('_', ' ', false!==strpos($ap,'/')?dirname($ap):preg_replace('#\.php$#','',$ap));    
        if(isset($rs[$ap])){
            $fields_list = join(', ', $rs[$ap]);
            $active = 1;
        }else{
            if($plugin_name_short==ICL_PLUGIN_FOLDER){
                continue;
            }
            $fields_list = sprintf(__('WPML doesn\'t know how to translate this plugin. If it has texts that require translation, contact us by opening an issue in our forum: %s', 'sitepress'), '<a href="http://forum.wpml.org">http://forum.wpml.org</a>');
            $active = 0;
        }                
        //exception for WPML
        if($plugin_name_short==ICL_PLUGIN_FOLDER) $plugin_name_short = 'WPML';
        $texts[] = array(
            'active' => $active,
            'enabled' => intval(in_array($ap, $enabled_plugins)),
            'plugin_name' => $ap,
            'plugin_name_short' => $plugin_name_short,
            'fields_list' => $fields_list
        );
        
    }
    return $texts;
} 

function icl_get_posts_translatable_fields($only_sync = false){
    global $wpdb;
    $enabled_plugins = get_option('icl_plugins_texts_enabled');    
    if(!empty($enabled_plugins)){
        foreach($enabled_plugins as $ap){
            $aps[] = "'" . $ap . "'";
        }    
        if($only_sync == true){
            $extra_cond = ' AND translate = 0';
        }else{
            $extra_cond = '';
        }
        $res = $wpdb->get_results("SELECT plugin_name, attribute_name, attribute_type, translate FROM {$wpdb->prefix}icl_plugins_texts WHERE plugin_name IN (". join(',', $aps).") {$extra_cond}");
    }else{
        $res = array();
    }
    return $res;
} 

function icl_pt_sync_pugins_texts($post_id, $trid){
    global $sitepress;
    if(!$trid) return;
    $translations = $sitepress->get_element_translations($trid);
    $fields_2_sync = icl_get_posts_translatable_fields(true);
    $custom_fields = array();
    foreach($fields_2_sync as $f2s){
        if($f2s->attribute_type == 'custom_field'){
            $custom_fields[] = $f2s->attribute_name;
        }
    } 
    if(!empty($custom_fields)){
        $sitepress->sync_custom_fields($post_id);
    }    
}

function icl_pt_handle_upload(){
    global $wpdb;    
    $file = $_FILES['plugins_texts_csv'];
    
    $fh = fopen($file['tmp_name'], 'r');
    $uplerr = false;
    while($data = fgetcsv($fh)){
        if(count($data) != 5){
            $uplerr = __('Incorrect CSV file format (there should be exactly 5 columns).','sitepress');            
            break;
        }
        if($data[4] != 0  && $data[4] != 1){
            $uplerr = __('The fifth column values should be \'0\' or \'1\'.','sitepress');            
            break;
        }        
        if(!isset($plugin)){
            $plugin = $data[0];
        }else{
            if($data[0] != $plugin){
                $uplerr = __('Inconsistent plugin name (the first column should have identical values).','sitepress');                
                break;
            }
        }                
    }
    fclose($fh);            
    if($file['error']==0 && $file['size'] && !$uplerr){
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_plugins_texts WHERE plugin_name='{$plugin}'");
        $fh = fopen($file['tmp_name'], 'rb');
        while($data = fgetcsv($fh)){
            $wpdb->insert($wpdb->prefix.'icl_plugins_texts', array(   
                    'plugin_name'=>substr($data[0],0,128),
                    'attribute_type' => substr($data[1], 0, 64),
                    'attribute_name' => substr($data[2], 0, 128),
                    'description'    => $data[3],
                    'translate'      => $data[4]
                )
            );
        }
        fclose($fh);
    }else{      
        if(!$file['size']){
            $uplerr = __('Please select a CSV file to upload.', 'sitepress');            
        }elseif(!$uplerr){
            $uplerr = $file['error'];
        }
    }    
    return $uplerr;        
}

?>