<?php 

/**
 * Add settings link to plugin page.
*/
function icl_plugin_action_links($links, $file) {
    $this_plugin = basename(ICL_PLUGIN_PATH) . '/sitepress.php';
    global $sitepress_settings;
    if($file == $this_plugin) {
        $links[] = '<a href="admin.php?page='.basename(ICL_PLUGIN_PATH).'/menu/languages.php">' . __('Configure', 'sitepress') . '</a>';
    }
    return $links;
}
 

if(defined('ICL_DEBUG_MODE') && ICL_DEBUG_MODE && !function_exists('icl_error_handler')){           
    ini_set('error_reporting',E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 'on');
    function icl_error_handler($errno, $errstr, $errfile, $errline){        
        global $icl_errors_stack;        
        $err = '<strong>'.$errstr.'</strong> ('. $errno . ')<br />';
        $err .= 'File: <i>'.$errfile.'</i><br />';
        $err .= 'Line: <i>'.$errline.'</i><br />';
        $err .= '<hr style="line-height:1px;border:none;border-top:1px solid #fff;" />';
        $icl_errors_stack[] = $err;
        return true;
    }
    if(defined('WP_ADMIN')){
        add_action('admin_footer', 'icl_display_errors_stack');
    }else{
        add_action('wp_footer', 'icl_display_errors_stack');
    }
    
    function icl_display_errors_stack($onactivate = false){        
        global $icl_errors_stack, $EZSQL_ERROR;
        if(isset($icl_errors_stack) || $EZSQL_ERROR){
            echo '<div id="icl_display_errors_stack" style="font:11px Arial;background-color:pink;padding:10px;border:1px solid #f00;width:98%;top:0;background-color:rgba(255,192,203,0.95);max-height:500px;overflow:auto;z-index:1000;';
            if(!$onactivate){
                echo 'position:fixed;';
            }
            echo '">';
            if(!$onactivate){
                echo '<a style="float:right" href="#" onclick="try{jQuery(\'#icl_display_errors_stack\').slideUp()}catch(err){document.getElementById(\'icl_display_errors_stack\').style.display=\'none\'}">[close]</a><br clear="all" />';
            }
            foreach($icl_errors_stack as $ies){
                echo $ies;
            }

            if(isset($EZSQL_ERROR)){
                foreach($EZSQL_ERROR as $k=>$v){
                    echo $v['error_str'] . '<br />';
                    echo '<strong>Query</strong>: ' . $v['query'];
                    echo '<hr style="line-height:1px;border:none;border-top:1px solid #fff;" />';
                }
            }            
            echo '</div>';
            if($onactivate){
                $errc = count($EZSQL_ERROR)+count($icl_errors_stack);
                $frameheight = ( $errc * 70 < 550)?$errc * 70:550;
                echo '<script type="text/javascript">
                        parent.document.getElementById("message").style.maxHeight="550px";
                        chld = parent.document.getElementById("message").childNodes;
                        chld[2].setAttribute("height","'.$frameheight.'");                        
                    </script>';
                die();                
            }
        }
    }
    set_error_handler("icl_error_handler",E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

function icl_js_escape($str){
    $str = esc_js($str);
    $str = htmlspecialchars_decode($str);
    return $str;
}       
?>