<?php
global $wpdb, $current_user, $sitepress_settings, $sitepress;
$active_languages = $this->get_active_languages();
foreach ($active_languages as $lang) {
    if ($default_language != $lang['code']) {
        $default = '';
    } else {
        $default = ' (' . __('default', 'sitepress') . ')';
    }
    $alanguages_links[] = $lang['display_name'] . $default;
}
//require_once(ICL_PLUGIN_PATH . '/inc/support.php');
//$SitePress_Support = new SitePress_Support;
//$pss_status = $SitePress_Support->get_subscription();
//if (!isset($pss_status['valid'])) {
//    $pss_string_status = __('None', 'sitepress');
//} else {
//    if ($pss_status['valid']) {
//        $pss_string_status = '<span class="icl_valid_text">' . sprintf(__('Valid! (amount: $%d - until %s)', 'sitepress'), $pss_status['amount'], date('d/m/Y', $pss_status['expires'])) . '</span>';
//    } else {
//        $pss_string_status = '<span class="icl_error_text">' . sprintf(__('Expired! - since %s', 'sitepress'), date('d/m/Y', $pss_status['expires'])) . '</span>';
//    }
//}

$docs_sent = 0;
$docs_completed = 0;
$docs_waiting = 0;
$docs_statuses = $wpdb->get_results("SELECT status FROM {$wpdb->prefix}icl_translation_status");
foreach ($docs_statuses as $doc_status) {
    $docs_sent += 1;
    if ($doc_status->status == ICL_TM_COMPLETE) {
        $docs_completed += 1;
    } elseif ($doc_status->status == ICL_TM_WAITING_FOR_TRANSLATOR
            || $doc_status->status == ICL_TM_IN_PROGRESS) {
        $docs_waiting += 1;
    }
}

?>
<?php if (!$this->settings['setup_complete']): ?>
    <p class="updated" style="text-align: center; padding:4px"><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php"><strong><?php _e('Setup languages', 'sitepress') ?></strong></a></p>
<?php else: ?>
        <p><?php _e('Site languages:', 'sitepress') ?> <b><?php echo join(', ', (array) $alanguages_links) ?></b> (<a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php"><?php _e('edit', 'sitepress'); ?></a>)</p>
        <p><?php if ($docs_sent)
            printf(__('%d documents sent to translation.<br />%d are complete, %d waiting for translation.', 'sitepress'), $docs_sent, $docs_completed, $docs_waiting); ?></p>
    <p><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER; ?>/menu/translation-management.php" class="button secondary"><strong><?php _e('Translate content', 'sitepress'); ?></strong></a></p>
            
    <h5 style="margin: 15px 0 0 0;"><?php _e('Need translation work?', 'sitepress'); ?></h5>
            <p><?php printf(__('%s offers affordable professional translation via a streamlined process.','sitepress'),'<a target="_blank" href="http://www.icanlocalize.com/site/">ICanLocalize</a>') ?></p><p>
                <a href="admin-ajax.php?icl_ajx_action=quote-get" class="button secondary thickbox"><strong><?php _e('Get quote','sitepress') ?></strong></a> <a href="admin.php?page=<?php echo(ICL_PLUGIN_FOLDER); ?>/menu/translation-management.php&amp;sm=translators&amp;service=icanlocalize" class="button secondary"><strong><?php _e('Get translators','sitepress') ?></strong></a>
            </p>
            
            <?php 
            global $ICL_Pro_Translation;
            $ICL_Pro_Translation->get_icl_manually_tranlations_box('icl_cyan_box'); // shows only when translation polling is on and there are translations in progress 
            ?>
                        
            <?php if (!isset($sitepress_settings['hide_affiliate_message'])) { ?>
            <div>
                <h5 style="margin: 10px 0 0 0;"><?php _e('Our affiliate program pays 30% commission', 'sitepress'); ?></h5>
                <p style="margin-top: 5px; line-height: 1.4em;">
                    <?php printf(__('Building a site for your client? Set up your affiliate account!<br />%s Learn more %s', 'sitepress'),
                            '<a href="http://www.icanlocalize.com/destinations/go?name=wpml-affiliate-info&amp;src=' . urlencode(get_bloginfo('url')) . '&amp;iso=' . $sitepress->get_default_language() . '" target="_blank">', '</a>'); ?>
                    |
                    <a href="javascript:void(0);" onclick="if (confirm('<?php _e('Are you sure you want to dismiss this message?\r\nThis operation is permanent', 'sitepress'); ?>')) {
    jQuery(this).parent().parent().fadeOut(); jQuery.post('admin-ajax.php', { icl_ajx_action: 'hide_affiliate_message' }); }"><?php _e('Dismiss this message', 'sitepress'); ?></a>
                </p>
            </div>
            <?php } ?>

