addLoadEvent(function(){     
    jQuery('#icl_change_default_button').click(editingDefaultLanguage);
    jQuery('#icl_save_default_button').click(saveDefaultLanguage);
    jQuery('#icl_cancel_default_button').click(doneEditingDefaultLanguage);
    jQuery('#icl_add_remove_button').click(showLanguagePicker);            
    jQuery('#icl_cancel_language_selection').click(hideLanguagePicker);
    jQuery('#icl_save_language_selection').click(saveLanguageSelection);                        
    jQuery('#icl_enabled_languages input').attr('disabled','disabled');    
    jQuery('#icl_save_language_negotiation_type').submit(iclSaveLanguageNegotiationType);    
    jQuery('#icl_save_language_switcher_options').submit(iclSaveForm);    
    jQuery('#icl_admin_language_options').submit(iclSaveForm);    
    jQuery('#icl_lang_more_options').submit(iclSaveForm);    
    jQuery('#icl_blog_posts').submit(iclSaveForm);        
    jQuery('#icl_hide_languages').submit(iclHideLanguagesCallback);    
    jQuery('#icl_hide_languages').submit(iclSaveForm);    
    jQuery('#icl_adjust_ids').submit(iclSaveForm);    
    jQuery('input[name="icl_language_negotiation_type"]').change(iclLntDomains);
    
    jQuery('#icl_setup_back_1').click(iclSetupStep1);
    jQuery('#icl_setup_back_2').click(iclSetupStep2);
    jQuery('#icl_setup_back_3').click(iclSetupStep3);
    jQuery('#icl_setup_next_1').click(saveLanguageSelection);
    
    jQuery('#icl_avail_languages_picker li input:checkbox').click(function(){             
        if(jQuery('#icl_avail_languages_picker li input:checkbox:checked').length > 1){
            jQuery('#icl_setup_next_1').removeAttr('disabled');
        }else{
            jQuery('#icl_setup_next_1').attr('disabled', 'disabled');
        }
    });
	             
    icl_lp_flag = jQuery('.iclflag:visible').length > 0;    
	icl_lp_footer_flag = jQuery('.iclflag:visible').length > 0;     
    
	jQuery('#icl_lang_preview_config input').each(iclUpdateLangSelQuickPreview);
	jQuery('#icl_lang_preview_config_footer input').each(iclUpdateLangSelQuickPreviewFooter);
	// Picker align
	jQuery(".pick-show").click(function () {
		var set = jQuery(this).offset();
   		jQuery("#colorPickerDiv").css({"top":set.top+25,"left":set.left});
	});
        
    jQuery('form[name="icl_promote_form"] input[name="icl_promote"]').change(function(){
        jQuery.post(icl_ajx_url, 'icl_ajx_action=icl_promote&icl_promote='+jQuery(this).attr('checked'));
    });    
    
    jQuery('#icl_lang_preview_config input').keyup(iclUpdateLangSelQuickPreview);
	jQuery('#icl_lang_preview_config_footer input').keyup(iclUpdateLangSelQuickPreviewFooter);
	  
    jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_flags"]').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('#lang_sel .iclflag').show();
			jQuery('#lang_sel_list .iclflag').show();
			jQuery('#lang_sel_footer .iclflag').show();
        }else{
			jQuery('#lang_sel .iclflag').hide();	
			jQuery('#lang_sel_list .iclflag').hide();	
			jQuery('#lang_sel_footer .iclflag').hide();	
            /*if(jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_display_lang"]').attr('checked')
                || jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_native_lang"]').attr('checked')){
                jQuery('#lang_sel .iclflag').hide();
            }else{
                jQuery(this).attr('checked','checked');
                return false;
            }*/
        }
    });
    
    jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_native_lang"]').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('.icl_lang_sel_native').show();
        }else{
            if(jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_display_lang"]').attr('checked')){
                jQuery('.icl_lang_sel_native').hide();
                if(!jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_display_lang"]').attr('checked')){
                    jQuery('.icl_lang_sel_current').hide();
                }
            }else{
                jQuery(this).attr('checked','checked');
                return false;
            }
        }
    });

    jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_display_lang"]').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('.icl_lang_sel_translated').show();
        }else{
            if(jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_native_lang"]').attr('checked')){
                jQuery('.icl_lang_sel_translated').hide();
                if(!jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_native_lang"]').attr('checked')){
                    jQuery('.icl_lang_sel_current').hide();
                }                
            }else{
                jQuery(this).attr('checked','checked');
                return false;
            }
        }
    });
    
    jQuery('#icl_lang_sel_color_scheme').change(iclUpdateLangSelColorScheme);
	jQuery('#icl_lang_sel_footer_color_scheme').change(iclUpdateLangSelColorSchemeFooter);
    
    //jQuery('#icl_lang_preview_config input').change(iclUpdateLangSelPreview);  
	
	jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lang_sel_footer"]').change(function(){
        if(jQuery(this).attr('checked')){
            jQuery('#icl_lang_sel_footer_preview_wrap').show();
			jQuery('#icl_lang_sel_footer_preview_link').show();
			//jQuery('#icl_lang_preview_config_footer_editor_wrapper').show();
        }else{
			jQuery('#icl_lang_sel_footer_preview_wrap').hide();		
			jQuery('#icl_lang_sel_footer_preview_link').hide();	
			jQuery('#icl_lang_preview_config_footer_editor_wrapper').hide();
        }
    });
	
	var icl_arrow_img = icl_ajxloaderimg_src.replace("ajax-loader.gif", "nav-arrow-down.png");
	jQuery('#icl_save_language_switcher_options :radio[name="icl_lang_sel_type"]').change(function(){
        if(jQuery(this).val() == 'dropdown'){
			//jQuery('#lang_sel_list').css('visibility','hidden');
			jQuery('#lang_sel_list').hide();
			//jQuery('#lang_sel').css('visibility','visible');
			jQuery('#lang_sel').show();
			/*jQuery('#lang_sel ul ul').css('visibility','hidden');
			jQuery('#lang_sel a.lang_sel_sel').css('background-image','url('+icl_arrow_img+')');
			jQuery('#lang_sel ul').hover(
				function(){
					jQuery('#lang_sel ul ul').css('visibility','visible');
				},
				function(){
					jQuery('#lang_sel ul ul').css('visibility','hidden');
				}
			);*/
        }else{
			//jQuery('#lang_sel').css('visibility','hidden');
			jQuery('#lang_sel').hide();
			//jQuery('#lang_sel_list').css('visibility','visible');
			jQuery('#lang_sel_list').show();
			/*jQuery('#lang_sel a.lang_sel_sel').css('background-image','url()');
			jQuery('#lang_sel ul').hover(function(){jQuery('#lang_sel ul ul').css('visibility','visible');}, function(){jQuery('#lang_sel ul ul').css('visibility','visible');});
			jQuery('#lang_sel ul ul').css('visibility','visible');*/
        }
    });
    
    jQuery('#icl_reset_languages').click(icl_reset_languages);
    
    jQuery(':radio[name=icl_translation_option]').change(function(){
        jQuery('#icl_enable_content_translation').removeAttr('disabled');
    });    
    jQuery('#icl_enable_content_translation, .icl_noenable_content_translation').click(iclEnableContentTranslation);    
        
});
function editingDefaultLanguage(){
    jQuery('#icl_change_default_button').hide();
    jQuery('#icl_save_default_button').show();
    jQuery('#icl_cancel_default_button').show();
    jQuery('#icl_enabled_languages input').css('visibility','visible');
    jQuery('#icl_enabled_languages input').removeAttr('disabled');
    jQuery('#icl_add_remove_button').hide();
    
}
function doneEditingDefaultLanguage(){
    jQuery('#icl_change_default_button').show();
    jQuery('#icl_save_default_button').hide();
    jQuery('#icl_cancel_default_button').hide();
    jQuery('#icl_enabled_languages input').css('visibility','hidden');
    jQuery('#icl_enabled_languages input').attr('disabled','disabled');
    jQuery('#icl_add_remove_button').show();
}        
function saveDefaultLanguage(){
    var arr = jQuery('#icl_enabled_languages input[type="radio"]');            
    var def_lang;
    jQuery.each(arr, function() {                
        if(this.checked){
            def_lang = this.value;    
        }                
    });             
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=set_default_language&lang="+def_lang,
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp(icl_ajx_saved);                         
                jQuery('#icl_avail_languages_picker input[value="'+spl[1]+'"]').removeAttr('disabled');
                jQuery('#icl_avail_languages_picker input[value="'+def_lang+'"]').attr('disabled','disabled');
                jQuery('#icl_enabled_languages li').removeClass('default_language');
                jQuery('#icl_enabled_languages li input[value="'+def_lang+'"]').parent().parent().attr('class','default_language');
                jQuery('#icl_enabled_languages li input[value="'+def_lang+'"]').parent().append(' ('+icl_default_mark+')');
                jQuery('#icl_enabled_languages li input').removeAttr('checked');
                jQuery('#icl_enabled_languages li input[value="'+def_lang+'"]').attr('checked','checked');
                jQuery('#icl_enabled_languages input[value="'+spl[1]+'"]').parent().html(jQuery('#icl_enabled_languages input[value="'+spl[1]+'"]').parent().html().replace('('+icl_default_mark+')',''));
                doneEditingDefaultLanguage();                     
                fadeInAjxResp('#icl_ajx_response',icl_ajx_saved);                  
                if(spl[2]){
                    jQuery('#icl_ajx_response').html(spl[2]);
                }else{                    
                    location.href = location.href.replace(/#.*/,'')+'&setup=2';
                }                
            }else{                        
                fadeInAjxResp('#icl_ajx_response',icl_ajx_error);                                         
            }                    
        }
    });
    
}        
function showLanguagePicker(){
    jQuery('#icl_avail_languages_picker').slideDown();
    jQuery('#icl_add_remove_button').fadeOut();
    jQuery('#icl_change_default_button').fadeOut();
}
function hideLanguagePicker(){
    jQuery('#icl_avail_languages_picker').slideUp();
    jQuery('#icl_add_remove_button').fadeIn();
    jQuery('#icl_change_default_button').fadeIn();
} 
function saveLanguageSelection(){
    fadeInAjxResp('#icl_ajx_response', icl_ajxloaderimg);
    var arr = jQuery('#icl_avail_languages_picker ul input[type="checkbox"]');            
    var sel_lang = new Array();
    jQuery.each(arr, function() {
        if(this.checked){
            sel_lang.push(this.value);
        }                
    }); 
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=set_active_languages&langs="+sel_lang.join(','),
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp('#icl_ajx_response', icl_ajx_saved);                         
                jQuery('#icl_enabled_languages').html(spl[1]);
            }else{                        
                fadeInAjxResp('#icl_ajx_response', icl_ajx_error,true);
            } 
            if(spl[2]=='1'){
                location.href = location.href.replace(/#.*/,'');
            }else if(spl[2]=='-1'){
                location.href = location.href.replace(/#.*/,'');
            }else {
                location.href = location.href.replace(/(#|&).*/,'');
            }                   

        }
    });
    hideLanguagePicker();
}   

