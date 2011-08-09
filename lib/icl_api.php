<?php
class ICanLocalizeQuery{
      private $site_id; 
      private $access_key;
      private $error = null;

      function __construct($site_id=null, $access_key=null){             
            $this->site_id = $site_id;
            $this->access_key = $access_key;
      } 
      
      public function setting($setting){
          return $this->$setting;
      }
      
      public function error(){
          return $this->error;
      }
      
    
    function createAccount($data){
		if (isset($_GET['page']) && $_GET['page'] == ICL_PLUGIN_FOLDER . '/menu/support.php') {
			$add = '?ignore_languages=1';
		}
        $request = ICL_API_ENDPOINT . '/websites/create_by_cms.xml'.$add;
        $response = $this->_request($request, 'POST', $data);        
        
        if(defined('ICL_DEB_SHOW_ICL_RAW_RESPONSE') && ICL_DEB_SHOW_ICL_RAW_RESPONSE){
            $response['HTTP_ERROR'] = $this->error();
            return $response;    
        }
                
        if(!$response){
            return array(0, $this->error);
        }else{
            $site_id = $response['info']['website']['attr']['id'];
            $access_key = $response['info']['website']['attr']['accesskey'];
        }
        return array($site_id, $access_key);
    }

    function updateAccount($data){        
        $request = ICL_API_ENDPOINT . '/websites/'.$data['site_id'].'/update_by_cms.xml';
        unset($data['site_id']);
        $response = $this->_request($request, 'POST', $data);        
        if(!$response){
            return $this->error;
        }else{
            return 0;            
        }
    }

    function transfer_account($data) {
        $request = ICL_API_ENDPOINT . '/websites/'.$data['site_id'].'/transfer_account.xml';
        $response = $this->_request($request, 'POST', $data);        
        if(!$response){
            return array(false, $this->error);
        }else{
            $error_code = $response['info']['status']['attr']['err_code'];
	    if ($error_code == 0) {
		  $access_key = $response['info']['website']['attr']['accesskey'];
                  return array(true, $access_key);
	    } else {
		  return array(false, $response['info']['status']['value']);
	    }
        }
    }
    
    function get_website_details(){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '.xml?accesskey=' . $this->access_key;
        $res = $this->_request($request_url);
        if(isset($res['info']['website'])){
            return $res['info']['website'];
        }else{
            return array();
        }
    }
    
    
    function _request($request, $method='GET', $formvars=null, $formfiles=null, $gzipped = false){
        global $sitepress_settings, $sitepress;
        $request = str_replace(" ", "%20", $request);
        $c = new IcanSnoopy();
        
        $debugvars =  array(
            'debug_cms' => 'WordPress',
            'debug_module' => 'WPML ' . ICL_SITEPRESS_VERSION,
            'debug_url'     => get_bloginfo('siteurl')
        );
        
        if($method == 'GET'){
            $request .= '&' . http_build_query($debugvars);    
        }else{
            $formvars += $debugvars;
        }
        
        // disable error reporting
        // needed for open_basedir restrictions (is_readable)
        $_display_errors = ini_get('display_errors');
        $_error_reporting = ini_get('error_reporting');
        ini_set('display_errors', '0');        
        ini_set('error_reporting', E_NONE);        
        
        if (!@is_readable($c->curl_path) || !@is_executable($c->curl_path)){
            $c->curl_path = '/usr/bin/curl';
        }        
        
        // restore error reporting
        // needed for open_basedir restrictions
        ini_set('display_errors', $_display_errors);        
        ini_set('error_reporting', $_error_reporting);        
        
        
        $c->_fp_timeout = 3;
        $c->read_timeout = 5;
        $url_parts = parse_url($request);
        if($sitepress_settings['troubleshooting_options']['http_communication']){
            $request = str_replace('https://','http://',$request);
        }
        if($method=='GET'){                        
            $c->fetch($request);  
            if($c->timed_out){die(__('Error:','sitepress').$c->error);}
        }else{
            $c->set_submit_multipart();          
            $c->submit($request, $formvars, $formfiles);            
            if($c->timed_out){die(__('Error:','sitepress').$c->error);}
        }
        
        if($c->error){
            $this->error = $c->error;
            return false;
        }
        
        if($gzipped){
            $c->results = $this->_gzdecode($c->results);
        }        
        $results = icl_xml2array($c->results,1);                        
        
        if(isset($results['info']) && $results['info']['status']['attr']['err_code']=='-1'){
            $this->error = $results['info']['status']['value'];            
            return false;
        }
                
        return $results;
    }
    
