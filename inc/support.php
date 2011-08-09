<?php
if (!class_exists('WP_Http')) {
    include_once(ABSPATH . WPINC . '/class-http.php');
}
require_once ICL_PLUGIN_PATH . '/lib/xml2array.php';

class SitePress_Support
{

    var $data;
    var $site_id = 0;
    var $access_key = 0;
    var $tickets = array();
    var $fetched_tickets = array();
    var $request;
    var $initial;

    function __construct() {
        global $sitepress, $sitepress_settings;
        $sp_settings = get_option('icl_sitepress_settings');
        $this->data = $sp_settings['icl_support'];
        $this->request = new WP_Http;

        if ($sitepress->icl_support_configured()) {
            $this->site_id = $sp_settings['support_site_id'];
            $this->access_key = $sp_settings['support_access_key'];
        }

        if (isset($_GET['page']) && $_GET['page'] == ICL_PLUGIN_FOLDER . '/menu/support.php') {
            wp_enqueue_script('sitepress-icl_reminders', ICL_PLUGIN_URL . '/res/js/icl_reminders.js', array('jquery'), ICL_SITEPRESS_VERSION);
            add_action('icl_support_admin_page', array(&$this, 'admin_page'));
        }
    }

    function admin_page() {
        global $sitepress;
        if (isset($_POST['icl_configure_support_account_data_nonce'])
                && $_POST['icl_configure_support_account_data_nonce']
                == wp_create_nonce('icl_configure_support_account_data')
                && isset($_POST['icl_support_site_id'])
                && isset($_POST['icl_support_access_key'])) {
            $sitepress->save_settings(array('support_site_id' => $_POST['icl_support_site_id'],
                'support_access_key' => $_POST['icl_support_access_key']));
            $this->site_id = $_POST['icl_support_site_id'];
            $this->access_key = $_POST['icl_support_access_key'];
            echo '<script type="text/javascript">location.href = "admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php";</script>';
        } else if (isset($_POST['icl_support_account']) && $sitepress->icl_support_configured()) {
            $sitepress->save_settings(array('support_icl_account_created' => 1));
            if ($_POST['icl_support_account'] == 'create') {
                if (!isset($_POST['icl_support_subscription_type'])) {
                    $_POST['icl_support_subscription_type'] = 1;
                }
                $this->data['subscription_type'] = $_POST['icl_support_subscription_type'];
                $sitepress->save_settings(array('icl_support' => $this->data));
            }
            echo '<script type="text/javascript">location.href = "admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php";</script>';
            return;
        } else if ((isset($_POST['icl_support_account']) && $_POST['icl_support_account'] == 'create') || isset($_GET['subscription'])) {
            $this->create_account_form();
            return;
        }

        if (isset($_GET['reset'])) {
            $this->data['subscription_type'] = 0;
            $sitepress->save_settings(array('icl_support' => $this->data));
            echo '<script type="text/javascript">location.href = "admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php";</script>';
        }

        if (!$this->check_subscription()) {
            if ($this->site_id && $this->data['subscription_type']) {
                _e('Your password is sent to your e-mail.', 'sitepress');
                echo '<br /><br />';
                switch ($this->data['subscription_type']) {
                    case 2:
                        echo '<a href="#" onclick="javascript:location.href=\'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php&amp;reset=1\'" class="button">' . __('Cancel', 'sitepress') . '</a>&nbsp;&nbsp;&nbsp;&nbsp;' . $this->thickbox('subscriptions/new?wid=' . $this->site_id . '&amp;code=2', ' icl_support_buy_link');
                        printf(__('Buy \'developer\' subscription %s / year', 'sitepress'), '$200');
                        echo '</a>';
                        break;
                    default:
                        echo '<a href="#" onclick="javascript:location.href=\'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php&amp;reset=1\'" class="button">' . __('Cancel', 'sitepress') . '</a>&nbsp;&nbsp;&nbsp;&nbsp;' . $this->thickbox('subscriptions/new?wid=' . $this->site_id . '&amp;code=1', ' icl_support_buy_link');
                        printf(__('Buy \'single site\' subscription %s / year', 'sitepress'), '$50');
                        echo '</a>';
                }
                return;
            }
            $this->offer_subscription();
            if (!$this->site_id) {
                $this->login_to_account_form();
            }
        } else {
            if (isset($this->data['tickets'])) {
                $this->tickets = $this->data['tickets'];
            }
            $url = 'websites/' . $this->site_id . '/new_ticket';
            echo '<p>' . $this->thickbox($url, ' icl_support_create_ticket_link') . __('Create new ticket', 'sitepress') . '</a></p>';
            $this->get_tickets();
            if (!empty($this->tickets)) {
                $this->render_tickets();
            }
        }
        $this->configure_account_form();
        echo '<p style="width: 410px; margin-top: 20px;">' . sprintf(__('For advanced access or to completely uninstall WPML and remove all language information, use the <a href="%s">troubleshooting</a> page.', 'sitepress'),
                'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/troubleshooting.php') . '</p>';
    }

