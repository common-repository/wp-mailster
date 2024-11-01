<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<table class="form-table">
	<tbody>
		<?php

        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_D_ARCHIVE)){
		    $this->mst_display_truefalse_field( __("Disable email archiving", 'wp-mailster'), 'archive_mode', $options->archive_mode, false, __("Email archiving disabled means that the content (body and attachments) of the emails are deleted after the email was forwarded to the mailing list. The subject will still be visible in the email archive.", 'wp-mailster') );
        }else{
            $this->mst_display_sometext( __('Disable email archiving', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_D_ARCHIVE)), __('Email archiving disabled means that the content (body and attachments) of the emails are deleted after the email was forwarded to the mailing list. The subject will still be visible in the email archive.', 'wp-mailster'));
        }
        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_D_ARCHIVE)){
            $archiveRetentionOpts = array (
                "0" => __('Keep emails indefinitely'),
                "1" => __('Keep emails for one day'),
                "3" => sprintf(__('Keep emails for %d days'), 3),
                "7" => sprintf(__('Keep emails for %d days'), 7),
                "14" => sprintf(__('Keep emails for %d days'), 14),
                "21" => sprintf(__('Keep emails for %d days'), 21),
                "30" => sprintf(__('Keep emails for %d days'), 30),
                "60" => sprintf(__('Keep emails for %d days'), 60),
                "90" => sprintf(__('Keep emails for %d days'), 90),
                "180" => sprintf(__('Keep emails for %d days'), 180),
                "365" => sprintf(__('Keep emails for %d days'), 365),
            );
            $this->mst_display_select_field( __("Archive Retention", 'wp-mailster'), 'archive_retention',
                $archiveRetentionOpts,
                $options->archive_retention,
                false,
                false,
                __("Keep emails in archive forever or delete them after the defined number of days", 'wp-mailster'),
                array(),
                $options->archive_mode == MstConsts::ARCHIVE_MODE_NO_CONTENT ? true : false
            );
        }else{
            $this->mst_display_sometext( __('Archive Retention', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_D_ARCHIVE)), __('Keep emails in archive forever or delete them after the defined number of days', 'wp-mailster'));
        }
//        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
//		    $this->mst_display_truefalse_field( __("Archive to digest articles", 'wp-mailster'), 'archive2article', $options->archive2article, false, __("All messages of a mailing list can be copied daily into a WordPress post as a digest", 'wp-mailster') );
//        }else{
//            $this->mst_display_sometext( __('Archive to digest articles', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('All messages of a mailing list can be copied daily into a WordPress post as a digest', 'wp-mailster'));
//        }
		?>
<!--		<tr>-->
<!--			<td colspan="2" >-->
<!--				<div class="subchoices">-->
<!--					<table>-->
<!--						--><?php
//                        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
//                            $this->mst_display_select_field( __("Author", 'wp-mailster'), 'archive2article_author',
//                                array(
//                                    //users
//                                ),
//                                $options->archive2article_author,
//                                false,
//                                false,
//                                __("Author under whose name the digest article is published", 'wp-mailster')
//                            );
//                        }else{
//                            $this->mst_display_sometext( __('Author', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('Author under whose name the digest article is published', 'wp-mailster'));
//                        }
//                        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
//                            $this->mst_display_select_field( __("Category", 'wp-mailster'), 'archive2article_cat',
//                                array(
//                                    //categories
//                                ),
//                                $options->archive2article_cat,
//                                false,
//                                false,
//                                __("Category under which the digest article is published", 'wp-mailster')
//                            );
//                        }else{
//                            $this->mst_display_sometext( __('Category', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('Category under which the digest article is published', 'wp-mailster'));
//                        }
//                        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
//                            $this->mst_display_select_field( __("State", 'wp-mailster'), 'archive2article_state',
//                                array(
//                                    "1" => __("Published", 'wp-mailster'),
//                                    "0" => __("Unpublished", 'wp-mailster'),
//                                    "2" => __("Archived", 'wp-mailster')
//                                ),
//                                $options->archive2article_state,
//                                false,
//                                false,
//                                __("State of the digest article", 'wp-mailster')
//                            );
//                        }else{
//                            $this->mst_display_sometext( __('State', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('State of the digest article', 'wp-mailster'));
//                        }
//						?>
<!--					</table>-->
<!--				</div>-->
<!--			</td>-->
<!--		</tr>-->
<!--		--><?php
//        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
//            $this->mst_display_select_field( __("Offline Archiving", 'wp-mailster'), 'archive_offline',
//                array(
//                    "0" => __("No offline archiving", 'wp-mailster'),
//                    "30" => __("Archive messages older than 30 days", 'wp-mailster'),
//                    "60" => __("Archive messages older than 60 days", 'wp-mailster'),
//                    "90" => __("Archive messages older than 90 days", 'wp-mailster'),
//                    "180" => __("Archive messages older than 180 days", 'wp-mailster'),
//                    "365" => __("Archive messages older than 365 days", 'wp-mailster')
//                ),
//                $options->archive_offline,
//                false,
//                false,
//                __("The messages in the email archive can be moved to an offline archive to clean up the live database. However, this makes the messages unavailable for access through the frontend archive.", 'wp-mailster')
//            );
//        }else{
//            $this->mst_display_sometext( __('Offline Archiving', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_EARCHIVE)), __('The messages in the email archive can be moved to an offline archive to clean up the live database. However, this makes the messages unavailable for access through the frontend archive.', 'wp-mailster'));
//        }
//		?>
	</tbody>
</table>