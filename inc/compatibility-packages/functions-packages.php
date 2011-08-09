<?php
                 
define('ICL_EXTRAS_DEFAULT_GROUP_NAME', __('Extra options', 'sitepress'));
define('ICL_EXTRAS_PACKAGES_BASE_PATH', ICL_PLUGIN_PATH . '/compatibility-packages');
define('ICL_EXTRAS_EXTERNAL_PACKAGE_FOLDER', 'wpml-compatibility-packages');

class WPML_Packages{
    
    private $packages;
    private $packages_enabled;
    var $packages_options;

    function __construct(){
        $this->_read_external_packages_themes();
        $this->_read_theme_packages();
        $this->_read_plugin_packages();
        $this->_read_external_packages_plugins();
        
        add_action('plugins_loaded', array($this,'load_packages'));
        add_action('icl_extra_options_' . $_GET['page'], array($this,'render_forms'));
        
        if(isset($_POST['icl_extras_submit'])){
            add_action('init', array($this,'process_forms'));
        }            
        
        if(isset($_POST['icl_packages'])){
                add_action('init', array($this, 'update_enabled_packages'));
        }
        
    }
    
    function scan($folder, $external = false){
        global $icl_extra_packages;
        $packages = array();
        $dh = opendir($folder);    
        $packages_type = basename($folder);
        if($dh){ 
            while($f = readdir($dh)){
                if(0 === strpos($f , '.')) continue;
                $package_main_file = $folder . '/' . $f . '/load.php';
                if(file_exists($package_main_file)){
                    $fp = fopen($package_main_file, 'r');
                    // Pull only the first 8kiB of the file in.
                    $package_info = fread( $fp, 8192 );
                    fclose($fp);             

                    preg_match( '|Package Name:(.*)$|mi', $package_info, $name );
                    preg_match( '|Package URI:(.*)$|mi', $package_info, $uri );
                    preg_match( '|\n[\s]*Version:(.*)|i', $package_info, $version );
                    if($packages_type == 'themes' || $external){
                        preg_match( '|Theme:(.*)|i', $package_info, $theme );
                        preg_match( '|Theme version:(.*)|i', $package_info, $theme_version );
                    }
                    if($packages_type == 'plugins' || $external){
                        preg_match( '|Plugin:(.*)|i', $package_info, $plugin );
                        preg_match( '|Plugin version:(.*)|i', $package_info, $plugin_version );
                    }                    
                    preg_match( '|Description:(.*)$|mi', $package_info, $description );
                    preg_match( '|Author:(.*)$|mi', $package_info, $author_name );
                    preg_match( '|Author URI:(.*)$|mi', $package_info, $author_uri );
                    
                    foreach ( array( 'name', 'uri', 'version', 'theme', 'theme_version', 'plugin', 'plugin_version', 'description', 'author_name', 'author_uri' ) as $field ) {
                        if ( !empty( ${$field} ) )
                            ${$field} = _cleanup_header_comment(${$field}[1]);
                        else
                            ${$field} = '';
                    }
                    if($name && $version){
                        $package_data = array(
                        'Name' => $name, 'URI' => $uri, 'Description' => $description, 
                        'Author' => $author_name, 'AuthorURI' => $author_uri, 'Version' => $version
                        );
                        if($packages_type == 'themes' || $external){
                            $package_data['Theme'] = $theme;
                            $package_data['ThemeVersion'] = $theme_version;
                            $package_data['id'] = $theme;
                        }  
                        if($packages_type == 'plugins' || $external){
                            $package_data['Plugin'] = $plugin;
                            $package_data['PluginVersion'] = $plugin_version;                            
                            $package_data['id'] = str_replace(array('/','.php'),array('-',''),$plugin);
                        }  
                                                
                        // add the package only if the theme is active
                        if($external && $package_data['Plugin'] && !in_array($package_data['Plugin'], get_option('active_plugins'))){
                            continue;
                        }
                        if($external && $package_data['Theme']){                            
                            if(($package_data['Theme'] != basename(get_template_directory())) && ($package_data['Theme'] != basename(get_stylesheet_directory()))){
                                continue;
                            }                                
                        }
                        
                        if($packages_type == 'themes'){
                            if($package_data['Theme'] != basename(get_template_directory()) && $package_data['Theme'] != basename(get_stylesheet_directory())){
                                continue;
                            }                                
                        }elseif($packages_type == 'plugins'){
                            $plugins = get_option('active_plugins');
                            if (function_exists('get_site_option')){
                                $plugins = array_merge($plugins, array_keys(get_site_option('active_sitewide_plugins',array())));
                            }
                            if(!in_array($package_data['Plugin'], $plugins)){
                                continue;
                            }
                        }
                        
                        $package_data['path'] = $folder . '/' . $f ;
                        $packages[$f] = $package_data;
                                                         
                    }
                }
            }
            closedir($dh);            
        }else{
            throw new Exception(sprintf('Can\'t open folder %s',$folder));
        }
        
        return $packages;
        
    }

