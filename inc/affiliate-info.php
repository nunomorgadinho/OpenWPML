<?php

if( !defined('DEBUG_WPML_AFFILIATE') || !DEBUG_WPML_AFFILIATE) return; 
  
add_action('admin_menu', 'icl_affiliate_info_menu', 20); 

function icl_affiliate_info_menu(){
    add_submenu_page(basename(ICL_PLUGIN_PATH).'/menu/translation-management.php', __('Affiliate','sitepress'), __('Affiliates','sitepress'),
                    'manage_options', basename(ICL_PLUGIN_PATH).'/menu/affiliate-info.php'); 
    
}
  
?>
