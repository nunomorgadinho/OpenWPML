<?php //included from menu translation-management.php ?>
<?php
if(isset($_SESSION['translation_jobs_filter'])){
    $icl_translation_filter = $_SESSION['translation_jobs_filter'];
}
$icl_translation_filter['limit_no'] = 20;
$translation_jobs = $iclTranslationManagement->get_translation_jobs((array)$icl_translation_filter);
?>
<br />

<form method="post" name="translation-jobs-filter" action="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translation-management.php&amp;sm=jobs">
<input type="hidden" name="icl_tm_action" value="jobs_filter" />
<table class="form-table widefat fixed">
    <thead>
    <tr>
        <th scope="col"><strong><?php _e('Filter by','sitepress')?></strong></th>
    </tr>
    </thead> 
    <tbody>
        <tr valign="top">
            <td>
                <label>
                    <strong><?php _e('Translation jobs for:', 'sitepress')?>&nbsp;
                    <?php $iclTranslationManagement->translators_dropdown(array(
                        'name'          => 'filter[translator_id]',
                        'default_name'  => __('All', 'sitepress'),
                        'selected'      => $icl_translation_filter['translator_id'],
                        'services'      => array('local', 'icanlocalize')
                        )
                     ); ?>            
                </label>&nbsp;
                <label>
                    <strong><?php _e('Status', 'sitepress')?></strong>&nbsp;
                    <select name="filter[status]">
                        <option value=""><?php _e('All', 'sitepress')?></option>
                        <option value="<?php echo ICL_TM_WAITING_FOR_TRANSLATOR ?>" <?php 
                            if(strlen($icl_translation_filter['status']) 
                                && $icl_translation_filter['status']== ICL_TM_WAITING_FOR_TRANSLATOR):?>selected="selected"<?php endif ;?>><?php 
                                echo $iclTranslationManagement->status2text(ICL_TM_WAITING_FOR_TRANSLATOR); ?></option>
                        <option value="<?php echo ICL_TM_IN_PROGRESS ?>" <?php 
                            if($icl_translation_filter['status']==ICL_TM_IN_PROGRESS):?>selected="selected"<?php endif ;?>><?php 
                                echo $iclTranslationManagement->status2text(ICL_TM_IN_PROGRESS); ?></option>
                        <option value="<?php echo ICL_TM_COMPLETE ?>" <?php 
                            if($icl_translation_filter['status']==ICL_TM_COMPLETE):?>selected="selected"<?php endif ;?>><?php 
                                echo $iclTranslationManagement->status2text(ICL_TM_COMPLETE); ?></option>                                                            
                    </select>
                </label>&nbsp;
                <label>
                    <strong><?php _e('From', 'sitepress');?></strong>
                        <select name="filter[from]">   
                            <option value=""><?php _e('Any language', 'sitepress')?></option>
                            <?php foreach($sitepress->get_active_languages() as $lang):?>
                            <option value="<?php echo $lang['code']?>" <?php 
                            if($icl_translation_filter['from']==$lang['code']):?>selected="selected"<?php endif ;?>><?php echo $lang['display_name']?></option>
                            <?php endforeach; ?>
                        </select>
                </label>&nbsp;        
                <label>
                    <strong><?php _e('To', 'sitepress');?></strong>
                        <select name="filter[to]">   
                            <option value=""><?php _e('Any language', 'sitepress')?></option>
                            <?php foreach($sitepress->get_active_languages() as $lang):?>
                            <option value="<?php echo $lang['code']?>" <?php 
                            if($icl_translation_filter['to']==$lang['code']):?>selected="selected"<?php endif ;?>><?php echo $lang['display_name']?></option>
                            <?php endforeach; ?>
                        </select>            
                </label>                
                &nbsp;
                <input class="button-secondary" type="submit" value="<?php _e('Apply', ' sitepress')?>" />
            </td>
        </tr>
    </tbody>     
</table>
</form>

<br />