    function _read_theme_packages(){
        try{
            $this->packages['themes'] = array_merge((array)$this->packages['themes'], 
                $this->scan(ICL_EXTRAS_PACKAGES_BASE_PATH . '/themes'));
        }catch (Exception $e){ echo $e->getMessage(); }
    }

    function _read_plugin_packages(){
        try{
            $this->packages['plugins'] = array_merge((array)$this->packages['plugins'],
                $this->scan(ICL_EXTRAS_PACKAGES_BASE_PATH . '/plugins'));
        }catch (Exception $e){ echo $e->getMessage(); }
    }

    function _read_external_packages_themes(){
        $external = true;
        // read package from the current theme
        if(get_template_directory() != get_stylesheet_directory()){
            $folder = get_stylesheet_directory() . '/' . ICL_EXTRAS_EXTERNAL_PACKAGE_FOLDER;        
            if(@file_exists($folder) && is_dir($folder)){
                $packages = $this->scan($folder, $external);
                if(is_array($packages) && !empty($packages)){
                    $this->packages['themes'] = array_merge((array)$this->packages['themes'] , $packages);
                }
            }
        }
        $folder = get_template_directory() . '/' . ICL_EXTRAS_EXTERNAL_PACKAGE_FOLDER;        
        if(@file_exists($folder) && is_dir($folder)){
            $packages = $this->scan($folder, $external);
            if(is_array($packages) && !empty($packages)){
                $this->packages['themes'] = array_merge((array)$this->packages['themes'] , $packages);
            }
        }
    }
    
    function _read_external_packages_plugins(){
        $plugins = get_option('active_plugins');
        
        if (function_exists('get_site_option')){
            $plugins = array_merge($plugins, array_keys(get_site_option('active_sitewide_plugins',array())));
        }
        
        if(is_array($plugins) && !empty($plugins)){
            foreach($plugins as $plugin){
                $exp = explode('/', $plugin);                
                $plugin_folder = WP_PLUGIN_DIR . '/' . $exp[0] . '/' . ICL_EXTRAS_EXTERNAL_PACKAGE_FOLDER;
                if(@file_exists($plugin_folder) && is_dir($plugin_folder)){
                    $packages = $this->scan($plugin_folder, $external);
                    if(is_array($packages) && !empty($packages)){
                        $this->packages['plugins'] = array_merge((array)$this->packages['plugins'] , $packages);
                    }
                }
            }
        }        
    }
    
    function get_packages(){
        return $this->packages;        
    }

    function load_package($type, $name){
        try{
            $bootstrap = $this->packages[$type][$name]['path'] . '/load.php';
            if(@file_exists($bootstrap)){
                @include $bootstrap;
            }else{
                global $sitepress;
                $enabled = $this->get_enabled_packages();        
                unset($enabled[$type][$name]);
                $this->packages_enabled[$type][$name] = 0;
                $iclsettings['packages_enabled'] = $this->packages_enabled;
                $sitepress->save_settings($iclsettings);
            }
            
        }catch(Exception $e){
            echo $e->getMessage();
        }            
    }
    