    function _request_gz($request_url){
        $gzipped = true;
        return $this->_request($request_url, 'GET', null, null, $gzipped);
    }   
       
    function build_cms_request_xml($data, $orig_lang) {
        global $wp_taxonomies;
        $taxonomies = array_diff(array_keys((array)$wp_taxonomies), array('post_tag','category'));
        
        $tab = "\t";
        $nl = PHP_EOL;
        
        if(!empty($data['previous_cms_request_id'])){
            $prev = ' previous_cms_request_id="' . $data['previous_cms_request_id'] . '"';
        }else{
            $prev = '';
        }
        
        $xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>".$nl;
        $xml .= '<cms_request_details type="sitepress" command="translate_content" from_lang="'.$orig_lang.'"'.$prev.'>'.$nl;
        $xml .= $tab.'<link url="'.$data['url'].'" />'.$nl;
        $xml .= $tab.'<contents>'.$nl;
        foreach($data['contents'] as $key=>$val){
            if($key=='categories' || $key == 'tags' || in_array($key, $taxonomies)){$quote="'";}else{$quote='"';}
            $xml .= $tab.$tab.'<content type="'.$key.'" translate="'.$val['translate'].'" data='.$quote.$val['data'].$quote;
            if(isset($val['format'])) $xml .= ' format="'.$val['format'].'"';
            $xml .=  ' />'.$nl;    
        }        
        $xml .= $tab.'</contents>'.$nl;
        $xml .= $tab.'<cms_target_languages>'.$nl;
        foreach($data['target_languages'] as $lang){
            $xml .= $tab.$tab.'<target_language lang="'.utf8_encode($lang).'" />'.$nl;    
        }                
        $xml .= $tab.'</cms_target_languages>'.$nl;
        $xml .= '</cms_request_details>';                
        
        return $xml;
    }
      
    function send_request($args){
        $request_url = ICL_API_ENDPOINT . '/websites/'. $this->site_id . '/cms_requests.xml';
        
        // $cms_id
        // $xml
        // $title 
        // $to_languages
        // $orig_language
        // $permlink
        // $translator_id
        // $note=""
        $args_defaults = array(
            'cms_id'        => false,
            'xml'           => '',
            'title'         => '',
            'to_languages'   => array(),
            'orig_language' => '',
            'permlink'      => '',
            'translator_id' => 0,
            'note'          => ''
        );
        extract($args_defaults);
        extract($args, EXTR_OVERWRITE);
        
        if(!empty($cms_id)){
            $parameters['cms_id'] = $cms_id;              
        }
        $parameters['accesskey'] = $this->access_key;
        $parameters['doc_count'] = 1;          
        $parameters['translator_id'] = $translator_id;          
        $i = 1;
        foreach($to_languages as $l){
          $parameters['to_language'.$i] = $l;
          $i++;
        }
        $parameters['orig_language'] = $orig_language;          
        $parameters['file1[description]'] = 'cms_request_details';          
        $parameters['title'] = $title;          
        if($permlink){
            $parameters['permlink'] = $permlink;          
        }
        
        $parameters['note'] = $note;
        
        // add a unique key so that so that the server can return an
        // existing cms_request_id if we are sending the same info
        $parameters['key'] = md5($xml);
        
        $file = "cms_request_details.xml.gz";
        
        // send the file upload as the file_name and file_content in an array.
        // Snoopy has been changed to use this format.
        $res = $this->_request($request_url, 'POST' , $parameters, array('file1[uploaded_data]'=>array(array($file, gzencode($xml)))));        
        
        if($res['info']['status']['attr']['err_code']=='0'){
            return $res['info']['result']['attr']['id'];
        }else{
            return isset($res['info']['status']['attr']['err_code'])?-1*$res['info']['status']['attr']['err_code']:0;
        }
        
        return $res;
        
        
    }   
    
