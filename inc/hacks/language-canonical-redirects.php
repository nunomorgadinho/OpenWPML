<?php
if(defined('WP_ADMIN')) return;

add_action('template_redirect', 'icl_language_canonical_redirects', 1);

function icl_language_canonical_redirects () {
    global $wp_query, $sitepress_settings;
    if(3 == $sitepress_settings['language_negotiation_type'] && is_singular() && empty($wp_query->posts)){
        global $wpdb;
        $pid = get_query_var('p');
        $permalink = html_entity_decode(get_permalink($pid));
        if($permalink){
            header ('HTTP/1.1 301 Moved Permanently');
            header ('Location: '. $permalink);
            exit;
        }
    }
}  
?>
