<?php
/*
Package Name: Extra options for the Hybrid theme framework
Package URI: http://wpml.org/
Description: This package enables basic Hybrid-WPML compatibility.
Theme: hybrid
Theme version: 0.6.1
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/

// Some properties that are inherited are:
// name  (the package identifier - in this case wp-default-theme) - very handy sometimes
// data  (meta information that you can see at the top of this post - just in case ypu need it)
// settings  (your package's settings as they are saved from the options it will register)
// type (e.g. themes or plugins)  
  
class Hybrid_theme_compatibility  extends WPML_Package{
    
    function __construct(){
        parent::__construct();
        
        $wpage = ICL_PLUGIN_FOLDER . '/menu/languages.php';
		$title = 'Hybrid - ';
		
			// Header switcher
		
        $this->add_option_checkbox(
			$wpage,
			__('Add a list of languages to the site\'s header','sitepress'),
			'header_language_selector',
			$title . __('Language selector options','sitepress'),
			'checked'
				);
		
        $this->add_option_checkbox(
			$wpage,
			__('Only include languages with translation in the languages list header', 'sitepress'),
			'header_skip_languages',
			$title . __('More options', 'sitepress'),
			'checked'
				);
		
		$this->add_option_checkbox(
			$wpage,
			__('Load CSS for header languages list', 'sitepress'),
			'header_load_css',
			$title . __('More options', 'sitepress'),
			'checked'
				);
		
		if($this->settings['header_language_selector']){
            add_action('hybrid_before_header',array(&$this,'language_selector_header'));
			if($this->settings['header_load_css']) {
				$this->load_css('css/selector-header.css');
			}
			$this->check_sidebar_language_selector_widget();
        }
		
		add_filter('hybrid_site_title',array(&$this,'filter_home_link'));
		add_filter('wp_page_menu',array(&$this,'filter_home_link'));
		
		$settings = get_option('hybrid_theme_settings');
		if ( $settings && !empty($settings['footer_insert']) ) {
			icl_register_string( 'theme '.$this->name, 'Footer text', $settings['footer_insert'] );
			add_action('hybrid_footer',array(&$this,'translate_footer_text'),0);
		}
		
		$this->load_css('css/compatibility-package.css');
    }

	function translate_footer_text() {
		global $hybrid, $hybrid_settings;
		$translation = icl_t('theme '.$this->name,'Footer text',$hybrid_settings['footer_insert']);
		$hybrid_settings['footer_insert'] = $translation;
		$hybrid->settings['footer_insert'] = $translation;
	}

    function __destruct(){
        parent::__destruct();
    }
}

$Hybrid_theme_compatibility = new Hybrid_theme_compatibility();
?>