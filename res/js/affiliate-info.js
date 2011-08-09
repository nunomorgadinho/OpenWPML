jQuery(document).ready(function(){
    jQuery('#icl_affiliate_info_check').submit(iclAffiliateInfoCheck);    
});

function iclAffiliateInfoCheck(){
    var thisf = jQuery(this);
    thisf.find('.icl_cyan_box').hide();
    jQuery.ajax({
            type: "POST",
            url: icl_ajx_url,
            dataType: 'json',
            data: "icl_ajx_action=affiliate_info_check&" + thisf.serialize(),
            success: function(msg){
                if(msg.error){
                    thisf.find('.icl_error_text').fadeIn();
                }else{
                    thisf.find('.icl_valid_text').fadeIn();
                }
            }
        });        
    
    return false;
}