    function request($url) {
        $result = $this->request->request($url);
        if (!is_object($result)) {
            return icl_xml2array($result['body'], 1);
        } else {
            return array();
        }
    }

    function thickbox($url, $class = null, $id = null) {
        if (!$this->site_id) {
            parse_str(htmlspecialchars_decode($url), $var);
            if (!isset($var['code'])) {
                $var['code'] = 1;
            }
            return '<a href="admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php&amp;subscription=' . $var['code'] . '&amp;support=1">';
        }
        global $sitepress;
        return '<a href="' . $sitepress->create_icl_popup_link(ICL_API_ENDPOINT . '/' . $url, array('title' => 'ICanLocalize', 'class' => $class, 'id' => $id), TRUE, TRUE) . '&amp;support=1" class="icl_thickbox ' . $class . '" title="ICanLocalize">';
    }

    function thickbox2($url, $class = null, $id = null) {
        if (strpos($url, '?') !== false) {
            $add = '&amp;';
        } else {
            $add = '?';
        }
        $id = ($id) ? ' id="' . $id . '"' : '';
        return '<a href="' . $url . $add . 'TB_iframe=true" class="thickbox icl_regular_thickbox' . $class . '" title="' . $url . '"' . $id . '>';
    }

    function process_tickets($tickets) {
        if (isset($tickets['support_ticket'][0])) {
            $tickets = $tickets['support_ticket'];
        }
        foreach ($tickets as $k => $v) {
            $temp[$v['attr']['id']] = $v['attr'];
        }
        return $temp;
    }

    function get_subscriptions() {
        $url = ICL_API_ENDPOINT . '/subscriptions.xml?wid=' . $this->site_id . '&accesskey=' . $this->access_key;
        $result = $this->request($url);
        return $result['info']['subscriptions'];
    }

    function get_subscription() {
        $subscriptions = $this->get_subscriptions();
        if (empty($subscriptions)) {
            return false;
        } else {
            if (isset($subscriptions['subscription'][0])) {
                $subscriptions = $subscriptions['subscription'];
            }
            foreach ($subscriptions as $k => $v) {
                if ($v['attr']['owner_id'] == $this->site_id) {
                    $valid = ($v['attr']['valid'] == 'true') ? true : false;
                    return array(
                        'valid' => $valid,
                        'expires' => $v['attr']['expires_date'],
                        'amount' => $v['attr']['amount']
                    );
                }
            }
            return false;
        }
    }

    function check_subscription() {
        $subscriptions = $this->get_subscriptions();
        if (empty($subscriptions)) {
            return false;
        } else {
            if (isset($subscriptions['subscription'][0])) {
                $subscriptions = $subscriptions['subscription'];
            }
            foreach ($subscriptions as $k => $v) {
                if (($v['attr']['owner_id'] === '' || $v['attr']['owner_id'] == $this->site_id) && $v['attr']['valid'] == 'true') {
                    printf(__('Your subscription is valid until %s', 'sitepress'), date(get_option('date_format'), $v['attr']['expires_date']));
                    return true;
                }
                if (($v['attr']['owner_id'] === '' || $v['attr']['owner_id'] == $this->site_id) && $v['attr']['valid'] == 'false') {
                    // TODO
                    $this->offer_renewal();
                    return false;
                }
            }
            return false;
        }
    }

