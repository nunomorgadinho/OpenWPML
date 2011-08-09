jQuery(document).ready(function(){
    if(jQuery('#category-adder').html()){
        jQuery('#category-adder').prepend('<p>'+icl_cat_adder_msg+'</p>');
    }
    jQuery('select[name="icl_post_language"]').change(iclPostLanguageSwitch);
    jQuery('#noupdate_but input[type="button"]').click(iclSetDocumentToDate);
    jQuery('select[name="icl_translation_of"]').change(function(){jQuery('#icl_translate_options').fadeOut();});
    jQuery('#icl_dismiss_help').click(iclDismissHelp);
    jQuery('#icl_dismiss_upgrade_notice').click(iclDismissUpgradeNotice);
    jQuery('#icl_dismiss_page_estimate_hint').click(iclDismissPageEstimateHint);
    jQuery('#icl_show_page_estimate_hint').click(iclShowPageEstimateHint);
    jQuery('a.icl_toggle_show_translations').click(iclToggleShowTranslations);
    
    icl_tn_initial_value   = jQuery('#icl_post_note textarea').val();
    jQuery('#icl_post_add_notes h4 a').live('click', iclTnOpenNoteBox);
    jQuery('#icl_post_note textarea').live('keyup', iclTnClearButtonState);
    jQuery('#icl_tn_clear').live('click', function(){jQuery('#icl_post_note textarea').val('');jQuery(this).attr('disabled','disabled')});
    jQuery('#icl_tn_save').live('click', iclTnCloseNoteBox);
    
    jQuery('#icl_pt_hide').click(iclHidePTControls);
    jQuery('#icl_pt_show').click(iclShowPTControls);
    
    jQuery('#icl_pt_controls ul li :checkbox').live('change', function(){
        if(jQuery('#icl_pt_controls ul li :checkbox:checked').length){
            jQuery('#icl_pt_send').removeAttr('disabled');
        }else{
            jQuery('#icl_pt_send').attr('disabled', 'disabled');
        }
        iclPtCostEstimate();
    });
    jQuery('#icl_pt_send').live('click', iclPTSend);

    /* needed for tagcloud */
    oldajaxurl = false;
    
});

var icl_tn_initial_value   = '';

window.onbeforeunload = function() { 
    if(icl_tn_initial_value != jQuery('#icl_post_note textarea').val()){
        return jQuery('#icl_tn_cancel_confirm').val();
    }
}





function fadeInAjxResp(spot, msg, err){
    if(err != undefined){
        col = jQuery(spot).css('color');
        jQuery(spot).css('color','red');
    }
    jQuery(spot).html('<span>'+msg+'<span>');
    jQuery(spot).fadeIn();
    window.setTimeout(fadeOutAjxResp, 3000, spot);
    if(err != undefined){
        jQuery(spot).css('color',col);
    }
}

function fadeOutAjxResp(spot){
    jQuery(spot).fadeOut();
}

var icl_ajxloaderimg = '<img src="'+icl_ajxloaderimg_src+'" alt="loading" width="16" height="16" />';

var iclHaltSave = false; // use this for multiple 'submit events'
var iclSaveForm_success_cb = new Array();
function iclSaveForm(){
    
    if(iclHaltSave){
        return false;
    }
    var formname = jQuery(this).attr('name');
    jQuery('form[name="'+formname+'"] .icl_form_errors').html('').hide();
    ajx_resp = jQuery('form[name="'+formname+'"] .icl_ajx_response').attr('id');
    fadeInAjxResp('#'+ajx_resp, icl_ajxloaderimg);
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action="+jQuery(this).attr('name')+"&"+jQuery(this).serialize(),
        success: function(msg){
            spl = msg.split('|');
            if(parseInt(spl[0]) == 1){
                fadeInAjxResp('#'+ajx_resp, icl_ajx_saved);                                         
                for(i=0;i<iclSaveForm_success_cb.length;i++){
                    iclSaveForm_success_cb[i](jQuery('form[name="'+formname+'"]'), spl);    
                }
            }else{                        
                jQuery('form[name="'+formname+'"] .icl_form_errors').html(spl[1]);
                jQuery('form[name="'+formname+'"] .icl_form_errors').fadeIn()
                fadeInAjxResp('#'+ajx_resp, icl_ajx_error,true);
            }  
        }
    });
    return false;     
}