function iclLntDomains(){
    if(jQuery(this).attr('checked') && jQuery(this).attr('id')=='icl_lnt_domains'){
        jQuery(this).parent().parent().append('<div id="icl_lnt_domains_box"></div>');
        jQuery('#icl_lnt_domains_box').html(icl_ajxloaderimg);
        jQuery('#icl_save_language_negotiation_type input[type="submit"]').attr('disabled','disabled');
        jQuery('#icl_lnt_domains_box').load(icl_ajx_url, {icl_ajx_action:'language_domains'}, function(resp){
            jQuery('#icl_save_language_negotiation_type input[type="submit"]').removeAttr('disabled');
        })
    }else{
        if(jQuery('#icl_lnt_domains_box').length){
            jQuery('#icl_lnt_domains_box').fadeOut('fast', function(){jQuery('#icl_lnt_domains_box').remove()});        
        }        
    }
    
}

function iclSaveLanguageNegotiationType(){
    var formname = jQuery(this).attr('name');
    var form_errors = false;
    jQuery('form[name="'+formname+'"] .icl_form_errors').html('').hide();
    jQuery('form[name="'+formname+'"] input').css('color','#000');
    ajx_resp = jQuery('form[name="'+formname+'"] .icl_ajx_response').attr('id');
    fadeInAjxResp('#'+ajx_resp, icl_ajxloaderimg);
    jQuery.ajaxSetup({async: false});
    var used_urls = new Array(jQuery('#icl_ln_home').html());
    jQuery('.validate_language_domain').each(function(){        
        if(jQuery(this).attr('checked')){
            var lang = jQuery(this).attr('value');
            jQuery('#ajx_ld_'+lang).html(icl_ajxloaderimg);
            var lang_td = jQuery('#icl_validation_result_'+lang);
            var lang_domain_input = jQuery('#language_domain_'+lang); 
            if(used_urls.indexOf(lang_domain_input.attr('value')) != -1 ){
                jQuery('#ajx_ld_'+lang).html('');
                lang_domain_input.css('color','#f00');
                form_errors = true;
            }else{
                used_urls.push(lang_domain_input.attr('value'));            
                lang_domain_input.css('color','#000');
                jQuery('#ajx_ld_'+lang).load(icl_ajx_url, 
                    {icl_ajx_action:'validate_language_domain',url:lang_domain_input.attr('value')}, 
                    function(resp){
                        jQuery('#ajx_ld_'+lang).html('');
                        if(resp=='0'){
                            lang_domain_input.css('color','#f00');
                            form_errors = true;
                            
                        }
                });
            }
        }        
    });
    jQuery.ajaxSetup({async: true});
    if(form_errors){        
        fadeInAjxResp('#'+ajx_resp, icl_ajx_error,true);
        return false;
    }    
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action="+jQuery(this).attr('name')+"&"+jQuery(this).serialize(),
        success: function(msg){
            spl = msg.split('|');
            if(spl[0]=='1'){
                fadeInAjxResp('#'+ajx_resp, icl_ajx_saved);                                         
            }else{                        
                jQuery('form[name="'+formname+'"] .icl_form_errors').html(spl[1]);
                jQuery('form[name="'+formname+'"] .icl_form_errors').fadeIn()
                fadeInAjxResp('#'+ajx_resp, icl_ajx_error,true);
            }  
        }
    });
    return false;     
}


