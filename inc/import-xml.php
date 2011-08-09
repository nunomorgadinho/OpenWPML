<?php

global $pagenow;
if ($pagenow == 'admin.php' && isset($_GET['import']) && $_GET['import'] == 'wordpress' && isset($_GET['step']) && $_GET['step'] == 1) {
	add_action('admin_head', 'icl_import_xml');
}

function icl_import_xml() {
	global $sitepress;
	$langs = $sitepress->get_active_languages();
	if (empty($langs)) {
		return;
	}
	$default = $sitepress->get_default_language();
	
		$out = '<h2>' . __('Select Language', 'sitepress') . '<\/h2><p><select name="icl_post_language">';
		foreach ($langs as $lang) {
			$out .= '<option value="' . $lang['code'] . '"';
			if ($default == $lang['code']) {
				$out .= ' selected="selected"';
			}
			$out .= '>' . $lang['native_name'] . '<\/option>';
		}
		$out .= '<\/select><\/p>';
	
	echo '
	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery("#wpbody-content .submit").before(\'' . $out . '\');
		});
	</script>
	';
}

add_action('import_start', 'icl_import_xml_start', 0);
function icl_import_xml_start() {
	set_time_limit(0);
	$_POST['icl_tax_post_tag_language'] = $_POST['icl_tax_category_language'] = $_POST['icl_tax_language'] = $_POST['icl_post_language'];
}