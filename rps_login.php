<?php session_start();
if (isset($_SESSION['rps_user'])) {	 header('Location: index.php'); exit; }

$titre_top = 'Connexion';
require('head.php'); ?>

<?php if (isset($_GET['msg_error'])) echo '<div class="msg_erreur"><div>Identifiants de connexion erronés.</div></div>'; ?>

<form method="post" action="index.php" class="form_login">
	<div class="form_titre">Connexion</div>
	<div class="input-block">
		<div class="left-bar"></div>
		<input type="username" name="username" placeholder="Pseudo" required>
	</div>
	<div class="input-block">
		<div class="left-bar"></div>
		<input type="password" name="password" placeholder="Mot de passe" required>
	</div>
	<button type="submit" class="btn-login">Se connecter</button>
</form>

<?php require('foot.php'); ?>
