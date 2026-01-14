<?php
// rps_logout.php
session_start();

// Suppression de toutes les variables de session
$_SESSION = [];

// Destruction du cookie de session si existant
if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params["path"], $params["domain"],
		$params["secure"], $params["httponly"]
	);
}

// Destruction complète de la session
session_destroy();

// Redirection vers l'accueil avec message
header('Location: index.php?msg=logout');
exit;
?>