function iclSetupStep1(){
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=setup_got_to_step1",
            success: function(msg){
                location.href = location.href.replace(/#.*/,'');
            }
    });    
    return false;
}
function iclSetupStep2(){
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=setup_got_to_step2",
            success: function(msg){
                location.href = location.href.replace(/#.*/,'');
            }
    });    
    return false;
}

function iclSetupStep3(){
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=setup_got_to_step3",
            success: function(msg){
                location.href = location.href.replace(/#.*/,'');
            }
    });    
    return false;
}

function iclUpdateLangSelPreview(){
    jQuery('#icl_lang_sel_preview_wrap').html(icl_ajxloaderimg);
    jQuery('#icl_lang_sel_preview_wrap').load(location.href + ' #icl_lang_sel_preview');
}

var icl_lp_font_current_normal = false;
var icl_lp_font_current_hover = false;
var icl_lp_background_current_normal = false;
var icl_lp_background_current_hover = false;
var icl_lp_font_other_normal = false;
var icl_lp_font_other_hover = false;
var icl_lp_background_other_normal = false;
var icl_lp_background_other_hover = false;
var icl_lp_border = false;
var icl_lp_flag = false;


function iclUpdateLangSelQuickPreview(){
    name = jQuery(this).attr('name');
    value = jQuery(this).val();
    switch(name){
        case 'icl_lang_sel_config[font-current-normal]':
            icl_lp_font_current_normal = value;
            break;
        case 'icl_lang_sel_config[font-current-hover]':
            icl_lp_font_current_hover = value;
            break;                
        case 'icl_lang_sel_config[background-current-normal]':
            icl_lp_background_current_normal = value;
            break;
        case 'icl_lang_sel_config[background-current-hover]':
            icl_lp_background_current_hover = value;
            break;                
        case 'icl_lang_sel_config[font-other-normal]':
            icl_lp_font_other_normal = value;
            break;
        case 'icl_lang_sel_config[font-other-hover]':
            icl_lp_font_other_hover = value;
            break;                
        case 'icl_lang_sel_config[background-other-normal]':
            icl_lp_background_other_normal = value;
            break;
        case 'icl_lang_sel_config[background-other-hover]':
            icl_lp_background_other_hover = value;
            break;                
        case 'icl_lang_sel_config[border]':
            icl_lp_border = value;
            break;            
        case 'icl_lso_flags':
            icl_lp_flag = jQuery(this).attr('checked');
            break;            
            
    }
    iclRenderLangPreview();
}

