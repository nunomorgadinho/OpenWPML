<?php

class SitePress_EditLanguages {

	var $active_languages;
	var $upload_dir;
	var $is_writable = false;
	var $error = false;
	var $required_fields = array('code' => '', 'english_name' => '', 'translations' => 'array', 'flag' => '', 'default_locale' => '');
	var $add_validation_failed = false;

	function __construct() {
		
			// Set upload dir
		$wp_upload_dir = wp_upload_dir();
		$this->upload_dir = $wp_upload_dir['basedir'] . '/flags';
		
		if (!is_dir($this->upload_dir)) {
			if (!mkdir($this->upload_dir)) {
				$this->error(__('Upload directory cannot be created. Check your permissions.','sitepress'));
			}
		}
		if (!$this->is_writable = is_writable($this->upload_dir)) {
			$this->error(__('Upload dir is not writable','sitepress'));
		}
		
		$this->migrate();
		
		$this->get_active_languages();
		
			// Trigger save.
		if (isset($_POST['icl_edit_languages_action']) && $_POST['icl_edit_languages_action'] == 'update') {
			$this->update();
		}
		
		add_action('admin_footer', array(&$this,'scripts'));
		
		
?>
<div class="wrap">
    <div id="icon-options-general" class="icon32 icon32_adv"><br /></div>
    <h2><?php _e('Edit Languages', 'sitepress') ?></h2>
	<div id="icl_edit_languages_info">
<?php
	_e('This table allows you to edit languages for your site. Each row represents a language.
<br /><br />
For each language, you need to enter the following information:
<ul>
    <li><strong>Code:</strong> a unique value that identifies the language. Once entered, the language code cannot be changed.</li>
    <li><strong>Translations:</strong> the way the language name will be displayed in different languages.</li>
    <li><strong>Flag:</strong> the flag to display next to the language (optional). You can either upload your own flag or use one of WPML\'s built in flag images.</li>
    <li><strong>Default locale:</strong> This determines the locale value for this language. You should check the name of WordPress localization file to set this correctly.</li>
</ul>', 'sitepress'); ?>

	</div>
<?php
	if ($this->error) {
		echo '	<div class="icl_error_text" style="margin:10px;">
    	<p>'.$this->error.'</p>
	</div>'; 
	}
?>
	<br />
	<?php $this->edit_table(); ?>
	<div class="icl_error_text icl_edit_languages_show" style="display: none; margin:10px;"><p><?php _e('Please note: language codes cannot be changed after adding languages. Make sure you enter the correct code.', 'sitepress'); ?></p></div>
</div>
<?php
	}

	function edit_table() {
?>
	<form enctype="multipart/form-data" action="" method="post" id="icl_edit_languages_form">
	<input type="hidden" name="icl_edit_languages_action" value="update" />
	<input type="hidden" name="icl_edit_languages_ignore_add" id="icl_edit_languages_ignore_add" value="<?php echo ($this->add_validation_failed) ? 'false' : 'true'; ?>" />
	<table id="icl_edit_languages_table" class="widefat" cellspacing="0">
            <thead>
                <tr>
                    <th><?php _e('Language name', 'sitepress'); ?></th>
					<th><?php _e('Code', 'sitepress'); ?></th>
					<th <?php if (!$this->add_validation_failed) echo 'style="display:none;" ';?>class="icl_edit_languages_show"><?php _e('Translation (new)', 'sitepress'); ?></th>
					<?php foreach ($this->active_languages as $lang) { ?>
					<th><?php _e('Translation', 'sitepress'); ?> (<?php echo $lang['english_name']; ?>)</th>
					<?php } ?>
					<th><?php _e('Flag', 'sitepress'); ?></th>
					<th><?php _e('Default locale', 'sitepress'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th><?php _e('Language name', 'sitepress'); ?></th>
					<th><?php _e('Code', 'sitepress'); ?></th>
					<th <?php if (!$this->add_validation_failed) echo 'style="display:none;" ';?>class="icl_edit_languages_show"><?php _e('Translation (new)', 'sitepress'); ?></th>
					<?php foreach ($this->active_languages as $lang) { ?>
					<th><?php _e('Translation', 'sitepress'); ?> (<?php echo $lang['english_name']; ?>)</th>
					<?php } ?>
					<th><?php _e('Flag', 'sitepress'); ?></th>
					<th><?php _e('Default locale', 'sitepress'); ?></th>
                </tr>
            </tfoot>        
            <tbody>
<?php
		foreach ($this->active_languages as $lang) {
			$this->table_row($lang);
		}
		if ($this->add_validation_failed) {
			$_POST['icl_edit_languages']['add']['id'] = 'add';
			$new_lang = $_POST['icl_edit_languages']['add'];
		} else {
			$new_lang = array('id'=>'add');
		}
		$this->table_row($new_lang,true,true);
?>
			</tbody>
	</table>
	<p class="submit alignleft"><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php">&laquo;&nbsp;<?php _e('Back to languages', 'sitepress'); ?></a></p>

	<p class="submit alignright">
		<input type="button" name="icl_edit_languages_add_language_button" id="icl_edit_languages_add_language_button" value="<?php _e('Add Language', 'sitepress'); ?>" class="button-secondary"<?php if ($this->add_validation_failed) { ?> style="display:none;"<?php } ?> />&nbsp;<input type="button" name="icl_edit_languages_cancel_button" id="icl_edit_languages_cancel_button" value="<?php _e('Cancel', 'sitepress'); ?>" class="button-secondary icl_edit_languages_show"<?php if (!$this->add_validation_failed) { ?> style="display:none;"<?php } ?> />&nbsp;<input disabled type="submit" class="button-primary" value="<?php _e('Save', 'sitepress'); ?>" /></p>
    <br clear="all" />
	</form>
    
    <p>
        <input class="button-primary" type="button" id="icl_reset_languages" value="<?php _e('Reset languages', 'sitepress'); ?>" />
        <span class="hidden"><?php _e('WPML will reset all language information to its default values. Any languages that you added or edited will be lost.','sitepress')?></span>
    </p>

<?php
	}

	function table_row( $lang, $echo = true, $add = false ){ ?>
		
		<tr style="<?php if ($add && !$this->add_validation_failed) echo 'display:none; '; if ($add) echo 'background-color:yellow; '; ?>"<?php if ($add) echo ' class="icl_edit_languages_show"'; ?>>
					<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][english_name]" value="<?php echo $lang['english_name']; ?>"<?php if (!$add) { ?> readonly="readonly"<?php } ?> /></td>
					<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][code]" value="<?php echo $lang['code']; ?>" style="width:30px;"<?php if (!$add) { ?> readonly="readonly"<?php } ?> /></td>
					<td <?php if (!$this->add_validation_failed) echo 'style="display:none;" ';?>class="icl_edit_languages_show"><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][translations][add]" value="<?php echo  $_POST['icl_edit_languages'][$lang['id']]['translations']['add']; ?>" /></td>
					<?php foreach($this->active_languages as $translation){ 
						if ($lang['id'] == 'add') {
							$value = $_POST['icl_edit_languages']['add']['translations'][$translation['code']];
						} else {
							$value = $lang['translation'][$translation['id']];
						}
					?>
					<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][translations][<?php echo $translation['code']; ?>]" value="<?php echo $value; ?>" /></td>
					<?php } ?>
					<td><?php if ($this->is_writable) { ?><input type="hidden" name="MAX_FILE_SIZE" value="100000" /><input name="icl_edit_languages[<?php echo $lang['id']; ?>][flag_file]" class="icl_edit_languages_flag_upload_field file" style="display:none; float:left;" type="file"  size="10" />&nbsp;<?php } ?><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][flag]" value="<?php echo $lang['flag']; ?>" class="icl_edit_languages_flag_enter_field" style="width:60px; float:left;" /><?php if ($this->is_writable) { ?><div style="float:left;"><label><input type="radio" name="icl_edit_languages[<?php echo $lang['id']; ?>][flag_upload]" value="true" class="radio icl_edit_languages_use_upload"<?php if ($lang['from_template']) { ?> checked="checked"<?php } ?> />&nbsp;<?php _e('Upload flag', 'sitepress'); ?></label><br /><label><input type="radio" name="icl_edit_languages[<?php echo $lang['id']; ?>][flag_upload]" value="false" class="radio icl_edit_languages_use_field"<?php if (!$lang['from_template']) { ?> checked="checked"<?php } ?> />&nbsp;<?php _e('Use flag from WPML', 'sitepress'); ?></label></div><?php } ?></td>
					<td><input type="text" name="icl_edit_languages[<?php echo $lang['id']; ?>][default_locale]" value="<?php echo $lang['default_locale']; ?>" style="width:60px;" /></td>
				</tr>
