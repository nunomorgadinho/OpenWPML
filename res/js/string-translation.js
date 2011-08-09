jQuery(document).ready(function(){
    jQuery('a[href="#icl-st-toggle-translations"]').click(icl_st_toggler);
    jQuery('.icl-st-inline textarea').focus(icl_st_monitor_ta);
    jQuery('.icl-st-inline textarea').keyup(icl_st_monitor_ta_check_modifications);
    jQuery(".icl_st_form").submit(icl_st_submit_translation);
    jQuery('select[name="icl_st_filter_status"]').change(icl_st_filter_status);
    jQuery('select[name="icl_st_filter_context"]').change(icl_st_filter_context);
    jQuery('#icl_st_filter_search_sb').click(icl_st_filter_search);    
    jQuery('#icl_st_filter_search_remove').click(icl_st_filter_search_remove);    
    
    jQuery('.check-column input').click(icl_st_select_all);
    jQuery('#icl_st_delete_selected').click(icl_st_delete_selected);
    jQuery('#icl_st_send_need_translation').click(icl_st_send_need_translation);
    jQuery('#icl_st_do_send_strings').submit(icl_st_do_send_strings);
    
    jQuery('#icl_st_po_translations').click(function(){
        if(jQuery(this).attr('checked')){
            jQuery('#icl_st_po_language').removeAttr('disabled').fadeIn();
        }else{
            jQuery('#icl_st_po_language').attr('disabled','disabled').fadeOut();
        }
    });
    jQuery('#icl-tr-opt :checkbox').click(icl_st_update_languages);
    jQuery('.icl_st_row_cb, .check-column :checkbox').click(icl_st_update_checked_elements);
    jQuery('.icl_htmlpreview_link').click(icl_st_show_html_preview);
    jQuery('#icl_st_po_form').submit(icl_validate_po_upload);
    jQuery('#icl_st_review_strings').submit(icl_st_review_strings);
    
    jQuery('.handlediv').click(function(){
        if(jQuery(this).parent().hasClass('closed')){
            jQuery(this).parent().removeClass('closed');
        }else{
            jQuery(this).parent().addClass('closed');
        }
    })
    
    jQuery('#icl_st_more_options').submit(iclSaveForm);
    
    jQuery('#icl_st_option_write_form').submit(icl_st_admin_options_form_submit);
    jQuery('#icl_st_option_write_form').submit(iclSaveForm);
    
    jQuery('.icl_stow_toggler').click(icl_st_admin_strings_toggle_strings);
    
    jQuery('#icl_st_ow_export').click(icl_st_ow_export_selected);
    jQuery('#icl_st_ow_export_close').click(icl_st_ow_export_close);
    
    
        
    // Picker align
    jQuery(".pick-show").click(function () {
        var set = jQuery(this).offset();
           jQuery("#colorPickerDiv").css({"top":set.top-180,"left":set.left, "z-index":99});
    });
    
});

function icl_st_toggler(){
    jQuery(".icl-st-inline").slideUp();
    var inl = jQuery(this).parent().next().next();
    if(inl.css('display') == 'none'){
        inl.slideDown();            
    }else{
        inl.slideUp();            
    }
    icl_st_show_html_preview_close();
}

var icl_st_ta_cache = new Array();
var icl_st_cb_cache = new Array();
function icl_st_monitor_ta(){
    var id = jQuery(this).attr('id').replace(/^icl_st_ta_/,'');
    if(icl_st_ta_cache[id] == undefined){
        icl_st_ta_cache[id] = jQuery(this).val();        
        icl_st_cb_cache[id] = jQuery('#icl_st_cb_'+id).attr('checked');
    }    
}

function icl_st_monitor_ta_check_modifications(){
    var id = jQuery(this).attr('id').replace(/^icl_st_ta_/,'');
    if(icl_st_ta_cache[id] != jQuery(this).val()){
        jQuery('#icl_st_cb_'+id).removeAttr('checked');
    }else{
        if(icl_st_cb_cache[id]){
            jQuery('#icl_st_cb_'+id).attr('checked','checked');
        }
    }
    icl_st_show_html_preview_close();
}

function icl_st_submit_translation(){
    var thisf = jQuery(this);
    var postvars = thisf.serialize();
    postvars += '&icl_ajx_action=icl_st_save_translation';
    thisf.contents().find('textarea, input').attr('disabled','disabled');
    thisf.contents().find('.icl_ajx_loader').fadeIn();
    var string_id = thisf.find('input[name="icl_st_string_id"]').val();
    jQuery.post(icl_ajx_url, postvars, function(msg){
        thisf.contents().find('textarea, input').removeAttr('disabled');
        thisf.contents().find('.icl_ajx_loader').fadeOut();
        spl = msg.split('|');
        jQuery('#icl_st_string_status_'+string_id).html(spl[1]);
    })
    return false;
}

