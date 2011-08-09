<?php
    global $sitepress;
    
    if (isset($sitepress->settings['icl_show_reminders'])) {
        $show = $sitepress->settings['icl_show_reminders'];
    } else {
        $show = true;
    }

?>

<br clear="all" />
<div id="icl_reminder_message" class="updated message fade" style="clear:both;margin-top:5px;display:none; padding-bottom: 7px;margin-bottom:30px;">
    <table width="100%">
        <tr>
            <td><h4 style="margin-top:5px 0 5px 0; padding: 0;">ICanLocalize Reminders</h4></td>
            <td align="right"><a id="icl_reminder_show" href="#" style="text-align:right">
            <span id="icl_show_text"<?php if($show) { echo ' style="display:none"';}?>><?php _e('Show reminders', 'sitepress')?></span>
            <span<?php if(!$show) { echo ' style="display:none"';}?>><?php _e('Hide reminders', 'sitepress')?></span>
            </a></td>
        </tr>
    </table>
    <div id="icl_reminder_list"<?php if(!$show) { echo ' style="display:none"';}?>>
    </div>
</div>
