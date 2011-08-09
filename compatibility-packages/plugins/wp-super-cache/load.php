<?php
/*
Package Name: WP Super Cache Integration
Package URI: http://wpml.org/
Description: Updates cache on certain WPML actions
Plugin: wp-super-cache/wp-cache.php
Plugin version: 0.9.9
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/
  
  

  
class WP_CPI_WP_Super_Cache extends WPML_Package{
    
    function __construct(){
        parent::__construct();
    }
    
    public function clear_cache(){
        if(function_exists('wp_cache_clean_cache')){
            global $file_prefix;
            wp_cache_clean_cache($file_prefix);            
        }
    }
    
    function __destruct(){
        parent::__destruct();
    }
}

// don't really need to instantiate this one
// $WP_CPI_WP_Super_Cache = new WP_CPI_WP_Super_Cache();


?>