    function cms_requests(){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests.xml?filter=pickup&accesskey=' . $this->access_key;        
        $res = $this->_request($request_url);
        if(empty($res['info']['pending_cms_requests']['cms_request'])){
            $pending_requests = array();
        }elseif(count($res['info']['pending_cms_requests']['cms_request'])==1){
            $pending_requests[0] = $res['info']['pending_cms_requests']['cms_request']['attr']; 
        }else{
            foreach($res['info']['pending_cms_requests']['cms_request'] as $req){
                $pending_requests[] = $req['attr'];
            }
        }
        return $pending_requests;
    }   
        
    function cms_requests_all(){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests.xml?show_languages=1&accesskey=' . $this->access_key;        
        $res = $this->_request($request_url);
        if(empty($res['info']['pending_cms_requests']['cms_request'])){
            $pending_requests = array();
        }elseif(count($res['info']['pending_cms_requests']['cms_request'])==1){
            $req = $res['info']['pending_cms_requests']['cms_request']['attr'];
            $req['target'] = $res['info']['pending_cms_requests']['cms_request']['target_language']['attr'];
            $pending_requests[0] = $req; 
        }else{
            foreach($res['info']['pending_cms_requests']['cms_request'] as $req){
                $req['attr']['target'] = $req['target_language']['attr'];
                $pending_requests[] = $req['attr'];
            }
        }
        return $pending_requests;
    }   
    
    function cms_request_details($request_id, $language){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/'.$request_id.'/cms_download.xml?accesskey=' . $this->access_key . '&language=' . $language;                
        $res = $this->_request($request_url);
        if(isset($res['info']['cms_download'])){
            return $res['info']['cms_download'];
        }else{
            return array();
        }
    }
    
    function cms_do_download($request_id, $language){
        global $wp_taxonomies;
        $taxonomies = array_diff(array_keys((array)$wp_taxonomies), array('post_tag','category'));
        
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/'.$request_id.'/cms_download?accesskey=' . $this->access_key . '&language=' . $language;                        
        $res = $this->_request_gz($request_url); 
    
        $content = $res['cms_request_details']['contents']['content'];
                
        $translation = array();
        if($content)        
        foreach($content as $c){
            if($c['attr']['type']=='tags' || $c['attr']['type']=='categories' || in_array($c['attr']['type'], $taxonomies)){
                $exp = explode(',',$c['translations']['translation']['attr']['data']);
                $arr = array();
                foreach($exp as $e){
                    if($c['attr']['format'] == 'csv_base64'){
                        $arr[] = base64_decode(html_entity_decode($e));
                    } else {
                        $arr[] = html_entity_decode($e);
                    }
                }
                $c['translations']['translation']['attr']['data'] = $arr;
            }
            if(isset($c['translations'])){
                $translation[$c['attr']['type']] = $c['translations']['translation']['attr']['data'];
            }else{
                $translation[$c['attr']['type']] = $c['attr']['data'];
            }
            if($c['attr']['format'] == 'base64'){
                $translation[$c['attr']['type']] = base64_decode($translation[$c['attr']['type']]);
            }
            
            if($c['attr']['type'] == 'body'){
                $translation['body'] = html_entity_decode($translation['body'], ENT_QUOTES, 'UTF-8');
            }
            
        }
        return $translation;
    }
    
    function cms_update_request_status($request_id, $status, $language){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/'.$request_id.'/update_status.xml';                            
        $parameters['accesskey'] = $this->access_key;
        $parameters['status'] = $status;
        if($language){
            $parameters['language'] = $language;
        }        
        
        $res = $this->_request($request_url, 'POST' , $parameters);
        
        return ($res['result']['attr']['error_code']==0);
    }
    
    function cms_request_translations($request_id){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/'.$request_id.'.xml?accesskey=' . $this->access_key;               
        $res = $this->_request($request_url);
        if(isset($res['info']['cms_request'])){
            return $res['info']['cms_request'];
        }else{
            return array();
        }        
    }