    function load_packages(){        
        global $sitepress_settings;                
        $enabled = $this->get_enabled_packages();        
        foreach($this->packages as $type => $packages){
            foreach($packages as $package => $package_data){
                // auto-enable   
                if(!isset($enabled[$type][$package]) && !isset($sitepress_settings['upgrade_flags']['1.5'])){
                    global $sitepress;
                    $enabled[$type][$package] = 1;
                    $this->packages_enabled[$type][$package] = 1;
                    $iclsettings['packages_enabled'] = $this->packages_enabled;
                    $sitepress->save_settings($iclsettings);
                } 
                if($enabled[$type][$package]){
                    $this->load_package($type, $package);                        
                }                
            }
        }
    }
    
    function get_enabled_packages(){
        global $sitepress_settings;
        if(!isset($this->packages_enabled)){
            $this->packages_enabled = $sitepress_settings['packages_enabled'];
        }        
        return $this->packages_enabled;
    }
    
    function update_enabled_packages(){
        global $sitepress, $sitepress_settings;
        $type = key($_POST['icl_packages']);        
        $updated_packages = $_POST['icl_packages'][$type];            
        
        
        $enabled_packages = $this->get_enabled_packages();
        if(!is_array($enabled_packages[$type])){
            $enabled_packages[$type] = array();
        }

        $iclsettings['packages_enabled'][$type] = array_merge((array)$enabled_packages[$type], (array)$updated_packages);
                
        $sitepress->save_settings($iclsettings);
        $this->packages_enabled[$type] = $iclsettings['packages_enabled'][$type];
        $sitepress_settings['packages_enabled'][$type] = $iclsettings['packages_enabled'][$type];
    }
    
    private function _render_form_title($group_name){
        echo '<h3>' . $group_name . '</h3>';    
    }
    
    private function _icl_extras_render_checkbox($package_type, $package_name, $option_name, $default_value = '', $extra_attributes = array()){
        global $sitepress_settings;
                                      
        if(isset($sitepress_settings['packages'][$package_type][$package_name][$option_name]) && $sitepress_settings['packages'][$package_type][$package_name][$option_name] 
            || !isset($sitepress_settings['packages'][$package_type][$package_name][$option_name]) && $default_value=='checked'){
            $checked = ' checked="checked"';
        }else{
            $checked = '';
        }
        $ea = '';
        if(!empty($extra_attributes)){
            foreach($extra_attributes as $k=>$v){
                $ea .= ' ' . $k . '="' . $v . '"';
            }        
        }
        echo '<input type="hidden" name="icl_extras['.$package_type.']['.$package_name.']['.$option_name.']" value="0" />';
        echo '<input type="checkbox" id="icl_extras_'.$package_name.'_'.$option_name.'" name="icl_extras['.$package_type.']['.$package_name.']['.$option_name.']" value="1"' .$checked . $ea . ' />';
    }    
    
    private function _icl_extras_render_text($package_type, $package_name, $option_name, $default_value = '', $extra_attributes = array()){
        global $sitepress_settings;

        if(isset($sitepress_settings['packages'][$package_type][$package_name][$option_name])){
            $value = $sitepress_settings['packages'][$package_type][$package_name][$option_name];
        }else{
            $value = $default_value;
        }    

        $ea = '';
        if(!empty($extra_attributes)){
            foreach($extra_attributes as $k=>$v){
                $ea .= ' ' . $k . '="' . $v . '"';
            }        
        }
        echo '<input type="text" id="icl_extras_'.$package_name.'_'.$option_name.'" name="icl_extras['.$package_type.']['.$package_name.']['.$option_name.']" value="'.$value.'"' . $ea . ' />';
    }    
    
