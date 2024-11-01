<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
global $wpdb;

$log = MstFactory::getLogger();

include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelDigest.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelList.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelUser.php";

$messages = array();

$digestModel = new MailsterModelDigest();
$mailModel = MstFactory::getModel('mail');
$User = new MailsterModelUser();

$digest = new stdClass();
$digest->id = 0;
$digest->list_id = 0;
$digest->user_id = 0;
$digest->is_core_user = 0;
$digest->digest_freq = MstConsts::DIGEST_NO_DIGEST;
$digest->last_send_date = null;
$digest->next_send_date = null;
$digest->digestFreqStr = null;
$digest->summaryStr = null;

if( isset( $_POST['user_action'] ) ) { //if form is submitted
    $log->debug('digest dialog save done, POST: '.print_r($_POST, true));
    $digest->id = intval($_POST['id']);
    if($digest->id){
        $digest = $digestModel->getDigest($digest->id);
    }
    $digest->user_id = intval($_POST['user_id']);
    $digest->is_core_user = intval($_POST['is_core_user']);
    $digest->list_id = intval($_POST['list_id']);
    $digest->digest_freq = intval($_POST['digest_freq']);

    $digest->id = $digestModel->store($digest);
    $digest = $digestModel->getDigest($digest->id); // load again to get updated next send data and so on
    $button = __('Save Digest', 'wp-mailster');
}else{
    $did   = ((isset($_GET['did']) && $_GET['did']!='') ? intval($_GET['did']) : ''); // Digest ID
    $uid   = ((isset($_GET['uid']) && $_GET['uid']!='') ? intval($_GET['uid']) : ''); // User ID
    $ic   = ((isset($_GET['ic']) && $_GET['ic']!='') ? intval($_GET['ic']) : ''); // Is Core User Flag
    $lid  = ((isset($_GET['lid']) && $_GET['lid']!='') ? intval($_GET['lid']) : ''); // List ID
    if($did) {
        $digest = $digestModel->getDigest($did);
        $button = __('Save Digest', 'wp-mailster');
        /* Example:
            [id] => 1
            [list_id] => 1
            [user_id] => 1
            [is_core_user] => 1
            [digest_freq] => 2
            [last_send_date] => 2018-04-24 22:28:58
            [next_send_date] => 2018-04-29 00:00:00
            [digestFreqStr] => Weekly digest
            [summaryStr] => Last week's topic summary
        */
    } else {
        $button = __('Add Digest', 'wp-mailster');
        $digest->list_id = $lid;
        $digest->user_id = $uid;
        $digest->is_core_user = $ic;
    }
}

if($digest->list_id) {
    $list = new MailsterModelList($digest->list_id);
} else {
    $list = new MailsterModelList();
}
$listData = $list->getData();
$userData = $User->getUserData($digest->user_id, $digest->is_core_user);


/** @var MailsterModelList $listsModel */
$listsModel = MstFactory::getModel('lists');
$allLists = $listsModel->getAll();
$listOptions = array();
foreach($allLists as $list){
    $listOptions[$list->id] = $list->name;
}

if( is_null($userData->name) || trim($userData->name)  == "" ) {
    $username = __("(no name)", 'wp-mailster');
} else {
    $username = $userData->name;
}
$log->debug('digest_mgmt digest: '.print_r($digest, true));

