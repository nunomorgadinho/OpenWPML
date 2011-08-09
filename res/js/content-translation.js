var icl_language_pairs_updated = false;

addLoadEvent(function(){         
    jQuery('.icl_language_pairs .icl_tr_from').change(toggleTranslationPairsSub);
    jQuery('.icl_language_pairs .icl_tr_from').change(iclShowNextButtonStep1);
    jQuery('.icl_tr_to').change(iclShowNextButtonStep1);
    jQuery('#icl_save_language_pairs').click(saveLanguagePairs);    
    jQuery('#icl_save_site_description').click(saveSiteDescription);    
    jQuery('#icl_save_account_transfer').click(doAccountTransfer);    
    jQuery('form[name="icl_more_options"]').submit(iclSaveForm);
    jQuery('form[name="icl_more_options"]').submit(iclSaveMoreOptions);
    jQuery('form[name="icl_editor_account"]').submit(iclSaveForm);    
    jQuery('#icl_enable_content_translation,#icl_disable_content_translation').click(iclToggleContentTranslation);
    jQuery('a[href="#icl-ct-advanced-options"]').click(iclToggleAdvancedOptions);        
    jQuery('a[href="#icl-show_disabled_langs"]').click(iclToggleMoreLanguages);        
    jQuery('input[name="icl_content_trans_setup_cancel"]').click(iclWizardCancel)
    
    jQuery('.handlediv').click(function(){
        if(jQuery(this).parent().hasClass('closed')){
            jQuery(this).parent().removeClass('closed');
        }else{
            jQuery(this).parent().addClass('closed');
        }
    })
    
    if (jQuery('input[name="icl_content_trans_setup_next_1"]').length > 0) {
        iclShowNextButtonStep1();
    }
    
    jQuery('#icl_save_language_pairs').click(function(){icl_language_pairs_updated = true});    
    jQuery('.icl_cost_estimate_toggle').click(function(){jQuery('#icl_cost_estimate').slideToggle()});
    jQuery('.icl_account_setup_toggle').click(icl_toggle_account_setup);
    
    if (location.href.indexOf("show_config=1") != -1) {
        icl_toggle_account_setup();
        location.href = location.href.replace("&show_config=1", "")
        location.href = location.href.replace("?show_config=1&", "&")
        location.href = location.href.replace("?show_config=1", "")
        location.href = location.href + '#icl_account_setup';
    }

    
    
});

function icl_toggle_account_setup(){
    if(jQuery('#icl_languages_translators_stats').is(':visible')){
        jQuery('#icl_languages_translators_stats').slideUp();
    }else{
        if(icl_language_pairs_updated){
            jQuery('#icl_languages_translators_stats').html('<div align="left" style="margin-bottom:5px;">'+icl_ajxloaderimg+"</div>").fadeIn();
            location.href = location.href.replace(/#(.*)$/g,'');    
            /*                
            jQuery('#icl_languages_translators_stats').load(location.href + ' #icl_languages_translators_stats > *', {}, function(){
                    icl_tb_init('a.icl_thickbox');
                    icl_tb_set_size('a.icl_thickbox');
            });
            */
        }else{
            jQuery('#icl_languages_translators_stats').slideDown();
        }
    }
    jQuery('#icl_account_setup').slideToggle();
    jQuery('.icl_account_setup_toggle_main').toggle();
    return false;
};

function iclSaveMoreOptions() {
    jQuery('input[name="icl_translator_choice"]:checked').each(function(){
        if (this.value == '1') {
            jQuery('#icl_own_translators_message').css("display", "");
        } else {
            jQuery('#icl_own_translators_message').css("display", "none");
        }
    });
}

function iclWizardCancel() {
    if(!confirm(jQuery('#icl_toggle_ct_confirm_message').html())){
        return false;
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=toggle_content_translation&new_val=0",
        success: function(msg){
            location.href=location.href;
        }
    });         
    
}

function iclShowNextButtonStep1() {
    // See if we have a language pair selected and enable the button if we have.
    var found = false;
    
    jQuery('.icl_tr_from:checked').each(function(){
        var from = this.id.substring(13);
        jQuery('.icl_tr_to:checked').each(function(){
            if (this.id.substr(13, 2) == from){
                found = true;
            }
        })
    });
    
    if (found) {
        jQuery('input[name="icl_content_trans_setup_next_1"]').attr("disabled", "");
    } else {
        jQuery('input[name="icl_content_trans_setup_next_1"]').attr("disabled", "disabled");
    }
}

