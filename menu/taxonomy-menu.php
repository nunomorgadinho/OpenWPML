<?php $this->noscript_notice() ?>
<?php
    global $sitepress;
    $sitepress->add_language_selector_to_page($active_languages,
                                              $selected_language,
                                              $translations,
                                              $element_id,
                                              $icl_element_type);
    $sitepress->add_translation_of_selector_to_page($trid,
                                                 $selected_language,
                                                 $default_language,
                                                 $source_language,
                                                 $untranslated_ids,
                                                 $element_id,
                                                 $icl_element_type);
    $sitepress->add_translate_options($trid,
                                   $active_languages,
                                   $selected_language,
                                   $translations,
                                   $icl_element_type);
    
?>

</div>
</div>
</div>
</div>
</div>
