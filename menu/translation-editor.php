<?php 
$job = $iclTranslationManagement->get_translation_job((int)$_GET['job_id'], false, true); // don't include not-translatable and auto-assign
if(empty($job)){
    $job_checked = true;
    include ICL_PLUGIN_PATH . '/menu/translations-queue.php';
    return;
}
?>
<div class="wrap icl-translation-editor">
    <div id="icon-options-general" class="icon32" 
        style="background: transparent url(<?php echo ICL_PLUGIN_URL ?>/res/img/icon.png) no-repeat"><br /></div>
    <h2><?php echo __('Translation editor', 'sitepress') ?></h2>    
    
    <?php do_action('icl_tm_messages'); ?>
    
    <p class="updated fade"><?php printf(__('You are translating %s from %s to %s.', 'sitepress'), 
        '<a href="'.get_edit_post_link($job->original_doc_id).'">' . esc_html($job->original_doc_title) . '</a>', $job->from_language, $job->to_language); ?></p>
    
    <form id="icl_tm_editor" method="post" action="">
    <input type="hidden" name="icl_tm_action" value="save_translation" />
    <input type="hidden" name="job_id" value="<?php echo $_GET['job_id'] ?>" />
    <div id="dashboard-widgets-wrap">
        <?php $icl_tm_all_finished = true; ?>
        <?php foreach($job->elements as $element): ?>    
        <?php 
            if(empty($element->field_data)) continue;
            $_iter = !isset($_iter) ? 1 : $_iter + 1; 
            if(!$element->field_finished){
                $icl_tm_all_finished = false;
            }
        ?>        
        <div class="metabox-holder" id="icl-tranlstion-job-elements-<?php echo $_iter ?>">
            <div class="postbox-container icl-tj-postbox-container-<?php echo $element->field_type ?>">
                <div class="meta-box-sortables ui-sortable" id="icl-tranlstion-job-sortables-<?php echo $_iter ?>">
                    <div class="postbox" id="icl-tranlstion-job-element-<?php echo $_iter ?>">
                        <div title="<?php _e('Click to toggle', 'sitepress')?>" class="handlediv">
                            <br />
                        </div>
                        <h3 class="hndle"><?php echo $element->field_type  ?></h3>
                        <div class="inside">
                            <?php /* TRANSLATED CONTENT */ ?>
                            <?php 
                                $icl_tm_original_content = TranslationManagement::decode_field_data($element->field_data, $element->field_format);
                                $icl_tm_translated_content = TranslationManagement::decode_field_data($element->field_data_translated, $element->field_format);
                                if($element->field_type=='tags' || $element->field_type=='categories'){
                                    $taxonomy = $element->field_type == 'tags' ? 'post_tag' : 'category';
                                    $icl_tm_translated_taxs[$element->field_type] = 
                                        TranslationManagement::determine_translated_taxonomies($icl_tm_original_content, $taxonomy, $job->language_code);
                                }
                                if(in_array($element->field_type, $sitepress->get_translatable_taxonomies(false, $job->original_post_type))){
                                    $taxonomy = $element->field_type;
                                    $icl_tm_translated_taxs[$element->field_type] = 
                                        TranslationManagement::determine_translated_taxonomies($icl_tm_original_content, $taxonomy, $job->language_code);
                                };
                            ?>
                            <p><?php _e('Translated content', 'sitepress'); echo ' - ' . $job->to_language; ?></p>
                            <?php if($element->field_type=='body'): ?>
                            <div id="poststuff">
                            <?php the_editor($icl_tm_translated_content, 'fields['.$element->field_type.'][data]', false, false); ?>
                            </div>
                            <?php elseif($element->field_format == 'csv_base64'): ?>
                            <?php foreach($icl_tm_original_content as $k=>$c): ?>
                            <?php 
                                if(empty($icl_tm_translated_content[$k]) && !empty($icl_tm_translated_taxs[$element->field_type][$k])){
                                    $icl_tm_translated_content[$k] = $icl_tm_translated_taxs[$element->field_type][$k];    
                                    $icl_tm_f_translated = true;
                                }else{
                                    $icl_tm_f_translated = false;
                                }
                            ?>
                            <label><input class="icl_multiple" type="text" name="fields[<?php echo $element->field_type ?>][data][<?php echo $k ?>]" value="<?php echo esc_attr($icl_tm_translated_content[$k]); ?>" /></label>
                            <?php if($icl_tm_f_translated): ?>
                            <div class="icl_tm_tf"><?php _e('Translated field', 'sitepress'); ?></div>
                            <?php endif; ?>
                            <?php endforeach;?>
                            <?php else: ?>
                            <label><input type="text" name="fields[<?php echo $element->field_type ?>][data]" value="<?php 
                                echo esc_attr($icl_tm_translated_content); ?>" /></label>
                            <?php endif; ?>                                
                            <p><label><input class="icl_tm_finished<?php if($element->field_format == 'csv_base64'): ?> icl_tmf_multiple<?php endif;
                                ?>" type="checkbox" name="fields[<?php echo $element->field_type ?>][finished]" value="1" <?php 
                                if($element->field_finished): ?>checked="checked"<?php endif;?> />&nbsp;<?php 
                                _e('This translation is finished.', 'sitepress')?></label></p>                            
                            <br />                                                            
                            <?php /* TRANSLATED CONTENT */ ?>
                            
                            <?php /* ORIGINAL CONTENT */ ?>
                            <p><?php _e('Original content', 'sitepress'); echo ' - ' . $job->from_language; ?></p>
                            <?php                                 
                                if($element->field_type=='body'){
                                    $icl_tm_original_content_html = esc_html($icl_tm_original_content);
                                    $icl_tm_original_content = apply_filters('the_content', $icl_tm_original_content);
                                    ?>
                                    <div id="icl_tm_orig_toggle">
                                        <a id="icl_tm_toggle_html" href="#"><?php _e('HTML', 'sitepress') ?></a><a id="icl_tm_toggle_visual" class="active" href="#"><?php _e('Visual', 'sitepress') ?></a>
                                        <br clear="all">
                                    </div>
                                    <?php
                                }
                            ?>
                            <div class="icl-tj-original">                                
                                <?php if($element->field_type=='body'): ?>
                                <div class="icl_single visual"><?php echo $icl_tm_original_content ?><br clear="all"/></div>
                                <div class="html"><textarea readonly="readonly"><?php echo $icl_tm_original_content_html ?></textarea></div>
                                <?php elseif($element->field_format == 'csv_base64'): ?>
                                <?php foreach($icl_tm_original_content as $c): ?>
                                <div class="icl_multiple"><?php echo $c ?></div>
                                <?php endforeach;?>
                                <?php else: ?>
                                <div class="icl_single"><?php echo esc_html($icl_tm_original_content) ?><br clear="all"/></div>
                                <?php endif; ?>
                            </div>
                            <?php /* ORIGINAL CONTENT */ ?>
                            
                            <input type="hidden" name="fields[<?php echo $element->field_type ?>][format]" value="<?php echo $element->field_format ?>" />
                            <input type="hidden" name="fields[<?php echo $element->field_type ?>][tid]" value="<?php echo $element->tid ?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <br clear="all" />
    <label><input type="checkbox" name="complete" <?php if(!$icl_tm_all_finished): ?>disabled="disabled"<?php endif; ?> <?php 
    if($job->translated):?> checked="checked"<?php endif; ?> value="1" />&nbsp;<?php 
        _e('Translation of this document is complete', 'sitepress')?></label>
    
    <div id="icl_tm_validation_error" class="icl_error_text"><?php _e('Please review the document translation and fill in all the required fields.', 'sitepress') ?></div>
    <p class="submit-buttons">
        <input type="submit" class="button-primary" value="<?php _e('Save translation', 'sitepress')?>" />&nbsp;
        <?php
        if (isset($_POST['complete']) && $_POST['complete']) {
            $cancel_txt = __('Jobs queue', 'sitepress');
        } else {
            $cancel_txt = __('Cancel', 'sitepress');
        }
        ?>
        <a class="button-secondary" href="<?php echo admin_url('admin.php?page='.ICL_PLUGIN_FOLDER.'/menu/translations-queue.php') ?>"><?php echo $cancel_txt; ?></a>
        <input type="submit" id="icl_tm_resign" class="button-secondary" value="<?php _e('Resign', 'sitepress')?>" onclick="if(confirm('<?php echo esc_js(__('Are you sure you want to resign from this job?', 'sitepress')) ?>')) jQuery(this).next().val(1); else return false;" /><input type="hidden" name="resign" value="0" />
    </p>
    </form>
        
</div>