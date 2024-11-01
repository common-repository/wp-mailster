<?php
/**
 * @copyright (C) 2016 - 2024 Holger Brandt IT Solutions
 * @license GNU/GPL, see license.txt
 * WP Mailster is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 2
 * as published by the Free Software Foundation.
 *
 * WP Mailster is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Mailster; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 * or see http://www.gnu.org/licenses/.
 */


    if(isset($_GET['listID'])) {
		$listId = intval($_GET['listID']);
	} else {
		$listId = 0;
	}
	if(isset($_GET['digestID'])) {
		$digestId = intval($_GET['digestID']);
	} else {
		$digestId = 0;
	}
	if(isset($_GET['digest_freq'])) {
		$digestFreq = intval($_GET['digest_freq']);
	} else {
		$digestFreq = 0;
	}
	if( isset($_GET['bl']) ) {
        $backlink = base64_decode( sanitize_text_field($_GET['bl']) );
	} else {
        $backlink = null;
	}
	$log = MstFactory::getLogger();
	$digestModel = MstFactory::getDigestModel();
	$listUtils = MstFactory::getMailingListUtils();
	$mList = $listUtils->getMailingList($listId);
	$subscrUtils = MstFactory::getSubscribeUtils();
	$convUtils = MstFactory::getConverterUtils();
	$page_sfx = ''; // TODO FIXME DYNAMIC PARAMETER DRIVEN PARAMETER

    $user = wp_get_current_user();
	if( !is_user_logged_in() ){
		_e("You need to login to access this section", 'wp-mailster');
	} else {

		if(isset($_GET['submitted'])) {
			$log->debug('saveDigest CALLED');
			if($mList){

				if($mList->allow_digests){
					if($subscrUtils->isUserSubscribedToMailingList($user->ID, 1, $listId)){
                        if($digestId > 0){
							$digest = $convUtils->object2Array($digestModel->getDigest($digestId));
                            if($digest['user_id'] != $user->ID){
                                $log->warning('The digest '.$digestId.' does not belong to user '.$user->ID.'! We will reset the entity.');
                                $digestId = 0;
                                $digest = array();
                                $digest['id'] = 0;
                            }else{
                                $log->debug('Successfully loaded existing digest: '.print_r($digest, true));
                            }
						}else{
							$digest = array();
                        }
                        $digest['list_id'] = $listId;
                        $digest['user_id'] = $user->ID;
                        $digest['is_core_user'] = 1;
						$digest['digest_freq'] = $digestFreq;
						$log->debug('Digest after assigning updated fields: '.print_r($digest, true));
						$digestModel->store($digest);
					}
				}
			}
		}

		$digest = false;
		if($digestId > 0){
			$digest = $digestModel->getDigest($digestId);
		} else {
			$digests = $digestModel->getDigestsOfUser($user->ID, true, $listId);
			if($digests && count($digests)>0){
				$digest = $digests[0];
                $log->error('profile_digest WARNING Found multiple digests for user '.$user->ID. ' and list ID '.$listId.'! Digests:'.print_r($digests, true));
			}
		}

		$digestFreq = 0;
		if($digest){
			$digestFreq = $digest->digest_freq;
		}
		$lists = array();
		$lists['digest_freq'] = $digestModel->getDigestChoiceHtml($digestFreq);
		?>
<form name="digestForm" method="get">
	<input type="hidden" name="bl" value="<?php echo esc_attr($backlink); ?>" />
	<input type="hidden" name="listID" value="<?php echo $mList->id; ?>" />
	<input type="hidden" name="digestID" value="<?php echo $digest ? $digest->id : 0; ?>" />
	<input type="hidden" name="subpage" value="digest" />
	<input type="hidden" name="submitted" value="1" />

	<div class="mailster_profile contentpane<?php echo $page_sfx; ?>">
		<div class="mailster_profile_container<?php echo $page_sfx; ?>">
			<table class="mailster_profile_table<?php echo $page_sfx; ?> mailster_std_table">
				<tbody>
				<tr>
					<th width="50px">
						<label for="list_id">
							<?php _e( 'Mailing List', 'wp-mailster' ); ?>:
						</label>
					</th>
					<td width="100px">
						<?php echo $mList->name; ?>
					</td>
				</tr>
				<tr>
					<th width="50px">
						<label for="digest_freq">
							<?php _e( 'Digest frequency', 'wp-mailster' ); ?>:
						</label>
					</th>
					<td width="100px">
						<?php echo $lists['digest_freq']; ?>
					</td>
				</tr>
				<tr>
					<th width="50px">
						<label for="last_send_date">
							<?php _e( 'Digest Last Sending', 'wp-mailster' ); ?>:
						</label>
					</th>
					<td width="100px">
						<?php echo $digest ? $digest->last_send_date : '-'; ?>
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align:right">
						<input type="submit" id="saveDigest" name="saveDigest" value="<?php _e('Save', 'wp-mailster'); ?>" />
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
</form>
<?php } ?>