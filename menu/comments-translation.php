<?php     
require_once ICL_PLUGIN_PATH . '/sitepress.php'; 
$active_languages = $sitepress->get_active_languages();            
$languages = $sitepress->get_languages();            
global $userdata, $current_user;
$users = get_editable_authors($userdata->ID);
if($user_language = get_user_meta($current_user->data->ID,'icl_admin_language',true)){
    $lang_details = $sitepress->get_language_details($user_language);
    $user_language = $lang_details['display_name'];
}else{
    $user_language = __('the default language','sitepress');
}
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32" style="background: transparent url(<?php echo ICL_PLUGIN_URL ?>/res/img/icon.png) no-repeat"><br /></div>
    <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>    
    
    <h3><?php echo __('Comments translation', 'sitepress') ?></h3>    
    <br />
    <p><?php _e('Visitor comments can be translated to and from each user’s language. Different users can choose their language preferences in their profile pages.','sitepress') ?></p>
    <p><?php printf(__('Your current admin language is %s. You can change it in your <a href="%s">profile page</a>.','sitepress'),$user_language, 'profile.php#wpml'); ?></p>
    <?php if(!$sitepress->icl_account_configured() || !$sitepress->get_icl_translation_enabled()): ?>
    <br />
    <?php printf(__('To translate your replies, you need to enable <a href="%s">professional translation</a>.','sitepress'),'admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/content-translation.php')?>
    <?php endif; ?>
    
    <form name="icl_ct_user_pref" id="icl_ct_user_pref" method="post" action="">
    <input type="hidden" name="icl_ajx_action" value="save_ct_user_pref" />    
    <table id="iclt_user_comments_settings" class="widefat" cellpadding="0" cellspacing="0" style="width:50%;margin:10px 0 10px 0;">
        <thead>
            <tr>
                <th scope="col"><?php _e('User login', 'sitepress') ?></th>
                <th scope="col" nowrap="nowrap"><?php _e('Translate comments by visitors', 'sitepress') ?></th>
                <th scope="col" nowrap="nowrap"><?php _e('Translate replies', 'sitepress') ?></th>                    
            </tr>
        </thead>
        <tbody>
            <?php foreach((array)$users as $u): 
                $enable_comments_translation = get_user_meta($u->ID,'icl_enable_comments_translation',true);
                $enable_replies_translation = get_user_meta($u->ID,'icl_enable_replies_translation',true);
            ?>
            <tr>
            <td><a href="user-edit.php?user_id=<?php echo $u->ID?>"><?php echo $u->user_login ?></a></td>
            <td width="5%" align="center"><input type="checkbox" name="icl_enable_comments_translation[<?php echo $u->ID ?>]" value="1" 
                <?php if($enable_comments_translation): ?>checked="checked"<?php endif?> /></td>
            <td width="5%" align="center"><input type="checkbox" name="icl_enable_replies_translation[<?php echo $u->ID ?>]" value="1" 
                <?php if($enable_replies_translation && $sitepress->icl_account_configured() && $sitepress->get_icl_translation_enabled()): ?>checked="checked"<?php endif?> <?php if(!$sitepress->icl_account_configured() || !$sitepress->get_icl_translation_enabled()) echo 'disabled="disabled"' ?> /></td>
            </tr>                                                                       
            <?php endforeach; ?>
        </tbody>
    </table>       
    <p>
        <input type="submit" class="button secondary" value="<?php _e('Save', 'sitepress'); ?>"/>
        <span class="icl_ajx_response" id="icl_ajx_response"></span>
    </p>
    </form>  
    
    
    <?php do_action('icl_menu_footer'); ?>
</div>