<?php if (count($active_languages) > 1) {

?>
            <div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php _e('Content translation', 'sitepress') ?></a></div>
            <div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;">
        <?php
            $your_translators = TranslationManagement::get_blog_translators();
            $other_service_translators = TranslationManagement::icanlocalize_translators_list();
            if (!empty($your_translators) || !empty($other_service_translators)) {
                echo '<p><strong>' . __('Your translators', 'sitepress') . '</strong></p><ul>';
                if (!empty($your_translators))
                foreach ($your_translators as $your_translator) {
                    echo '<li>';
                    if ($current_user->ID == $your_translator->ID) {
                        $edit_link = 'profile.php';
                    } else {
                        $edit_link = esc_url(add_query_arg('wp_http_referer', urlencode(esc_url(stripslashes($_SERVER['REQUEST_URI']))), "user-edit.php?user_id=$your_translator->ID"));
                    }
                    echo '<a href="' . $edit_link . '"><strong>' . $your_translator->display_name . '</strong></a> - ';
                    foreach ($your_translator->language_pairs as $from => $lp) {
                        $tos = array();
                        foreach ($lp as $to => $null) {
                            $tos[] = $active_languages[$to]['display_name'];
                        }
                        printf(__('%s to %s', 'sitepress'), $active_languages[$from]['display_name'], join(', ', $tos));
                    }
                    echo '</li>';
                }
                
                if (!empty($other_service_translators)){
                    $langs = $sitepress->get_active_languages();
                    foreach ($other_service_translators as $rows){
                        
                        foreach($rows['langs'] as $from => $lp){
                            $from = isset($langs[$from]['display_name']) ? $langs[$from]['display_name'] : $from;
                            $tos = array();
                            foreach($lp as $to){
                                $tos[] =  isset($langs[$to]['display_name']) ? $langs[$to]['display_name'] : $to;
                            }
                        }
                        echo '<li>';
                        echo '<strong>' . $rows['name'] . '</strong> | ' . sprintf(__('%s to %s', 'sitepress'), $from, join(', ', $tos)) . ' | ' . $rows['action']; 
                        echo '</li>';
                    }
                }
                
                echo '</ul><hr />';
            }
            
        ?>
            <p><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER; ?>/menu/translation-management.php&amp;sm=translators&amp;service=icanlocalize"><strong><?php _e('Add translators from ICanLocalize &raquo;', 'sitepress'); ?></strong></a></p>
            <p><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER; ?>/menu/translation-management.php&amp;sm=translators&amp;service=local"><strong><?php _e('Add your own translators &raquo;', 'sitepress'); ?></strong></a></p>
            <p><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER; ?>/menu/translation-management.php"><strong><?php _e('Translate contents &raquo;', 'sitepress'); ?></strong></a></p>
        </div>
<?php } ?>

        <div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php _e('Theme and plugins localization', 'sitepress') ?></a></div>
        <div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;"><p>
        <?php
        echo __('Current configuration', 'sitepress');
        echo '<br /><strong>';
        switch ($sitepress_settings['theme_localization_type']) {
            case '1': echo __('Translate the theme by WPML', 'sitepress');
                break;
            case '2': echo __('Using a .mo file in the theme directory', 'sitepress');
                break;
            default: echo __('No localization', 'sitepress');
        }
        echo '</strong>';

        ?>
    </p>
    <p><a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/theme-localization.php' ?>"><?php echo __('Manage theme and plugins localization', 'sitepress'); ?></a></p>
</div>