    function offer_subscription() {
        $subscription_rows = array(
            array(__('24h support', 'sitepress'), 'http://wpml.org/?page_id=3933'),
            array(__('Support tickets to WPML developers', 'sitepress'), 'http://wpml.org/?page_id=4799'),
            array(__('Consultation and planning', 'sitepress'), 'http://wpml.org/?page_id=4802'),
            array(__('Help troubleshooting', 'sitepress'), 'http://wpml.org/?page_id=4806')
        );

?>

        <p style="line-height:1.5"><?php _e('In order to get premium support, you need to create a support subscription.', 'sitepress'); ?>
            <br /><?php _e('A support subscription gives you 24h response directly from WPML\'s developers.', 'sitepress'); ?></p>
        <br /><br />
        <table id="icl_support_subscriptions" cellspacing="0" cellpadding="0" border="0">
            <tr class="title">
                <td class="first">&nbsp;</td>
                <td class="smaller-heading"><h2><?php _e('Community Support', 'sitepress'); ?></h2></td>
                <td><h2><?php _e('Single Site Support', 'sitepress'); ?></h2></td>
                <td class="last"><h2><?php _e('Developer Support', 'sitepress'); ?></h2></td>
            </tr>
            <tr class="info">
                <td class="first"><?php printf(__('Community support via %s WPML\'s technical forum %s', 'sitepress'), $this->thickbox2('http://forum.wpml.org/'), '</a>'); ?></td>
                <td><?php _e('Free', 'sitepress'); ?></td>
                <td><?php _e('Free', 'sitepress'); ?></td>
                <td class="last"><?php _e('Free', 'sitepress'); ?></td>
            </tr>
    <?php foreach ($subscription_rows as $row) { ?>
            <tr class="info">
                <td class="first"><?php
            if (isset($row[1])) {
                echo $this->thickbox2($row[1]);
            }
            echo $row[0];
            if (isset($row[1])) {
                echo '</a>';
            }

    ?></td>
        <td><?php _e('Not included', 'sitepress'); ?></td>
        <td><?php _e('Included', 'sitepress'); ?></td>
        <td class="last"><?php _e('Included', 'sitepress'); ?></td>
    </tr>
    <?php } ?>
        <tr class="info">
            <td class="first"><?php _e('Number of sites', 'sitepress'); ?></td>
            <td>&nbsp;</td>
            <td><?php _e('One site', 'sitepress'); ?></td>
            <td class="last"><?php _e('Unlimited sites', 'sitepress'); ?></td>
        </tr>
        <tr class="buy-link">
            <td class="first">&nbsp;</td>
            <td>&nbsp;</td>
            <td><?php echo $this->thickbox('subscriptions/new?wid=' . $this->site_id . '&amp;code=1');
        printf(__('Buy %s / year', 'sitepress'), '$50'); ?></a></td>
        <td class="last"><?php echo $this->thickbox('subscriptions/new?wid=' . $this->site_id . '&amp;code=2');
            printf(__('Buy %s / year', 'sitepress'), '$200'); ?></a></td>
    </tr>
</table>

<?php
        }

        function offer_renewal() {
            echo '<p>';
            _e('Renew your subscription', 'sitepress');
            echo '</p>';
        }

        function get_tickets() {
            global $sitepress;
            $url = ICL_API_ENDPOINT . '/support.xml?wid=' . $this->site_id . '&accesskey=' . $this->access_key;
            $result = $this->request($url);
            if (isset($result['info']['support_tickets']['support_ticket'])) {
                $this->fetched_tickets = $this->process_tickets($result['info']['support_tickets']);
            } else {
                return array();
            }
            if (empty($this->tickets)) {
                $this->data['tickets'] = $this->tickets = $this->fetched_tickets;
                $sitepress->save_settings(array('icl_support' => $this->data));
                $this->initial = true;
            }
            foreach ($this->fetched_tickets as $id => $v) {
                if (!isset($this->tickets[$id]) && $v['status'] !== 0) {
                    $this->data['tickets'][$id] = $this->tickets[$id] = $this->fetched_tickets[$id];
                    $sitepress->save_settings(array('icl_support' => $this->data));
                }
            }
        }

