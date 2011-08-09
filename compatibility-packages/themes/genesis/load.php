<?php
/*
Package Name: Compatibility package for Genesis
Package URI: http://wpml.org/
Description: This package enables basic Genesis-WPML compatibility.
Theme: genesis
Theme version: 1.0.2
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/

// Some properties that are inherited are:
// name  (the package identifier - in this case wp-default-theme) - very handy sometimes
// data  (meta information that you can see at the top of this post - just in case ypu need it)
// settings  (your package's settings as they are saved from the options it will register)
// type (e.g. themes or plugins)  
  
class Genesis_theme_compatibility  extends WPML_Package{
    
    function __construct(){
        parent::__construct();
        
        global $sitepress;
        if($sitepress->get_current_language() != $sitepress->get_default_language()){
            add_filter('genesis_seo_title', array($this, 'genesis_seo_title_filter'));
        }        
        
    }

    function genesis_seo_title_filter($str){
        $str = preg_replace('@href="([^"]+)"@i', 'href="'.icl_get_home_url().'"', $str);
        return $str;
    }
    
    function __destruct(){
        parent::__destruct();
    }
}

$Genesis_theme_compatibility = new Genesis_theme_compatibility();
?>