function iclRenderLangPreview(){
                                                                                                               
    if(icl_lp_font_other_normal){
        jQuery('#lang_sel li ul a').css('color', icl_lp_font_other_normal); 
		jQuery('#lang_sel_list ul a').css('color', icl_lp_font_other_normal);
    }    
    if(icl_lp_font_other_hover){
        jQuery('#lang_sel li ul a').unbind('hover');
        jQuery('#lang_sel li ul a').hover(
            function(){jQuery(this).css('color',icl_lp_font_other_hover)},
            function(){jQuery(this).css('color',icl_lp_font_other_normal)}
            );
		jQuery('#lang_sel_list ul a').unbind('hover');
        jQuery('#lang_sel_list ul a').hover(
            function(){jQuery(this).css('color',icl_lp_font_other_hover)},
            function(){jQuery(this).css('color',icl_lp_font_other_normal)}
            );
    }

    if(icl_lp_background_other_normal){
        jQuery('#lang_sel li ul a').css('background-color', icl_lp_background_other_normal) ; 
        jQuery('#lang_sel li ul a').unbind('hover');
        jQuery('#lang_sel li ul a').hover(
            function(){jQuery(this).css('background-color', '')},
            function(){jQuery(this).css('background-color', icl_lp_background_other_normal)}
            );   
		
		jQuery('#lang_sel_list ul a').css('background-color', icl_lp_background_other_normal) ; 
        jQuery('#lang_sel_list ul a').unbind('hover');
        jQuery('#lang_sel_list ul a').hover(
            function(){jQuery(this).css('background-color', '')},
            function(){jQuery(this).css('background-color', icl_lp_background_other_normal)}
            );     
    }    
    if(icl_lp_background_other_hover){
        jQuery('#lang_sel li ul a').unbind('hover');
        jQuery('#lang_sel li ul a').hover(
            function(){jQuery(this).css('background-color', icl_lp_background_other_hover)},
            function(){jQuery(this).css('background-color', icl_lp_background_other_normal)}
            );
		jQuery('#lang_sel_list ul a').unbind('hover');
        jQuery('#lang_sel_list ul a').hover(
            function(){jQuery(this).css('background-color', icl_lp_background_other_hover)},
            function(){jQuery(this).css('background-color', icl_lp_background_other_normal)}
            );
    }
    
    if(icl_lp_border){
        jQuery('#lang_sel a').css('border-color', icl_lp_border);
		jQuery('#lang_sel ul ul').css('border-color', icl_lp_border);
		
		jQuery('#lang_sel_list a').css('border-color', icl_lp_border);
		jQuery('#lang_sel_list ul').css('border-color', icl_lp_border);
    }
    
    if(jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_flags"]').attr('checked')){
        jQuery('#lang_sel .iclflag').show();
		jQuery('#lang_sel_list .iclflag').show();
    }else{
        jQuery('#lang_sel .iclflag').hide();
		jQuery('#lang_sel_list .iclflag').hide();
    }
	
	if(icl_lp_font_current_normal){                                                                          
        jQuery('#lang_sel a:first').css('color',icl_lp_font_current_normal) ; 
		jQuery('#lang_sel_list a.lang_sel_sel').css('color',icl_lp_font_current_normal) ; 
    }    
    if(icl_lp_font_current_hover){
        jQuery('#lang_sel a:first, #lang_sel a.lang_sel_sel').unbind('hover');
        jQuery('#lang_sel a:first, #lang_sel a.lang_sel_sel').hover(
            function(){jQuery(this).css('color',icl_lp_font_current_hover)},
            function(){
                jQuery(this).css('color',icl_lp_font_current_normal);
                jQuery('#lang_sel a.lang_sel_sel').css('color',icl_lp_font_current_normal);
                }
            );
		jQuery('#lang_sel_list a.lang_sel_sel').unbind('hover');
        jQuery('#lang_sel_list a.lang_sel_sel').hover(
            function(){jQuery(this).css('color',icl_lp_font_current_hover)},
            function(){
                jQuery(this).css('color',icl_lp_font_current_normal);
                jQuery('#lang_sel_list a.lang_sel_sel').css('color',icl_lp_font_current_normal);
                }
            );
    }
	
	if(icl_lp_background_current_normal){
        jQuery('#lang_sel a:first').css('background-color', icl_lp_background_current_normal); 
		jQuery('#lang_sel_list a.lang_sel_sel').css('background-color', icl_lp_background_current_normal);
        
        jQuery('#lang_sel a:first').unbind('hover');
        jQuery('#lang_sel a:first').hover(
            function(){jQuery(this).css('background-color', '')},
            function(){jQuery(this).css('background-color', icl_lp_background_current_normal)}
            );
		
		jQuery('#lang_sel_list a.lang_sel_sel').unbind('hover');
        jQuery('#lang_sel_list a.lang_sel_sel').hover(
            function(){jQuery(this).css('background-color', '')},
            function(){jQuery(this).css('background-color', icl_lp_background_current_normal)}
            );
        
    }
    
    if(icl_lp_background_current_hover){                                                          
        jQuery('#lang_sel a:first').unbind('hover');
        jQuery('#lang_sel a:first').hover(
            function(){jQuery(this).css('background-color', icl_lp_background_current_hover)},
            function(){jQuery(this).css('background-color', icl_lp_background_current_normal)}
            );
		jQuery('#lang_sel_list a.lang_sel_sel').unbind('hover');
        jQuery('#lang_sel_list a.lang_sel_sel').hover(
            function(){jQuery(this).css('background-color', icl_lp_background_current_hover)},
            function(){jQuery(this).css('background-color', icl_lp_background_current_normal)}
            );
    }
    
}