        function render_tickets() {
            //[messages] [status] [subject] [create_time] [id]

?>
            <table id="icl_support_table" class="widefat" cellspacing="0">
                <thead>
                    <tr>
                        <th><?php _e('Subject', 'sitepress'); ?></th>
                        <th><?php _e('Created', 'sitepress'); ?></th>
                        <th><?php _e('Messages', 'sitepress'); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php _e('Subject', 'sitepress'); ?></th>
                        <th><?php _e('Created', 'sitepress'); ?></th>
                        <th><?php _e('Messages', 'sitepress'); ?></th>
                    </tr>
                </tfoot>
                <tbody>
        <?php
            $url = 'support/show/';
            $updated_tickets = '';
            $tickets = '';

            foreach ($this->tickets as $id => $v) {
                if (!isset($this->fetched_tickets[$id]) || $this->fetched_tickets[$id]['status'] === 0) {
                    unset($this->data['tickets'][$id]);
                    $update = true;
                    continue;
                }
                if (!$this->initial && $v['messages'] != $this->fetched_tickets[$id]['messages']) {
                    $check_user_message = $this->request(ICL_API_ENDPOINT . '/' . $url . $v['id'] . '.xml?wid=' . $this->site_id . '&accesskey=' . $this->access_key);
                    if ($check_user_message['info']['support_ticket']['attr']['last_message_by_user'] == 'true') {
                        $tickets .= '<tr><td>' . $this->thickbox($url . $v['id']) . $v['subject'] . '</a></td><td>' . date(get_option('date_format'), $v['create_time']) . '</td><td>' . $this->fetched_tickets[$id]['messages'] . '</td></tr>';
                        $this->data['tickets'][$id]['messages'] = $this->fetched_tickets[$id]['messages'];
                        $update = true;
                        continue;
                    }
                    $add = ' style="background-color: Yellow;"';
                    $add3 = ' icl_support_viewed';
                    $add2 = '<strong><span style="color: Red;">' . __('New message', 'sitepress') . '</span></strong>';
                    $add4 = 'icl_support_ticket_' . $v['id'] . '_' . $this->fetched_tickets[$id]['messages'];
                    $updated_tickets .= '<tr' . $add . '><td>' . $this->thickbox($url . $v['id'], $add3, $add4) . $v['subject'] . '</a></td><td>' . date(get_option('date_format'), $v['create_time']) . '</td><td>' . $this->fetched_tickets[$id]['messages'] . '&nbsp;' . $add2 . '</td></tr>';
                } else {
                    $tickets .= '<tr><td>' . $this->thickbox($url . $v['id']) . $v['subject'] . '</a></td><td>' . date(get_option('date_format'), $v['create_time']) . '</td><td>' . $v['messages'] . '</td></tr>';
                }
            }

            echo $updated_tickets . $tickets;
            if ($update) {
                global $sitepress;
                $sitepress->save_settings(array('icl_support' => $this->data));
            }

        ?>
        </tbody>
    </table>
<?php
        }

        function form_errors() {
            if (isset($_POST['icl_form_errors'])) {

?>
                <div class="icl_form_errors">
<?php echo $_POST['icl_form_errors'] ?>
            </div>
<?php
            }
        }

