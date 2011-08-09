addLoadEvent(function(){     
    jQuery('#icl_theme_localization').submit(iclSaveThemeLocalization);
    jQuery('#icl_theme_localization_type').submit(iclSaveThemeLocalizationType);
    jQuery('#icl_tl_rescan').click(iclThemeLocalizationRescan);
    jQuery('#icl_tl_rescan_p').submit(iclThemeLocalizationRescanP);
    
    jQuery('.check-column :checkbox').live('change', iclCheckColumn);
});

function iclSaveThemeLocalization(){
    var ajx = location.href.replace(/#(.*)$/,'');
    if(-1 == location.href.indexOf('?')){
        url_glue='?';
    }else{
        url_glue='&';
    }
    spl = jQuery(this).serialize().split('&');    
    var parameters = {};
    for(var i=0; i< spl.length; i++){        
        var par = spl[i].split('=');
        eval('parameters.' + par[0] + ' = par[1]');
    }    
    jQuery('#icl_theme_localization_wrap').load(location.href + ' #icl_theme_localization_subwrap', parameters, function(){
        fadeInAjxResp('#icl_ajx_response_fn', icl_ajx_saved);                                                 
    }); 
    return false;   
}

function iclSaveThemeLocalizationType(){
    
    var formname = jQuery(this).attr('name');
    ajx_resp = jQuery('form[name="'+formname+'"] .icl_ajx_response').attr('id');
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action="+jQuery(this).attr('name')+"&"+jQuery(this).serialize(),
        success: function(msg){
            spl = msg.split('|');
            location.href=location.href.replace(/#(.*)$/,'');
        }
    });
    return false;         
}

function iclThemeLocalizationRescan(){
    var thisb = jQuery(this);
    thisb.next().fadeIn();
    var data = "icl_ajx_action=icl_tl_rescan";
    if(jQuery('#icl_load_mo_themes').attr('checked')){
        data += '&icl_load_mo=1';
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: data,
        success: function(msg){
            thisb.next().fadeOut();
            spl = msg.split('|');
            jQuery('#icl_tl_scan_stats').html(spl[1]).fadeIn();
            jQuery("#icl_strings_in_theme_wrap").load(location.href.replace(/#(.*)$/,'') + ' #icl_strings_in_theme');
        }
    });    
    return false;
}

function iclThemeLocalizationRescanP(){
    var thisf = jQuery(this);
    thisf.contents().find('.icl_ajx_loader_p').fadeIn();
    thisf.contents().find('input:submit').attr('disabled','disabled');

    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=icl_tl_rescan_p&"+thisf.serialize(),
        success: function(msg){
            thisf.contents().find('.icl_ajx_loader_p').fadeOut();
            thisf.contents().find('input:submit').removeAttr('disabled');
            spl = msg.split('|');
            jQuery('#icl_tl_scan_stats_p').html(spl[1]).fadeIn();
            jQuery("#icl_strings_in_plugins_wrap").load(location.href.replace(/#(.*)$/,'') + ' #icl_strings_in_plugins');
        }
    });    
    return false;
}

function iclCheckColumn(){
    if(jQuery(this).attr('checked')){
        jQuery('#icl_strings_in_plugins :checkbox').attr('checked','checked');
    }else{
        jQuery('#icl_strings_in_plugins :checkbox').removeAttr('checked');
    }    
}