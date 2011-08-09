<?php
$icl_ncp_plugins = array(
    'absolute-links/absolute-links-plugin.php',
    'cms-navigation/CMS-Navigation.php'
);  
$active_plugins = get_option('active_plugins');

$icl_ncp_plugins = array_intersect($icl_ncp_plugins, $active_plugins);

if(!empty($icl_ncp_plugins)){
    $icl_sitepress_disabled = true;
    $icl_sitepress_idx = array_search(ICL_PLUGIN_FOLDER . '/sitepress.php', $active_plugins);
    if(false !== $icl_sitepress_idx){
        unset($active_plugins[$icl_sitepress_idx]);
        update_option('active_plugins', $active_plugins);
        unset($_GET['activate']);
        $recently_activated = get_option('recently_activated');
        if(!isset($recently_activated[ICL_PLUGIN_FOLDER . '/sitepress.php'])){
            $recently_activated[ICL_PLUGIN_FOLDER . '/sitepress.php'] = time();
            update_option('recently_activated', $recently_activated);
        }
    }
    
    
    add_action('admin_notices', 'icl_incomp_plugins_warn');
    function icl_incomp_plugins_warn(){
        global $icl_ncp_plugins;
        echo '<div class="error"><ul><li><strong>';
        echo __('WPML cannot be activated together with these older plugins:', 'sitepress');
        echo '<ul style="list-style:disc;margin:20px;">';
        foreach($icl_ncp_plugins as $incp){
            echo '<li>'.$incp.'</li>';
        }
        echo '</ul>';
        echo __('WPML will be deactivated', 'sitepress');
        echo '</strong></li></ul></div>';        
    }
}else{
    $icl_sitepress_disabled = false;
}