        function create_account_form() {
            global $current_user;
            $this->form_errors();

?>
            <form id="icl_create_account" method="post" action="">
<?php wp_nonce_field('icl_create_support_account', 'icl_create_support_account_nonce'); ?>    
            <input type="hidden" name="icl_support_account" value="create" />
    <?php
            if (!isset($_REQUEST['subscription'])) {
                $_REQUEST['subscription'] = 1;
            }

    ?>
            <input type="hidden" name="icl_support_subscription_type" value="<?php echo $_REQUEST['subscription']; ?>" />
            <p style="line-height:1.5"><?php _e('Premium support is provided by ICanLocalize. You will need to create an account to get started.', 'sitepress'); ?><br />
<?php _e('WPML will use this account to create support tickets and connect you with the development team.', 'sitepress'); ?></p>

        <table class="form-table icl-account-setup">
            <tbody>
                <tr class="form-field">
                    <th scope="row"><?php _e('First name', 'sitepress'); ?></th>
                    <td><input name="user[fname]" type="text" value="<?php echo $_POST['user']['fname'] ? $_POST['user']['fname'] : $current_user->first_name; ?>" /></td>
                </tr>
                <tr class="form-field">
                    <th scope="row"><?php _e('Last name', 'sitepress'); ?></th>
                    <td><input name="user[lname]" type="text" value="<?php echo $_POST['user']['lname'] ? $_POST['user']['lname'] : $current_user->last_name; ?>" /></td>
                </tr>
                <tr class="form-field">
                    <th scope="row"><?php _e('Email', 'sitepress'); ?></th>
                    <td><input name="user[email]" type="text" value="<?php echo $_POST['user']['email'] ? $_POST['user']['email'] : $current_user->data->user_email; ?>" /></td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="hidden" name="create_account" value="1" />
            <a href="#" onclick="javascript:location.href='<?php echo 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php'; ?>'" class="button"><?php _e('Cancel', 'sitepress'); ?></a>
            <input class="button" name="create account" value="<?php _e('Create account', 'sitepress'); ?>" type="submit" />
        </p>
        <div class="icl_progress" style="display:none;"><?php _e('Saving. Please wait...', 'sitepress'); ?></div>
    </form>


<?php
        }

        function configure_account_form() {
            global $current_user;

?>
            <br /><br />
            <a href="javascript:;" onclick="jQuery(this).next('div').slideToggle();" class="button"><?php _e('Configure account', 'sitepress'); ?></a>
            <div style="display:none;">
                <form id="icl_configure_account" action="" method="post">
<?php wp_nonce_field('icl_configure_support_account_data', 'icl_configure_support_account_data_nonce'); ?>
            <input type="hidden" name="icl_support_account" value="configure" />
            <table class="form-table icl-account-setup">
                <tbody>
                    <tr class="form-field">
                        <th scope="row"><?php _e('Site ID', 'sitepress'); ?></th>
                        <td><input type="text" name="icl_support_site_id" value="<?php echo $this->site_id ? $this->site_id : ''; ?>" /></td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row"><?php _e('Access key', 'sitepress'); ?></th>
                        <td><input type="text" name="icl_support_access_key" value="<?php echo $this->access_key ? $this->access_key : ''; ?>" /></td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="hidden" name="configure_account" value="1" />
                <a href="#" onclick="javascript:location.href='<?php echo 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php'; ?>'" class="button"><?php _e('Cancel', 'sitepress'); ?></a>
                <input class="button" name="configure account" value="<?php _e('Save', 'sitepress'); ?>" type="submit" />
            </p>
        </form>
    </div>
<?php
        }

        function login_to_account_form() {
            $this->form_errors();
            global $current_user;

?>
            <br /><br />
            <a href="javascript:;" onclick="jQuery('#icl_support_form_show').slideToggle();" class="button"><?php _e('I already have an ICanLocalize account', 'sitepress'); ?></a>
            <div id="icl_support_form_show" style="display:none;">
                <form id="icl_configure_account" action="" method="post">
<?php wp_nonce_field('icl_configure_support_account', 'icl_configure_support_account_nonce'); ?>
            <input type="hidden" name="icl_support_account" value="configure" />
            <table class="form-table icl-account-setup">
                <tbody>
                    <tr class="form-field">
                        <th scope="row"><?php _e('Email', 'sitepress'); ?></th>
                        <td><input name="user[email]" type="text" value="<?php echo $_POST['user']['email'] ? $_POST['user']['email'] : $current_user->data->user_email; ?>" /></td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row"><?php _e('Password', 'sitepress'); ?></th>
                        <td><input name="user[password]" type="password" /></td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="hidden" name="create_account" value="0" />
                <a href="#" onclick="javascript:location.href='<?php echo 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php'; ?>'" class="button"><?php _e('Cancel', 'sitepress'); ?></a>
                <input class="button" name="configure account" value="<?php _e('Log in to my account', 'sitepress'); ?>" type="submit" />
            </p>
        </form>
    </div>
<?php
        }

    }