function iclUpdateLangSelColorScheme(){
    scheme = jQuery(this).val();
    if(scheme && confirm(jQuery(this).next().html())){
        jQuery('#icl_lang_preview_config input[type="text"]').each(function(){
            thisn = jQuery(this).attr('name').replace('icl_lang_sel_config[','').replace(']','');
            value = jQuery('#icl_lang_sel_config_alt_'+scheme+'_'+thisn).val();
            jQuery(this).val(value);
                        
            switch(jQuery(this).attr('name')){
                case 'icl_lang_sel_config[font-current-normal]':
                    icl_lp_font_current_normal = value;
                    break;
                case 'icl_lang_sel_config[font-current-hover]':
                    icl_lp_font_current_hover = value;
                    break;                
                case 'icl_lang_sel_config[background-current-normal]':
                    icl_lp_background_current_normal = value;
                    break;
                case 'icl_lang_sel_config[background-current-hover]':
                    icl_lp_background_current_hover = value;
                    break;                
                case 'icl_lang_sel_config[font-other-normal]':
                    icl_lp_font_other_normal = value;
                    break;
                case 'icl_lang_sel_config[font-other-hover]':
                    icl_lp_font_other_hover = value;
                    break;                
                case 'icl_lang_sel_config[background-other-normal]':
                    icl_lp_background_other_normal = value;
                    break;
                case 'icl_lang_sel_config[background-other-hover]':
                    icl_lp_background_other_hover = value;
                    break;                
                case 'icl_lang_sel_config[border]':
                    icl_lp_border = value;
                    break;            
            }            
            
        });
        
        iclRenderLangPreview();
        
    }
}






