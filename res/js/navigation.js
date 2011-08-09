addLoadEvent(function(){         
    jQuery('#icl_navigation_show_cat_menu').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('#icl_cat_menu_contents').fadeIn();
        }else{
            jQuery('#icl_cat_menu_contents').fadeOut();
        }
    })
    jQuery('#icl_navigation_form').submit(iclSaveForm);

    jQuery('#icl_navigation_caching_clear').click(clearNavigationCache);    
    
});

function clearNavigationCache() {
    fadeInAjxResp('#icl_ajx_response_clear_cache', icl_ajxloaderimg);
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=icl_clear_nav_cache",
        success: function(msg){
            fadeInAjxResp('#icl_ajx_response_clear_cache', icl_ajx_cache_cleared);                                         
        }
    });
}