function iclPostLanguageSwitch(){
    var lang = jQuery(this).attr('value');
    var ajx = location.href.replace(/#(.*)$/,'');
    if(-1 == location.href.indexOf('?')){
        url_glue='?';
    }else{
        url_glue='&';
    }
    
    if(icl_this_lang != lang){
        jQuery('#icl_translate_options').fadeOut();
    }else{
        jQuery('#icl_translate_options').fadeIn();
    }
    
    if(jQuery('#parent_id').length > 0){
        jQuery('#parent_id').load(ajx+url_glue+'lang='+lang + ' #parent_id option',{lang_switch:jQuery('#post_ID').attr('value')}, function(resp){
            tow1 = resp.indexOf('<div id="translation_of_wrap">');
            tow2 = resp.indexOf('</div><!--//translation_of_wrap-->');            
            jQuery('#translation_of_wrap').html(resp.substr(tow1+31, tow2-tow1-31));                   
            if(-1 == jQuery('#parent_id').html().indexOf('selected="selected"')){
                jQuery('#parent_id').attr('value','');
            }        
        });
    }else if(jQuery('#categorydiv').length > 0){
        jQuery('.categorydiv').hide();
        var ltlhlpr = document.createElement('div');
        ltlhlpr.setAttribute('style','display:none');
        ltlhlpr.setAttribute('id','icl_ltlhlpr');
        jQuery(this).after(ltlhlpr);
        jQuery('#categorydiv').slideUp();        
        jQuery('#icl_ltlhlpr').load(ajx+url_glue+'icl_ajx=1&lang='+lang + ' #categorydiv',{}, function(resp){ 
            tow1 = resp.indexOf('<div id="translation_of_wrap">');
            tow2 = resp.indexOf('</div><!--//translation_of_wrap-->');            
            jQuery('#translation_of_wrap').html(resp.substr(tow1+31, tow2-tow1-31));           
            jQuery('#icl_ltlhlpr').html(jQuery('#icl_ltlhlpr').html().replace('categorydiv',''));
            jQuery('#categorydiv').html(jQuery('#icl_ltlhlpr div').html());
            jQuery('#categorydiv').slideDown();            
            jQuery('#icl_ltlhlpr').remove();    
            jQuery('#category-adder').prepend('<p>'+icl_cat_adder_msg+'</p>');
            
            var tx = '';
            jQuery('.categorydiv').each(function(){
                var id = jQuery(this).attr('id');            
                var tx = id.replace(/^taxonomy-/,'');

                if(id != 'taxonomy-category'){                    
                    jQuery('#'+tx+'div').html(jQuery(resp).find('#'+tx+'div').html());
                }
                
                
                /* WP scrap */
                jQuery(".categorydiv").each(function () {
                    var this_id = jQuery(this).attr("id"),
                        noSyncChecks = false,
                        syncChecks, catAddAfter, taxonomyParts, taxonomy, settingName;
                    taxonomyParts = this_id.split("-");
                    taxonomyParts.shift();
                    taxonomy = taxonomyParts.join("-");
                    settingName = taxonomy + "_tab";
                    if (taxonomy == "category") {
                        settingName = "cats"
                    }
                    jQuery("a", "#" + taxonomy + "-tabs").click(function () {
                        var t = jQuery(this).attr("href");
                        jQuery(this).parent().addClass("tabs").siblings("li").removeClass("tabs");
                        jQuery("#" + taxonomy + "-tabs").siblings(".tabs-panel").hide();
                        jQuery(t).show();
                        if ("#" + taxonomy + "-all" == t) {
                            deleteUserSetting(settingName)
                        } else {
                            setUserSetting(settingName, "pop")
                        }
                        return false
                    });
                    if (getUserSetting(settingName)) {
                        jQuery('a[href="#' + taxonomy + '-pop"]', "#" + taxonomy + "-tabs").click()
                    }
                    jQuery("#new" + taxonomy).one("focus", function () {
                        jQuery(this).val("").removeClass("form-input-tip")
                    });
                    jQuery("#" + taxonomy + "-add-submit").click(function () {
                        jQuery("#new" + taxonomy).focus()
                    });
                    syncChecks = function () {
                        if (noSyncChecks) {
                            return
                        }
                        noSyncChecks = true;
                        var th = jQuery(this),
                            c = th.is(":checked"),
                            id = th.val().toString();
                        jQuery("#in-" + taxonomy + "-" + id + ", #in-" + taxonomy + "-category-" + id).attr("checked", c);
                        noSyncChecks = false
                    };
                    catAddBefore = function (s) {
                        if (!jQuery("#new" + taxonomy).val()) {
                            return false
                        }
                        s.data += "&" + jQuery(":checked", "#" + taxonomy + "checklist").serialize();
                        return s
                    };
                    catAddAfter = function (r, s) {
                        var sup, drop = jQuery("#new" + taxonomy + "_parent");
                        if ("undefined" != s.parsed.responses[0] && (sup = s.parsed.responses[0].supplemental.newcat_parent)) {
                            drop.before(sup);
                            drop.remove()
                        }
                    };
                    jQuery("#" + taxonomy + "checklist").wpList({
                        alt: "",
                        response: taxonomy + "-ajax-response",
                        addBefore: catAddBefore,
                        addAfter: catAddAfter
                    });
                    jQuery("#" + taxonomy + "-add-toggle").click(function () {
                        jQuery("#" + taxonomy + "-adder").toggleClass("wp-hidden-children");
                        jQuery('a[href="#' + taxonomy + '-all"]', "#" + taxonomy + "-tabs").click();
                        return false
                    });
                    jQuery("#" + taxonomy + "checklist li.popular-category :checkbox, #" + taxonomy + "checklist-pop :checkbox").live("click", function () {
                        var t = jQuery(this),
                            c = t.is(":checked"),
                            id = t.val();
                        if (id && t.parents("#taxonomy-" + taxonomy).length) {
                            jQuery("#in-" + taxonomy + "-" + id + ", #in-popular-" + taxonomy + "-" + id).attr("checked", c)
                        }
                    })
                });         
                /* WP scrap - end */    
                
            }); 
            jQuery('.categorydiv').show();                
            

            /* tagcloud */

            if (oldajaxurl == false) {
                oldajaxurl = ajaxurl;
            }
            if(-1 == ajaxurl.indexOf('?')){
                temp_url_glue='?';
            } else {
                temp_url_glue='&';
            }
            
            if (lang == icl_this_lang) {
                ajaxurl = oldajaxurl;
            } else if (-1 == ajaxurl.indexOf('lang')) {
                ajaxurl = ajaxurl+temp_url_glue+'lang='+lang;
            } else {
                ajaxurl = oldajaxurl+temp_url_glue+'lang='+lang;
            }

            jQuery('div[id^=tagsdiv-]').each(function(){
                jQuery(this).slideUp();
                jQuery(this).find('.the-tagcloud').remove();
                jQuery(this).find('.tagchecklist span').remove();
                jQuery(this).find('.the-tags').val('');
                tag_tax = jQuery(this).attr('id').substring(8);
                tagBox.get('link-'+tag_tax);
                jQuery(this).find('a.tagcloud-link').unbind().click(function(){
                    jQuery(this).siblings('.the-tagcloud').toggle();
                    return false;
                });
                jQuery(this).slideDown();
            });

            ajaxurl = oldajaxurl;
        });    
        
    }
}

function iclSetDocumentToDate(){
    var thisbut = jQuery(this);
    if(!confirm(jQuery('#noupdate_but_wm').html())) return;
    thisbut.attr('disabled','disabled');
    thisbut.css({'background-image':"url('"+icl_ajxloaderimg_src+"')", 'background-position':'center right', 'background-repeat':'no-repeat'});
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=set_post_to_date&post_id="+jQuery('#post_ID').val(),
            success: function(msg){
                spl = msg.split('|');
                thisbut.removeAttr('disabled');
                thisbut.css({'background-image':'none'});
                thisbut.parent().remove();
                var st = jQuery('#icl_translations_status td.icl_translation_status_msg');
                st.each(function(){
                    jQuery(this).html(jQuery(this).html().replace(spl[0],spl[1]))                     
                })
                jQuery('#icl_minor_change_box').fadeIn();
            }
        });        
}

