<?php
class iclNavMenu{
    private $current_menu;
    private $current_lang;
    
    function __construct(){
        global $pagenow;
        
        add_action('init', array($this, 'init'));
                
        if(is_admin()){
            // hooks for saving menus    
            add_action('wp_create_nav_menu', array($this, 'wp_update_nav_menu'), 10, 2);
            add_action('wp_update_nav_menu', array($this, 'wp_update_nav_menu'), 10, 2);
        
            // hook for saving menu items
            add_action('wp_update_nav_menu_item', array($this, 'wp_update_nav_menu_item'), 10, 3);
            
            // filter for nav_menu_options
            add_filter('option_nav_menu_options', array($this, 'option_nav_menu_options'));
            
            add_action('wp_delete_nav_menu', array($this, 'wp_delete_nav_menu'));
            add_action('delete_post', array($this, 'wp_delete_nav_menu_item'));
                                    
        }
        
        // add language controls for menus no option but javascript
        if($pagenow == 'nav-menus.php'){
            add_action('admin_footer', array($this, 'nav_menu_language_controls'), 10);
            
            wp_enqueue_script('wp_nav_menus', ICL_PLUGIN_URL . '/res/js/wp-nav-menus.js', ICL_SITEPRESS_VERSION, true);    
            wp_enqueue_style('wp_nav_menus_css', ICL_PLUGIN_URL . '/res/css/wp-nav-menus.css', array(), ICL_SITEPRESS_VERSION,'all');    
            
            // filter posts by language
            add_action('parse_query', array($this, 'parse_query'));
            
            // filter taxonomies by language
            //add_action('get_terms', array($this, 'get_terms'));
            
            // filter menus by language
            add_filter('get_terms', array($this, 'get_terms_filter'), 1, 3);        
        }
        
        
        add_filter('theme_mod_nav_menu_locations', array($this, 'theme_mod_nav_menu_locations'));
        $theme = get_current_theme();
        add_filter('pre_update_option_mods_' . $theme, array($this, 'pre_update_theme_mods_theme'));
        
        add_filter('wp_nav_menu_args', array($this, 'wp_nav_menu_args_filter'));
        add_filter('wp_nav_menu_items', array($this, 'wp_nav_menu_items_filter'));
        
    }
    
