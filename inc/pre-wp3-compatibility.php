<?php 
if(version_compare(preg_replace('#-(.+)#','',$wp_version), '3.0', '<')){
    define('ICL_PRE_WP3', true);

    // redirect post-new.php?post_type='page' to page-new.php
    if(is_admin() && $pagenow=='post-new.php' && isset($_GET['post_type']) && $_GET['post_type']=='page'){
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: page-new.php?".$_SERVER['QUERY_STRING']);
        exit;
    }
    
    if(is_admin() && $pagenow=='edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy']=='category'){
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: categories.php?".$_SERVER['QUERY_STRING']);
        exit;
    }
    
    
}else{
    define('ICL_PRE_WP3', false);
}

if(!isset($wp_post_types)){
    $__pw3_post = new stdClass();
    $__pw3_post->labels = new stdClass();
    $__pw3_post->labels->name = 'Posts';
    $__pw3_post->labels->singular_name = 'Post';

    $__pw3_page = new stdClass();
    $__pw3_page->labels = new stdClass();
    $__pw3_page->labels->name = 'Pages';
    $__pw3_page->labels->singular_name = 'Page';
    
    $wp_post_types = array(
        'post' => $__pw3_post,
        'page' => $__pw3_page,
    );
    unset($__pw3_post, $__pw3_page);
}


if(!function_exists('get_user_meta')){
    function get_user_meta($k,$v,$single){
        return get_usermeta($k, $v);
    }
}

?>
