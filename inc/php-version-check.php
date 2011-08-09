<?php
if(isset($_GET['icl_phpinfo']) && $_GET['icl_phpinfo']==1){
    add_action('init', 'icl_dump_phpinfo');
}

function icl_dump_phpinfo(){
    if(current_user_can('manage_options')){
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();
        echo $phpinfo;
        exit;    
    }
}

  if(version_compare(phpversion(), '5', '<')){
      add_action('admin_notices', 'icl_php_version_warn');
      add_action('admin_print_scripts', 'icl_php_version_warn_js');
      
      function icl_php_version_warn(){
          echo '<div class="error"><ul><li><strong>';
          echo __('WPML cannot be activated because your version of PHP is too old. To run correctly, you must have PHP5 installed.<br /> We recommend that you contact your hosting company and request them to switch you to PHP5.', 'sitepress');
          echo sprintf('<br />PHP reports version %s -  (<a href="#phpinfo">show detailed phpinfo</a>)',phpversion());
          echo '</strong></li></ul>';     
          echo '<div id="phpinfo_container"></div>';               
          echo '</div>';
      }
      
      $active_plugins = get_option('active_plugins');
      $icl_sitepress_idx = array_search(ICL_PLUGIN_FOLDER . '/sitepress.php', $active_plugins);
      if(false !== $icl_sitepress_idx){
          unset($active_plugins[$icl_sitepress_idx]);
          update_option('active_plugins', $active_plugins);
          unset($_GET['activate']);
          $recently_activated = get_option('recently_activated');
          if(!isset($recently_activated[ICL_PLUGIN_FOLDER . '/sitepress.php'])){
              $recently_activated[ICL_PLUGIN_FOLDER . '/sitepress.php'] = time();
              update_option('recently_activated', $recently_activated);
          }
      }  
      define('PHP_VERSION_INCOMPATIBLE', true);    
      
      function icl_php_version_warn_js(){
        ?>
        <script type="text/javascript">        
        addLoadEvent(function(){
            jQuery('a[href="#phpinfo"]').click(function(){                
                
                var pleft = (jQuery('body').width() - 700)/2;
                jQuery('#phpinfo_container').css('left', pleft+'px').css('top','30px');
                
                jQuery('#phpinfo_container').html('<div style="background-color:#fff;padding-right:10px;font-weight:bold;text-align:right;"><a href="#phpinfo-close"><?php echo __('Close', 'sitepress')?></a></div><iframe width="700" height="600" src="<?php echo ICL_PLUGIN_URL ?>/inc/php-version-check.php?icl_phpinfo=1">Loading...</iframe>')
                jQuery('a[href="#phpinfo-close"]').click(function(){
                    jQuery('#phpinfo_container').html('');
                });                
            });
        });        
        </script>        
        <style type="text/css">
        #phpinfo_container{
            position:absolute;
            top:10px;
            z-index:1000;
            border:1px solid #ccc;
            margin:0 auto;
        }
        </style>
        <?php          
      }
  }  
?>