    function update_cms_id($args){
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/update_cms_id.xml';               
        $parameters['accesskey'] = $this->access_key;
        $parameters['permlink'] = $args['permalink'];
        $parameters['from_language'] = $args['from_language'];
        $parameters['to_language'] = $args['to_language'];
        $parameters['cms_id'] = $args['cms_id'];
        
        $res = $this->_request($request_url, 'POST', $parameters);
        
        if(isset($res['info']['status']['attr']['err_code'])){
            return $res['info']['updated']['cms_request']['attr']['cms_id'];
        }else{
            return array();
        }        
    }
    
    function _gzdecode($data){
        
        return icl_gzdecode($data);
    }
    
    function cms_create_message($body, $from_language, $to_language){
        $request_url = ICL_API_ENDPOINT . '/websites/'. $this->site_id . '/create_message.xml';    
        $parameters['accesskey'] = $this->access_key;
        $parameters['body'] = base64_encode($body);
        $parameters['from_language'] = $from_language;
        $parameters['to_language'] = $to_language;
        $parameters['signature'] = md5($body.$from_language.$to_language);
        $res = $this->_request($request_url, 'POST' , $parameters);        
        if($res['info']['status']['attr']['err_code']=='0'){
            return $res['info']['result']['attr']['id'];
        }else{
            return isset($res['info']['status']['attr']['err_code'])?-1*$res['info']['status']['attr']['err_code']:0;
        }
        
        return $res;
        
    }
    
    function get_session_id($support_mode) {
        global $sitepress;
        $sitepress_settigs = $sitepress->get_settings();
        $request_url = ICL_API_ENDPOINT . '/login/login.xml';    
        $request_url .= '?accesskey=' . $this->access_key;
        $request_url .= '&wid=' . $this->site_id;
        $request_url .= '&usertype=Client';
        $request_url .= '&compact=1';
		if ($support_mode) {
			$email_setting = 'support_icl_account_email';
		} else {
			$email_setting = 'icl_account_email';
		}
		if (!isset($sitepress_settigs[$email_setting])) {
			$current_user = wp_get_current_user();
			$email = $current_user->data->user_email;
		} else {
			$email = $sitepress_settigs[$email_setting];
		}
        $request_url .= '&email=' . $email;
        
        $res = $this->_request($request_url, 'GET');        
        if($res['info']['status']['attr']['err_code']=='0'){
            return $res['info']['session_num']['value'];
        }else{
            return null;
        }
      
    }
    
    function get_reminders($refresh = false) {
        global $wpdb, $sitepress;
        
        // see if we need to refresh the reminders from ICanLocalize
        $icl_settings = $sitepress->get_settings();
        $last_time = $icl_settings['last_icl_reminder_fetch'];
        
        if (!$refresh && ((time() - $last_time) > 60) && $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}icl_reminders w WHERE w.show=1") == 0) {
            $refresh = true;
        }
        
        if (((time() - $last_time) > 10 * 60) || $refresh) {
    
            $request_url = ICL_API_ENDPOINT . '/reminders.xml?accesskey='.$this->access_key.'&wid=' . $this->site_id;
    
            $res = $this->_request($request_url, 'GET');        
            if($res['info']['status']['attr']['err_code']=='3'){
                // not logged in get a new session_id
                $session_id = $this->get_session_id(FALSE);
        
                $request_url = ICL_API_ENDPOINT . '/reminders.xml?accesskey='.$this->access_key.'&wid=' . $this->site_id;
        
                $res = $this->_request($request_url, 'GET');
            }
                
            if($res['info']['status']['attr']['err_code']=='0'){
                
                @mysql_query("TRUNCATE {$wpdb->prefix}icl_reminders");
                
                // First add any low funding warning.
                $website_data = $this->get_website_details();
                if (isset($website_data['unfunded_cms_requests'])) {
                    $missing_funds = $website_data['unfunded_cms_requests']['attr']['missing_funds'];
                    $number_waiting = sizeof($website_data['unfunded_cms_requests']['cms_request']);
                    
                    $r['message'] = sprintf(__('You don\'t have enough funds in your ICanLocalize account - [b]Funds required - $%s[/b]', 'sitepress'), $missing_funds);
                    $r['id'] = -1;
                    $r['can_delete'] = 1;
                    $r['show'] = 1;
                    $r['url'] = '/finance';
                    $wpdb->insert($wpdb->prefix.'icl_reminders', $r);
                }
                // save the translator status
                $sitepress->get_icl_translator_status($icl_settings, $website_data);
                $sitepress->save_settings($iclsettings);               
                
                // Now add the reminders.
                $reminders_xml = $res['info']['reminders']['reminder'];
                if($reminders_xml) {
                    
                    if (sizeof($reminders_xml) == 1) {
                        // fix problem when only on item found
                        $reminders_xml = array($reminders_xml);
                    }
                    
                    foreach($reminders_xml as $r){
    
                        $r['attr']['can_delete'] = $r['attr']['can_delete'] == 'true' ? 1 : 0;
                        $r['attr']['show'] = 1;
                        
                        $wpdb->insert($wpdb->prefix.'icl_reminders', $r['attr']);
                    }
                }
                $last_time = time();
                $sitepress->save_settings(array('last_icl_reminder_fetch' => $last_time));
            }
        }

