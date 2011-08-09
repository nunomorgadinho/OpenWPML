<?php
/*
Package Name: Extra options for the Atahualpa theme
Package URI: http://wpml.org/
Description: This package enables basic Atahualpa-WPML compatibility.
Theme: atahualpa
Theme version: 3.4.4
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/


class Atahualpa_theme_compatibility  extends WPML_Package{
    
		var $translatable_strings = array(
			'homepage_meta_description',
			'homepage_meta_keywords',
			'searchbox_text',
			'post_feed_link_title',
			'home_page_menu_bar',
			'home_cat_menu_bar',
			'default_cat_descr_text',
			'home_multi_next_prev',
			'home_single_next_prev',
			'multi_next_prev_newer',
			'multi_next_prev_older',
			'single_next_prev_newer',
			'single_next_prev_older',
			'comments_next_prev_newer',
			'comments_next_prev_older',
			'widget_title_box',
			'widget_content',
			'post_byline_home',
			'post_byline_multi',
			'post_byline_single',
			'post_kicker_page',
			'post_byline_page',
			'post_footer_home',
			'post_footer_multi',
			'post_footer_single',
			'post_footer_page',
			'custom_read_more',
			'more_tag',
			'comment_reply_link_text',
			'comment_edit_link_text',
			'comment_moderation_text',
			'comments_are_closed_text',
			'footer_style_content',
			'archives_category_title'
			);
	
    function __construct(){
        parent::__construct();
        
		if (is_admin()) add_action('init',array(&$this,'register_strings'));
		else add_action('get_header',array(&$this,'filter_options'));
		
        $wpage = ICL_PLUGIN_FOLDER . '/menu/languages.php';
		$title = 'Atahualpa - ';
        
			// Widget switcher CSS
		$this->add_option_checkbox($wpage, __('Load CSS for language selector widget', 'sitepress'), 'widget_load_css', $title . __('More options', 'sitepress'), 'checked');
		
		
		
		if($this->settings['widget_load_css']) {
			$this->load_css('css/selector-widget.css');
		} else {
			if(!defined('ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS'))
				define('ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS',true);
		}
		
		$this->load_css('css/compatibility-package.css');
    }

	function make_title($string){
		return ucfirst(str_replace('_',' ',$string));
	}

	function register_strings(){
		global $bfa_ata;
		foreach ($this->translatable_strings as $string) {
			icl_register_string( 'theme '.$this->name, $this->make_title($string), $bfa_ata[$string] );
		}
	}

	function filter_options(){
		global $bfa_ata;
		$bfa_ata['get_option_home'] = rtrim(icl_get_home_url(),'/');
		foreach ($this->translatable_strings as $string) {
			$bfa_ata[$string] = icl_t( 'theme '.$this->name, $this->make_title($string), $bfa_ata[$string] );
		}
	}

    function __destruct(){
        parent::__destruct();
    }
}

$Atahualpa_theme_compatibility = new Atahualpa_theme_compatibility();
?>