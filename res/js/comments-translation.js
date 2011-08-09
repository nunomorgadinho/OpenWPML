jQuery(document).ready(function(){
    jQuery('#icl_ct_user_pref').submit(iclSaveForm);
    
    jQuery('.icl_original_comment_link').live('click', iclShowOriginalComment);
    var icl_preload_ajx_img = new Image(16,16);
    icl_preload_ajx_img.src = icl_ajxloaderimg_src;
});

var icl_anchor_original_text = 0;
function iclShowOriginalComment(){
    var cid = jQuery(this).attr('href').replace(/#c/,'');
    
    if(jQuery('#comment-'+cid).length){
        var ctr = jQuery('#comment-'+cid);    
    }else if(jQuery('.comments-box').length){
        var ctr = jQuery('.comments-box');    
    }
    
    if(ctr.find('#submitted-on').next().length){
        var icl_comment_place = ctr.find('#submitted-on').next();
    }else if(ctr.find('.dashboard-comment-wrap blockquote')){
        var icl_comment_place = ctr.find('.dashboard-comment-wrap blockquote');
    }
    
    icl_comment_place.html(icl_ajxloaderimg);    
    
    jthis = jQuery(this);
    if(!icl_anchor_original_text){
        icl_anchor_original_text = jthis.html();
    }    
    jQuery.ajax({
        type: "POST",
        url: icl_ajx_url,
        dataType: 'json',
        data: "icl_ajx_action=get_original_comment&comment_id="+cid,
        success: function(ret){

            icl_comment_place.html(ret['comment_content']);
            
            var edit_link = ctr.find('.row-actions span.edit a');
            edit_link.attr('href', edit_link.attr('href').replace(/&c=[0-9]+/,'&c='+ret['comment_ID']));
            
            var quick_edit_link = ctr.find('.quickedit a');
            quick_edit_link.unbind('click');
            quick_edit_link.bind('click', function(){ commentReply.open(ret['comment_ID'],ret['comment_post_ID'],'edit');return false;});
            
            var reply_link = ctr.find('.reply a');
            reply_link.unbind('click');
            reply_link.bind('click', function(){ commentReply.open(ret['comment_ID'],ret['comment_post_ID']);return false;});
            
            jQuery('#inline-'+cid+' textarea.comment').val(ret['comment_content']);
            jQuery('#comment-'+cid).attr('id', 'comment-'+ret['comment_ID']);
            jQuery('#inline-'+cid).attr('id', 'inline-'+ret['comment_ID']);
            jthis.attr('href','#c'+ret['comment_ID'])
            
            if(ret['translated_version']){                
                jthis.html(icl_anchor_original_text);                
                //jQuery('#replysubmit input[name="icl_translate_reply"]').removeAttr('disabled').parent().show();
            }else{
                jthis.html(ret['anchor_text']);
                //jQuery('#replysubmit input[name="icl_translate_reply"]').attr('disabled','disabled').parent().hide();
            }
            
            jQuery('#replycontainer').prepend('<input type="hidden" name="icl_comment_language_'+ret['comment_ID']+'" value="'+ret['language_code']+'" />');
        }
    });    
    return false;
}

function icl_retry_mtr(a){
    var id = a.getAttribute('id');
    spl = id.split('_');
    var loc = location.href.replace(/#(.*)$/,'').replace(/(&|\?)(retry_mtr)=([0-9]+)/g,'').replace(/&nonce=([0-9a-z]+)(&|$)/g,'');
    if(-1 == loc.indexOf('?')){
        url_glue='?';
    }else{
        url_glue='&';
    }    
    location.href=loc+url_glue+'retry_mtr='+spl[3]+'&nonce='+spl[4];
    return false;
}

