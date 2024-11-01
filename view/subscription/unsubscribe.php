<?php
// load WP core
require_once(dirname(__FILE__).'/../../../../../wp-load.php');

$notSubscribed = (array_key_exists('ns', $_REQUEST) && (intval($_REQUEST['ns']) == 1)) ? true : false;
$unsubscribeCompletedOk = (array_key_exists('sc', $_REQUEST) && (intval($_REQUEST['sc']) == 1)) ? true : false;
$unsubscribeFailed = (array_key_exists('sf', $_REQUEST) && (intval($_REQUEST['sf']) == 1)) ? true : false;
$unsubscribeFormNeeded = (array_key_exists('uf', $_REQUEST) && (intval($_REQUEST['uf']) == 1)) ? true : false;
$requestId = array_key_exists('rid', $_REQUEST) ? ($_REQUEST['rid']) : null;
if($requestId){
    $requestId = preg_replace("/[^A-Za-z0-9 ]/", '', $requestId); // remove all non-alphanumerical characters
}

$transientId = 'mst_frontend_unsub_confirm'.'_'.$requestId;
$unsubInfo = get_transient($transientId);

if(class_exists('MstFactory')){
    $log = MstFactory::getLogger();
}else{
    $log = null;
}
if($unsubscribeFormNeeded){
    showUnsubscribeDialog($requestId, $unsubInfo);
}elseif($notSubscribed) {
    if($log){
        $log->warning('Unsubscribing failed, hash was not correct, display Email address is not subscribed. Request was: '.print_r($_REQUEST, true));
    }
    fail(__('Email address is not subscribed', 'wp-mailster'));
}elseif($unsubscribeFailed){
    if($log){
        $log->warning('Unsubscription failed. Request was: '.print_r($_REQUEST, true));
    }
    fail(__('Unsubscription failed', 'wp-mailster'));
}elseif($unsubscribeCompletedOk){
    if($log){
        $log->debug('Unsubscription completed / successful');
    }
    success();
}

function showUnsubscribeDialog($requestId, $unsubInfo){
    $query = get_site_url().'?confirm_unsubscribe=indeed2';
    if($unsubInfo){
        $listName = array_key_exists('listName', $unsubInfo) ? $unsubInfo['listName'] : '';
        $email = array_key_exists('email', $unsubInfo) ? $unsubInfo['email'] : '';
    }else{
        $listName = '';
        $email = '';
    }

    $unsubscribeButton = __('Unsubscribe', 'wp-mailster');
    $unsubscribeButton = apply_filters('wpmailster_unsubscribe_dialog_button', $unsubscribeButton, $listName, $email);
    $unsubscribeHeader = __('Unsubscription', 'wp-mailster');
    $unsubscribeHeader = apply_filters('wpmailster_unsubscribe_dialog_header', $unsubscribeHeader, $listName, $email);
    $unsubscribeQuestion = __('Are you sure you want to unsubscribe?', 'wp-mailster');
    $unsubscribeQuestion = apply_filters('wpmailster_unsubscribe_dialog_are_you_sure', $unsubscribeQuestion, $listName, $email);

    ?>
    <h2 class="componentheading mailsterUnsubscriberHeader"><?php echo esc_html($unsubscribeHeader); ?></h2>
    <div class="contentpane">
        <div id="mailsterContainer">
            <div id="mailsterUnsubscriber">
                <div class="unsubscribe_header"><?php echo esc_html($unsubscribeQuestion); ?></div>
                <form action="<?php echo $query; ?>" method="post" >
                    <input type="hidden" value="<?php echo $requestId; ?>" name="rid" />
                    <table id="person_details">
                        <tr>
                            <th><?php _e('Mailing list', 'wp-mailster'); ?></th>
                            <td><?php echo esc_html($listName); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Email', 'wp-mailster'); ?></th>
                            <td><input name="email" type="text" size="45" maxlength="100" value="<?php echo is_null($email) ? '' : $email; ?>" /></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td><td><input type="submit" value="<?php echo esc_attr($unsubscribeButton); ?>"/></td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
<?php
}

function success() {
    $unsubscribeHeader = __('Unsubscription', 'wp-mailster');
    $unsubscribeHeader = apply_filters('wpmailster_unsubscribe_dialog_success_header', $unsubscribeHeader);
    $unsubscribeOkMessage = __('Unsubscription successful', 'wp-mailster');
    $unsubscribeOkMessage = apply_filters('wpmailster_unsubscribe_dialog_success_message', $unsubscribeOkMessage);
	?>
	<h2 class="componentheading mailsterUnsubscriberHeader"><?php echo esc_html($unsubscribeHeader); ?></h2>
	<div class="contentpane">
		<div id="mailsterContainer">
			<div id="mailsterUnsubscriber">
				<div id="mailsterUnsubscriberDescription" class="success"><?php echo esc_html($unsubscribeOkMessage); ?></div>
			</div>
		</div>
	</div>
<?php
}

function fail($error_message) {
    $unsubscribeHeader = __('Unsubscription', 'wp-mailster');
    $unsubscribeHeader = apply_filters('wpmailster_unsubscribe_dialog_failure_header', $unsubscribeHeader);
    $error_message = apply_filters('wpmailster_unsubscribe_dialog_failure_error_msg', $error_message);
	?>
	<h2 class="componentheading mailsterUnsubscriberHeader"><?php echo esc_html($unsubscribeHeader); ?></h2>
	<div class="contentpane">
		<div id="mailsterContainer">
			<div id="mailsterUnsubscriber">
				<div id="mailsterUnsubscriberDescription" class="failure"><?php echo esc_html($error_message); ?></div>
			</div>
		</div>
	</div>
	<?php
}