<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
require_once plugin_dir_path( __FILE__ )."../../models/MailsterModelMailster.php";
$Mailster = new MailsterModelMailster();
$data = $Mailster->getData();

//fix collation issues
$dbUtils 	= MstFactory::getDBUtils();
$dbUtils->checkAndFixDBCollations(false);

$active = '<span class="dashicons dashicons-yes"></span>';
$inactive = '<span class="dashicons dashicons-no"></span>';

$mstversion = mst_get_version();
global $wp_version;

$mstApp = MstFactory::getApplication();
$systemProblems = $mstApp->detectSystemProblems();
$showSystemDiagnosis = '?page=wpmst_mailster_intro&subpage=diagnosis';
?>

<?php
/*  */
?>
<div class="mst_container">
    <div style="width:100%">
        <?php
        if($systemProblems->error){
            $noticeClass = ($systemProblems->warningOnly) ? 'notice inline notice-warning' : 'notice inline notice-error';
            ?>
            <div class="<?php echo $noticeClass; ?>"><p>
                    <?php echo __('Problem identified', 'wp-mailster') . ': ' . $systemProblems->errorMsg;
                    if($systemProblems->autoFixAvailable){
                        ?>
                        <a href="<?php echo $systemProblems->autoFixLink; ?>" title="<?php _e('Fix the problem', 'wp-mailster'); ?>" style="margin-left:10px;">
                            <?php echo $systemProblems->autoFixLinkName ?: _e('Automatically resolve issue', 'wp-mailster'); ?>
                        </a>
                        <?php
                    }
                    if(!$systemProblems->hideSysDiagnosisLink){
                        ?>
                        <a href="<?php echo $showSystemDiagnosis; ?>" style="margin-left:10px;"><?php _e('Show system diagnosis', 'wp-mailster'); ?></a>
                        <?php
                    }
                    ?>
                </p></p>
            <?php
        }
        ?>
    </div>
	<div class="wrap">
		<?php $mstImgPath =  plugins_url( '/../asset/images/', dirname(__FILE__) );
        $imageName = 'logo_01_free.png';  ?>
		<div class="statistics">
            <input type="hidden" id="wpmst-licsubinfo-nonce" value="<?php echo wp_create_nonce('wpmst_licsubinfo'); ?>" />

			<div class="statistic clearfix">
                <div class="statistic-right">
                    <img src="<?php echo $mstImgPath.$imageName; ?>" alt="<?php _e( 'WP Mailster Logo', 'wp-mailster' ); ?>" id="mainLogo"/>
                    <br/>
                    <?php $documentationLink = 'https://wpmailster.com/documentation?utm_source=wpmst&utm_medium=doc&utm_campaign=wpmst-intro'; ?>
                    <a href="<?php echo $documentationLink; ?>" target="_blank" ><?php _e("Show Documentation", 'wp-mailster'); ?></a>
                    <br/><br/>
                    <?php
                    if(MstFactory::getV()->upgradePossible()){
                    $upgradeLink = MstFactory::getV()->getUpgradeLink(); ?>
                    <a href="<?php echo $upgradeLink; ?>" target="_blank" ><?php echo sprintf(__("Upgrade to %s", 'wp-mailster'), MstFactory::getV()->getUpgradeOption()); ?></a>
                    <?php } ?>
                </div>
                <div class="statistic-left">
                    <h3><?php _e("General Stats", 'wp-mailster'); ?></h3>
                    <ul>
                        <li>
                            <?php echo $data->totalLists; ?> <?php _e("Mailing Lists", 'wp-mailster'); ?>
                        </li>
                        <li>
                            <?php echo $data->inactiveLists; ?> <?php _e("Inactive Lists", 'wp-mailster'); ?>
                        </li>
                        <li>
                            <?php echo $data->totalMails; ?> <?php _e("Mails", 'wp-mailster'); ?>
                        </li>
                        <?php if($data->offlineMails) { ?>
                        <li>
                            <?php echo $data->offlineMails; ?> <?php _e("Offline mails", 'wp-mailster'); ?>
                        </li>
                        <?php } ?>
                        <li>
                            <a href="?page=mst_queued">
                                <?php echo $data->queuedMails; ?> <?php _e("Queued mails", 'wp-mailster'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
			</div>
            <?php
            /*  */
            ?>
			
			<div class="statistic">
				<?php
				$pluginUtils = MstFactory::getPluginUtils();
				$dateUtils = MstFactory::getDateUtils();
				?>
				<h3><?php _e("Plugin Activity", 'wp-mailster'); ?> <a href="#" id="resetTimer"><?php _e("Reset", 'wp-mailster'); ?></a><div tabindex="-1" id="progressIndicator1" style="display:inline; margin:5px; padding-right:20px;min-height:30px;width:30px;">&nbsp;</div></h3>

				<ul>
					<li><?php _e("Next check", 'wp-mailster'); ?> <?php echo $data->nextRetrieveRun; ?> </li>
					<li><?php _e("Next sending", 'wp-mailster'); ?> <?php echo $data->nextSendRun; ?> </li>
					<li><?php _e("Next cleanup", 'wp-mailster'); ?> <?php echo $data->nextMaintenance; ?> </li>
				</ul>
			</div>

			<div class="statistic">
				<h3><?php _e("Mailing lists", 'wp-mailster'); ?> </h3>
				<ul class="lists">
				<?php
				$lists = &$data->lists;
				
				for( $i=0; $i < count( $lists ); $i++ )	{
					$list = &$lists[$i];
					$edit_nonce = wp_create_nonce( 'mst_deactivate_list' );
					$editListLink = sprintf(
						'?page=mst_mailing_lists&subpage=edit&lid=%d&_wpnonce=%d',
						$list->id, 
						$edit_nonce
					);
					$editListMembersLink = sprintf( 
						'?page=mst_mailing_lists&subpage=recipients&lid=%d',
						$list->id
					);
					$mailArchiveLink = "?page=mst_archived&selectedlid=".$list->id;
					$editList = '<a href="' . $editListLink . '" title="' . __("Edit mailing list", 'wp-mailster') . '" >' . __("Edit mailing list", 'wp-mailster') . '</a>';
					$editMembers = '<a href="' . $editListMembersLink . '" title="' . __("Manage Recipients", 'wp-mailster') . '" >' . __("Manage recipients", 'wp-mailster') . '</a>';
					$mailArchive = '<a href="' . $mailArchiveLink . '" title="' . __("View email archive", 'wp-mailster') . '" >' . __("View email archive", 'wp-mailster') . '</a>';
					
				?>
					<li>
						<div class="listTitle">

							<h4>
								<a href="#" class="activeToggler" id="activeToggler<?php echo $list->id; ?>" title="<?php echo ($list->active == '1' ? esc_attr__("Currently active, click to deactivate", 'wp-mailster') : esc_attr__("Curretnly inactive, click to activate", 'wp-mailster')); ?>">
									<?php echo ($list->active == '1' ? $active : $inactive); ?>
								</a>
								<?php echo $list->name; ?>
							</h4>
							<span><?php echo $editList; ?></span>
							<span><?php echo $editMembers; ?></span>
							<span><?php echo $mailArchive; ?></span>
						</div>
						<ul>
							<li><?php echo $list->recipients; ?> <?php _e("Recipients", 'wp-mailster'); ?></li>
							<li>
								<?php echo $list->totalMails; ?> <?php _e("Forwarded mails", 'wp-mailster'); ?> (<?php echo $list->unsentMails; ?> <?php _e("unsent", 'wp-mailster'); ?>, <?php echo $list->errorMails; ?> <?php _e("send errors", 'wp-mailster'); ?>)
							</li>
							<li>
								<?php echo $list->blockedFilteredBounced; ?> <?php _e("Not forwarded mails", 'wp-mailster'); ?> (<?php echo $list->blockedMails . ' ' . __("blocked", 'wp-mailster'); ?>, <?php echo $list->filteredMails . ' ' .__("filtered", 'wp-mailster'); ?>, <?php echo $list->bouncedMails . ' ' . __("bounced", 'wp-mailster'); ?>)
							</li>
						</ul>
					</li>
				<?php }
                if(count( $lists ) == 0){ ?>
                    <a href="?page=mst_mailing_list_add" class="add-new-h2"><?php _e( "Add New", 'wp-mailster' ); ?></a>
                    <?php
                }
                ?>
				</ul>
			</div> 


		</div>
        <div class="statistic shortcode-info">
            <h3><?php _e("Available Shortcodes:", 'wp-mailster'); ?></h3>
            <?php
            /*  */
            ?>
            <p><span>[mst_profile]</span> <?php _e("Shows digest and subscription/unsubscription options to logged in users.", 'wp-mailster'); ?></p>
        </div>
		<div class="statistic">
			<h3><?php _e("Tools", 'wp-mailster'); ?></h3>
			<p><a href="?page=wpmst_mailster_intro&subpage=import"><?php _e("Import Users from CSV", 'wp-mailster'); ?></a></p>
			<p><a href="?page=wpmst_mailster_intro&subpage=export"><?php _e("Export Users to CSV", 'wp-mailster'); ?></a></p>
		</div>
        <div class="statistic product-info clearfix">
            <div class="statistic-left">
                <br/>
                <span><?php  echo MstFactory::getV()->getProductName(true); ?></span><br/>
                <a href="<?php echo $showSystemDiagnosis; ?>" ><?php _e("Show System Diagnosis", 'wp-mailster'); ?></a>
                </div>
            <div class="statistic-right">
                <br/>
            </div>
        </div>
	</div>
</div>