function iclDismissHelp(){
    var thisa = jQuery(this);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=dismiss_help",
            success: function(msg){
                thisa.closest('#message').fadeOut();    
            }
    });    
    return false;
}

function iclDismissUpgradeNotice(){
    var thisa = jQuery(this);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=dismiss_upgrade_notice",
            success: function(msg){
                thisa.parent().parent().fadeOut();    
            }
    });    
    return false;
}

var iclShowPageEstimateHint_img = false;
function iclDismissPageEstimateHint(){
    var thisa = jQuery(this);
    if(!iclShowPageEstimateHint_img){
        iclShowPageEstimateHint_img = jQuery('#icl_dismiss_page_estimate_hint').parent().find('img').attr('src');        
    }
    jQuery('#icl_show_page_estimate_hint').find('img').attr('src', iclShowPageEstimateHint_img);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=dismiss_page_estimate_hint",
            success: function(msg){
                thisa.parent().fadeOut(function(){jQuery('#icl_show_page_estimate_hint').fadeIn()});                    
            }
    });    
    return false;
} 

function iclShowPageEstimateHint(){
    var thisa = jQuery(this);
    iclShowPageEstimateHint_img = thisa.find('img').attr('src');
    thisa.find('img').attr('src', icl_ajxloaderimg_src);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=dismiss_page_estimate_hint",
            success: function(msg){
                thisa.fadeOut(function(){jQuery('#icl_dismiss_page_estimate_hint').parent().fadeIn();});                    
            }
    });    
    return false;    
}

function iclToggleShowTranslations(){
    jQuery('a.icl_toggle_show_translations').toggle();
    jQuery('#icl_translations_table').toggle();
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=toggle_show_translations"
    });        
    return false;
}

