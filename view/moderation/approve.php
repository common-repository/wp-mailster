<?php

if( isset($_REQUEST['success']) ) {
	success();
} else {
	failed();
}

function success() {
	?>
	<h2 class="componentheading mailsterModerationHeader">
		Moderation Decision
	</h2>
	<div class="contentpane">
		<div id="mailsterContainer">
			<div id="mailsterModeration">

				<div id="mailsterModerationDecision success">Message approved</div>
			</div>
		</div>
	</div>
	<?php
}
function failed() {?>

	<h2 class="componentheading mailsterModerationHeader">
        Moderation Decision
	</h2>
	<div class="contentpane">
		<div id="mailsterContainer">
			<div id="mailsterModeration">
				<div id="mailsterModerationDecision failure">Could not approve message</div>
			</div>
		</div>
	</div>
	<?php
}
