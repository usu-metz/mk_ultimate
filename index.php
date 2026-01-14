<?php require('config.php');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim($_POST['username']);
	$password = trim($_POST['password']);

	// Vérifie si les champs sont remplis
	if ($username !== '' && $password !== '') {
		$stmt = $pdo->prepare("SELECT id, username, email, password_hash, startgg_id, role FROM rps_users WHERE username = ?");
		$stmt->execute([$username]);
		$user = $stmt->fetch();

		if ($user && password_verify($password, $user['password_hash'])) {
			$_SESSION['rps_user'] = [
				'id' => $user['id'],
				'pseudo' => $user['username'],
				'email' => $user['email'],
				'startgg_id' => $user['startgg_id'],
				'role' => $user['role']
			];
			$message = '<span class="info-message" id="logout-msg">Bonjour '.$_SESSION['rps_user']['pseudo'].' !</span>';
		} else {
			header('Location: rps_login.php?msg_error');
			exit;
		}
	}
}
if(isset($_GET['msg_noadmin'])) $message = '<span class="info-message" id="logout-msg">/!\ Accès interdit /!\</span>';
 ?>

<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Role Playing Smash</title>

	<link rel="manifest" href="manifest.json">
	<link rel="icon" href="img/icon-192.png" type="image/png">
	<link rel="apple-touch-icon" href="img/icon-512.png">
	<meta name="theme-color" content="black">

	<link rel="stylesheet" href="style.css?t=<?php echo time(); ?>">
</head>
<body>

	<div class="rectangles">
		<div class="rect r1"></div>
		<div class="rect r2"></div>
		<div class="rect r3"></div>
		<div class="rect r4"></div>
		<div class="rect r5"></div>
	</div>
	
	<div class="cercle">
		<div class="overlay rA"></div>
		<div class="overlay rB"></div>
		<img src="img/logo.png" alt="Logo" class="logo-cercle">
	</div>
	
	<header>
		<?php if (isset($_SESSION['rps_user'])) echo '<span><a href="rps_logout.php">Déconnexion</a></span>'.$message;
		else { echo '<span><a href="rps_login.php">Connexion</a></span>';
		if(isset($_GET['msg']) && $_GET['msg'] === 'logout') echo '<span class="info-message" id="logout-msg">Vous êtes désormais déconnecté.</span>';
		echo '<span><a href="rps_register.php">Inscription</a></span>'; } ?>
	</header>

<?php include('foot.php'); ?>