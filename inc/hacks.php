<?php
//using this file to handle particular situations that would involve more ellaborate solutions

add_action('init', 'icl_load_hacks');  

function icl_load_hacks(){    
    include ICL_PLUGIN_PATH . '/inc/hacks/language-domains-preview.php';    
    //include ICL_PLUGIN_PATH . '/inc/hacks/supress-warnings-for-xmlrpc.php';        
    include ICL_PLUGIN_PATH . '/inc/hacks/language-canonical-redirects.php';            
}


include ICL_PLUGIN_PATH . '/inc/hacks/missing-php-functions.php';            
?>