?>
<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $listData[0]->name . " - " . $username . " - " .__("Manage Digest", 'wp-mailster'); ?></h2>
		<?php $this->wpmst_print_messages($messages); ?>
		<div id="mst_digest_management" class="mst_listing mst_digest_management">
			<div class="wptl_container">
				<div class="wrap">
                    <form action="" method="post">
                        <table class="form-table">
                            <tbody>
                            <tr class="">
                                <th scope="row">
                                    <label for="user_name"><?php echo __("Mailing List", 'wp-mailster'); ?> </label>
                                </th>
                                <td>
                                    <?php echo $listData[0]->name ; ?>
                                </td>
                            </tr>
                            <tr class="">
                                <th scope="row">
                                    <label for="user_origin"><?php echo __("User data origin", 'wp-mailster'); ?> </label>
                                </th>
                                <td>
                                    <?php echo ($digest->is_core_user == 1 ? 'WordPress' : 'WP Mailster'); ?>
                                </td>
                            </tr>
                            <tr class="">
                                <th scope="row">
                                    <label for="user_name"><?php echo __("Name", 'wp-mailster'); ?> </label>
                                </th>
                                <td>
                                    <?php echo $username; ?>
                                </td>
                            </tr>
                            <tr class="">
                                <th scope="row">
                                    <label for="user_email"><?php echo __("Email", 'wp-mailster'); ?> </label>
                                </th>
                                <td>
                                    <?php echo $userData->email; ?>
                                </td>
                            </tr>
                            <tr class="">
                                <th scope="row">
                                    <label for="digest_freq"><?php echo __("Digest Frequency", 'wp-mailster'); ?> </label>
                                </th>
                                <td>
                                    <?php echo $digestModel->getDigestChoiceHtml($digest->digest_freq); ?>
                                </td>
                            </tr>
                            <tr class="">
                                <th scope="row">
                                    <label for="last_send_date"><?php echo __("Digest last sent", 'wp-mailster'); ?> </label>
                                </th>
                                <td>
                                    <?php echo (!is_null($digest->last_send_date) ? $digest->last_send_date : '-'); ?>
                                </td>
                            </tr>
                            <tr class="">
                                <th scope="row">
                                    <label for="last_send_date"><?php echo __("Digest next sending", 'wp-mailster'); ?> </label>
                                </th>
                                <td>
                                    <?php echo (!is_null($digest->next_send_date) ? $digest->next_send_date : '-'); ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <a href="<?php echo admin_url(); ?>admin.php?page=mst_mailing_lists&amp;subpage=recipients&amp;lid=<?php echo $digest->list_id; ?>"><?php _e("Back", 'wp-mailster'); ?></a>
                        <input type="submit" class="button-primary" name="user_action" value="<?php echo $button; ?>">
                        <?php
                        $this->mst_display_hidden_field( "id", $digest->id );
                        $this->mst_display_hidden_field( "user_id", $digest->user_id );
                        $this->mst_display_hidden_field( "is_core_user", $digest->is_core_user );
                        $this->mst_display_hidden_field( "list_id", $digest->list_id );
                        ?>
                    </form>
                </div>
			</div>
            <div>
                <?php
                $digestMailQueue = array();
                if($digest->id){
                    $mstQueue = MstFactory::getMailQueue();
                    $digestMailQueue= $mstQueue->getMailQueueForDigest($digest->id);
                    for($i=0;$i<count($digestMailQueue);$i++){
                        $mail = $mailModel->getData($digestMailQueue[$i]->mail_id);
                        $digestMailQueue[$i]->mail_subject = $mail->subject;
                    }
                ?>
                <h3><?php echo __("Digest Queue", 'wp-mailster'); ?></h3>
                <table class="widefat fixed table table-striped" cellspacing="1">
                    <thead>
                    <tr>
                        <th class="title"><?php echo __("Subject", 'wp-mailster'); ?></th>
                        <th><?php echo __("Date", 'wp-mailster'); ?></th>
                        <th><?php echo __("Mail ID", 'wp-mailster'); ?></th>
                        <th><?php echo __("ID", 'wp-mailster') . ' (' . __("Queue", 'wp-mailster') .')'; ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $k=0;
                    for($i=0, $n=count( $digestMailQueue ); $i < $n; $i++) {
                        $digestMail = $digestMailQueue[$i];
                        ?>
                        <tr class="alternate <?php echo "row$k"; ?>">
                            <td><?php echo $digestMail->mail_subject; ?></td>
                            <td><?php echo $digestMail->digest_time; ?></td>
                            <td><?php echo $digestMail->mail_id; ?></td>
                            <td><?php echo $digestMail->id; ?></td>
                        </tr>
                        <?php
                        $k = 1 - $k;
                    }
                    if(count($digestMailQueue) == 0){ ?>
                        <tr>
                            <td colspan="4"><?php echo __("Queue empty", 'wp-mailster'); ?></td>
                        </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    </tfoot>
                </table>
                <?php
                }
                ?>
            </div>
		</div>
    </div>
</div>
<script type="text/javascript">
</script>