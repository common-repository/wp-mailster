<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>

<input type="hidden" id="ajax_url" name="ajax_url" value="<?php echo admin_url('admin-ajax.php') ?>">
<table class="form-table">
	<tbody>
		<?php
		$this->mst_display_truefalse_field( __("Notify senders of not forwarded emails", 'wp-mailster'), 'notify_not_fwd_sender', $options->notify_not_fwd_sender, false, __("If an email is not forwarded (e.g. because the email address is not allowed to send or the email is filtered because of its content) the sender can be notified", 'wp-mailster') );
		?>
        <tr>
            <th colspan="2">
                <?php echo __('Custom Notifications', 'wp-mailster'); ?>
            </th>
        </tr>
        <tr>
            <td rowspan="5" style="vertical-align: top;" colspan="3">
                <select id="copyStationUsers" style="display:none;"><?php echo $wpUserOptionsHtml; ?></select>
                <select id="copyStationGroups" style="display:none;"><?php echo $groupsOptionsHtml; ?></select>
                <?php
                if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_NOTIFY)){
                    ?>
                    <table id="notifiesTbl" class="notifiesTbl" style="">
                        <tr>
                            <th>#</th>
                            <th><?php echo __('When?', 'wp-mailster'); ?></th>
                            <th colspan="2"><?php echo __('Notify who?', 'wp-mailster'); ?></th>
                            <th>&nbsp;</th>
                        </tr>
                        <?php
                        for($i=0; $i<count($notifies); $i++){
                            $notify = &$notifies[$i];
                            ?>

                            <tr id="notifiesTbl_row<?php echo $i;?>">
                                <td style="width:5%;"><?php echo ($i+1);?></td>
                                <td><select id="triggerType<?php echo $i;?>" name="triggerType<?php echo $i;?>" class="triggerTypeClass" style="width:150px;"><?php echo $notify->triggerTypes; ?></select></td>
                                <td><select id="targetType<?php echo $i;?>" name="targetType<?php echo $i;?>" class="targetTypeClass"  style="width:150px;"><?php echo $notify->targetTypes; ?></select></td>
                                <td><select id="targetId<?php echo $i;?>" name="targetId<?php echo $i;?>" class="targetIdClass" style="width:150px;<?php echo $notify->target_type == 0 ? 'display:none;' : ''; ?>"><?php echo property_exists($notify, 'targetChoice') ? $notify->targetChoice : ''; ?></select></td>
                                <td>
                                    <a id="removeNotifyButton<?php echo $i;?>" href="#" class="notifierRemoverClass"><span class="wpmst-dashicons dashicons dashicons-minus">&nbsp;</span><?php echo __('Delete', 'wp-mailster'); ?></a>
                                    <input id="notifyId<?php echo $i;?>" name="notifyId<?php echo $i;?>" value="<?php echo $notify->id;?>" type="hidden">
                                    <div tabindex="-1" id="removeNotifyButtonProgressIndicator<?php echo $i;?>" style="display:inline; margin:5px; padding-right:20px;min-height:30px;width:30px;">&nbsp;</div>
                                </td>
                            </tr>

                        <?php
                        }
                        ?>
                    </table>
                <?php
                }else{
                    $imgPath = $this->WPMST_PLUGIN_DIR_URL.'/asset/images/';
                    ?>
                    <img src="<?php echo $imgPath . 'notify_mockup.png'; ?>" style="vertical-align:middle;" title="<?php echo sprintf(__( "Available in %s", 'wp-mailster' ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_NOTIFY)); ?>"/>
                <?php
                }
                ?>
            </td>
            <td style="vertical-align: top;">
                <?php
                $displayEmpty = true;
                if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_NOTIFY)){
                    $displayEmpty = false;
                    ?>
                    <a id="addNotifyButton" href="#"><span class="wpmst-dashicons dashicons dashicons-plus-alt">&nbsp;</span> <?php echo __('Add Notification', 'wp-mailster'); ?></a>
                <?php
                }
                ?>
            </td>
            <td colspan="2">&nbsp;</td>
        </tr>
	</tbody>
</table>