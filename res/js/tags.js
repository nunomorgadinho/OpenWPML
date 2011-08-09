jQuery(document).ready(function(){  
    if(jQuery('form input[name="action"]').attr('value')=='add-tag'){
        jQuery('.form-wrap p[class="submit"]').before(jQuery('#icl_tax_menu').html());    
    }else{
        jQuery('#edittag table[class="form-table"]').append(jQuery('#edittag table[class="form-table"] tr:last').clone());    
        jQuery('#edittag table[class="form-table"] tr:last th:first').html('&nbsp;');
        jQuery('#edittag table[class="form-table"] tr:last td:last').html(jQuery('#icl_tax_menu').html());        
    }    
    jQuery('#icl_tax_menu').remove();
       
   jQuery('select[name="icl_tag_language"]').change(function(){
        var icl_subsubsub_save = jQuery('#icl_subsubsub').html();
        var lang = jQuery(this).val();
        var ajx = location.href.replace(/#(.*)$/,'');
        ajx = ajx.replace(/pagenum=([0-9]+)/,'');
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
        

        jQuery('#posts-filter').parent().load(ajx+url_glue+'lang='+lang + ' #posts-filter', {}, function(resp){
            strt = resp.indexOf('<span id="icl_subsubsub">');
            endd = resp.indexOf('</span>\'', strt);
            lsubsub = resp.substr(strt,endd-strt+7);
            jQuery('table.widefat').before(lsubsub);            
                                                                         
            tag_start = resp.indexOf('<div class="tagcloud">');
            tag_end  = resp.indexOf('</div>', tag_start);            
            tag_cloud = resp.substr(tag_start+22,tag_end-tag_start-22);
            jQuery('.tagcloud').html(tag_cloud);
        });        
        
   })     
    
        
});

