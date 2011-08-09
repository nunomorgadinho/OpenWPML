<?php
/*
Package Name: Extra options for the Default theme (demo)
Package URI: http://wpml.org/
Description: This is a demo package that would illustrate how these packages should be buillt. It applies to the WP default theme.
Theme: default
Theme version: 1.6
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/
  
  

// Instructions: 
// 1. create your class by inheriting WPML_Package (defined in /inc/compatibility-packages/wpml-package.class.php - check it out to see what methods it has)  
// 2. instantiate the class
// Done.

// Some properties that are inherited are:
// name  (the package identifier - in this case wp-default-theme) - very handy sometimes
// data  (meta information that you can see at the top of this post - just in case ypu need it)
// settings  (your package's settings as they are saved from the options it will register)
// type (e.g. themes or plugins)  
  
class WP_Default_theme_compatibility  extends WPML_Package{
    
    // do call the constructor of the parent class
    function __construct(){
        parent::__construct();
        
        $wpage = ICL_PLUGIN_FOLDER . '/menu/languages.php';
		$title = 'Default - ';
		
		$this->load_css('css/compatibility-package.css');
    }
    
    // do call the destructor of the parent class
    function __destruct(){
        parent::__destruct();
    }
}


// make it happen
// instantiate the package class
$WP_Default_theme_compatibility = new WP_Default_theme_compatibility();


?>