<div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php _e('String translation', 'sitepress') ?></a></div>
<div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;"><p><?php echo __('String translation allows you to enter translation for texts such as the site\'s title, tagline, widgets and other text not contained in posts and pages.', 'sitepress') ?></p>
    <?php
        $strings_need_update = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}icl_strings WHERE status <> 1");
        if ($strings_need_update == 1):

    ?>
            <p><b><?php printf(__('There is <a href="%s"><b>1</b> string</a> that needs to be updated or translated. ', 'sitepress'), 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/string-translation.php&amp;status=0') ?></b></p>
    <?php elseif ($strings_need_update): ?>
                <p><b><?php printf(__('There are <a href="%s"><b>%s</b> strings</a> that need to be updated or translated. ', 'sitepress'), 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/string-translation.php&amp;status=0', $strings_need_update) ?></b></p>
    <?php else: ?>
                    <p>
        <?php echo __('All strings are up to date.', 'sitepress'); ?>
                </p>
    <?php endif; ?>
                    <p>
                        <a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/string-translation.php' ?>"><?php echo __('Translate strings', 'sitepress') ?></a>
                    </p>




    <?php endif; ?>
                </div>

                <div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php _e('Navigation', 'sitepress') ?></a></div>
                <div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;"><p>
        <?php echo __('WPML provides advanced menus and navigation to go with your WordPress website, including drop-down menus, breadcrumbs and sidebar navigation.', 'sitepress') ?>
                </p>
    <?php if (!$sitepress_settings['modules']['cms-navigation']['enabled']): ?>
                        <p><b><?php echo __('CMS Navigation is disabled.', 'sitepress') ?></b></p>
                        <p><a class="button secondary" href="<?php echo 'index.php?enable-cms-navigation=1' ?>"><?php echo __('Enable CMS navigation', 'sitepress') ?></a></p>
    <?php else: ?>
                            <p><b><?php echo __('CMS Navigation is enabled.', 'sitepress') ?></b></p>
                            <p>
                                <a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/navigation.php' ?>"><?php echo __('Configure navigation', 'sitepress') ?></a>
                                <a class="button secondary" href="<?php echo 'index.php?enable-cms-navigation=0' ?>"><?php echo __('Disable CMS navigation', 'sitepress') ?></a>
                            </p>
    <?php endif; ?>
                        </div>

                        <div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php _e('Sticky links', 'sitepress') ?></a></div>

                        <div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;"><p><?php echo __('With Sticky Links, WPML can automatically ensure that all links on posts and pages are up-to-date, should their URL change.', 'sitepress'); ?></p>

    <?php if ($sitepress_settings['modules']['absolute-links']['enabled']): ?>
                                <p><b><?php echo __('Sticky links are enabled.', 'sitepress') ?></b></p>
                                <p>
                                    <a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/absolute-links.php' ?>"><?php echo __('Configure sticky links', 'sitepress') ?></a>
                                    <a class="button secondary" href="<?php echo 'index.php?icl_enable_alp=0' ?>"><?php echo __('Disable sticky links', 'sitepress') ?></a>
                                </p>

    <?php else: ?>
                                    <p><b><?php echo __('Sticky links are disabled.', 'sitepress') ?></b></p>
                                    <p><a class="button secondary" href="<?php echo 'index.php?icl_enable_alp=1' ?>"><?php echo __('Enable sticky links', 'sitepress') ?></a></p>
    <?php endif; ?>
                                </div>

                                <div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php _e('Help resources', 'sitepress'); ?></a></div>
                                <div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;">
                                    <p><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/question1.png" width="16" height="16" style="position: relative; top: 4px;" alt="<?php _e('WPML home page', 'sitepress'); ?>" />&nbsp;<a href="http://wpml.org/"><?php _e('WPML home page', 'sitepress'); ?></a>
                                        <br /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/RO-Mx1-16_tool-wrench.png" width="16" height="16" style="position: relative; top: 4px;" alt="<?php _e('Commercial support', 'sitepress'); ?>" />&nbsp;<a href="admin.php?page=<?php echo basename(ICL_PLUGIN_PATH); ?>/menu/support.php"><?php _e('Commercial support', 'sitepress'); ?></a></p>
                                    <!-- <p><?php printf(__('Support Subscription - %s', 'sitepress'), $pss_string_status); ?>
    <?php if (!$pss_status['valid']): ?>(<a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/support.php"><?php _e('purchase', 'sitepress'); ?></a>)<?php endif; ?></p> -->
                                    </div>

<?php
$rss = fetch_feed('http://wpml.org/feed/');
if (!is_wp_error($rss)) { // Checks that the object is created correctly
    // Figure out how many total items there are, but limit it to 2.
    $maxitems = $rss->get_item_quantity(2);
    // Build an array of all the items, starting with element 0 (first element).
    $rss_items = $rss->get_items(0, $maxitems);
}
if ($maxitems != 0) {
?>
                                                <div class="rss-widget"><p><strong><?php _e('WPML news', 'sitepress'); ?></strong></p>
                                                <ul>
<?php
    // Loop through each feed item and display each item as a hyperlink.
    foreach ($rss_items as $item) {

?>
                                                    <li><a class="rsswidget" href='<?php echo $item->get_permalink(); ?>'><?php echo $item->get_title(); ?></a> <span class="rss-date"><?php echo $item->get_date('j F Y'); ?></span></li>
<?php } ?>
                                                </ul></div>
<?php
}
?>
<?php do_action('icl_dashboard_widget_content'); ?>