function icl_st_filter_status(){
    var qs = jQuery(this).val() != '' ? '&status=' + jQuery(this).val() : '';
    location.href=location.href.replace(/#(.*)$/,'').replace(/&paged=([0-9]+)/,'').replace(/&updated=true/,'').replace(/&status=([0-9])/g,'') + qs;
}
function icl_st_filter_context(){
    var qs = jQuery(this).val() != '' ? '&context=' + jQuery(this).val() : '';
    location.href=location.href.replace(/#(.*)$/,'').replace(/&paged=([0-9]+)/,'').replace(/&updated=true/,'').replace(/&context=(.*)/g,'') + qs;
}
function icl_st_filter_search(){
    var val = jQuery('#icl_st_filter_search').val();
    var exact_match = jQuery('#icl_st_filter_search_em').attr('checked');
    var qs = val != '' ? '&search=' + val : '';
     qs = qs.replace(/&em=1/g,'');
    if(exact_match){
        qs += '&em=1';
    }
    location.href=location.href.replace(/#(.*)$/,'').replace(/&paged=([0-9]+)/,'').replace(/&updated=true/,'').replace(/&search=(.*)/g,'') + qs;
}
function icl_st_filter_search_remove(){
    location.href=location.href.replace(/#(.*)$/,'').replace(/&search=(.*)/g,'').replace(/&em=1/g,'');
}

function icl_st_select_all(){
    if(jQuery(this).attr('checked')){
        jQuery('.icl_st_row_cb, .check-column input').attr('checked','checked');
    }else{
        jQuery('.icl_st_row_cb, .check-column input').removeAttr('checked');
    }
}

function icl_st_delete_selected(){
    if(!jQuery('.icl_st_row_cb:checked').length || !confirm(jQuery(this).next().html())){
        return false;
    }
    var delids = [];
    jQuery('.icl_st_row_cb:checked').each(function(){
        delids.push(jQuery(this).val());        
    });
    if(delids){
        postvars = 'icl_ajx_action=icl_st_delete_strings&value='+delids.join(',');
        jQuery.post(icl_ajx_url, postvars, function(){
            for(i=0; i < delids.length; i++){
                jQuery('.icl_st_row_cb[value="'+delids[i]+'"]').parent().parent().fadeOut('fast', function(){jQuery(this).remove()});
            }
        })
    }
    return false;
}

function icl_st_review_strings(){
    if(!jQuery('.icl_st_row_cb:checked').length){
        return false;
    }
    var sendids = [];
    jQuery('.icl_st_row_cb:checked').each(function(){
        sendids.push(jQuery(this).val());        
    });
    var trlangs = [];
    jQuery('#icl-tr-opt input:checked').each(function(){trlangs.push(jQuery(this).val())});
    
    if(!sendids.length || !trlangs.length){
        return false;
    }
    jQuery('#icl_st_review_strings input[name="strings"]').val(sendids.join(','));
    jQuery('#icl_st_review_strings input[name="langs"]').val(trlangs.join('#'));
    
    return true;
    
}

function icl_st_send_need_translation(){
    var trlangs = [];
    jQuery('#icl-tr-opt input:checked').each(function(){trlangs.push(jQuery(this).val())});
    if(!trlangs.length){
        return false;
    }
    jQuery('#icl_st_review_strings input[name="strings"]').val('need');
    jQuery('#icl_st_review_strings input[name="langs"]').val(trlangs.join(','));
    document.getElementById('icl_st_review_strings').submit();
    return false;
}

function icl_st_do_send_strings(){    
    thisf = jQuery(this);        
    var buttons = thisf.contents().find('input:submit, input:button');
    if(jQuery('input[name="strings"]').val() == 'need'){
        var all = '_all';
    }else{
        var all = '';   
    }    
    buttons.attr('disabled','disabled');
    jQuery('#icl_st_send_progress').fadeIn();
    postvars = 'icl_ajx_action=icl_st_send_strings'+all+'&' + thisf.serialize();
    jQuery.post(icl_ajx_url, postvars, function(msg){
        if(msg==1) buttons.removeAttr('disabled');
        jQuery('#icl_st_send_progress').fadeOut('fast', function(){location.href=location.href.replace(/#(.*)$/,'')});
    });
    return false;
}



function icl_st_update_languages(){
    if(!jQuery('#icl-tr-opt :checkbox:checked').length){
        jQuery('#icl_st_send_selected, #icl_st_send_need_translation').attr('disabled','disabled');
    }else{
        if(jQuery('.icl_st_row_cb:checked, .check-column :checkbox:checked').length){
            jQuery('#icl_st_send_selected').removeAttr('disabled');            
        }
        jQuery('#icl_st_send_need_translation').removeAttr('disabled');            
    }
}

function icl_st_update_checked_elements(){    
    if(!jQuery('.icl_st_row_cb:checked, .check-column :checkbox:checked').length){
        jQuery('#icl_st_delete_selected, #icl_st_send_selected').attr('disabled','disabled');
    }else{        
        jQuery('#icl_st_delete_selected').removeAttr('disabled');
        if(!jQuery('#icl-tr-opt').length || jQuery('#icl-tr-opt :checkbox:checked').length){
            jQuery('#icl_st_send_selected').removeAttr('disabled');
        }
    }
}

function icl_st_show_html_preview(){
    var parent = jQuery(this).parent();    
    var textarea = parent.parent().prev().find('textarea[name="icl_st_translation"]');
    if(parent.find('.icl_html_preview').css('display')=='none'){
        parent.find('.icl_html_preview').html(textarea.val()).slideDown();
    }else{
        parent.find('.icl_html_preview').slideUp();
    }
    
    return false;
}

function icl_st_show_html_preview_close(){
    jQuery('.icl_html_preview').slideUp();
}

function icl_validate_po_upload(){
    cont = jQuery(this).contents();
    cont.find('.icl_error_text').hide();
    if(!jQuery('#icl_po_file').val()){
        cont.find('#icl_st_err_po').fadeIn();
        return false;
    }    
    if(!cont.find('select[name="icl_st_i_context"]').val() && !cont.find('input[name="icl_st_i_context_new"]').val()){
        cont.find('#icl_st_err_domain').fadeIn();
        return false;
    }
    
}

var icl_show_in_source_scroll_once = false;
jQuery('#icl_show_source_wrap').live('mouseover', function(){
    if(icl_show_in_source_scroll_once){
        icl_show_in_source(0, icl_show_in_source_scroll_once);
        icl_show_in_source_scroll_once = false;
    }
})
function icl_show_in_source(tabfile, line){
    
    if(icl_show_in_source_scroll_once){
        if(line > 40){
            line = line - 10;
            location.href=location.protocol+'//'+location.host+location.pathname+location.search+'#icl_source_line_'+tabfile+'_'+line;
        }
    }else{
        jQuery('.icl_string_track_source').fadeOut(
            function(){
                jQuery('#icl_string_track_source_'+tabfile).fadeIn(
                    function(){
                        
                        if(line > 40){
                            line = line - 10;
                            location.href=location.protocol+'//'+location.host+location.pathname+location.search+'#icl_source_line_'+tabfile+'_'+line;
                        }
                        
                        /*
                        var divOffset = jQuery('#TB_ajaxContent').offset().top;
                        var pOffset = jQuery('#icl_source_line_'+tabfile+'_'+line).offset().top;
                        var pScroll = pOffset - divOffset - 90;                    
                        jQuery('#TB_ajaxContent').animate({scrollTop: '+=' + pScroll}, 1000);
                        */
                        
                    }
                );
            }
        );
    }
    return false;
}

var cp = new ColorPicker();
function pickColor(color) {
            jQuery('#icl_st_hl_color').val(color).css('background-color',color);
        }
cp.writeDiv();

function iclResizeIframe(){
    jQuery('#icl_string_track_frame_wrap iframe').attr('height',jQuery('#TB_ajaxContent').height()-20);
    jQuery('#icl_string_track_frame_wrap iframe').attr('width',jQuery('#TB_ajaxContent').width());
}

function icl_st_admin_options_form_submit(frm,msg){
    if(jQuery('input:checkbox.icl_st_has_translations[checked!=true]').length){
        c = confirm(jQuery('#icl_st_options_write_confirm').html());
        if(c){
            iclHaltSave = false;
        }else{
            iclHaltSave = true;
        }
    }
    iclSaveForm_success_cb.push(function(){
        jQuery('#icl_st_options_write_success').fadeIn();
    });
}


function icl_st_admin_strings_toggle_strings(){
    var thisa = jQuery(this);
    jQuery(this).parent().next().slideToggle(function(){
        if(thisa.html().charAt(0)=='+'){
            thisa.html(thisa.html().replace(/^\+/,'-'));
        }else{
            thisa.html(thisa.html().replace(/^-/,'+'));
        }
    });
    return false;
}

function icl_st_ow_export_selected(){
    jQuery('#icl_st_ow_export').attr('disabled','disabled');
    jQuery('#icl_st_option_writes .ajax_loader').fadeIn();
    jQuery.ajax({
        type: "POST",
        dataType: 'json',
        url: icl_ajx_url,
        data: "icl_ajx_action=icl_st_ow_export&"+jQuery('#icl_st_option_write_form').serialize(),
        success: function(res){            
            jQuery('#icl_st_ow_export_out').html(res.message).slideDown();
            jQuery('#icl_st_option_writes .ajax_loader').fadeOut(
                function(){
                    jQuery('#icl_st_ow_export_close').fadeIn();            
                }
            );
            
        }
    });
}

function icl_st_ow_export_close(){
    jQuery('#icl_st_ow_export_out').slideUp(function(){jQuery('#icl_st_ow_export_close').fadeOut()});
    jQuery('#icl_st_ow_export').removeAttr('disabled');
}