<table class="widefat fixed" id="icl-translation-jobs" cellspacing="0">
    <thead>
        <tr>
            <th scope="col"><?php _e('Title', 'sitepress')?></th>
            <th scope="col"><?php _e('Language', 'sitepress')?></th>            
            <th scope="col" class="manage-column" style="width:150px"><?php _e('Status', 'sitepress')?></th>
            <th scope="col" class="manage-column"><?php _e('Translator') ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th scope="col"><?php _e('Title', 'sitepress')?></th>
            <th scope="col"><?php _e('Language', 'sitepress')?></th>
            <th scope="col"><?php _e('Status', 'sitepress')?></th>
            <th scope="col" class="manage-column"><?php _e('Translator') ?></th>
        </tr>
    </tfoot>    
    <tbody>
        <?php if(empty($translation_jobs)):?>
        <tr>
            <td colspan="4" align="center"><?php _e('No translation jobs found', 'sitepress')?></td>
        </tr>
        <?php else: foreach($translation_jobs as $job):?>        
        <tr>
            <td><a href="<?php echo $job->edit_link ?>"><?php echo esc_html($job->post_title) ?></a></td>
            <td><?php echo $job->lang_text ?></td>            
            <td><span id="icl_tj_job_status_<?php echo $job->job_id ?>"><?php echo $iclTranslationManagement->status2text($job->status) ?></span>
                <?php if($job->needs_update) _e(' - (needs update)', 'sitepress'); ?>
            </td>
            <td>
                <?php if(!empty($job->translator_id) && $job->status != ICL_TM_WAITING_FOR_TRANSLATOR): ?>
                    <?php if($job->translation_service == 'icanlocalize'): ?>
                    <?php 
                    foreach($sitepress_settings['icl_lang_status'] as $lp){
                        if($lp['from'] == $job->source_language_code && $lp['to'] == $job->language_code){
                            $contract_id = $lp['contract_id'];
                            $lang_tr_id =  $lp['id']; 
                            break;
                        }
                    }
                    echo $sitepress->create_icl_popup_link(ICL_API_ENDPOINT . '/websites/' . $sitepress_settings['site_id']
                    . '/website_translation_offers/' . $lang_tr_id . '/website_translation_contracts/'
                    . $contract_id, array('title' => __('Chat with translator', 'sitepress'), 'unload_cb' => 'icl_thickbox_refresh')) . esc_html($job->translator_name)  . '</a> (ICanLocalize)';                    
                    ?>
                    <?php else: ?>
                    <a href="<?php echo $iclTranslationManagement->get_translator_edit_url($job->translator_id) ?>"><?php echo esc_html($job->translator_name) ?></a>
                    <?php endif;?>
                <?php else: ?>
                <span class="icl_tj_select_translator"><?php 
                    $iclTranslationManagement->translators_dropdown(
                        array(
                            'name'=>'icl_tj_translator_for_'.$job->job_id , 
                            'from'=>$job->source_language_code,
                            'to'=>$job->language_code, 
                            'selected'=>@intval($job->translator_id),
                            'services' => array('local', 'icanlocalize')
                        )
                    );
                    ?></span>
                <input type="hidden" id="icl_tj_ov_<?php echo $job->job_id ?>" value="<?php echo @intval($job->translator_id) ?>" />
                <span class="icl_tj_select_translator_controls" id="icl_tj_tc_<?php echo $job->job_id ?>">
                    <input type="button" class="button-secondary icl_tj_ok" value="<?php _e('Send', 'sitepress') ?>" />&nbsp;
                    <input type="button" class="button-secondary icl_tj_cancel" value="<?php _e('Cancel', 'sitepress') ?>" />
                </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; endif; ?>
    </tbody>    
</table>

    <?php 
    // pagination  
    $page_links = paginate_links( array(
        'base' => add_query_arg('paged', '%#%' ),
        'format' => '',
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'total' => $wp_query->max_num_pages,
        'current' => $_GET['paged'],
        'add_args' => isset($icl_translation_filter)?$icl_translation_filter:array() 
    ));         
    ?> 
    <div class="tablenav">    
        <?php if ( $page_links ) { ?>
        <div class="tablenav-pages"><?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', 'sitepress' ) . '</span>%s',
            number_format_i18n( ( $_GET['paged'] - 1 ) * $wp_query->query_vars['posts_per_page'] + 1 ),
            number_format_i18n( min( $_GET['paged'] * $wp_query->query_vars['posts_per_page'], $wp_query->found_posts ) ),
            number_format_i18n( $wp_query->found_posts ),
            $page_links
        ); echo $page_links_text; ?>
        </div>
        <?php } ?>
    </div>    
    <?php // pagination - end ?>
