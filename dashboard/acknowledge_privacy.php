<?php
session_start();

// Mark privacy as acknowledged for this session
$_SESSION['privacy_acknowledged'] = true;

// Redirect back to the found item form
header("Location: founditem_form.php");
exit;

?>