        // check if low funding is still valid
        if ($wpdb->get_var("SELECT id FROM {$wpdb->prefix}icl_reminders WHERE id=-1") == -1) {
        
            $website_data = $this->get_website_details();
            if (!isset($website_data['unfunded_cms_requests'])) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_reminders WHERE id=-1");
            }
        }

            
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}icl_reminders w WHERE w.show=1 ORDER BY id");
        
    }

    function get_current_session($refresh = false, $support_mode = false) {
        global $sitepress;
    
        // see if we need to refresh the reminders from ICanLocalize
        $icl_settings = $sitepress->get_settings();
        $setting = $support_mode ? 'icl_support_current_session' : 'icl_current_session';
        $last_time = $support_mode ? 'last_icl_support_fetch' : 'last_icl_reminder_fetch';

        if (empty($icl_settings[$setting]) || ($refresh && time() - $icl_settings[$last_time] > 30 * 60)) {
            $session_id = $this->get_session_id($support_mode);
            $new_time = time();
            $sitepress->save_settings(array($setting => $session_id, $last_time => $new_time));
            return $session_id;
        } else {
            return $icl_settings[$setting];
        }
        
    }
    
    function delete_message($message_id) {
        global $wpdb;

        if ((int)$message_id >= 0) {
            $session_id = $this->get_current_session();
    
            $request_url = ICL_API_ENDPOINT . '/reminders/' . $message_id . '.xml?wid='.$this->site_id.'&accesskey=' . $this->access_key;
            
            $data = array('session' => $session_id, 'accesskey' => $this->access_key, 
                          '_method' => 'DELETE');
    
            $res = $this->_request($request_url, 'POST', $data);
            if($res['info']['status']['attr']['err_code']=='3'){
                // not logged in get a new session_id
                $session_id = $this->get_session_id(FALSE);

                $res = $this->_request($request_url, 'POST', $data);
            }

            if($res['info']['result']['value']=='Reminder deleted' ||
                    $res['info']['result']['value']=='Reminder not found'){
                // successfully deleted on the server.
                $wpdb->query("DELETE FROM {$wpdb->prefix}icl_reminders WHERE id={$message_id}");
            }
        
            
        } else {
            // this is the low funding reminder.
            $wpdb->query("DELETE FROM {$wpdb->prefix}icl_reminders WHERE id={$message_id}");
        }
            
    }
    
    function report_back_permalink($request_id, $language, $translation) {
        global $wpdb;
        $request_url = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '/cms_requests/'. $request_id . '/update_permlink.xml';
        
        $parameters['accesskey'] = $this->access_key;
        $parameters['language'] = $language;
        if($wpdb->get_var("SELECT post_type FROM $wpdb->posts WHERE ID={$translation->element_id}")=='page'){
            $parameters['permlink'] = get_option('home') . '?page_id=' . $translation->element_id;
        }else{
            $parameters['permlink'] = get_option('home') . '?p=' . $translation->element_id;
        }
        
        $res = $this->_request($request_url, 'POST', $parameters);
        
    }
    
    function get_help_links() {
        $request_url = 'http://wpml.org/wpml-resource-maps/pro-translation.xml';

        $res = $this->_request($request_url, 'GET');
        
        return $res;
    }
    
    function test_affiliate_info($id, $key){
        $request_url = ICL_API_ENDPOINT . '/websites/validate_affiliate.xml?affiliate_id='.$id.'&affiliate_key=' . $key;
        $res = $this->_request($request_url, 'GET');
        return $res['info']['result']['value'] == 'OK';
    }
}
  
