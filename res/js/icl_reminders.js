jQuery(document).ready(function(){

    jQuery('#icl_reminder_show').click(icl_show_hide_reminders);

    jQuery('#icl_reminder_message').css({'margin-bottom' : '5px'});
    jQuery('#icl_reminder_message').css({'padding-bottom' : '2px'});
    jQuery('#icl_reminder_message h4').css({'margin-bottom' : '0px'});
    jQuery('#icl_reminder_message h4').css({'margin-top' : '0px'});
    
    if (location.href.indexOf('&icl_refresh_langs') != -1) {
        do_message_refresh = true;
    }
    show_messages();    
    do_message_refresh = false;
	
	// Added box resize for regular 'thickbox'
	icl_tb_set_size('a.icl_regular_thickbox');
});

var do_message_refresh = false;
function show_messages() {
    var command = "icl_ajx_action=icl_messages";
    if (do_message_refresh) {
        command += "&refresh=1";
        do_message_refresh = false;
    }
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: command,
        cache: false,
        dataType: 'json',                
        success: function(resp){ 
            if(resp && resp.messages > 0){
                jQuery('#icl_show_text').html(resp.reminder_text);
                jQuery('#icl_reminder_list').html(resp.output);
                jQuery('#icl_reminder_message').fadeIn();
                icl_tb_init('a.icl_thickbox');
                icl_tb_set_size('a.icl_thickbox');
            } else {
                jQuery('#icl_reminder_message').fadeOut();                
            }  
        }
    }); 

}

function icl_tb_init(domChunk) {
    // copied from thickbox.js
    // add code so we can detect closure of popup
	
    jQuery(domChunk).unbind('click');
	icl_support_view_ticket();
    
    jQuery(domChunk).click(function(){
    var t = this.title || this.name || "ICanLocalize Reminder";
    var a = this.href || this.alt;
    var g = this.rel || false;
    tb_show(t,a,g);
    
    do_message_refresh = true;
    jQuery('#TB_window').bind('unload', function(){
        url = location.href;
        if (url.indexOf('content-translation.php') != -1) {
        
            url = url.replace(/&icl_refresh_langs=1/g, '');
            url = url.replace(/&show_config=1/g, '');
            url = url.replace(/#.*/,'');
            if(jQuery('#icl_account_setup').is(':visible')) {
                location.href = url + "&icl_refresh_langs=1&show_config=1"
            } else {
                location.href = url + "&icl_refresh_langs=1"
            }
        } else if (url.indexOf('support.php') != -1) {
			location.href = url;
		} else {           
            if (t == "ICanLocalize Reminder" && do_message_refresh) {
                
                // do_message_refresh will only be true if we close the popup.
                // if the dismiss link is clicked then do_message_refresh is set to false before closing the popup.
                
                jQuery('#icl_reminder_list').html('Refreshing messages  ' + icl_ajxloaderimg);
                show_messages();
                }
            
            if(a.indexOf('after=refresh_langs') != -1) {
            
                icl_refresh_translator_not_available_links();
            }
        }        
        });
    
    this.blur();
    return false;
    });
}

function icl_prevent_tb_reload(){
    // simply not call the default unload event
    return false;
}

function icl_tb_set_size(domChunk) {
    if (typeof(tb_getPageSize) != 'undefined') {

        var pagesize = tb_getPageSize();
        jQuery(domChunk).each(function() {
            var url = jQuery(this).attr('href');
            url += '&width=' + (pagesize[0] - 150);
            url += '&height=' + (pagesize[1] - 150);
            url += '&tb_avail=1'; // indicate that thickbox is available.
            jQuery(this).attr('href', url);
        });
    }
}

function dismiss_message(message_id) {
    do_message_refresh = false;
    jQuery('#icl_reminder_list').html('Refreshing messages  ' + icl_ajxloaderimg);
    tb_remove();
    
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        data: "icl_ajx_action=icl_delete_message&message_id=" + message_id,
        async: false,
        success: function(msg){
        }
    }); 
    
    show_messages();
}

function icl_show_hide_reminders() {
    jqthis = jQuery(this);
    if(jQuery('#icl_reminder_list').css('display')=='none'){
        jQuery('#icl_reminder_list').fadeIn();
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=icl_show_reminders&state=show",
            async: true,
            success: function(msg){
            }
        }); 
    } else {
        jQuery('#icl_reminder_list').fadeOut();
        jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            data: "icl_ajx_action=icl_show_reminders&state=hide",
            async: true,
            success: function(msg){
            }
        }); 
        
    }
    jqthis.children().toggle();    
}


function icl_support_view_ticket() {
		jQuery('#icl_support_table a.icl_support_viewed').bind('click',function(){
			jQuery.ajax({
				type: "POST",
				url: icl_ajx_url,
				data: "icl_ajx_action=icl_support_update_ticket&ticket=" + jQuery(this).attr('id'),
				async: false,
				success: function(msg){
				}
			}); 
		});
	}

function icl_thickbox_reopen(url) {
  tb_remove();
  if (url.indexOf("?") == -1) {
    var glue = '?';
  } else {
    var glue = '&';
  }
  jQuery('#iclThickboxReopenLink').remove();
  jQuery('body').prepend('<a id="iclThickboxReopenLink" href="'+url+glue+'keepThis=true&amp;TB_iframe=true" class="thickbox" style="display:none;">test</a>');
  icl_tb_set_size('#iclThickboxReopenLink');
  jQuery('#iclThickboxReopenLink').addClass('initThickbox-processed').click(function() {
    var t = this.title || this.name || null;
    var a = this.href || this.alt;
    var g = this.rel || false;
    tb_show(t,a,g);
    this.blur();
    return false;
  });
  window.setTimeout(function() {
    jQuery('#iclThickboxReopenLink').trigger('click');
    jQuery('#TB_window').bind('unload', function() {
      window.location.href = unescape(window.location); // Add .pathname to get URL without query
    });
  }, 1000);
}

function icl_thickbox_refresh() {
  window.location.href = unescape(window.location);
}