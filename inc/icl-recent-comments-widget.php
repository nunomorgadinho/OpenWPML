<?php
/**
 * Customized version of WP original recent comments widget
    It overrides the original
 */
function icl_wp_widget_recent_comments($args) {
    global $wpdb, $comments, $comment, $sitepress;
    extract($args, EXTR_SKIP);
    $options = get_option('widget_recent_comments');
    $title = empty($options['title']) ? __('Recent Comments', 'sitepress') : apply_filters('widget_title', $options['title']);
    if ( !$number = (int) $options['number'] )
        $number = 5;
    else if ( $number < 1 )
        $number = 1;
    else if ( $number > 15 )
        $number = 15;

    if ( !$comments = wp_cache_get( 'recent_comments-'.$sitepress->get_current_language(), 'widget' ) ) {
        $comments = $wpdb->get_results("
            SELECT * FROM {$wpdb->comments} c
                JOIN {$wpdb->prefix}icl_translations t ON c.comment_post_id = t.element_id AND t.element_type='post_post'
            WHERE comment_approved = '1' AND language_code = '".$sitepress->get_current_language()."'
            ORDER BY comment_date_gmt DESC LIMIT {$number}
        ");
        wp_cache_add( 'recent_comments-'.$sitepress->get_current_language(), $comments, 'widget' );
    }
?>

        <?php echo $before_widget; ?>
            <?php echo $before_title . $title . $after_title; ?>
            <ul id="recentcomments"><?php
            if ( $comments ) : foreach ( (array) $comments as $comment) :
            echo  '<li class="recentcomments">' . sprintf(__('%1$s on %2$s', 'sitepress'), get_comment_author_link(), '<a href="'. get_comment_link($comment->comment_ID) . '">' . get_the_title($comment->comment_post_ID) . '</a>') . '</li>';
            endforeach; endif;?></ul>
        <?php echo $after_widget; ?>
<?php
}

/**
 * Remove the cache for recent comments widget.
 *
 * @since 2.2.0
 */
function icl_wp_delete_recent_comments_cache() {
    global $sitepress;
    wp_cache_delete( 'recent_comments-'.$sitepress->get_current_language(), 'widget' );
}
add_action( 'comment_post', 'icl_wp_delete_recent_comments_cache' );
add_action( 'wp_set_comment_status', 'icl_wp_delete_recent_comments_cache' );

/**
 * Display and process recent comments widget options form.
 *
 * @since 2.2.0
 */
function icl_wp_widget_recent_comments_control() {
    $options = $newoptions = get_option('widget_recent_comments');
    if ( isset($_POST["recent-comments-submit"]) ) {
        $newoptions['title'] = strip_tags(stripslashes($_POST["recent-comments-title"]));
        $newoptions['number'] = (int) $_POST["recent-comments-number"];
    }
    if ( $options != $newoptions ) {
        $options = $newoptions;
        update_option('widget_recent_comments', $options);
        icl_wp_delete_recent_comments_cache();
    }
    $title = attribute_escape($options['title']);
    if ( !$number = (int) $options['number'] )
        $number = 5;
?>
            <p><label for="recent-comments-title"><?php _e('Title:','sitepress'); ?> <input class="widefat" id="recent-comments-title" name="recent-comments-title" type="text" value="<?php echo $title; ?>" /></label></p>
            <p>
                <label for="recent-comments-number"><?php _e('Number of comments to show:','sitepress'); ?> <input style="width: 25px; text-align: center;" id="recent-comments-number" name="recent-comments-number" type="text" value="<?php echo $number; ?>" /></label>
                <br />
                <small><?php _e('(at most 15)','sitepress'); ?></small>
            </p>
            <input type="hidden" id="recent-comments-submit" name="recent-comments-submit" value="1" />
<?php
}

/**
 * Display the style for recent comments widget.
 *
 * @since 2.2.0
 */
function icl_wp_widget_recent_comments_style() {
?>
<style type="text/css">.recentcomments a{display:inline !important;padding: 0 !important;margin: 0 !important;}</style>
<?php
}

/**
 * Register recent comments with control and hook for 'wp_head' action.
 *
 * @since 2.2.0
 */
function icl_wp_widget_recent_comments_register() {
    $widget_ops = array('classname' => 'widget_recent_comments', 'description' => __( 'The most recent comments', 'sitepress') );
    wp_register_sidebar_widget('recent-comments', __('Recent Comments', 'sitepress'), 'icl_wp_widget_recent_comments', $widget_ops);
    wp_register_widget_control('recent-comments', __('Recent Comments', 'sitepress'), 'icl_wp_widget_recent_comments_control');

    if ( is_active_widget('icl_wp_widget_recent_comments') )
        add_action('wp_head', 'icl_wp_widget_recent_comments_style');
}  

add_action('init', 'icl_wp_widget_recent_comments_register', 1);
?>