// FOOTER


var icl_lp_footer_font_current_normal = false;
var icl_lp_footer_font_current_hover = false;
var icl_lp_footer_background_current_normal = false;
var icl_lp_footer_background_current_hover = false;
var icl_lp_footer_font_other_normal = false;
var icl_lp_footer_font_other_hover = false;
var icl_lp_footer_background_other_normal = false;
var icl_lp_footer_background_other_hover = false;
var icl_lp_footer_border = false;
var icl_lp_footer_flag = false;
var icl_lp_footer_background = false;


function iclUpdateLangSelQuickPreviewFooter(){
    name = jQuery(this).attr('name');
    value = jQuery(this).val();
    switch(name){
        case 'icl_lang_sel_footer_config[font-current-normal]':
            icl_lp_footer_font_current_normal = value;
            break;
        case 'icl_lang_sel_footer_config[font-current-hover]':
            icl_lp_footer_font_current_hover = value;
            break;                
        case 'icl_lang_sel_footer_config[background-current-normal]':
            icl_lp_footer_background_current_normal = value;
            break;
        case 'icl_lang_sel_footer_config[background-current-hover]':
            icl_lp_footer_background_current_hover = value;
            break;                
        case 'icl_lang_sel_footer_config[font-other-normal]':
            icl_lp_footer_font_other_normal = value;
            break;
        case 'icl_lang_sel_footer_config[font-other-hover]':
            icl_lp_footer_font_other_hover = value;
            break;                
        case 'icl_lang_sel_footer_config[background-other-normal]':
            icl_lp_footer_background_other_normal = value;
            break;
        case 'icl_lang_sel_footer_config[background-other-hover]':
            icl_lp_footer_background_other_hover = value;
            break;                
        case 'icl_lang_sel_footer_config[border]':
            icl_lp_footer_border = value;
            break;            
        case 'icl_lso_footer_flags':
            icl_lp_footer_flag = jQuery(this).attr('checked');
            break;            
        case 'icl_lang_sel_footer_config[background]':
            icl_lp_footer_background = value;
            break;  
    }
    iclRenderLangPreviewFooter();
}