function toggleTranslationPairsSub(){
    var code = jQuery(this).attr('name').split('_').pop();
    if(jQuery(this).attr('checked')){
        jQuery('#icl_tr_pair_sub_'+code).slideDown();
    }else{
        // we should leave any to languages checked.
        //jQuery('#icl_tr_pair_sub_'+code+' input[type="checkbox"]').removeAttr('checked');
        
        //jQuery('#icl_tr_pair_sub_'+code).slideUp();
        // NOTE:
        // slideup is not working in wp2.8.4 so set display to none instead.
        jQuery('#icl_tr_pair_sub_'+code).css("display", "none");
    }            
}

function saveLanguagePairs(){
    fadeInAjxResp('#icl_ajx_response', icl_ajxloaderimg);
    var qargs = new Array();
    qargs.push(jQuery('#icl_language_pairs_form').serialize());
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=save_language_pairs&"+qargs.join('&'),
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                url = location.href;
                url = url.replace(/&icl_refresh_langs=1/g, '');
                url = url.replace(/&show_config=1/g, '');
                url = url.replace(/#.*/,'');
                url += "&icl_refresh_langs=1&show_config=1";

                location.href = url;
            }else{                        
                fadeInAjxResp('#icl_ajx_response',icl_ajx_error + spl[1],true);
            }  
        }
    }); 
    
}

function saveSiteDescription(){
    fadeInAjxResp('#icl_ajx_response_site', icl_ajxloaderimg);
    var qargs = new Array();
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=save_site_description&"+jQuery('#icl_site_description').serialize(),
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp('#icl_ajx_response_site',spl[1],true);
            }else{                        
                fadeInAjxResp('#icl_ajx_response_site',icl_ajx_error + spl[1],true);
            }  
        }
    }); 
    
}

function doAccountTransfer(){
    fadeInAjxResp('#icl_ajx_response_account', icl_ajxloaderimg);
    jQuery('#icl_account_errors').hide();
    jQuery('#icl_account_success').hide();
    var qargs = new Array();
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=do_account_transfer&"+jQuery('#icl_configure_account_transfer').serialize(),
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp('#icl_ajx_response_account',spl[1],true);
                jQuery('#icl_account_success').text(spl[1]);
                jQuery('#icl_account_success').fadeIn();
            }else{
                jQuery('#icl_account_errors').text(spl[1]);
                jQuery('#icl_account_errors').fadeIn();
                
                fadeInAjxResp('#icl_ajx_response_account',spl[1],true);
            }  
        }
    }); 
    
}

function iclToggleContentTranslation(){
    var val = jQuery(this).attr('id')=='icl_enable_content_translation'?1:0;
    if(!val && !confirm(jQuery('#icl_toggle_ct_confirm_message').html())){
        return false;
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=toggle_content_translation&new_val="+val,
        success: function(msg){
            location.href = location.href.replace(/#.*/,'');
        }
    });         
}

function iclToggleAdvancedOptions(){    
    jqthis = jQuery(this);
    if(jQuery('#icl-content-translation-advanced-options').css('display')=='none'){
        jQuery('#icl-content-translation-advanced-options').fadeIn('fast',function(){
            jqthis.children().toggle();
        });        
    }else{
        jQuery('#icl-content-translation-advanced-options').fadeOut('fast',function(){
            jqthis.children().toggle();
        });
    }    
}

function iclToggleMoreLanguages(){    
    jqthis = jQuery(this);
    if(jQuery('#icl_languages_disabled').css('display')=='none'){
        jQuery('#icl_languages_disabled').fadeIn('fast',function(){
            jqthis.children().toggle();
        });        
    }else{
        /* NOTE:
            this fade out is not working in wp 2.8.4. set the display to none instead.
        jQuery('#icl_languages_disabled').fadeOut('fast',function(){
            jqthis.children().toggle();
        });
        */
        
        jQuery('#icl_languages_disabled').css('display', 'none');
        jqthis.children().toggle();
    }    
}




//jQuery('#TB_window').live('unload', function(){
//    location.href=location.href.replace(/#(.+)$/,'');    
//});



