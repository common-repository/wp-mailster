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

				<div id="mailsterModerationDecision" class="success" >Message rejected</div>
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
				<div id="mailsterModerationDecision" class="failure" >Could not reject message</div>
			</div>
		</div>
	</div>
	<?php
}