function iclRenderLangPreviewFooter(){
   
    
                                                                                                               
    if(icl_lp_footer_font_other_normal){
        jQuery('#lang_sel_footer ul a').css('color', icl_lp_footer_font_other_normal); 
    }    
    if(icl_lp_footer_font_other_hover){
        jQuery('#lang_sel_footer ul a').unbind('hover');
        jQuery('#lang_sel_footer ul a').hover(
            function(){jQuery(this).css('color',icl_lp_footer_font_other_hover)},
            function(){jQuery(this).css('color',icl_lp_footer_font_other_normal)}
            );
    }

    if(icl_lp_footer_background_other_normal){
        jQuery('#lang_sel_footer ul a').css('background-color', icl_lp_footer_background_other_normal) ; 
        jQuery('#lang_sel_footer ul a').unbind('hover');
        jQuery('#lang_sel_footer ul a').hover(
            function(){jQuery(this).css('background-color', '')},
            function(){jQuery(this).css('background-color', icl_lp_footer_background_other_normal)}
            );        
    }    
    if(icl_lp_footer_background_other_hover){
        jQuery('#lang_sel_footer ul a').unbind('hover');
        jQuery('#lang_sel_footer ul a').hover(
            function(){jQuery(this).css('background-color', icl_lp_footer_background_other_hover)},
            function(){jQuery(this).css('background-color', icl_lp_footer_background_other_normal)}
            );
    }
    
    if(icl_lp_footer_border){
        jQuery('#lang_sel_footer').css('border-color', icl_lp_footer_border);
    }
	
	if(icl_lp_footer_background){
        jQuery('#lang_sel_footer').css('background-color', icl_lp_footer_background);
    }
    
    if(jQuery('#icl_save_language_switcher_options :checkbox[name="icl_lso_flags"]').attr('checked')){
        jQuery('#lang_sel_footer .iclflag').show();
    }else{
        jQuery('#lang_sel_footer .iclflag').hide();
    }
	
	if(icl_lp_footer_font_current_normal){                                                                     
        jQuery('#lang_sel_footer a:first').css('color',icl_lp_footer_font_current_normal) ; 
    } 
	
	if(icl_lp_footer_font_current_hover){
        jQuery('#lang_sel_footer a:first, #lang_sel_footer a.lang_sel_sel').unbind('hover');
        jQuery('#lang_sel_footer a:first, #lang_sel_footer a.lang_sel_sel').hover(
            function(){jQuery(this).css('color',icl_lp_footer_font_current_hover)},
            function(){
                jQuery(this).css('color',icl_lp_footer_font_current_normal);
                jQuery('#lang_sel_footer a.lang_sel_sel').css('color',icl_lp_footer_font_current_normal);
                }
            );
    }
    
    if(icl_lp_footer_background_current_normal){
        jQuery('#lang_sel_footer a:first').css('background-color', icl_lp_footer_background_current_normal); 
        
        jQuery('#lang_sel_footer a:first').unbind('hover');
        jQuery('#lang_sel_footer a:first').hover(
            function(){jQuery(this).css('background-color', '')},
            function(){jQuery(this).css('background-color', icl_lp_footer_background_current_normal)}
            );
        
    }
    
    if(icl_lp_footer_background_current_hover){                                                          
        jQuery('#lang_sel_footer a:first').unbind('hover');
        jQuery('#lang_sel_footer a:first').hover(
            function(){jQuery(this).css('background-color', icl_lp_footer_background_current_hover)},
            function(){jQuery(this).css('background-color', icl_lp_footer_background_current_normal)}
            );
    }
    
}

