<?php 

    if(!isset($job_checked) && isset($_GET['job_id']) && $_GET['job_id'] > 0){
        include ICL_PLUGIN_PATH . '/menu/translation-editor.php';
        return;
    }
    if(!empty($_GET['resigned'])){
        $iclTranslationManagement->messages[] = array('type'=>'updated', 'text'=>__("You've resigned from this job.", 'sitepress'));    
    }
    if(isset($_SESSION['translation_ujobs_filter'])){
        $icl_translation_filter = $_SESSION['translation_ujobs_filter'];
    }        
    $current_translator = $iclTranslationManagement->get_current_translator();
    $icl_translation_filter['translator_id'] = $current_translator->translator_id;
    $icl_translation_filter['include_unassigned'] = true;
    
    if(!empty($current_translator->language_pairs)){
        if(1 < count($current_translator->language_pairs)){
            foreach($current_translator->language_pairs as $lang=>$to){
                $langs_from[] = $sitepress->get_language_details($lang);
                $_langs_to = array_merge((array)$_langs_to, array_keys($to));                                                
            }
            $_langs_to = array_unique($_langs_to);
        }else{        
            $_langs_to = array_keys(current($current_translator->language_pairs));
            $lang_from = $sitepress->get_language_details(key($current_translator->language_pairs));         
            $icl_translation_filter['from'] = $lang_from['code'];
        }

        if(1 < count($_langs_to)){
            foreach($_langs_to as $lang){
                $langs_to[] = $sitepress->get_language_details($lang);
            }
        }else{
            $lang_to  = $sitepress->get_language_details(current($_langs_to));             
            $icl_translation_filter['to'] = $lang_to['code'];
        }
        
        $icl_translation_filter['limit_no'] = 20;
        $translation_jobs = $iclTranslationManagement->get_translation_jobs((array)$icl_translation_filter);    
    }
        
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32" style="background: transparent url(<?php echo ICL_PLUGIN_URL ?>/res/img/icon.png) no-repeat"><br /></div>
    <h2><?php echo __('Translations queue', 'sitepress') ?></h2>    
    
    <?php if(empty($current_translator->language_pairs)): ?>
    <div class="error below-h2"><p><?php _e("No translation languages configured for this user.", 'sitepress'); ?></p></div>
    <?php endif; ?>
    <?php do_action('icl_tm_messages'); ?>
    
    
    <?php if(!empty($current_translator->language_pairs)): ?>
    <form method="post" name="translation-jobs-filter" action="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translations-queue.php">
    <input type="hidden" name="icl_tm_action" value="ujobs_filter" />
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
                        <strong><?php _e('Status', 'sitepress')?></strong>&nbsp;
                        <select name="filter[status]">
                            <option value=""><?php _e('All', 'sitepress')?></option>
                            <option value="<?php echo ICL_TM_COMPLETE ?>" <?php 
                                if($icl_translation_filter['status']==ICL_TM_COMPLETE):?>selected="selected"<?php endif ;?>><?php 
                                    echo $iclTranslationManagement->status2text(ICL_TM_COMPLETE); ?></option>                                                            
                            <option value="<?php echo ICL_TM_IN_PROGRESS ?>" <?php 
                                if($icl_translation_filter['status']==ICL_TM_IN_PROGRESS):?>selected="selected"<?php endif ;?>><?php 
                                    echo $iclTranslationManagement->status2text(ICL_TM_IN_PROGRESS); ?></option>
                            <option value="<?php echo ICL_TM_WAITING_FOR_TRANSLATOR ?>" <?php 
                                if(strlen($icl_translation_filter['status']) 
                                    && $icl_translation_filter['status']== ICL_TM_WAITING_FOR_TRANSLATOR):?>selected="selected"<?php endif ;?>><?php 
                                    _e('Available to translate', 'sitepress') ?></option>                                    
                        </select>
                    </label>&nbsp;
                    <label>
                        <strong><?php _e('From', 'sitepress');?></strong>
                            <?php if(1 < count($current_translator->language_pairs)): ?>
                            <select name="filter[from]">   
                                <option value=""><?php _e('Any language', 'sitepress')?></option>
                                <?php foreach($langs_from as $lang):?>
                                <option value="<?php echo $lang['code']?>" <?php 
                                if($icl_translation_filter['from']==$lang['code']):?>selected="selected"<?php endif ;?>><?php echo $lang['display_name']?></option>
                                <?php endforeach; ?>
                            </select>                            
                            <?php else: ?>
                            <input type="hidden" name="filter[from]" value="<?php echo $lang_from['code'] ?>" />   
                            <?php echo $lang_from['display_name']; ?>                            
                            <?php endif; ?>
                    </label>&nbsp;        
                    <label>
                        <strong><?php _e('To', 'sitepress');?></strong>
                            <?php if(1 < count($langs_to)): ?>
                            <select name="filter[to]">   
                                <option value=""><?php _e('Any language', 'sitepress')?></option>
                                <?php foreach($langs_to as $lang):?>
                                <option value="<?php echo $lang['code']?>" <?php 
                                if($icl_translation_filter['to']==$lang['code']):?>selected="selected"<?php endif ;?>><?php echo $lang['display_name']?></option>
                                <?php endforeach; ?>
                            </select>            
                            <?php else: ?>
                            <input type="hidden" name="filter[to]" value="<?php echo $lang_to['code'] ?>" />   
                            <?php echo $lang_to['display_name']; ?>
                            <?php endif; ?>
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
                <th scope="col" class="manage-column column-date">&nbsp;</th>
                <th scope="col" class="manage-column column-date" style="width:14px;">&nbsp;</th>
                <th scope="col" class="manage-column"><?php _e('Status', 'sitepress')?></th>                
                <th scope="col" class="manage-column column-date">&nbsp;</th>                
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th scope="col"><?php _e('Title', 'sitepress')?></th>
                <th scope="col"><?php _e('Language', 'sitepress')?></th>
                <th scope="col">&nbsp;</th>
                <th scope="col">&nbsp;</th>
                <th scope="col"><?php _e('Status', 'sitepress')?></th>
                <th scope="col" class="manage-column column-date">&nbsp;</th>                
            </tr>
        </tfoot>    
        <tbody>
            <?php if(empty($translation_jobs)):?>
            <tr>
                <td colspan="6" align="center"><?php _e('No translation jobs found', 'sitepress')?></td>
            </tr>
            <?php else: foreach($translation_jobs as $job):?>
            <tr>
                <td><a href="<?php echo $job->edit_link ?>"><?php echo $job->post_title ?></a></td>
                <td><?php echo $job->lang_text ?></td>
                <td><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/translations-queue.php&job_id=<?php echo $job->job_id ?>"><?php _e('edit', 'sitepress'); ?></td>
                <td><?php if($job->translator_id && $job->status == ICL_TM_WAITING_FOR_TRANSLATOR): ?><div class="icl_tj_your_job" title="<?php echo esc_html(__('This job is assigned specifically to you.','sitepress')) ?>">!</div><?php endif; ?></td>
                <td><?php 
                    echo $iclTranslationManagement->status2text($job->status);
                    if($job->needs_update) _e(' - (needs update)', 'sitepress');
                ?></td>
                <td align="right">
                    <?php if($job->translator_id > 0 && ($job->status == ICL_TM_WAITING_FOR_TRANSLATOR || $job->status == ICL_TM_IN_PROGRESS)): ?>
                    <a href="<?php echo admin_url('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/translations-queue.php&icl_tm_action=save_translation&resign=1&job_id='.$job->job_id) ?>" onclick="if(!confirm('<?php echo esc_js(__('Are you sure you want to resign from this job?', 'sitepress')) ?>')) return false;"><?php _e('Resign', 'sitepress')?></a>
                    <?php else: ?>
                    &nbsp;
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
    
    <?php endif; ?>
    
</div>
