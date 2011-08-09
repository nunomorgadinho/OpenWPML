jQuery(document).ready(function(){
    jQuery('#icl_menu_language').live('change', icl_wp_nav_language_change);
});

function icl_wp_nav_language_change(){
    var thiss = jQuery(this);
    thiss.attr('disabled', 'disabled');
    var trid = jQuery('#icl_nav_menu_trid').val();
    data = {icl_wp_nav_menu_ajax:'translation_of', lang:jQuery(this).val(), trid:trid}
    jQuery.ajax({
        type: 'POST',
        data: data,
        url: location.href,
        success: function(res){
            jQuery('#icl_translation_of_wrap').html(res);
            thiss.removeAttr('disabled');
        }
    });
}