    private function _icl_extras_render_textarea($package_type, $package_name, $option_name, $default_value = '', $extra_attributes = array()){
        global $sitepress_settings;

        if(isset($sitepress_settings['packages'][$package_type][$package_name][$option_name])){
            $value = $sitepress_settings['packages'][$package_type][$package_name][$option_name];
        }else{
            $value = $default_value;
        }    

        $ea = '';
        if(!empty($extra_attributes)){
            foreach($extra_attributes as $k=>$v){
                $ea .= ' ' . $k . '="' . $v . '"';
            }        
        }
        echo '<textarea id="icl_extras_'.$package_name.'_'.$option_name.'" name="icl_extras['.$package_type.']['.$package_name.']['.$option_name.']" ' . $ea . '>'.$value.'</textarea>';
    }
    
    private function _icl_extras_render_select($package_type, $package_name, $option_name, $option_options, $default_value = '', $extra_attributes = array()){
        global $sitepress_settings;

        if(isset($sitepress_settings['packages'][$package_type][$package_name][$option_name])){
            $value = $sitepress_settings['packages'][$package_type][$package_name][$option_name];
        }else{
            $value = $default_value;
        }    

        $ea = '';
        if(!empty($extra_attributes)){
            foreach($extra_attributes as $k=>$v){
                $ea .= ' ' . $k . '="' . $v . '"';
            }        
        }
        echo '<select type="text" id="icl_extras_'.$package_name.'_'.$option_name.'" name="icl_extras['.$package_type.']['.$package_name.']['.$option_name.']"' . $ea . '>';
        foreach($option_options as $opt_value=>$name){
            if($value==$opt_value){
                $selected = ' selected="selected"';
            }else{
                $selected = '';
            }
            echo '<option value="'.$opt_value.'"'.$selected.'>' . $name . '</option>';
        }
        echo '</select>';
    }        
    
    private function _icl_extras_render_radio($package_type, $package_name, $option_name, $option_values, $default_value = '', $extra_attributes = array()){
        global $sitepress_settings;
        
        if(isset($sitepress_settings['packages'][$package_type][$package_name][$option_name])){
            $value = $sitepress_settings['packages'][$package_type][$package_name][$option_name];
        }else{
            $value = $default_value;
        }    

        $ea = '';
        if(!empty($extra_attributes)){
            foreach($extra_attributes as $k=>$v){
                $ea .= ' ' . $k . '="' . $v . '"';
            }        
        }
        foreach($option_values as $value=>$name){
            if($default_value && $default_value==$value){
                $checked = ' checked="checked"';
            }else{
                $checked = '';
            }
            echo '<label><input type="radio" id="icl_extras_'.$package_name.'_'.$option_name.'" name="icl_extras['.$package_type.']['.$package_name.']['.$option_name.']" value="'.$value.'"' .$checked . $ea . ' />' . $name . '</label>&nbsp;&nbsp;&nbsp;';
        }
    }        
    
