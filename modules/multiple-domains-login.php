<?php
add_filter('login_url', 'emw_login_url', 10, 2);
add_action('wp_authenticate', 'emw_intercept_login', 10, 1);
add_action('wp_logout', 'emw_intercept_logout');
add_action('plugins_loaded', 'emw_check_cross_domain_login', 1);

function emw_login_url ($login_url, $redirect) {
    global $sitepress_settings;
    $domains = $sitepress_settings['language_domains'];
    if ($domains) {
        $domains[$sitepress_settings['default_language']] = get_option('siteurl');
        $login_url = $domains[ICL_LANGUAGE_CODE].'/wp-login.php';
    }
    if ($redirect != '')
        $login_url .= '?redirect_to='.$redirect;
    return $login_url;
}

function emw_intercept_login ($username) {
    global $sitepress_settings;
    if (user_pass_ok($username, $_POST['pwd'])) {
        wp_set_auth_cookie(get_profile('ID', $username), $_POST['rememberme'], is_ssl());
        $domains = $sitepress_settings['language_domains'];
        if ($domains) {
            $time = floor(time()/10);
            $_languages=icl_get_languages('skip_missing=0');
            foreach($_languages as $l){
                $languages[] = $l;
            }
            $next_domain = $domains[$languages[1]['language_code']];
            $parts = parse_url($next_domain);
            $options['nonce'] = md5($parts['scheme'] . '://' . $parts['host']."-{$username}-{$time}");
            $options['redirect'] = $_REQUEST['redirect_to'];
            $options['remember'] = $_POST['rememberme'];
            $options['language_number'] = 1;
            update_option('emw_login', $options);
            header ('HTTP/1.1 301 Moved Permanently');
            header ('Location: '.$next_domain."?emw-login&user={$username}&nonce={$options['nonce']}");
            die();
        }
    }
}

function emw_intercept_logout () {
    global $sitepress_settings;
    $domains = $sitepress_settings['language_domains'];
    if ($domains) {
        $_languages=icl_get_languages('skip_missing=0');
        foreach($_languages as $l){
            $languages[] = $l;
        }
        $next_domain = $domains[$languages[1]['language_code']];
        wp_clear_auth_cookie();
        header ('HTTP/1.1 301 Moved Permanently');
        header ('Location: '.$next_domain.'?emw-logout&next_language=1&redirect_to='.$_GET['redirect_to']);
        die();
    }
}

function emw_check_cross_domain_login () {
    global $sitepress_settings;
    if (isset($_REQUEST['emw-login'])) {
        $options = get_option('emw_login');
        $username = $_GET['user'];
        $time = floor(time()/10);
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
        $hash1 = md5("http{$https}://".$_SERVER['SERVER_NAME']."-{$username}-{$time}");
        $time = $time-1;
        $hash2 = md5("http://".$_SERVER['SERVER_NAME']."-{$username}-{$time}");
        if ($options['nonce'] == $hash1 | $options['nonce'] == $hash2) {
            if ($_GET['nonce'] == $hash1 | $_GET['nonce'] == $hash2) {
                wp_set_auth_cookie(get_profile('ID', $username), $options['remember'], is_ssl());
                $domains = $sitepress_settings['language_domains'];
                if ($domains) {
                    $_languages=icl_get_languages('skip_missing=0');
                    foreach($_languages as $l){
                        $languages[] = $l;
                    }                    
                    if (isset($languages[$options['language_number']+1])) {
                        $next_domain = $domains[$languages[$options['language_number']+1]['language_code']];
                        $options['nonce'] = md5($next_domain."-{$username}-{$time}");
                        $options['remember'] = $_POST['rememberme'];
                        $options['language_number'] = $options['language_number']+1;
                        update_option('emw_login', $options);
                        header ('HTTP/1.1 301 Moved Permanently');
                        header ('Location: '.$next_domain."?emw-login&user={$username}&nonce={$options['nonce']}");
                        die();
                    } else {
                        delete_option ('emw_login');
                        header ('HTTP/1.1 301 Moved Permanently');
                        header ('Location: '.$options['redirect']);
                        die();
                    }
                }
            } else {
                delete_option ('emw_login');
                wp_die(__('Possible login hack attempt','sitepress'));
            }
        } else {
            delete_option ('emw_login');
            wp_die(__('Possible login hack attempt','sitepress'));
        }
    } elseif (isset($_REQUEST['emw-logout'])) {
        $domains = $sitepress_settings['language_domains'];
        if ($domains) {
            $languages=icl_get_languages('skip_missing=0');
            $_languages=icl_get_languages('skip_missing=0');
            foreach($_languages as $l){
                $languages[] = $l;
            }            
            $language_index = $_GET['next_language']+1;            
            if (isset($languages[$language_index]['language_code'])) {
                $next_domain = $domains[$languages[$language_index]['language_code']];
                wp_clear_auth_cookie();
                header ('HTTP/1.1 301 Moved Permanently');
                header ('Location: '.$next_domain.'?emw-logout&next_language='.$language_index.'&redirect_to='.$_GET['redirect_to']);
                die();
            } else {
                wp_clear_auth_cookie();
                header ('HTTP/1.1 301 Moved Permanently');
                if ($_GET['redirect_to'])
                    header ('Location: '.$_GET['redirect_to']);
                else
                    header ('Location: '.get_option('siteurl'));
                die();
            }
        }
    }
}
?>