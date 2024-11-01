<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}

$modDisabled = false;
if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_MODERATE)){
    $options->mod_mode = MstConsts::MODERATION_MODE_NO_MODERATION;
    echo '<p>'.sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_MODERATE)).'</p>';
    $modDisabled = true;
}
?>
<table class="form-table">
    <tbody>
        <tr class="" >
			<th scope="row">
				<?php echo __("Moderation Mode", 'wp-mailster'); ?>
			</th>
            <td><?php
                $this->mst_display_simple_radio_field('mod_mode',
                    MstConsts::MODERATION_MODE_NO_MODERATION,
                    'mod_no_moderation',
                    __("No moderation", 'wp-mailster'),
                    ($options->mod_mode == MstConsts::MODERATION_MODE_NO_MODERATION),
                    false,
                    __("Per default all messages get forwarded to the list without prior approval of the message content", 'wp-mailster'));?><br/><?php
                $this->mst_display_simple_radio_field('mod_mode',
                    MstConsts::MODERATION_MODE_ALL_MESSAGES,
                    'mod_all_messages',
                    __("All messages", 'wp-mailster'),
                    ($options->mod_mode == MstConsts::MODERATION_MODE_ALL_MESSAGES),
                    false,
                    __("All messages need to be approved before they are forwarded to the list", 'wp-mailster'),
                    $modDisabled);?><br/><?php
                $this->mst_display_simple_radio_field('mod_mode',
                    MstConsts::MODERATION_MODE_MEMBERS_OF_GROUP,
                    'mod_for_group',
                    __("Messages from members of the group", 'wp-mailster'),
                    ($options->mod_mode == MstConsts::MODERATION_MODE_MEMBERS_OF_GROUP),
                    false,
                    __("Messages coming from members of the given group need to be approved before they are forwarded to the list", 'wp-mailster'),
                    $modDisabled);?><?php

                    include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelGroup.php";
                    $Groups = new Mst_groups();
                    $groups = $Groups->getAllGroups();
                    if( $groups ) { ?>
                        <select name="mod_moderated_group" id="mod_moderated_group" >
                            <?php foreach ( $groups as $group ) { ?>
                                <option value="<?php echo $group->id; ?>" <?php echo ($options->mod_moderated_group == $group->id?'selected="selected"':''); ?>><?php echo $group->name; ?></option>
                            <?php } ?>
                        </select>
                    <?php } else {
                        $this->mst_display_hidden_field("mod_moderated_group", 0);
                        ?><em><?php echo __('Create groups first', 'wp-mailster'); ?></em><?php
                    }?>
            </td>
		</tr>
        <tr class="" >
            <th scope="row">
                <?php echo __("Auto-Approve (Whitelist)", 'wp-mailster'); ?>
            </th>
            <td>
                <p><input id="mod_approve_recipients"   type="checkbox" name="mod_approve_recipients" 	value="<?php echo $options->mod_approve_recipients; ?>" onclick="this.value = this.checked ? 1:0;" <?php echo $options->mod_approve_recipients == 1 ? 'checked="checked"' : ''; ?>  /> <?php echo __('Messages from recipients', 'wp-mailster') ?></p>
                <p><input id="mod_approve_group"        type="checkbox" name="mod_approve_group" 	    value="<?php echo $options->mod_approve_group; ?>" 	    onclick="this.value = this.checked ? 1:0;" <?php echo $options->mod_approve_group == 1      ? 'checked="checked"' : ''; ?> /> <?php echo __('Messages from members of the group', 'wp-mailster'); ?>
                <?php
                if( $groups ) { ?>
                    <select name="mod_approve_group_id" id="mod_approve_group_id" >
                        <?php foreach ( $groups as $group ) { ?>
                            <option value="<?php echo $group->id; ?>" <?php echo ($options->mod_approve_group_id == $group->id?'selected="selected"':''); ?>><?php echo $group->name; ?></option>
                        <?php } ?>
                    </select>
                <?php } else {
                    $this->mst_display_hidden_field("mod_approve_group_id", 0);
                    ?><em><?php echo __('Create groups first', 'wp-mailster'); ?></em><?php
                }?>
            </td>
        </tr>
        <tr class="" >
            <th scope="row">
                <?php echo __("Notifications", 'wp-mailster'); ?>
            </th>
            <td>
                <p><input id="mod_info_sender_moderation"   type="checkbox" name="mod_info_sender_moderation" 	value="<?php echo $options->mod_info_sender_moderation; ?>" 	onclick="this.value = this.checked ? 1:0;" <?php echo $options->mod_info_sender_moderation == 1 ? 'checked="checked"' : ''; ?>  /> <?php echo __("Notify sender about moderation", 'wp-mailster'); ?></p>
                <p><input id="mod_info_sender_approval"     type="checkbox" name="mod_info_sender_approval" 	value="<?php echo $options->mod_info_sender_approval; ?>" 	    onclick="this.value = this.checked ? 1:0;" <?php echo $options->mod_info_sender_approval == 1 ? 'checked="checked"' : ''; ?>    /> <?php echo __("Notify sender about moderator's approval", 'wp-mailster'); ?></p>
                <p><input id="mod_info_sender_rejection"    type="checkbox" name="mod_info_sender_rejection" 	value="<?php echo $options->mod_info_sender_rejection; ?>" 	    onclick="this.value = this.checked ? 1:0;" <?php echo $options->mod_info_sender_rejection == 1 ? 'checked="checked"' : ''; ?>   /> <?php echo __("Notify sender about moderator's rejection", 'wp-mailster'); ?></p>
            </td>
        </tr>
    </tbody>
</table>