    function render_forms(){
        $page = $_GET['page'];
        if(isset($this->packages_options[$page])){
            $frm_idx = 0;
            foreach($this->packages_options[$page] as $group_name => $options){        
                echo '<a name="icl-extras-'.$frm_idx.'"></a>';
                $this->_render_form_title($group_name);
                if(isset($_GET['updated']) && $_GET['updated']==$frm_idx){
                    echo '<div class="icl_form_success">';
                    echo __('Options saved', 'sitepress');
                    echo '</div>';
                }                                
                echo '<form name="icl_extras_form_'.$frm_idx.'" method="post" action="admin.php?page='.$page.'&amp;updated=true#icl-extras-'.$frm_idx.'">';
                echo '<input type="hidden" name="icl_extras_frm_idx" value="'.$frm_idx.'" />';
                echo '<input type="hidden" name="icl_extras_group" value="'.$group_name.'" />';
                echo '<table class="form-table">';
                echo '<tbody>';
                foreach($options as $package_name => $package){
                    foreach($package as $o){
                        echo '<tr valign="top">';
                        echo '<th scope="row" style="width:auto;border-bottom:1px solid #ddd;"><label for="icl_extras_'.$package_name.'_'.$o['option_name'].'">' . $o['option_label'] . '</label></th>';
                        echo '<td style="width:auto;border-bottom:1px solid #ddd;">';                
                        switch($o['option_type']){                    
                            case 'checkbox':
                                $this->_icl_extras_render_checkbox($o['package_type'], $package_name, $o['option_name'], $o['default_value'], $o['extra_attributes']);                        
                                break;    
                            case 'text':
                                $this->_icl_extras_render_text($o['package_type'], $package_name, $o['option_name'], $o['default_value'], $o['extra_attributes']);                        
                                break;                                
                            case 'textarea':
                                $this->_icl_extras_render_textarea($o['package_type'], $package_name, $o['option_name'], $o['default_value'], $o['extra_attributes']);                        
                                break;                                
                            case 'select':
                                $this->_icl_extras_render_select($o['package_type'], $package_name, $o['option_name'], $o['option_options'], $o['default_value'], $o['extra_attributes']);                        
                                break;                                
                            case 'radio':
                                $this->_icl_extras_render_radio($o['package_type'], $package_name, $o['option_name'], $o['option_options'], $o['default_value'], $o['extra_attributes']);                        
                                break;                                
                                
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                }  
                echo '<tr><td colspan="2" align="right"><input name="icl_extras_submit" class="button" type="submit" value="'.__('Save', 'sitepress').'" /></td></tr>';  
                echo '</tbody>';
                echo '</table>';
                $frm_idx++;
                echo '</form>';
                
            }
        }        
    }
    
    function process_forms(){
        global $sitepress, $sitepress_settings;    
        
        $iclsettings['packages'] = $sitepress_settings['packages'];
        
        foreach($_POST['icl_extras'] as $package_type => $packages){            
            foreach($packages as $package => $options){
                foreach($options as $option_name => $option_value){
                    $iclsettings['packages'][$package_type][$package][$option_name] = $option_value;                
                }
            }
        }
        
        $sitepress->save_settings($iclsettings);
        
        $page = $_GET['page'];
        wp_redirect('admin.php?page='.$page.'&updated=' . $_POST['icl_extras_frm_idx']);
        
    }
    
}



// OLD STUFF DOWN HERE

/*  

function icl_extras_add_option_checkbox($wpml_page, $package_name, $option_label, $option_name, 
                                        $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
    global $icl_extras;
    if(!$group_name){
        $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
    }
    $icl_extras[$wpml_page][$group_name][$package_name][] = array(
        'option_type' => 'checkbox',
        'option_label' => $option_label,
        'option_name' => $option_name,
        'default_value' => $default_value,
        'extra_attributes' => $extra_attributes
    );
} 


function icl_extras_add_option_text($wpml_page, $package_name, $option_label, $option_name, 
                                        $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME, $default_value='', $extra_attributes = array()){
    global $icl_extras;
    if(!$group_name){
        $group_name = ICL_EXTRAS_DEFAULT_GROUP_NAME;
    }
    $icl_extras[$wpml_page][$group_name][$package_name][] = array(
        'option_type' => 'text',
        'option_label' => $option_label,
        'option_name' => $option_name,
        'default_value' => $default_value,
        'extra_attributes' => $extra_attributes
    );
} 
*/
/*
function _icl_extras_render_form_title($group_name){
    echo '<h3>' . $group_name . '</h3>';
}

function _icl_extras_render_checkbox($package_name, $option_name, $default_value = '', $extra_attributes = array()){
    global $sitepress_settings;
    
    if(isset($sitepress_settings['packages'][$package_name][$option_name]) && $sitepress_settings['packages'][$package_name][$option_name] 
        || !isset($sitepress_settings['packages'][$package_name][$option_name]) && $default_value=='checked'){
        $checked = ' checked="checked"';
    }else{
        $checked = '';
    }
    $ea = '';
    if(!empty($extra_attributes)){
        foreach($extra_attributes as $k=>$v){
            $ea .= ' ' . $k . '=' . $v;
        }        
    }
    echo '<input type="checkbox" id="icl_extras_'.$package_name.'_'.$option_name.'" name="icl_extras['.$package_name.']['.$option_name.']" value="1"' .$checked . $ea . ' />';
}

function _icl_extras_render_text($package_name, $option_name, $default_value = '', $extra_attributes = array()){
    global $sitepress_settings;

    if(isset($sitepress_settings['packages'][$package_name][$option_name])){
        $value = $sitepress_settings['packages'][$package_name][$option_name];
    }else{
        $value = '';
    }    
    
    $ea = '';
    if(!empty($extra_attributes)){
        foreach($extra_attributes as $k=>$v){
            $ea .= ' ' . $k . '=' . $v;
        }        
    }
    echo '<input type="text" id="icl_extras_'.$package_name.'_'.$option_name.'" name="icl_extras['.$package_name.']['.$option_name.']" value="'.$value.'"' . $ea . ' />';
}
*/

/*
function icl_extras_render_forms(){
    $page = $_GET['page'];
    global $icl_extras;
    if(isset($icl_extras[$page])){
        $frm_idx = 0;
        foreach($icl_extras[$page] as $group_name => $options){        
            _icl_extras_render_form_title($group_name);
            echo '<form name="icl_extras_form_'.$frm_idx.'" method="post" action="admin.php?page='.$page.'&amp;updated=true#icl-extras-'.$frm_idx.'">';
            echo '<input type="hidden" name="icl_extras_frm_idx" value="'.$frm_idx.'" />';
            echo '<input type="hidden" name="icl_extras_group" value="'.$group_name.'" />';
            if(isset($_GET['updated']) && $_GET['updated']==$frm_idx){
                echo '<div class="icl_form_success">';
                echo __('Options saved', 'sitepress');
                echo '</div>';
            }
            echo '<table id="icl-extras-'.$frm_idx.'">';
            foreach($options as $package_name => $package){
                foreach($package as $o){
                    echo '<tr>';
                    echo '<th><label for="icl_extras_'.$package_name.'_'.$o['option_name'].'">' . $o['option_label'] . '</label></th>';
                    echo '<td>';                
                    switch($o['option_type']){                    
                        case 'checkbox':
                            _icl_extras_render_checkbox($package_name, $o['option_name'], $o['default_value'], $o['extra_attributes']);                        
                            break;    
                        case 'text':
                            _icl_extras_render_text($package_name, $o['option_name'], $o['default_value'], $o['extra_attributes']);                        
                            break;                                
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            }  
            echo '<tr><td colspan="2" align="right"><input name="icl_extras_submit" class="button" type="submit" value="'.__('Save', 'sitepress').'"></td></tr>';  
            echo '</table>';
            $frm_idx++;
            echo '</form>';
            
        }
    }
}
*/

/*
function icl_extras_process_forms(){
    global $sitepress, $sitepress_settings, $icl_extras;    
    
    $iclsettings['packages'] = $sitepress_settings['packages'];
    
    foreach($_POST['icl_extras'] as $package => $options){
        foreach($options as $option_name => $option_value){
            $iclsettings['packages'][$package][$option_name] = $option_value;
        }
        
    }
    
    $page = $_GET['page'];
    foreach($icl_extras[$page] as $group_name=>$options){
        foreach($options as $package_name => $package){
            foreach($package as $o){                
                switch($o['option_type']){                    
                    case 'checkbox';                                                     
                        if(!isset($_POST['icl_extras'][$package_name][$o['option_name']]) && $_POST['icl_extras_group'] == $group_name){
                            $iclsettings['packages'][$package_name][$o['option_name']] = 0;
                        }
                        break;
                }
            }
        }
    }
    
    $sitepress->save_settings($iclsettings);
    
    wp_redirect('admin.php?page='.$page.'&updated=' . $_POST['icl_extras_frm_idx']);
}
*/

?>
