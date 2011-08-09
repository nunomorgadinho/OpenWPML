<?php        
    if(defined('ICL_AFFILIATE_ID')){
        $icl_affiliate_id = ICL_AFFILIATE_ID;
    }else{
        $icl_affiliate_id = '';
    }
    if(defined('ICL_AFFILIATE_KEY')){
        $icl_affiliate_key = ICL_AFFILIATE_KEY;
    }else{
        $icl_affiliate_key = '';
    }    
?>
<?php $sitepress->noscript_notice() ?>
<div class="wrap">
    <div id="icon-options-general" class="icon32 icon32_adv"><br /></div>
    <h2><?php echo __('Affiliate information check', 'sitepress') ?></h2>    
    
    <form id="icl_affiliate_info_check" method="post" action="">
    
    <h3><?php _e('Affiliate test') ?></h3>
    <table class="widefat">
        <tr>
            <td align="right"><?php _e('Affiliate ID', 'sitepress') ?></td>
            <td>
                <input type="text" name="icl_affiliate_id" readonly="readonly" value="<?php echo $icl_affiliate_id ?>" />
                <?php if(!$icl_affiliate_id): ?>
                <p class="icl_error_text"><?php _e('Affiliate ID not defined in the theme','sitepress') ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td align="right"><?php _e('Affiliate Key', 'sitepress') ?></td>
            <td>
                <input type="text" name="icl_affiliate_key" readonly="readonly" value="<?php echo $icl_affiliate_key ?>" size="38" />
                <?php if(!$icl_affiliate_key): ?>
                <p class="icl_error_text"><?php _e('Affiliate KEY not defined in the theme','sitepress') ?></p>
                <?php endif; ?>                
            </td>
        </tr>
        <tr>
            <td align="right" colspan="2">
                <span class="icl_cyan_box icl_valid_text" 
                    style="padding:3px;display:none;"><?php _e('Congratulations! Your affiliate information is correct.','sitepress') ?></span>
                <span class="icl_cyan_box icl_error_text" 
                    style="padding:3px;display:none;"><?php _e('Sorry, the affiliate information did not validate.','sitepress') ?></span>
                <input type="submit" value="<?php _e('Test', 'sitepress')?>" <?php 
                    if(!$icl_affiliate_id || !$icl_affiliate_key) echo 'disabled="disabled"'; ?> />
            </td>
        </tr>
    </table>
    
    </form>
     
    <?php do_action('icl_menu_footer'); ?>       
</div>