    function init(){
        global $sitepress;
        
        if(is_admin()){
            $this->_set_menus_language();
        }
        
        $this->get_current_menu();
        
        if($this->current_menu['language']){
            $this->current_lang = $this->current_menu['language'];   
            //if($this->current_lang != $sitepress->get_default_language() && !isset($_GET['lang'])){
            //    wp_redirect(admin_url('nav-menus.php').'?lang='.$this->current_lang);
            //} 
        }elseif(isset($_REQUEST['lang'])){
            $this->current_lang = $_REQUEST['lang'];    
        }else{
            $this->current_lang = $sitepress->get_default_language();
        }

        if(isset($_POST['icl_wp_nav_menu_ajax'])){
            $this->ajax($_POST);
        }
        
        // for theme locations that are not translated into the curent language
        // reflect status in the theme location navigation switcher
        add_action('admin_footer', array($this, '_set_custom_status_in_theme_location_switcher'));
    }
    /**
    * associates menus without language information with default language
    * 
    */
    function _set_menus_language(){
        global $wpdb, $sitepress;
        $translated_menus = $wpdb->get_col("
            SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_nav_menu'
        ");
        $translated_menus[] = 0; //dummy
        $untranslated_menus = $wpdb->get_col("
            SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy='nav_menu' AND term_taxonomy_id NOT IN(".join(",",$translated_menus).")
        ");
        if(!empty($untranslated_menus)){
            foreach($untranslated_menus as $item){
                $sitepress->set_element_language_details($item, 'tax_nav_menu', null, $sitepress->get_default_language());
            }
        }
        
        $translated_menu_items = $wpdb->get_col("
            SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type='post_nav_menu_item'
        ");
        $translated_menu_items[] = 0; //dummy
        $untranslated_menu_items = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts} WHERE post_type='nav_menu_item' AND ID NOT IN(".join(",", $translated_menu_items).")
        ");
        if(!empty($untranslated_menu_items)){
            foreach($untranslated_menu_items as $item){
                $sitepress->set_element_language_details($item, 'post_nav_menu_item', null, $sitepress->get_default_language());
            }
        }
    }
    
    function ajax($data){
        if($data['icl_wp_nav_menu_ajax'] == 'translation_of'){
            $this->_render_translation_of($data['lang'], $data['trid']);
        }
        exit;
    }
    
    function _get_menu_language($menu_id){
        global $sitepress, $wpdb;
        $menu_tt_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'",$menu_id));
        $lang = $sitepress->get_element_language_details($menu_tt_id, 'tax_nav_menu');
        return $lang;
    }
    
    /**
    * gets first menu in a specific language
    * used to override nav_menu_recently_edited when a different language is selected
    * @param $lang
    * @return int
    */
    function _get_first_menu($lang){
        global $wpdb;
        $menu_tt_id = $wpdb->get_var("SELECT MIN(element_id) FROM {$wpdb->prefix}icl_translations WHERE element_type='tax_nav_menu' AND language_code='".$wpdb->escape($lang)."'");    
        $menu_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d",$menu_tt_id));
        return (int) $menu_id;
    }
    
    function get_current_menu(){
        global $sitepress;
        $nav_menu_recently_edited = get_user_option( 'nav_menu_recently_edited' );        
        $nav_menu_recently_edited_lang = $this->_get_menu_language($nav_menu_recently_edited);                
        if( !isset( $_REQUEST['menu'] ) && isset($_GET['lang']) && $nav_menu_recently_edited_lang->language_code != $_GET['lang']){            
            // if no menu is specified and the language is set override nav_menu_recently_edited
            $nav_menu_selected_id = $this->_get_first_menu($_GET['lang']);                            
            if($nav_menu_selected_id){
                update_user_option(get_current_user_id(), 'nav_menu_recently_edited', $nav_menu_selected_id);    
            }else{
                $_REQUEST['menu'] = 0;
            }
            
        }elseif( !isset( $_REQUEST['menu'] ) && !isset($_GET['lang']) && $nav_menu_recently_edited_lang->language_code != $sitepress->get_default_language() && $_POST['action']!='update'){
            // if no menu is specified, no language is set, override nav_menu_recently_edited if its language is different than default           
            $nav_menu_selected_id = $this->_get_first_menu($sitepress->get_default_language());    
            update_user_option(get_current_user_id(), 'nav_menu_recently_edited', $nav_menu_selected_id);
        }elseif(isset( $_REQUEST['menu'] )){
            $nav_menu_selected_id = $_REQUEST['menu'];
        }else{
            $nav_menu_selected_id = $nav_menu_recently_edited;
        }
        
        $this->current_menu['id'] = $nav_menu_selected_id;        
        if($this->current_menu['id']){
            $this->_load_menu($this->current_menu['id']);
        }else{
            $this->current_menu['trid'] = isset($_GET['trid']) ? intval($_GET['trid']) : null;
            if(isset($_POST['icl_nav_menu_language'])){
                $this->current_menu['language'] = $_POST['icl_nav_menu_language'];    
            }elseif(isset($_GET['lang'])){
                $this->current_menu['language'] = $_GET['lang'];    
            }else{
                $this->current_menu['language'] = $sitepress->get_default_language();   
            }            
            $this->current_menu['translations'] = array();
        }
    }
    
    function _load_menu($menu_id){
        global $sitepress, $wpdb;
        
        $menu_tt_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'",$this->current_menu['id']));        
        $this->current_menu['trid'] = $sitepress->get_element_trid($menu_tt_id, 'tax_nav_menu');        
        
        if($this->current_menu['trid']){
            $this->current_menu['translations'] = $sitepress->get_element_translations($this->current_menu['trid'], 'tax_nav_menu');    
        }else{
            $this->current_menu['translations'] = array();
        }
        
        foreach($this->current_menu['translations'] as $tr){
            if($menu_tt_id == $tr->element_id){
                $this->current_menu['language'] = $tr->language_code;                    
            }
        }
    }
    
    function wp_update_nav_menu($menu_id, $menu_data = null){
        global $sitepress, $wpdb;
        if($menu_data){
            if($_POST['icl_translation_of']){
                $trid = $sitepress->get_element_trid($_POST['icl_translation_of'], 'tax_nav_menu');
            }else{
                $trid = isset($_POST['icl_nav_menu_trid']) ? intval($_POST['icl_nav_menu_trid']) : null;                 
            }        
            $language_code = isset($_POST['icl_nav_menu_language']) ? $_POST['icl_nav_menu_language'] : $sitepress->get_default_language(); 
            $menu_id_tt = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'",$menu_id));
            $sitepress->set_element_language_details($menu_id_tt, 'tax_nav_menu', $trid, $language_code);
        }
        $this->current_menu['id'] = $menu_id;
        $this->_load_menu($this->current_menu['id']);
    }
    
    function wp_delete_nav_menu($id){
        global $wpdb;
        $menu_id_tt = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'",$id));
        $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE element_id='{$menu_id_tt}' AND element_type='tax_nav_menu' LIMIT 1");
    }
    
    function wp_update_nav_menu_item($menu_id, $menu_item_db_id, $args){
        // TBD
        // TBD
        global $sitepress;
        
        // deal with the case of auto-added pages
        /*
        if(isset($_POST['icl_post_language'])){
            $menu_language = $this->_get_menu_language($menu_id);
            if($menu_language != $_POST['icl_post_language']){
                _wp_delete_post_menu_item($menu_id);
                return;
            }
        }
        */        
        $trid = null;
        $language_code = $this->current_lang;
        $sitepress->set_element_language_details($menu_item_db_id, 'post_nav_menu_item', $trid, $language_code);
    }
    
    function wp_delete_nav_menu_item($menu_item_id){
        global $wpdb;
        $post = get_post($menu_item_id);
        if($post->post_type == 'nav_menu_item'){
            $wpdb->query("DELETE FROM {$wpdb->prefix}icl_translations WHERE element_id='{$menu_item_id}' AND element_type='post_nav_menu_item' LIMIT 1");
        }        
    }
    
    function nav_menu_language_controls(){
        global $sitepress, $wpdb;
        if($this->current_menu['language'] != $sitepress->get_default_language()){
            $menus_wout_translation = $this->get_menus_without_translation($this->current_menu['language']);    
        }
        if(isset($this->current_menu['translations'][$sitepress->get_default_language()])){
            $menus_wout_translation['0'] = (object)array(
                'element_id'=>$this->current_menu['translations'][$sitepress->get_default_language()]->element_id,
                'trid'      =>'0',
                'name'      =>$this->current_menu['translations'][$sitepress->get_default_language()]->name
                );
        }
        
        $langsel = '<br class="clear" />';    
        
        // show translations links if this is not a new element              
        if($this->current_menu['id']){
            $langsel .= '<div class="howto icl_nav_menu_text" style="float:right;">';    
            $langsel .= __('Translations:', 'sitepress');    
            foreach($sitepress->get_active_languages() as $lang){            
                if($lang['code'] == $this->current_menu['language']) continue;
                if(isset($this->current_menu['translations'][$lang['code']])){
                    $lang_suff = $lang['code'] != $sitepress->get_default_language() ? '&lang=' . $lang['code'] :  '';
                    $menu_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id=%d",$this->current_menu['translations'][$lang['code']]->element_id));
                    $tr_link = '<a style="text-decoration:none" title="'. esc_attr(__('edit translation', 'sitepress')).'" href="'.admin_url('nav-menus.php').
                        '?menu='.$menu_id. $lang_suff .'">'.
                        $lang['display_name'] . '&nbsp;<img src="'.ICL_PLUGIN_URL.'/res/img/edit_translation.png" alt="'. esc_attr(__('edit', 'sitepress')).
                        '" width="12" height="12" /></a>';
                }else{
                    $tr_link = '<a style="text-decoration:none" title="'. esc_attr(__('add translation', 'sitepress')).'" href="'.admin_url('nav-menus.php').
                        '?action=edit&menu=0&trid='.$this->current_menu['trid'].'&lang='.$lang['code'].'">'. 
                        $lang['display_name'] . '&nbsp;<img src="'.ICL_PLUGIN_URL.'/res/img/add_translation.png" alt="'. esc_attr(__('add', 'sitepress')).
                        '" width="12" height="12" /></a>';
                }
                $trs[] = $tr_link ;
            }
            $langsel .= '&nbsp;' . join (', ', $trs);
            $langsel .= '</div>';    
        }
        
        // show languages dropdown                
        $langsel .= '<label class="menu-name-label howto"><span>' . __('Language', 'sitepress') . '</span>';
        $langsel .= '&nbsp;&nbsp;';    
        $langsel .= '<select name="icl_nav_menu_language" id="icl_menu_language">';    
        foreach($sitepress->get_active_languages() as $lang){
            if(isset($this->current_menu['translations'][$lang['code']]) && $this->current_menu['language'] != $lang['code']) continue;            
            $selected = $lang['code'] == $this->current_menu['language'] ? ' selected="selected"' : '';
            $langsel .= '<option value="' . $lang['code'] . '"' . $selected . '>' . $lang['display_name'] . '</option>';    
        }
        $langsel .= '</select>';
        $langsel .= '</label>';  
        
        // show 'translation of' if this element is not in the default language and there are untranslated elements
        $langsel .= '<span id="icl_translation_of_wrap">';
        if($this->current_menu['language'] != $sitepress->get_default_language() && !empty($menus_wout_translation)){
            $langsel .= '<label class="menu-name-label howto"><span>' . __('Translation of:', 'sitepress') . '</span>';                
            if(!$this->current_menu['id'] && isset($_GET['trid'])){
                $disabled = ' disabled="disabled"';
            }else{
                $disabled = '';
            }
            $langsel .= '<select name="icl_translation_of" id="icl_menu_translation_of"'.$disabled.'>';    
            $langsel .= '<option value="">--' . __('none', 'sitepress') . '--</option>';                
            foreach($menus_wout_translation as $mtrid=>$m){
                if($this->current_menu['trid'] === $mtrid || $this->current_menu['translations'][$sitepress->get_default_language()]->element_id){
                    $selected = ' selected="selected"';
                }else{
                    $selected = '';
                }
                $langsel .= '<option value="' . $m->element_id . '"' . $selected . '>' . $m->name . '</option>';    
            }
            $langsel .= '</select>';
            $langsel .= '</label>';
        }
        $langsel .= '</span>';
        
        // add trid to form
        if($this->current_menu['trid']){
            $langsel .= '<input type="hidden" id="icl_nav_menu_trid" name="icl_nav_menu_trid" value="' . $this->current_menu['trid'] . '" />';
        }
        
        $langsel .= '';
        ?>
        <script type="text/javascript">
        addLoadEvent(function(){
            jQuery('#update-nav-menu .publishing-action').before('<?php echo addslashes($langsel); ?>');
            jQuery('#side-sortables').before('<?php $this->languages_menu() ?>');
            <?php if($this->current_lang != $sitepress->get_default_language()): echo "\n"; ?>
            jQuery('.nav-tabs .nav-tab').each(function(){
                jQuery(this).attr('href', jQuery(this).attr('href')+'&lang=<?php echo $this->current_lang ?>');
            });        
            jQuery('#update-nav-menu').attr('ACTION', jQuery('#update-nav-menu').attr('ACTION')+'?lang=<?php echo $this->current_lang ?>');            
            <?php endif; ?>            
        });
        </script>
        <?php            
    }
    
    function get_menus_without_translation($lang){
        global $sitepress, $wpdb;
        $res = $wpdb->get_results("
            SELECT ts.element_id, ts.trid, t.name 
            FROM {$wpdb->prefix}icl_translations ts
            JOIN {$wpdb->term_taxonomy} tx ON ts.element_id = tx.term_taxonomy_id
            JOIN {$wpdb->terms} t ON tx.term_id = t.term_id
            WHERE ts.element_type='tax_nav_menu' 
                AND ts.language_code='{$sitepress->get_default_language()}'
                AND tx.taxonomy = 'nav_menu'
        ");
        $menus = array();
        foreach($res as $row){            
            if(!$wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE trid='{$row->trid}' AND language_code='{$lang}'")){
                $menus[$row->trid] = $row;
            }
        }       
        return $menus;
    }
    
    function _render_translation_of($lang, $trid = false){
        global $sitepress;
        $out = '';
        
        if($sitepress->get_default_language() != $lang){
            $menus = $this->get_menus_without_translation($lang);        
            $out .= '<label class="menu-name-label howto"><span>' . __('Translation of:', 'sitepress') . '</span>';                
            $out .= '<select name="icl_translation_of" id="icl_menu_translation_of">';    
            $out .= '<option value="">--' . __('none', 'sitepress') . '--</option>';                
            foreach($menus as $mtrid=>$m){
                if(intval($trid) === $mtrid){
                    $selected = ' selected="selected"';
                }else{
                    $selected = '';
                }
                $out .= '<option value="' . $m->element_id . '"' . $selected . '>' . $m->name . '</option>';    
            }
            $out .= '</select>';
            $out .= '</label>';
        }
                
        echo $out;
    }
    
    function get_menus_by_language(){
        global $wpdb, $sitepress;
        $res = $wpdb->get_results("
            SELECT lt.name AS language_name, l.code AS lang, COUNT(ts.translation_id) AS c
            FROM {$wpdb->prefix}icl_languages l
                JOIN {$wpdb->prefix}icl_languages_translations lt ON lt.language_code = l.code
                JOIN {$wpdb->prefix}icl_translations ts ON l.code = ts.language_code            
            WHERE lt.display_language_code='".$sitepress->get_admin_language()."'
                AND l.active = 1
                AND ts.element_type = 'tax_nav_menu'
            GROUP BY ts.language_code
        ");
        foreach($res as $row){
            $langs[$row->lang] = $row;
        }        
        return $langs;
    }
    
    function languages_menu($echo = true){
        global $sitepress;
        $langs = $this->get_menus_by_language();
        // include empty languages
        foreach($sitepress->get_active_languages() as $lang){
            if(!isset($langs[$lang['code']])){
                $langs[$lang['code']] = new stdClass();
                $langs[$lang['code']]->language_name = $lang['display_name'];
                $langs[$lang['code']]->lang = $lang['code'];
                $langs[$lang['code']]->c = 0;
            }            
        }
        $url = admin_url('nav-menus.php');
        foreach($langs as $l){
            $class = $l->lang == $this->current_lang ? ' class="current"' : '';
            $urlsuff = $l->lang != $sitepress->get_default_language() ? '?lang=' . $l->lang : '';
            $ls[] = '<a href="'.$url.$urlsuff.'"'.$class.'>'.esc_html($l->language_name).' ('.$l->c.')</a>';
        }
        $ls_string = '<div class="icl_lang_menu icl_nav_menu_text">';
        $ls_string .= join('&nbsp;|&nbsp;', $ls);
        $ls_string .= '</div>';
        if($echo){
            echo $ls_string;
        }else{
            return $ls_string;
        }
    }
    
    function get_terms_filter($terms, $taxonomies, $args){
        global $wpdb, $sitepress, $pagenow;        
        // deal with the case of not translated taxonomies
        // we'll assume that it's called as just a single item
        if(!$sitepress->is_translated_taxonomy($taxonomies[0]) && 'nav_menu' != $taxonomies[0]){
            return $terms;
        }      
        
        // special case for determining list of menus for updating auto-add option
        if($taxonomies[0] == 'nav_menu' && $args['fields'] == 'ids' && $_POST['action'] == 'update' && $pagenow=='nav-menus.php'){
            return $terms;
        }
          
        if(!empty($terms)){
           
            foreach($taxonomies as $t){
                $txs[] = 'tax_' . $t;
            }
            $el_types = "'".join(',',$txs)."'";
            
            // get all term_taxonomy_id's
            $tt = array();
            foreach($terms as $t){
                if(is_object($t)){
                    $tt[] = $t->term_taxonomy_id;    
                }else{
                    $tt[] = $t;
                }
            }
            // filter the ones in the current language
            if(!empty($tt)){
                $ftt = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations 
                    WHERE element_type IN ({$el_types}) AND element_id IN (".join(',',$tt).") AND language_code='{$this->current_lang}'");
            }
            foreach($terms as $k=>$v){
                if(!in_array($v->term_taxonomy_id, $ftt)){
                    unset($terms[$k]);
                }
            }
        }                
        return $terms;        
    }
    
    // filter posts by language    
    function parse_query($q){
        global $sitepress;
        // not filtering nav_menu_item
        if($q->query_vars['post_type'] == 'nav_menu_item'){
            return $q;
        } 
        
        // also - not filtering custom posts that are not translated
        if($sitepress->is_translated_post_type($q->query_vars['post_type'])){
            $q->query_vars['suppress_filters'] = 0;
        }
        
        return $q;
    }
        
    function theme_mod_nav_menu_locations($val){        
        global $sitepress;
        if($sitepress->get_default_language() != $this->current_lang){
            if(!empty($val)){
                foreach($val as $k=>$v){
                    $val[$k] = icl_object_id($val[$k], 'nav_menu', true, $this->current_lang);       
                }
            }
        }
        return $val;
    }
    
    function pre_update_theme_mods_theme($val){
        global $sitepress;
        if(!empty($val['nav_menu_locations'])){
            foreach($val['nav_menu_locations'] as $k=>$v){
                if(!$v && $this->current_lang != $sitepress->get_default_language()){
                    $tl = get_theme_mod('nav_menu_locations');
                    $val['nav_menu_locations'][$k] = $tl[$k]; 
                }else{
                    $val['nav_menu_locations'][$k] = icl_object_id($val['nav_menu_locations'][$k], 'nav_menu',true, $sitepress->get_default_language());           
                }            
            }        
        }
        return $val;
    }
    
    function option_nav_menu_options($val){
        global $wpdb;
        // special case of getting menus with auto-add only in a specific language
        $db = debug_backtrace();
        if($db[4]['function'] == '_wp_auto_add_pages_to_menu' && !empty($val['auto_add'])){
            $post_lang = $_POST['icl_post_language'];
            $val['auto_add'] = $wpdb->get_col("
                SELECT element_id 
                FROM {$wpdb->prefix}icl_translations 
                WHERE element_type='tax_nav_menu' 
                    AND element_id IN (".join(',',$val['auto_add']).")
                    AND language_code = '{$post_lang}'");    
        }
        
        return $val;
    }
    
    function wp_nav_menu_args_filter($args){
        global $sitepress;
        
        if ( ! $args['menu'] ){            
            $locations = get_nav_menu_locations();
            if(isset( $args['theme_location'] )){
                $args['menu'] = $locations[$args['theme_location']];    
            }
        }; 
        
        if ( ! $args['menu'] ){            
            remove_filter('theme_mod_nav_menu_locations', array($this, 'theme_mod_nav_menu_locations'));
            $locations = get_nav_menu_locations();
            if(isset( $args['theme_location'] )){
                $args['menu'] = $locations[$args['theme_location']];    
            }            
            add_filter('theme_mod_nav_menu_locations', array($this, 'theme_mod_nav_menu_locations'));    
        }
        return $args;
    }
    
    function wp_nav_menu_items_filter($items){
        $items = preg_replace(
            '|<li id="([^"]+)" class="menu-item menu-item-type-taxonomy"><a href="([^"]+)">([^@]+) @([^<]+)</a>|', 
            '<li id="$1" class="menu-item menu-item-type-taxonomy"><a href="$2">$3</a>', $items);
        return $items;
    }
    
    function _set_custom_status_in_theme_location_switcher(){
        global $sitepress, $wpdb;
        $tl = (array)get_theme_mod('nav_menu_locations');
        $menus_not_translated = array();
        foreach($tl as $k=>$menu){
            $menu_tt_id = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu'",$menu));
            $menu_trid = $sitepress->get_element_trid($menu_tt_id, 'tax_nav_menu');
            $menu_translations = $sitepress->get_element_translations($menu_trid, 'tax_nav_menu');
            if(!$menu_translations[$this->current_lang]){
                $menus_not_translated[] = $k;                
            }
        }
        if(!empty($menus_not_translated)){
            ?>
            <script type="text/javascript">
            addLoadEvent(function(){
                <?php foreach($menus_not_translated as $menu_id): ?>
                jQuery('#locations-<?php echo $menu_id?> option').first().html('<?php esc_js(_e('not translated in current language','sitepress')) ?>');
                jQuery('#locations-<?php echo $menu_id?>').css('font-style','italic');
                jQuery('#locations-<?php echo $menu_id?>').change(function(){if(jQuery(this).val()!=0) jQuery(this).css('font-style','normal');else jQuery(this).css('font-style','italic')});
                <?php endforeach; ?>
            });            
            </script>
            <?php             
        }
    }
    
    
    
} 

?>
