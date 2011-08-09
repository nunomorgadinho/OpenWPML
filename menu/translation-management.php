<?php 
    global $iclTranslationManagement;
    $selected_translator = $iclTranslationManagement->get_selected_translator();
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32" 
        style="background: transparent url(<?php echo ICL_PLUGIN_URL ?>/res/img/icon.png) no-repeat"><br /></div>
    <h2><?php echo __('Translation management', 'sitepress') ?></h2>    
    
    <?php do_action('icl_tm_messages'); ?>
    
    <a class="nav-tab <?php if(!isset($_GET['sm']) || (isset($_GET['sm']) && $_GET['sm']=='dashboard')): ?> nav-tab-active<?php endif;?>" 
        href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&sm=dashboard"><?php _e('Translation Dashboard', 'sitepress') ?></a>
    <?php if ( current_user_can('list_users') ): ?>
    <a class="nav-tab<?php if(isset($_GET['sm']) && $_GET['sm']=='translators'): ?> nav-tab-active<?php endif;?>" 
        href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&sm=translators"><?php _e('Translators', 'sitepress') ?></a> 
    <?php endif;  ?>        
    <a class="nav-tab <?php if(isset($_GET['sm']) && $_GET['sm']=='jobs'): ?> nav-tab-active<?php endif;?>" 
        href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&sm=jobs"><?php _e('Translation Jobs', 'sitepress') ?></a>
    <a class="nav-tab <?php if(isset($_GET['sm']) && $_GET['sm']=='mcsetup'): ?> nav-tab-active<?php endif;?>" 
        href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&sm=mcsetup"><?php _e('Multilingual Content Setup', 'sitepress') ?></a>
    <a class="nav-tab <?php if(isset($_GET['sm']) && $_GET['sm']=='notifications'): ?> nav-tab-active<?php endif;?>" 
        href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&sm=notifications"><?php _e('Translation Notifications', 'sitepress') ?></a>
    
    <div class="icl_tm_wrap">
    
    <?php 
        switch($_GET['sm']){
            case 'translators':
                include dirname(__FILE__) . '/translation-management/translators.php';
                break;
            case 'jobs':
                include dirname(__FILE__) . '/translation-management/jobs.php';
                break;
            case 'mcsetup':
                include dirname(__FILE__) . '/translation-management/mcsetup.php';
                break;
            case 'notifications':
                include dirname(__FILE__) . '/translation-management/notifications.php';
                break;
            default:
                include dirname(__FILE__) . '/translation-management/dashboard.php';
                
        }
    ?>
    
    </div>
    
    
</div>
