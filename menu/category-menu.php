<?php $this->noscript_notice() ?>

<?php
    if ($referer = $_SERVER['HTTP_REFERER']) {
        if (false !== strpos($referer, 'action=edit')) {
            // we have come from another edit page and we don't want to
            // return there after the "Update"
            $ref = "categories.php";
            echo '<input type="hidden" name="_wp_original_http_referer" value="' . attribute_escape( stripslashes( $ref ) ) . '" />';
        }
    }
?>

<?php
    global $sitepress;
    $sitepress->add_language_selector_to_page($active_languages,
                                              $selected_language,
                                              $translations,
                                              $element_id,
                                              'tax_category');
    $sitepress->add_translation_of_selector_to_page($trid,
                                                 $selected_language,
                                                 $default_language,
                                                 $source_language,
                                                 $untranslated_ids,
                                                 $element_id,
                                                 'tax_category');
    $sitepress->add_translate_options($trid,
                                   $active_languages,
                                   $selected_language,
                                   $translations,
                                   'tax_category');

?>



</div>
</div>
</div>
</div>
</div>