function iclTnOpenNoteBox(){
    jQuery('#icl_post_add_notes #icl_post_note').slideDown();
    jQuery('#icl_post_note textarea').focus();
    return false;
}
function iclTnClearButtonState(){
    if(jQuery.trim(jQuery(this).val())){
        jQuery('#icl_tn_clear').removeAttr('disabled');
    }else{
        jQuery('#icl_tn_clear').attr('disabled', 'disabled');
    }  
}
function iclTnCloseNoteBox(){
    jQuery('#icl_post_add_notes #icl_post_note').slideUp('fast', function(){
        if(icl_tn_initial_value != jQuery('#icl_post_note textarea').val()){
            jQuery('#icl_tn_not_saved').fadeIn();
        }else{
            jQuery('#icl_tn_not_saved').fadeOut();
        }
    });
}

function iclShowPTControls(){
    var thisa = jQuery(this);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=toggle_pt_controls&value=0",
            success: function(msg){
                jQuery('#icl_pt_controls').slideDown();
                thisa.fadeOut(function(){jQuery('#icl_pt_hide').fadeIn();});                    
            }
    });
    return false;    
}

function iclHidePTControls(){
    var thisa = jQuery(this);
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=toggle_pt_controls&value=1",
            success: function(msg){
                thisa.fadeOut(function(){
                    jQuery('#icl_pt_controls').slideUp(function(){
                        jQuery('#icl_pt_show').fadeIn()
                    });
                });
            }
    }); 
    return false;   
}

function iclPtCostEstimate(){    
    var estimate = 0;
    var words = parseInt(jQuery('#icl_pt_wc').val());
    jQuery('#icl_pt_controls ul li :checkbox:checked').each(
        function(){
            lang = jQuery(this).attr('id').replace(/^icl_pt_to_/,'');
            rate = jQuery('#icl_pt_rate_'+lang).val();
            estimate += words * rate;
        }
    )
    if(estimate < 1){
        precision = Math.floor(estimate).toString().length + 1;    
    }else{
        precision = Math.floor(estimate).toString().length + 2;
    }
    
    jQuery('#icl_pt_cost_estimate').html(estimate.toPrecision(precision));
}

function iclPTSend(){
    jQuery('#icl_pt_error, #icl_pt_success').hide();
    jQuery('#icl_pt_send').attr('disabled', 'disabled');
    
    if(jQuery('#icl_pt_controls ul li :checkbox:checked').length==0) return false;
    
    target_languages = new Array();
    jQuery('#icl_pt_controls ul li :checkbox:checked').each(function(){
        target_languages.push(jQuery(this).val());
    });
    
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: "icl_ajx_action=send_translation_request&post_ids=" + jQuery('#icl_pt_post_id').val() 
            + '&icl_post_type['+ jQuery('#icl_pt_post_id').val() + ']=' + jQuery('#icl_pt_post_type').val() 
            + '&target_languages='+target_languages.join('#')
            + '&service=icanlocalize'
            + '&tn_note_'+jQuery('#icl_pt_post_id').val()+'=' + jQuery('#icl_pt_tn_note').val(),
        success: function(msg){
            for(i in msg){
                p = msg[i];    
            }
            if(p.status > 0){
                location.href = location.href.replace(/#(.+)/,'')+'&icl_message=success';
            }else{
                jQuery('#icl_pt_error').fadeIn();
            }
        }
    });
    
    
}

function icl_pt_reload_translation_box(){
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: "icl_ajx_action=get_translator_status",
        success: function(){
            jQuery('#icl_pt_hide').hide();
            jQuery('#icl_pt_controls').html(icl_ajxloaderimg+'<br class="clear" />');    
            jQuery.get(location.href, {rands:Math.random()}, function(data){
                jQuery('#icl_pt_controls').html(jQuery(data).find('#icl_pt_controls').html());
                icl_tb_init('a.icl_thickbox');
                icl_tb_set_size('a.icl_thickbox');
                jQuery('#icl_pt_hide').show();
                
            })
        }
    });
}

function icl_pt_reload_translation_options(){
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: "icl_ajx_action=get_translator_status",
        success: function(){
            jQuery('#icl-tr-opt').html(icl_ajxloaderimg+'<br class="clear" />');    
            jQuery.get(location.href, {rands:Math.random()}, function(data){
                jQuery('#icl-tr-opt').html(jQuery(data).find('#icl-tr-opt').html());
                icl_tb_init('a.icl_thickbox');
                icl_tb_set_size('a.icl_thickbox');
            })
        }
    });
    
}


//jQuery('#TB_window').live('unload', function(){
//    console.log(jQuery(this).find('iframe').attr('src'));
//});