function iclUpdateLangSelColorSchemeFooter(){
    scheme = jQuery(this).val();
    if(scheme && confirm(jQuery(this).next().html())){
        jQuery('#icl_lang_preview_config_footer input[type="text"]').each(function(){
            thisn = jQuery(this).attr('name').replace('icl_lang_sel_footer_config[','').replace(']','');
            value = jQuery('#icl_lang_sel_footer_config_alt_'+scheme+'_'+thisn).val();
            jQuery(this).val(value);
                        
            switch(jQuery(this).attr('name')){
                case 'icl_lang_sel_footer_config[font-current-normal]':
                    icl_lp_footer_font_current_normal = value;
                    break;
                case 'icl_lang_sel_footer_config[font-current-hover]':
                    icl_lp_footer_font_current_hover = value;
                    break;                
                case 'icl_lang_sel_footer_config[background-current-normal]':
                    icl_lp_footer_background_current_normal = value;
                    break;
                case 'icl_lang_sel_footer_config[background-current-hover]':
                    icl_lp_footer_background_current_hover = value;
                    break;                
                case 'icl_lang_sel_footer_config[font-other-normal]':
                    icl_lp_footer_font_other_normal = value;
                    break;
                case 'icl_lang_sel_footer_config[font-other-hover]':
                    icl_lp_footer_font_other_hover = value;
                    break;                
                case 'icl_lang_sel_footer_config[background-other-normal]':
                    icl_lp_footer_background_other_normal = value;
                    break;
                case 'icl_lang_sel_footer_config[background-other-hover]':
                    icl_lp_footer_background_other_hover = value;
                    break;                
                case 'icl_lang_sel_footer_config[border]':
                    icl_lp_footer_border = value;
                    break;
				case 'icl_lang_sel_footer_config[background]':
                    icl_lp_footer_background = value;
                    break;             
            }            
            
        });
        
        iclRenderLangPreviewFooter();
        
    }
}




// Picker f
var cp = new ColorPicker();
function pickColor(color) {
			jQuery('#'+icl_cp_target).val(color);
			jQuery('#'+icl_cp_target).trigger('keyup');
		}
cp.writeDiv();

function iclHideLanguagesCallback(){
    iclSaveForm_success_cb.push(function(frm,res){
        jQuery('#icl_hidden_languages_status').html(res[1]);
    });
}

function icl_reset_languages(){
    var thisb = jQuery(this);
    if(confirm(thisb.next().html())){
        thisb.attr('disabled','disabled').next().html(icl_ajxloaderimg).fadeIn();
        jQuery.ajax({
                type: "POST",
                url: icl_ajx_url,
                data: "icl_ajx_action=reset_languages",
                success: function(msg){location.href=location.pathname+location.search}
        });    
        
        
    }
}

function iclEnableContentTranslation(){
    var val = jQuery(':radio[name=icl_translation_option]:checked').val();
    jQuery(this).attr('disabled','disabled');
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=toggle_content_translation&wizard=1&new_val="+val,
        success: function(msg){
            spl = msg.split('|');
            if(spl[1]){
                location.href = spl[1];
            }else{
                location.href = location.href.replace(/#.*/,'');
            }
        }
    });
    return false;         
}
