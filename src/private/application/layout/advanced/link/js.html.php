<?php
// TODO: implement defer='defer', but not for JsLibraries
// Not really necessary when scripts are included just before </body> element
?>
<script type="text/javascript" src="<?php echo $url; ?>" <?php echo $arguments; ?>></script>