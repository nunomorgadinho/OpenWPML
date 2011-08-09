<?php

class TM_Notification{
    
    
    
    function __construct(){
        
    }
    
    function footer(){
    }
    
    function new_job_any($job_id){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);
        $translators = $iclTranslationManagement->get_blog_translators(array('to'=>$job->language_code));
        $edit_url = get_option('siteurl') . '/wp-admin/admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translations-queue.php&job_id=' . $job_id;
        $tj_url   = get_option('siteurl') . '/wp-admin/admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translations-queue.php';
        
        foreach($translators as $t){
            
            if($job->manager_id == $t->ID) return;
            
            // get current user admin language
            $user_language = $sitepress->get_user_admin_language($t->ID);
            if(empty($user_language)){
                $user_language = $sitepress->get_admin_language();
            }
            $lang_from = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
                $job->source_language_code, $user_language));
            $lang_to = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
                $job->language_code, $user_language));

            // hack current user language            
            //add_filter('icl_set_current_language', array($this, '_hj_lang'));
            //$mail['to'] = $t->display_name . ' <' . $t->user_email . '>';
            $mail['to'] = $t->user_email;
            $mail['subject'] = sprintf(__('New translation job from %s', 'sitepress'), get_bloginfo('name'));
            $mail['body'] = sprintf(__("New job available from %s to %s.\n\nStart editing: %s\nYou can view your other translation jobs here: %s", 'sitepress'),
                $lang_from, $lang_to, $edit_url, $tj_url);            
            $mail['type'] = 'translator';
                
            $this->send_mail($mail);
        }
    }
    
    //function _hj_lang($lang){
    //    return 'fr';
    //}
    
    function new_job_translator($job_id, $translator_id){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);
        
        if($job->manager_id == $job->translator_id) return;
        
        $edit_url = get_option('siteurl') . '/wp-admin/admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translations-queue.php&job_id=' . $job_id;
        $tj_url   = get_option('siteurl') . '/wp-admin/admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translations-queue.php';
        
        $user = new WP_User($translator_id);
        
        // get current user admin language
        $user_language = $sitepress->get_user_admin_language($user->ID);
        if(empty($user_language)){
            $user_language = $sitepress->get_admin_language();
        }
        $lang_from = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->source_language_code, $user_language));
        $lang_to = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->language_code, $user_language));

        // hack current user language            
        //add_filter('icl_set_current_language', array($this, '_hj_lang'));
        //$mail['to'] = $user->display_name . ' <' . $user->user_email . '>';
        $mail['to'] = $user->user_email;
        $mail['subject'] = sprintf(__('New translation job from %s', 'sitepress'), get_bloginfo('name'));
        $mail['body'] = sprintf(__("You have been assigned to new translation job from %s to %s.\n\nStart editing: %s\nYou can view your other translation jobs here: %s", 'sitepress'),
            $lang_from, $lang_to, $edit_url, $tj_url);            
        $mail['type'] = 'translator';
                   
        $this->send_mail($mail);
    }
    
    function work_complete($job_id, $update = false){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);    
        if($job->manager_id == $job->translator_id) return;
        $manager = new WP_User($job->manager_id);
        $translator = new WP_User($job->translator_id);

        // get current user admin language
        $user_language = $sitepress->get_user_admin_language($manager->ID);
        if(empty($user_language)){
            $user_language = $sitepress->get_admin_language();
        }
        $lang_from = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->source_language_code, $user_language));
        $lang_to = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->language_code, $user_language));
        
        $tj_url = get_option('siteurl') . '/wp-admin/admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translation-management.php&sm=jobs';
        $doc_url = get_edit_post_link($job->original_doc_id);
        
        if($iclTranslationManagement->settings['notification']['completed'] == ICL_TM_NOTIFICATION_IMMEDIATELY){
            $mail['to'] = $manager->user_email;
            if($update){
                $mail['subject'] = sprintf(__('Translator has updated translation job for %s', 'sitepress'), get_bloginfo('name'));
                $mail['body'] = sprintf(__("Translator (%s) has updated translation of job \"%s\" for %s to %s.\n%s\n\nView translation jobs: %s", 'sitepress'),
                    $translator->display_name, $job->original_doc_title, $lang_from, $lang_to, $doc_url, $tj_url);            
            }else{
                $mail['subject'] = sprintf(__('Translator has completed translation job for %s', 'sitepress'), get_bloginfo('name'));
                $mail['body'] = sprintf(__("Translator (%s) has completed translation of job \"%s\" for %s to %s.\n%s\n\nView translation jobs: %s", 'sitepress'),
                    $translator->display_name, $job->original_doc_title, $lang_from, $lang_to, $doc_url, $tj_url);            
            }
            $mail['type'] = 'admin';
            $this->send_mail($mail);
        }
        
        
    }
    
    function translator_resigned($translator_id, $job_id){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);    
        if($job->manager_id == $translator_id) return;
        $translator = new WP_User($translator_id);
        $manager = new WP_User($job->manager_id);
        
        $tj_url = get_option('siteurl') . '/wp-admin/admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translation-management.php&sm=jobs';
        
        // get current user admin language
        $user_language = $sitepress->get_user_admin_language($manager->ID);
        if(empty($user_language)){
            $user_language = $sitepress->get_admin_language();
        }
        $lang_from = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->source_language_code, $user_language));
        $lang_to = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='%s' AND display_language_code='%s'",
            $job->language_code, $user_language));        
        
        if($iclTranslationManagement->settings['notification']['resigned'] == ICL_TM_NOTIFICATION_IMMEDIATELY){
            $mail['to'] = $manager->user_email;
            $mail['subject'] = sprintf(__('Translator has resigned from job on %s', 'sitepress'), get_bloginfo('name'));
            $mail['body'] = sprintf(__('Translator %s has resigned from the translation job "%s" for %s to %s.%sView translation jobs: %s', 'sitepress'),
            $translator->display_name, $job->original_doc_title, $lang_from, $lang_to, "\n", $tj_url);            
            $mail['type'] = 'admin';
            
            $this->send_mail($mail);
        }
    }
    
    function translator_removed($translator_id, $job_id){
        global $iclTranslationManagement, $sitepress, $wpdb;
        $job = $iclTranslationManagement->get_translation_job($job_id);    
        if($job->manager_id == $translator_id) return;
        $translator = new WP_User($translator_id);
        $manager = new WP_User($job->manager_id);
        
        $tj_url   = get_option('siteurl') . '/wp-admin/admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/translations-queue.php';
        
        $mail['to'] = $manager->user_email;
        $mail['subject'] = sprintf(__('Removed from translation job on %s', 'sitepress'), get_bloginfo('name'));
        $mail['body'] = sprintf(__('You have been removed from the translation job "%s" for %s to %s.%sView translation jobs: %s', 'sitepress'),
        $job->original_doc_title, $lang_from, $lang_to, "\n",  $tj_url);            
        $mail['type'] = 'translator';
            
        $this->send_mail($mail);
    }
    
    function send_mail($mail){
        
        if($mail['type'] == 'translator'){
            $footer = sprintf(__("This message was automatically sent by Translation Management running on %s. To stop receiving these notifications contact the system administrator at %s.\n\nThis email is not monitored for replies."), get_bloginfo('name'), get_option('home'));        
        }else{
            $footer = sprintf(__("This message was automatically sent by Translation Management running on %s. To stop receiving these notifications, go to Notification Settings, or contact the system administrator at %s.\n\nThis email is not monitored for replies."), get_bloginfo('name'), get_option('home'));        
        }
        
        $mail['body'] .= "\n\n" . $footer . "\n\n" . sprintf(__('- The folks at ICanLocalize%s','sitepress'), "\n101 Convention Center Dr., Las Vegas, Nevada, 89109, USA");
        
        wp_mail($mail['to'], $mail['subject'], $mail['body']);
        
    }
}
 
?>