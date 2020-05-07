<?php

// Siyavula Question API PHP Example
// 2020 Steve Veerman (steve@hitch.video)

// for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); // E_ERROR
set_time_limit(30);
ini_set('memory_limit','32M');

include_once 'siyavula.php';

$api_host = 'https://www.siyavula.com';
$api_client_name = '';
$api_client_password = '';

$siyavula = new siyavula([
	'api_host' => $api_host,
	'api_client_name' => $api_client_name,
	'api_client_password' => $api_client_password
]);

$template_id = $_REQUEST['template_id'] ?? 2122;
$random_seed = $_REQUEST['random_seed'] ?? mt_rand(1, 100);

$output = '';
if ($_POST){
	$response_hash = $_REQUEST['response_hash']; // datetimestamp
	unset($_REQUEST['template_id']);
	unset($_REQUEST['random_seed']);
	unset($_REQUEST['response_hash']);
	$user_responses = $_REQUEST; // left over REQUEST key/values considered user_responses
	$question = $siyavula->mark_question($template_id, $random_seed, $user_responses);
	if (isset($question->question_html)){
		$output .= $question->question_html;
	}
}
else {
	$question = $siyavula->get_question($template_id, $random_seed);
	if (isset($question->question_html)){
		$question_html = str_replace('</form>', '<input type="hidden" name="template_id" value="'.$template_id.'"><input type="hidden" name="random_seed" value="'.$random_seed.'"></form>', $question->question_html);
		$output .= $question_html;
	}
}

?>
<html>
	<head>
		<script type="text/javascript" id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
	</head>
	<body>
		<?=$output ?>
	</body>
</html>