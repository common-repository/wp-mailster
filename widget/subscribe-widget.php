<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}

// register widget
function register_MstSubscribe_Widget() {
	register_widget( 'MstSubscribe_Widget' );
}
add_action( 'widgets_init', 'register_MstSubscribe_Widget' );
/**
 * Adds MstSubscribe_Widget widget.
 */
class MstSubscribe_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'mstSubscribe_widget', // Base ID
			__('WP Mailster Subscribe/Unsubscribe', 'wp-mailster'), // Name
			array( 'description' => __( 'Display a form for subscribing to (or unsubscribing from) WP Mailster mailing lists', 'wp-mailster' ), ) // Args
		);
	}
	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
        $log = MstFactory::getLogger();
        $headerTxt = array_key_exists('title', $instance) ? $instance['title'] : '';
        $headerTxt = apply_filters( 'wpmailster_widget_header', $headerTxt );
		echo $args['before_widget'];
		if ( ! empty( $headerTxt ) ) {
			echo $args['before_title'] . $headerTxt . $args['after_title'];
		}

		$settings = array();

        $subscriberType = (isset($instance['subscriber_type'])) ? $instance['subscriber_type'] : 1;
        $designChoice = (isset($instance['design_choice'])) ? $instance['design_choice'] : 0;
        $cssPrefix = (isset($instance['prefix_class'])) ? $instance['prefix_class'] : "";
        $listLabel = (isset($instance['list_label'])) ? $instance['list_label'] : "";
        $listChoice = (isset($instance['list_choice'])) ? $instance['list_choice'] : 0;
        $captcha = (isset($instance['captcha'])) ? $instance['captcha'] : 0;
        $hideListName = (isset($instance['hide_list_name'])) ? $instance['hide_list_name'] : 0;

		$digestChoice =  get_option('digest_choice', 0);
		$suggestUserData = get_option('suggest_user_data', 1);
		$enforceUserData = get_option('enforce_user_data', 0);
		$digestChoiceLabel =  get_option('digest_choice_label', __( 'Digest Choice', 'wp-mailster' ));
        $log->debug('subscribe-widget with args '.print_r($args, true).' has instance settings: '.print_r($instance, true));

		if($subscriberType == 1){
            $hideSubscriberName = (isset($instance['hide_subscriber_name'])) ? $instance['hide_subscriber_name'] : "";
            $subscribeAdd2Group = (isset($instance['subscription_group_choice'])) ? $instance['subscription_group_choice'] : "";
            $unsubscribeRemoveFromGroup = 0;
            $nameLabel = (isset($instance['subscriber_name'])) ? $instance['subscriber_name'] : "";
            $emailLabel = (isset($instance['subscriber_email'])) ? $instance['subscriber_email'] : "";
            $buttonTxt = (isset($instance['subscribe_button'])) ? $instance['subscribe_button'] : "";
            $submitTxt = (isset($instance['subscribe_thank_msg'])) ? $instance['subscribe_thank_msg'] : "";
            $submitConfirmTxt = (isset($instance['subscribe_confirm_email_msg'])) ? $instance['subscribe_confirm_email_msg'] : "";
            $errorTxt = (isset($instance['subscribe_error_msg'])) ? $instance['subscribe_error_msg'] : "";
            $smartHide = (isset($instance['subscriber_smart_hide'])) ? $instance['subscriber_smart_hide'] : "";
		} else {
			$hideSubscriberName = 0;
			$subscribeAdd2Group = 0;
            $unsubscribeRemoveFromGroup = (isset($instance['unsubscription_group_choice'])) ? $instance['unsubscription_group_choice'] : "";
			$nameLabel = '';
            $emailLabel = (isset($instance['unsubscriber_email'])) ? $instance['unsubscriber_email'] : "";
            $buttonTxt = (isset($instance['unsubscribe_button'])) ? $instance['unsubscribe_button'] : "";
            $submitTxt = (isset($instance['unsubscribe_thank_msg'])) ? $instance['unsubscribe_thank_msg'] : "";
            $submitConfirmTxt = (isset($instance['unsubscribe_confirm_email_msg'])) ? $instance['unsubscribe_confirm_email_msg'] : "";
            $errorTxt = (isset($instance['unsubscribe_error_msg'])) ? $instance['unsubscribe_error_msg'] : "";
            $smartHide = (isset($instance['unsubscriber_smart_hide'])) ? $instance['unsubscriber_smart_hide'] : "";
		}

		if($listChoice == 0){
			$settings['allLists'] = true;
			$settings['listIdSpecified'] = false;
			$settings['listNameSpecified'] = false;
			$settings['listId'] = 0;
		}else{
			$settings['allLists'] = false;
			$settings['listIdSpecified'] = true;
			$settings['listNameSpecified'] = false;
			$settings['listId'] = $listChoice;
		}

		if($captcha){
			// if captcha = 1 then take recaptcha (backward compatibility)
			$settings['captcha'] = (($captcha == 1) ? MstConsts::CAPTCHA_ID_MATH :  $captcha);
		}else{
			$settings['captcha'] = false;
		}

		$settings['hideListName'] = ($hideListName == 1);
		$settings['hideNameField'] = ($hideSubscriberName == 1);
		$settings['smartHide'] = ($smartHide == 1);
		$settings['suggestUserData'] = ($suggestUserData == 1);
		$settings['enforceUserData'] = ($enforceUserData == 1);
		$settings['subscribeAdd2Group'] = $subscribeAdd2Group;
        $settings['unsubscribeRemoveFromGroup'] = $unsubscribeRemoveFromGroup;
		$settings['nameLabel'] 			= $nameLabel;
		$settings['emailLabel'] 		= $emailLabel;
		$settings['digestChoiceLabel'] 	= $digestChoiceLabel;
		$settings['listLabel'] 			= $listLabel;
		$settings['buttonTxt'] 			= $buttonTxt;
		$settings['submitTxt'] 			= $submitTxt;
		$settings['submitConfirmTxt'] 	= $submitConfirmTxt;
		$settings['errorTxt'] 			= $errorTxt;
		$settings['headerTxt'] 			= $headerTxt;
		$settings['cssPrefix'] 			= $cssPrefix;
		$settings['designChoice'] 		= $designChoice;
		$settings['digestChoice'] 		= $digestChoice;

        $settings['reqTosApproval']     = false; // we are not adding it to the legacy widget, use standard settings
        $settings['tosLabelText'] 		= __('Yes, I have read and accept the terms of service', 'wp-mailster');
        $settings['tosLinkLabel'] 		= __('Our Terms', 'wp-mailster');
        $settings['tosLinkUrl'] 		= __('Our Terms', 'wp-mailster');
        $settings['tosConfirmError']    = __('Please accept our terms of service first', 'wp-mailster');

		if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)){
			$settings['digestChoice'] = false;
		}

        $formIdentifier = sha1(($subscriberType == 1 ? 'subscribe' : 'unsubscribe').rand(0, 999999));

        $formSessionInfo = new stdClass();
        $formSessionInfo->id = $formIdentifier;
        $formSessionInfo->type = ($subscriberType == 1 ? 'subscribe' : 'unsubscribe');
        $formSessionInfo->origin = 'widget';
        $formSessionInfo->wp_widget_id = array_key_exists('widget_id', $args) ? $args['widget_id'] : 'no_wp_id';
        $formSessionInfo->submitTxt = $submitTxt;
        $formSessionInfo->submitConfirmTxt = $submitConfirmTxt;
        $formSessionInfo->errorTxt = $errorTxt;
        $formSessionInfo->captcha = $settings['captcha'];
        $formSessionInfo->subscribeAdd2Group = $subscribeAdd2Group;
        $formSessionInfo->unsubscribeRemoveFromGroup = $unsubscribeRemoveFromGroup;

        $transientId = 'wpmst_subscribe_forms'.'_'.$formIdentifier;
        $log->debug('widget Store to transient '.$transientId.': '.print_r($formSessionInfo, true));
        set_transient($transientId, $formSessionInfo,HOUR_IN_SECONDS);

        $subscrUtils = MstFactory::getSubscriberPlugin();
		if($subscriberType == 1){
			$subscrUtils->getSubscriberHtml($settings, $formIdentifier, false);
		}else{
			$subscrUtils->getUnsubscriberHtml($settings, $formIdentifier, false);
		}

		echo $args['after_widget'];
	}

	/**
	 * @param array $instance
	 *
	 * @return void echoes the form that generates the widget
	 */
	public function form( $instance ) {
        $log = MstFactory::getLogger();
        $log->debug('subscribe-widget -> form -> instance: '.print_r($instance, true));

		?>
		<p>
			<?php //title
			if ( isset( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			} else {
				$title = __( 'WP Mailster Mailing List Subscription', 'wp-mailster' );
			}
			?>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php _e( 'Title:', 'wp-mailster' ); ?>
			</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<?php //subscriber type
			if ( isset( $instance[ 'subscriber_type' ] ) ) {
				$selectedValue = $instance[ 'subscriber_type' ];
			} else {
				$selectedValue = 1;
			}
			?>
			<label for="<?php echo $this->get_field_id( 'subscriber_type' ); ?>">
				<?php _e("Form choice", 'wp-mailster'); ?>
			</label>
			<select name="<?php echo $this->get_field_name( 'subscriber_type' ); ?>" id="<?php echo $this->get_field_id( 'subscriber_type' ); ?>" onchange="toggleSubscriberType(this.id, '<?php echo $this->get_field_id('mst-subscribeForm'); ?>', '<?php echo $this->get_field_id('mst-unsubscribeForm'); ?>');">
				<?php $value = 1; ?>
				<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
					<?php _e('Subscribe', 'wp-mailster'); ?>
				</option>
				<?php $value = 2; ?>
				<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
					<?php _e('Unsubscribe', 'wp-mailster'); ?>
				</option>
			</select>
		</p>
		<div class="mst-subscribeForm" id="<?php echo $this->get_field_id('mst-subscribeForm'); ?>">
			<p>
				<?php //list choice
				$MailingListUtils = MstFactory::getMailingListUtils();
				if ( isset( $instance[ 'list_choice' ] ) ) {
					$selectedValue = $instance[ 'list_choice' ];
				} else {
					$selectedValue = '';
				}
				?>
				<label for="<?php echo $this->get_field_id( 'list_choice' ); ?>">
					<?php _e("List choice", 'wp-mailster'); ?>
				</label>
				<?php
				$lists = $MailingListUtils->getAllLists();

				?>
				<select name="<?php echo $this->get_field_name( 'list_choice' ); ?>" id="<?php echo $this->get_field_id( 'list_choice' ); ?>">
					<?php
					foreach($lists as $list) {
						$value =$list->value;
						$name = $list->list_choice;
						?>
						<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
							<?php echo $name; ?>
						</option>
						<?php
					} ?>
				</select>
			</p>
			<p>
				<?php // add2Group functionality
				$groupModel = MstFactory::getGroupModel();
				$groups = $groupModel->getAllGroupsForm(true);
				if ( isset( $instance[ 'subscription_group_choice' ] ) ) {
					$selectedValue = $instance[ 'subscription_group_choice' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscription_group_choice' ); ?>">
					<?php _e("Add to group on subscription", 'wp-mailster'); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'subscription_group_choice' ); ?>" id="<?php echo $this->get_field_id( 'subscription_group_choice' ); ?>">
					<?php
					foreach($groups as $group) {
						$value = $group->value;
						$name = $group->list_choice;
						?>
						<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
							<?php echo $name; ?>
						</option>
						<?php
					} ?>
				</select>
			</p>
            <p>
                <?php // removeFromGroup functionality
                $groupModel = MstFactory::getGroupModel();
                $groups = $groupModel->getAllGroupsForm(false, true);
                if ( isset( $instance[ 'unsubscription_group_choice' ] ) ) {
                    $selectedValue = $instance[ 'unsubscription_group_choice' ];
                } else {
                    $selectedValue = 0;
                }
                ?>
                <label for="<?php echo $this->get_field_id( 'unsubscription_group_choice' ); ?>">
                    <?php _e("Remove from group on unsubscription", 'wp-mailster'); ?>
                </label>
                <select name="<?php echo $this->get_field_name( 'unsubscription_group_choice' ); ?>" id="<?php echo $this->get_field_id( 'unsubscription_group_choice' ); ?>">
                    <?php
                    foreach($groups as $group) {
                        $value = $group->value;
                        $name = $group->list_choice;
                        ?>
                        <option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
                            <?php echo $name; ?>
                        </option>
                        <?php
                    } ?>
                </select>
            </p>
			<p>
				<?php //subscriber type
				if ( isset( $instance[ 'captcha' ] ) ) {
					$selectedValue = $instance[ 'captcha' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'captcha' ); ?>">
					<?php _e("Captcha protection active", 'wp-mailster'); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'captcha' ); ?>" id="<?php echo $this->get_field_id( 'captcha' ); ?>">
					<?php $value = 0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value === $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('No', 'wp-mailster'); ?>
					</option>
					<?php $value = MstConsts::CAPTCHA_ID_RECAPTCHA_V2; ?>
					<option value="<?php echo $value; ?>" <?php if( $value === $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Recaptcha v2', 'wp-mailster'); ?>
					</option>
					<?php $value = MstConsts::CAPTCHA_ID_MATH; ?>
					<option value="<?php echo $value; ?>" <?php if( $value === $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Math captcha', 'wp-mailster'); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //design choice
				if ( isset( $instance[ 'design_choice' ] ) ) {
                    if($instance[ 'design_choice' ] != '0' && $instance[ 'design_choice' ] != ''){
                        $selectedValue = $instance[ 'design_choice' ];
                    }else{
                        $selectedValue = 'none';
                    }
				} else {
					$selectedValue = 'none';
				}
				?>
				<label for="<?php echo $this->get_field_id( 'design_choice' ); ?>">
					<?php _e("Design Choice", 'wp-mailster'); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'design_choice' ); ?>" id="<?php echo $this->get_field_id( 'design_choice' ); ?>">
					<?php $value = "none"; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('No design', 'wp-mailster'); ?>
					</option>
					<?php $value = "black"; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Black', 'wp-mailster'); ?>
					</option>
					<?php $value = "blue"; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Blue', 'wp-mailster'); ?>
					</option>
					<?php $value = "red"; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('Red', 'wp-mailster'); ?>
					</option>
					<?php $value = "white"; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e('White', 'wp-mailster'); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //prefix class
				if ( isset( $instance[ 'prefix_class' ] ) ) {
					$value = $instance[ 'prefix_class' ];
				} else {
					$value = "mailster_subscriber_";
				}
				?>
				<label for="<?php echo $this->get_field_id( 'prefix_class' ); ?>">
					<?php _e( 'Prefix class', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'prefix_class' ); ?>" name="<?php echo $this->get_field_name( 'prefix_class' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //list label
				if ( isset( $instance[ 'list_label' ] ) ) {
					$value = $instance[ 'list_label' ];
				} else {
					$value = __( 'Newsletter', 'wp-mailster');
				}
				?>
				<label for="<?php echo $this->get_field_id( 'list_label' ); ?>">
					<?php _e( 'Mailing list label', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'list_label' ); ?>" name="<?php echo $this->get_field_name( 'list_label' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //hide list name
				if ( isset( $instance[ 'hide_list_name' ] ) ) {
					$selectedValue = $instance[ 'hide_list_name' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'hide_list_name' ); ?>">
					<?php _e( 'Hide list', 'wp-mailster' ); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'hide_list_name' ); ?>" id="<?php echo $this->get_field_id( 'hide_list_name' ); ?>">
					<?php $value=0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("No", 'wp-mailster'); ?>
					</option>
					<?php $value=1; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("Yes", 'wp-mailster'); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //smart hide
				if ( isset( $instance[ 'subscriber_smart_hide' ] ) ) {
					$selectedValue = $instance[ 'subscriber_smart_hide' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscriber_smart_hide' ); ?>">
					<?php _e( 'Smart Hide', 'wp-mailster' ); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'subscriber_smart_hide' ); ?>" id="<?php echo $this->get_field_id( 'subscriber_smart_hide' ); ?>">
					<?php $value=0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("No", 'wp-mailster'); ?>
					</option>
					<?php $value=1; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("Yes", 'wp-mailster'); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //smart hide
				if ( isset( $instance[ 'hide_subscriber_name' ] ) ) {
					$selectedValue = $instance[ 'hide_subscriber_name' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'hide_subscriber_name' ); ?>">
					<?php _e( 'Hide Subscriber Name', 'wp-mailster' ); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'hide_subscriber_name' ); ?>" id="<?php echo $this->get_field_id( 'hide_subscriber_name' ); ?>">
					<?php $value=0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("No", 'wp-mailster'); ?>
					</option>
					<?php $value=1; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("Yes", 'wp-mailster'); ?>
					</option>
				</select>
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscriber_name' ] ) ) {
					$value = $instance[ 'subscriber_name' ];
				} else {
					$value = __( 'Name', 'wp-mailster');
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscriber_name' ); ?>">
					<?php _e( 'Name field label', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscriber_name' ); ?>" name="<?php echo $this->get_field_name( 'subscriber_name' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscriber_email' ] ) ) {
					$value = $instance[ 'subscriber_email' ];
				} else {
					$value = __( 'Email', 'wp-mailster');
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscriber_email' ); ?>">
					<?php _e( 'Email field label', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscriber_email' ); ?>" name="<?php echo $this->get_field_name( 'subscriber_email' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscribe_button' ] ) ) {
					$value = $instance[ 'subscribe_button' ];
				} else {
					$value = __( 'Subscribe', 'wp-mailster');
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscribe_button' ); ?>">
					<?php _e( 'Subscribe field label', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscribe_button' ); ?>" name="<?php echo $this->get_field_name( 'subscribe_button' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscribe_thank_msg' ] ) ) {
					$value = $instance[ 'subscribe_thank_msg' ];
				} else {
					$value = __( 'Thank you for subscribing', 'wp-mailster');
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscribe_thank_msg' ); ?>">
					<?php _e( 'Subscription OK text', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscribe_thank_msg' ); ?>" name="<?php echo $this->get_field_name( 'subscribe_thank_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscribe_confirm_email_msg' ] ) ) {
					$value = $instance[ 'subscribe_confirm_email_msg' ];
				} else {
					$value = __( 'Thank you for your subscription. An email was sent to confirm your subscription. Please follow the instructions in the email.', 'wp-mailster');
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscribe_confirm_email_msg' ); ?>">
					<?php _e( 'Subscription confirmation sent text', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscribe_confirm_email_msg' ); ?>" name="<?php echo $this->get_field_name( 'subscribe_confirm_email_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'subscribe_error_msg' ] ) ) {
					$value = $instance[ 'subscribe_error_msg' ];
				} else {
					$value =  __( 'Subscription error occurred. Please try again.', 'wp-mailster' );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'subscribe_error_msg' ); ?>">
					<?php _e( 'Subscription error text', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'subscribe_error_msg' ); ?>" name="<?php echo $this->get_field_name( 'subscribe_error_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
		</div>
		<div class="mst-unsubscribeForm" id="<?php echo $this->get_field_id('mst-unsubscribeForm'); ?>">
			<p>
				<?php //smart hide
				if ( isset( $instance[ 'unsubscriber_smart_hide' ] ) ) {
					$selectedValue = $instance[ 'unsubscriber_smart_hide' ];
				} else {
					$selectedValue = 0;
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscriber_smart_hide' ); ?>">
					<?php _e( 'Smart Hide', 'wp-mailster' ); ?>
				</label>
				<select name="<?php echo $this->get_field_name( 'unsubscriber_smart_hide' ); ?>" id="<?php echo $this->get_field_id( 'unsubscriber_smart_hide' ); ?>">
					<?php $value=0; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("No", 'wp-mailster'); ?>
					</option>
					<?php $value=1; ?>
					<option value="<?php echo $value; ?>" <?php if( $value == $selectedValue ) { echo "selected='selected'"; } ?> >
						<?php _e("Yes", 'wp-mailster'); ?>
					</option>
				</select>
			</p>
            <p>
                <?php //list label
                if ( isset( $instance[ 'list_label' ] ) ) {
                    $value = $instance[ 'list_label' ];
                } else {
                    $value = __( 'Newsletter', 'wp-mailster');
                }
                ?>
                <label for="<?php echo $this->get_field_id( 'list_label' ); ?>">
                    <?php _e( 'Mailing list label', 'wp-mailster' ); ?>
                </label>
                <input class="widefat" id="<?php echo $this->get_field_id( 'list_label' ); ?>" name="<?php echo $this->get_field_name( 'list_label' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
            </p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscriber_email' ] ) ) {
					$value = $instance[ 'unsubscriber_email' ];
				} else {
					$value = __( 'Email', 'wp-mailster');
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscriber_email' ); ?>">
					<?php _e( 'Email field label', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscriber_email' ); ?>" name="<?php echo $this->get_field_name( 'unsubscriber_email' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscribe_button' ] ) ) {
					$value = $instance[ 'unsubscribe_button' ];
				} else {
					$value = __( 'Unsubscribe', 'wp-mailster' );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscribe_button' ); ?>">
					<?php _e( 'Subscribe field label', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscribe_button' ); ?>" name="<?php echo $this->get_field_name( 'unsubscribe_button' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscribe_thank_msg' ] ) ) {
					$value = $instance[ 'unsubscribe_thank_msg' ];
				} else {
					$value = __( 'Sorry that you decided to unsubscribe. Hope to see you again in the future!', 'wp-mailster' );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscribe_thank_msg' ); ?>">
					<?php _e( 'Unsubscription OK text', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscribe_thank_msg' ); ?>" name="<?php echo $this->get_field_name( 'unsubscribe_thank_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscribe_confirm_email_msg' ] ) ) {
					$value = $instance[ 'unsubscribe_confirm_email_msg' ];
				} else {
					$value = __( 'An email was sent to you to confirm that you unsubscribed. Please follow the instructions in the email.', 'wp-mailster' );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscribe_confirm_email_msg' ); ?>">
					<?php _e( 'Unsubscription confirmation sent text', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscribe_confirm_email_msg' ); ?>" name="<?php echo $this->get_field_name( 'unsubscribe_confirm_email_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
			<p>
				<?php //Name field
				if ( isset( $instance[ 'unsubscribe_error_msg' ] ) ) {
					$value = $instance[ 'unsubscribe_error_msg' ];
				} else {
					$value =  __( 'Unsubscription error occurred. Please try again.', 'wp-mailster' );
				}
				?>
				<label for="<?php echo $this->get_field_id( 'unsubscribe_error_msg' ); ?>">
					<?php _e( 'Unsubscription error text', 'wp-mailster' ); ?>
				</label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'unsubscribe_error_msg' ); ?>" name="<?php echo $this->get_field_name( 'unsubscribe_error_msg' ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>">
			</p>
		</div>
		<script type="text/javascript">
			function showSubscription(subscribeId, unsubscribeId) {
				jQuery("#"+subscribeId).css("display", "block");
				jQuery("#"+unsubscribeId).css("display", "none");
			}
			function showUnsubscription(subscribeId, unsubscribeId) {
                jQuery("#"+subscribeId).css("display", "none");
                jQuery("#"+unsubscribeId).css("display", "block");
			}
            function toggleSubscriberType(subscribeSettingId, subscribePartId, unsubscribePartId){
                console.log(subscribeSettingId);
                console.log(subscribePartId);
                console.log(unsubscribePartId);
                if(jQuery('#'+subscribeSettingId).val() == 1) {
                    jQuery("#"+subscribePartId).css("display", "block");
                    jQuery("#"+unsubscribePartId).css("display", "none");
                } else {
                    jQuery("#"+subscribePartId).css("display", "none");
                    jQuery("#"+unsubscribePartId).css("display", "block");
                }
            }
            toggleSubscriberType('<?php echo $this->get_field_id( 'subscriber_type' ); ?>', '<?php echo $this->get_field_id('mst-subscribeForm'); ?>', '<?php echo $this->get_field_id('mst-unsubscribeForm'); ?>');
		</script>
		<?php 
	}
	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['subscriber_type'] = ( ! empty( $new_instance['subscriber_type'] ) ) ? strip_tags( $new_instance['subscriber_type'] ) : 1;
		$instance['list_choice'] = ( ! empty( $new_instance['list_choice'] ) ) ? strip_tags( $new_instance['list_choice'] ) : 0;
		$instance['subscription_group_choice'] = ( ! empty( $new_instance['subscription_group_choice'] ) ) ? strip_tags( $new_instance['subscription_group_choice'] ) : 0;
		$instance['captcha'] = ( ! empty( $new_instance['captcha'] ) ) ? strip_tags( $new_instance['captcha'] ) : 0;
		$instance['design_choice'] = ( ! empty( $new_instance['design_choice'] ) ) ? strip_tags( $new_instance['design_choice'] ) : 0;
		$instance['prefix_class'] = ( ! empty( $new_instance['prefix_class'] ) ) ? strip_tags( $new_instance['prefix_class'] ) : 'mailster_subscriber_';
		$instance['list_label'] = ( ! empty( $new_instance['list_label'] ) ) ? strip_tags( $new_instance['list_label'] ) : __( 'Newsletter', 'wp-mailster');
		$instance['hide_list_name'] = ( ! empty( $new_instance['hide_list_name'] ) ) ? strip_tags( $new_instance['hide_list_name'] ) : 0;
		$instance['subscriber_smart_hide'] = ( ! empty( $new_instance['subscriber_smart_hide'] ) ) ? strip_tags( $new_instance['subscriber_smart_hide'] ) : 0;
		$instance['hide_subscriber_name'] = ( ! empty( $new_instance['hide_subscriber_name'] ) ) ? strip_tags( $new_instance['hide_subscriber_name'] ) : 0;
		$instance['subscriber_name'] = ( ! empty( $new_instance['subscriber_name'] ) ) ? strip_tags( $new_instance['subscriber_name'] ) : __( 'Name', 'wp-mailster');
		$instance['subscriber_email'] = ( ! empty( $new_instance['subscriber_email'] ) ) ? strip_tags( $new_instance['subscriber_email'] ) : __( 'Email', 'wp-mailster');
		$instance['subscribe_button'] = ( ! empty( $new_instance['subscribe_button'] ) ) ? strip_tags( $new_instance['subscribe_button'] ) : __( 'Subscribe', 'wp-mailster');
		$instance['unsubscribe_button'] = ( ! empty( $new_instance['unsubscribe_button'] ) ) ? strip_tags( $new_instance['unsubscribe_button'] ) : __( 'Unsubscribe', 'wp-mailster');
		$instance['subscribe_thank_msg'] = ( ! empty( $new_instance['subscribe_thank_msg'] ) ) ? strip_tags( $new_instance['subscribe_thank_msg'] ) : __( 'Thank you for subscribing', 'wp-mailster');
		$instance['subscribe_confirm_email_msg'] = ( ! empty( $new_instance['subscribe_confirm_email_msg'] ) ) ? strip_tags( $new_instance['subscribe_confirm_email_msg'] ) : __( 'Thank you for your subscription. An email was sent to confirm your subscription. Please follow the instructions in the email.', 'wp-mailster');
		$instance['subscribe_confirm_email_msg'] = ( ! empty( $new_instance['subscribe_confirm_email_msg'] ) ) ? strip_tags( $new_instance['subscribe_confirm_email_msg'] ) : __( 'Subscription error occurred. Please try again.', 'wp-mailster' );
		$instance['subscribe_error_msg'] = ( ! empty( $new_instance['subscribe_error_msg'] ) ) ? strip_tags( $new_instance['subscribe_error_msg'] ) : __( 'Subscription error occurred. Please try again.', 'wp-mailster' );

		$instance['unsubscriber_smart_hide'] = ( ! empty( $new_instance['unsubscriber_smart_hide'] ) ) ? strip_tags( $new_instance['unsubscriber_smart_hide'] ) : 0;
		$instance['unsubscriber_email'] = ( ! empty( $new_instance['unsubscriber_email'] ) ) ? strip_tags( $new_instance['unsubscriber_email'] ) : __( 'Email', 'wp-mailster');
		$instance['unsubscribe_button'] = ( ! empty( $new_instance['unsubscribe_button'] ) ) ? strip_tags( $new_instance['unsubscribe_button'] ) : __( 'Unsubscribe', 'wp-mailster');
		$instance['unsubscribe_thank_msg'] = ( ! empty( $new_instance['unsubscribe_thank_msg'] ) ) ? strip_tags( $new_instance['unsubscribe_thank_msg'] ) : __( 'Sorry that you decided to unsubscribe. Hope to see you again in the future!', 'wp-mailster' );
		$instance['unsubscribe_confirm_email_msg'] = ( ! empty( $new_instance['unsubscribe_confirm_email_msg'] ) ) ? strip_tags( $new_instance['unsubscribe_confirm_email_msg'] ) : __( 'An email was sent to you to confirm that you unsubscribed. Please follow the instructions in the email.', 'wp-mailster' );
		$instance['unsubscribe_error_msg'] = ( ! empty( $new_instance['unsubscribe_error_msg'] ) ) ? strip_tags( $new_instance['unsubscribe_error_msg'] ) : __( 'Unsubscription error occurred. Please try again.', 'wp-mailster' );

		return $instance;
	}
}