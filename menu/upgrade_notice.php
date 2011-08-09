<?php 
$upgrade_lines =  array(
    '1.3.1' => __('translation controls on posts and pages lists', 'sitepress'),
    '1.3.3' => __('huge speed improvements and the ability to prevent loading WPML\'s CSS and JS files', 'sitepress'),
    '1.3.4' => __('you can configure the position and contents of the posts page in the top navigation', 'sitepress'),
    '1.3.5' => __('many bugs fixed and an easy way to show your love for WPML', 'sitepress'),
    '1.4.0' => __('simplified operation for basic usage and for getting professional translation', 'sitepress'),
    '1.5.0' => __('theme compatibility packages, design for language switcher and language fall-back for posts', 'sitepress'),
    '1.5.1' => __('bugs fixed and new support for Headspace2 SEO plugin', 'sitepress'),
    '1.6.0' => __('WPML can now translate other plugins', 'sitepress'),
    '1.7.0' => __('WPML adapts to any WordPress theme', 'sitepress'),
    '1.7.1' => __('Home-page link automatically adjusts per language', 'sitepress'),
    '1.7.2' => __('Bug fixes and stability improvements', 'sitepress'),
    '1.7.3' => __('Added languages editing and translation for admin-texts','sitepress'),
    '1.7.4' => __('Works with WordPress 3','sitepress'),
    '1.7.6' => __('Works with WordPress 3','sitepress'),
    '1.7.7' => __('Supports custom taxonomies and lots of bug fixes for tags and categories','sitepress'),
    '1.7.8' => __('Supports custom post types','sitepress'),
    '1.8.0' => __('Supports multilingual menus','sitepress'),
    '1.8.1' => __('Multilingual menus bug fixes and improved translation interface','sitepress'),
    '2.0.0' => __('New Translator role and full translation management workflow','sitepress')
);

$short_v = implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3));
if(!isset($upgrade_lines[$short_v])) return;

?>
<br clear="all" />
<div id="icl_update_message" class="updated message fade" style="clear:both;margin-top:5px;">
    <p><?php printf(__('New in WPML %s: <b>%s</b>', 'sitepress'), $short_v, $upgrade_lines[$short_v]); ?></p>
    <p>
        <a href="http://wpml.org/?cat=48"><?php _e('Learn more', 'sitepress')?></a>&nbsp;|&nbsp;
        <a title="<?php _e('Stop showing this message', 'sitepress') ?>" id="icl_dismiss_upgrade_notice" href="#"><?php _e('Dismiss', 'sitepress') ?></a>
    </p>
</div>
