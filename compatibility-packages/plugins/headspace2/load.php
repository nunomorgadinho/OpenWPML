<?php
/*
Package Name: Compatibility for Headspace2 SEO
Package URI: http://wpml.org/
Description: Makes Headspace2 SEO compatible with WPML
Plugin: headspace2/headspace.php
Plugin version: 3.6.32
Author: WPML
Author URI: http://www.onthegosystems.com
Version: 1.0
*/
  
class WP_Headspace2_SEO_compatibility  extends WPML_Package{
    
	
    var $context = 'plugin headspace2';
    var $current_option;
    var $translatable_strings = array(
            'headspace_global' => array(
                'title' => 'Global Settings',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_page' => array(
                'title' => 'Global Settings Page',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_post' => array(
                'title' => 'Global Settings Post',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_attachment' => array(
                'title' => 'Global Settings Attachment',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_login' => array(
                'title' => 'Global Settings Login',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_404' => array(
                'title' => 'Global Settings 404',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_search' => array(
                'title' => 'Global Settings Search',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_category' => array(
                'title' => 'Global Settings Category',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_author' => array(
                'title' => 'Global Settings Author',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_home' => array(
                'title' => 'Global Settings Home',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_front' => array(
                'title' => 'Global Settings Front',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_tags' => array(
                'title' => 'Global Settings Tags',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_taxonomy' => array(
                'title' => 'Global Settings Taxonomy',
                'values' => array('page_title','description','keywords')
                ),
            'headspace_archive' => array(
                'title' => 'Global Settings Archive',
                'values' => array('page_title','description','keywords')
                )
        );
	
    function __construct(){
        parent::__construct();
        if(!is_admin()) {
            foreach ($this->translatable_strings as $option => $value) {
                $option_value = get_option($option);
                if (!$option_value) continue;
                $this->current_option = $option;
                add_filter('option_'.$option,array(&$this,'filter_option'));
            }
            add_filter('option_headspace_options',array(&$this,'filter_option_firsttimevisitor'));
        } else {
            foreach ($this->translatable_strings as $option => $value) {
                $option_value = get_option($option);
                if (!$option_value) continue;
                foreach($value['values'] as $v){
                    if (!$option_value[$v]) continue;
                    icl_register_string( $this->context, $value['title'].' - '.$this->make_title($v), $option_value[$v] );
                }
            }
            $first_time = get_option ('headspace_options');
            if ( isset($first_time['site']['hss_firsttimevisitor']['message']) && !empty($first_time['site']['hss_firsttimevisitor']['message']) )
                icl_register_string( $this->context, 'Site Modules - First time visitor message', $first_time['site']['hss_firsttimevisitor']['message'] );
        }
    }

	function filter_option($value){
        foreach ( $this->translatable_strings[$this->current_option]['values'] as $v ) {
            $value[$v] = icl_t( $this->context, $this->translatable_strings[$this->current_option]['title'].' - '.$this->make_title($v), $value[$v] );
        }
        return $value;
    }

    function filter_option_firsttimevisitor($value){
        if ( !isset($value['site']['hss_firsttimevisitor']['message']) || empty($value['site']['hss_firsttimevisitor']['message']) ) return $value;
        $value['site']['hss_firsttimevisitor']['message'] = icl_t( $this->context, 'Site Modules - First time visitor message', $value['site']['hss_firsttimevisitor']['message']);
        return $value;
    }

    function make_title($string){
        return ucfirst(str_replace('_',' ',$string));
    }

    function __destruct(){
        parent::__destruct();
    }
}

$WP_Headspace2_SEO_compatibility = new WP_Headspace2_SEO_compatibility();
?>