<?php
	}

	function scripts(){
?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery("#icl_edit_languages_add_language_button").click(function(){
					jQuery(this).fadeOut('fast',function(){jQuery("#icl_edit_languages_table tr:last, .icl_edit_languages_show").show();});
					jQuery('#icl_edit_languages_ignore_add').val('false');
				});
				jQuery("#icl_edit_languages_cancel_button").click(function(){
					jQuery(this).fadeOut('fast',function(){
						jQuery("#icl_edit_languages_add_language_button").show();
						jQuery(".icl_edit_languages_show").hide();
						jQuery("#icl_edit_languages_table tr:last input").each(function(){
							jQuery(this).val('');
						});
						jQuery('#icl_edit_languages_ignore_add').val('true');
						jQuery('#icl_edit_languages_form :submit').attr('disabled',true);
					});
				});
				jQuery('.icl_edit_languages_use_upload').click(function(){
						jQuery(this).parent().parent().parent().children('.icl_edit_languages_flag_enter_field').hide();
  						jQuery(this).parent().parent().parent().children('.icl_edit_languages_flag_upload_field').show();
				});
				jQuery('.icl_edit_languages_use_field').click(function(){
						jQuery(this).parent().parent().parent().children('.icl_edit_languages_flag_upload_field').hide();
						jQuery(this).parent().parent().parent().children('.icl_edit_languages_flag_enter_field').show();
				});
				jQuery('#icl_edit_languages_form :submit').attr('disabled',true);
				jQuery('#icl_edit_languages_form input').click(function(){
					jQuery('#icl_edit_languages_form :submit').attr('disabled',false);
				});
			});
		</script>