/**
 * gzdecode implementation
 *
 * @see http://hu.php.net/manual/en/function.gzencode.php#44470
 * 
 * @param string $data
 * @param string $filename
 * @param string $error
 * @param int $maxlength
 * @return string
 */
function icl_gzdecode($data, &$filename = '', &$error = '', $maxlength = null) {
    $len = strlen ( $data );
    if ($len < 18 || strcmp ( substr ( $data, 0, 2 ), "\x1f\x8b" )) {
        $error = "Not in GZIP format.";
        return null; // Not GZIP format (See RFC 1952)
    }
    $method = ord ( substr ( $data, 2, 1 ) ); // Compression method
    $flags = ord ( substr ( $data, 3, 1 ) ); // Flags
    if ($flags & 31 != $flags) {
        $error = "Reserved bits not allowed.";
        return null;
    }
    // NOTE: $mtime may be negative (PHP integer limitations)
    $mtime = unpack ( "V", substr ( $data, 4, 4 ) );
    $mtime = $mtime [1];
    $xfl = substr ( $data, 8, 1 );
    $os = substr ( $data, 8, 1 );
    $headerlen = 10;
    $extralen = 0;
    $extra = "";
    if ($flags & 4) {
        // 2-byte length prefixed EXTRA data in header
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $extralen = unpack ( "v", substr ( $data, 8, 2 ) );
        $extralen = $extralen [1];
        if ($len - $headerlen - 2 - $extralen < 8) {
            return false; // invalid
        }
        $extra = substr ( $data, 10, $extralen );
        $headerlen += 2 + $extralen;
    }
    $filenamelen = 0;
    $filename = "";
    if ($flags & 8) {
        // C-style string
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $filenamelen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
        if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
            return false; // invalid
        }
        $filename = substr ( $data, $headerlen, $filenamelen );
        $headerlen += $filenamelen + 1;
    }
    $commentlen = 0;
    $comment = "";
    if ($flags & 16) {
        // C-style string COMMENT data in header
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $commentlen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
        if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
            return false; // Invalid header format
        }
        $comment = substr ( $data, $headerlen, $commentlen );
        $headerlen += $commentlen + 1;
    }
    $headercrc = "";
    if ($flags & 2) {
        // 2-bytes (lowest order) of CRC32 on header present
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $calccrc = crc32 ( substr ( $data, 0, $headerlen ) ) & 0xffff;
        $headercrc = unpack ( "v", substr ( $data, $headerlen, 2 ) );
        $headercrc = $headercrc [1];
        if ($headercrc != $calccrc) {
            $error = "Header checksum failed.";
            return false; // Bad header CRC
        }
        $headerlen += 2;
    }
    // GZIP FOOTER
    $datacrc = unpack ( "V", substr ( $data, - 8, 4 ) );
    $datacrc = sprintf ( '%u', $datacrc [1] & 0xFFFFFFFF );
    $isize = unpack ( "V", substr ( $data, - 4 ) );
    $isize = $isize [1];
    // decompression:
    $bodylen = $len - $headerlen - 8;
    if ($bodylen < 1) {
        // IMPLEMENTATION BUG!
        return null;
    }
    $body = substr ( $data, $headerlen, $bodylen );
    $data = "";
    if ($bodylen > 0) {
        switch ($method) {
            case 8 :
                // Currently the only supported compression method:
                $data = gzinflate ( $body, $maxlength );
                break;
            default :
                $error = "Unknown compression method.";
                return false;
        }
    } // zero-byte body content is allowed
    // Verifiy CRC32
    $crc = sprintf ( "%u", crc32 ( $data ) );
    $crcOK = $crc == $datacrc;
    $lenOK = $isize == strlen ( $data );
    if (! $lenOK || ! $crcOK) {
        $error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
        return false;
    }
    return $data;
}
?>