<?php
	}

	function get_active_languages() {
		global $sitepress, $wpdb;
		$this->active_languages = $sitepress->get_active_languages(true);
		foreach ($this->active_languages as $lang) {
			foreach ($this->active_languages as $lang_translation) {
				$this->active_languages[$lang['code']]['translation'][$lang_translation['id']] = $sitepress->get_display_language_name($lang['code'], $lang_translation['code']);
			}
			$flag = $sitepress->get_flag($lang['code']);
			$this->active_languages[$lang['code']]['flag'] = $flag->flag;
			$this->active_languages[$lang['code']]['from_template'] = $flag->from_template;
			$this->active_languages[$lang['code']]['default_locale'] = $wpdb->get_var("SELECT default_locale FROM {$wpdb->prefix}icl_languages WHERE code='".$lang['code']."'");
		}
	}

	function insert_main_table($code, $english_name, $default_locale, $major = 0, $active = 0) {
		global $wpdb;
		return $wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages (code, english_name, default_locale, major, active) VALUES('".$code."', '".$english_name."', '".$default_locale."', ".$major.", ".$active.")");
	}

	function update_main_table($id, $code, $default_locale){
		global $wpdb;
		$wpdb->query("UPDATE {$wpdb->prefix}icl_languages SET code='".$code."', default_locale='".$default_locale."'  WHERE ID = ".$id);
	}

	function insert_translation($name, $language_code, $display_language_code) {
		global $wpdb;
		return $wpdb->query("INSERT INTO {$wpdb->prefix}icl_languages_translations (name, language_code, display_language_code) VALUES('".$name."', '".$language_code."', '".$display_language_code."')");
	}

	function update_translation($name, $language_code, $display_language_code) {
		global $wpdb;
		$wpdb->query("UPDATE {$wpdb->prefix}icl_languages_translations SET name='".$name."' WHERE language_code = '".$language_code."' AND display_language_code = '".$display_language_code."'");
	}

	function insert_flag($lang_code, $flag, $from_template) {
		global $wpdb;
		return $wpdb->query("INSERT INTO {$wpdb->prefix}icl_flags (lang_code, flag, from_template) VALUES('".$lang_code."', '".$flag."', ".$from_template.")");
	}

	function update_flag($lang_code, $flag, $from_template) {
		global $wpdb;
		$wpdb->query("UPDATE {$wpdb->prefix}icl_flags SET flag='".$flag."',from_template=".$from_template." WHERE lang_code = '".$lang_code."'");
	}

	function update() {
			// Basic check.
		if (!isset($_POST['icl_edit_languages']) || !is_array($_POST['icl_edit_languages'])){
			$this->error(__('Please, enter valid data.','sitepress'));
			return;
		}
		
		global $sitepress,$wpdb;
		
			// First check if add and validate it.
		if (isset($_POST['icl_edit_languages']['add']) && $_POST['icl_edit_languages_ignore_add'] == 'false') {
			if ($this->validate_one('add', $_POST['icl_edit_languages']['add'])) {
				$this->insert_one($this->sanitize($_POST['icl_edit_languages']['add']));
			}
				// Reset flag upload field.
			$_POST['icl_edit_languages']['add']['flag_upload'] = 'false';
		}
		
		foreach ($_POST['icl_edit_languages'] as $id => $data){
			
				// Ignore insert.
			if ($id == 'add') { continue; }
			
				// Validate and sanitize data.
			if (!$this->validate_one($id, $data)) continue;
			$data = $this->sanitize($data);
			
				// Update main table.
			$this->update_main_table($id, $data['code'], $data['default_locale']);
			
				// Update translations table.
			foreach ($data['translations'] as $translation_code => $translation_value) {
				
					// If new (add language) translations are submitted.
				if ($translation_code == 'add') {
					if ($this->add_validation_failed || $_POST['icl_edit_languages_ignore_add'] == 'true') {
						continue;
					}
					if (empty($translation_value)) {
						$translation_value = $data['english_name'];
					}
					$translation_code = $_POST['icl_edit_languages']['add']['code'];
				}
				
					// Check if update.
				if ($wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_languages_translations WHERE language_code='".$data['code']."' AND display_language_code='".$translation_code."'")) {
					$this->update_translation($translation_value, $data['code'], $translation_code);
				} else {
					if (!$this->insert_translation($translation_value, $data['code'], $translation_code)) {
						$this->error(sprintf(__('Error adding translation %s for %s.', 'sitepress'), $data['code'], $translation_code));
					}
				}
			}
			
				// Handle flag.
			if ($data['flag_upload'] == 'true' && !empty($_FILES['icl_edit_languages']['name'][$id]['flag_file'])) {
				if ($filename = $this->upload_flag($id, $data)) {
					$data['flag'] = $filename;
					$from_template = 1;
				} else {
					$data['flag'] = $data['code'] . '.png';
					$this->error(__('Error uploading flag file.', 'sitepress'));
					$from_template = 0;
				}
			} else {
				if (empty($data['flag'])) {
					$data['flag'] = $data['code'] . '.png';
					$from_template = 0;
				} else {
					$from_template = isset($data['from_template']) ? 1 : 0;
				}
			}
				// Update flag table.
			$this->update_flag($data['code'], $data['flag'], $from_template);
				// Reset flag upload field.
			$_POST['icl_edit_languages'][$id]['flag_upload'] = 'false';
		}
			// Refresh cache.
		$sitepress->icl_language_name_cache->clear();
		$sitepress->icl_flag_cache->clear();
		delete_option('_icl_cache');
		
			// Unset ADD fields.
		if (!$this->add_validation_failed) {
			unset($_POST['icl_edit_languages']['add']);
		}
			// Reser active languages.
		$this->get_active_languages();
	}

	function insert_one($data) {
		global $sitepress;
		
			// Insert main table.
		if (!$this->insert_main_table($data['code'], $data['english_name'], $data['default_locale'], 0, 1)) {
			$this->error(__('Adding language failed.', 'sitepress'));
			return false;
		}
		
			// Insert translations.
		$all_languages = $sitepress->get_languages();
		foreach ($all_languages as $key => $lang) {
			
				// If submitted.
			if (array_key_exists($lang['code'], $data['translations'])) {
				if (empty($data['translations'][$lang['code']])) {
					$data['translations'][$lang['code']] = $data['english_name'];
				}
				if (!$this->insert_translation($data['translations'][$lang['code']], $data['code'], $lang['code'])) {
					$this->error(sprintf(__('Error adding translation %s for %s.', 'sitepress'), $data['code'], $lang['code']));
				}
				continue;
			}
			
				// Insert dummy translation.
			if (!$this->insert_translation($data['english_name'], $data['code'], $lang['code'])) {
					$this->error(sprintf(__('Error adding translation %s for %s.', 'sitepress'), $data['code'], $lang['code']));
			}
		}
		
			// Insert native name.
		if (!isset($data['translations']['add']) || empty($data['translations']['add'])) {
			$data['translations']['add'] = $data['english_name'];
		}
		if (!$this->insert_translation($data['translations']['add'], $data['code'], $data['code'])) {
			$this->error(__('Error adding native name.', 'sitepress'));
		}
		
			// Handle flag.
		if ($data['flag_upload'] == 'true' && !empty($_FILES['icl_edit_languages']['name']['add']['flag_file'])) {
			if ($filename = $this->upload_flag('add', $data)) {
				$data['flag'] = $filename;
				$from_template = 1;
			} else {
				$data['flag'] = $data['code'] . '.png';
				$from_template = 0;
			}
		} else {
			if (empty($data['flag'])) {
				$data['flag'] = $data['code'] . '.png';
			}
			$from_template = 0;
		}
		
			// Insert flag table.
		if (!$this->insert_flag($data['code'], $data['flag'], $from_template)) {
			$this->error(__('Error adding flag.', 'sitepress'));
		}
	}


	function validate_one($id, $data) {
	
		global $wpdb;
		
			// If insert, check if languge code (unique) exists.
		if ($exists = $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='".$data['code']."'") && $id == 'add') {
			$this->error = __('Language code exists','sitepress');
			$this->add_validation_failed = true;
			return false;
			
			// Illegal change of code
		} else if ($exists && $wpdb->get_var("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code='".$data['code']."' AND id=".$data['id']) != $data['code']) {
			$this->error = __('Language code exists','sitepress');
			if ($id == 'add') $this->add_validation_failed = true;
			return false;
		}
		
		foreach ($this->required_fields as $name => $type) {
			if ($name == 'flag') {
				if ($data['flag_upload'] == 'true') {
					$check =  $_FILES['icl_edit_languages']['name'][$id]['flag_file'];
					if (empty($check)) continue;
					if (!$this->check_extension($check)) {
						if ($id == 'add') {
							$this->add_validation_failed = true;
						}
						return false;
					}
				}
				continue;
			}
			if (!isset($_POST['icl_edit_languages'][$id][$name]) || empty($_POST['icl_edit_languages'][$id][$name])) {
				if ($_POST['icl_edit_languages_ignore_add'] == 'true') {
					return false;
				}
				$this->error(__('Please, enter required data.','sitepress'));
				if ($id == 'add') {
					$this->add_validation_failed = true;
				}
				return false;
			}
			if ($type == 'array' && !is_array($_POST['icl_edit_languages'][$id][$name])) {
				if ($id == 'add') {
					$this->add_validation_failed = true;
				}
				$this->error(__('Please, enter valid data.','sitepress')); return false;
			}
		}
		return true;
	}

	function sanitize($data) {
		global $wpdb;
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $k => $v) {
					$data[$key][$k] = $wpdb->escape($v);
				}
			}
			$data[$key] = $wpdb->escape($value);
		}
		return $data;
	}

	function check_extension($file) {
		$extension = substr($file, strrpos($file, '.') + 1);
		if (!in_array($extension,array('png','gif','jpg'))) {
			$this->error(__('File extension not allowed.','sitepress'));
			return false;
		}
		return true;
	}

	function error($str = false) {
		if (!$this->error) {
			$this->error = '';
		}
		$this->error .= $str . '<br />';
	}

	function upload_flag($id, $data) {
		$filename = basename($_FILES['icl_edit_languages']['name'][$id]['flag_file']);
		$target_path = $this->upload_dir . '/' . $filename;
		if (move_uploaded_file($_FILES['icl_edit_languages']['tmp_name'][$id]['flag_file'], $target_path) ) {
    		return $filename;
		} else {
    		$this->error(__('There was an error uploading the file, please try again!','sitepress'));
			return false;
		}
	}

	function migrate() {
		global $sitepress, $sitepress_settings;
		if (!isset($sitepress_settings['edit_languages_flag_migration'])) {
			foreach( glob(get_stylesheet_directory().'/flags/*') as $filename ){
				rename($filename, $this->upload_dir . '/' . basename($filename));
			}
			$sitepress->save_settings(array('edit_languages_flag_migration' => 1));
		}
	}

}

global $icl_edit_languages;
$icl_edit_languages = new SitePress_EditLanguages;