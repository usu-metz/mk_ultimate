<?php session_start();
if (isset($_SESSION['rps_user'])) {	 header('Location: index.php'); exit; }

$titre_top = 'Inscription';
require('head.php'); ?>

<form method="post" action="rps_register_traitement.php" class="form_register">
	<div class="form_titre">Tes informations</div>
	<div class="input-block">
		<div class="left-bar"></div>
		<input type="text" name="username" placeholder="Pseudo" required>
	</div>
	<div class="input-block">
		<div class="left-bar"></div>
		<input type="email" name="email" placeholder="Email" required>
	</div>
	<div class="input-block">
		<div class="left-bar"></div>
		<input type="password" name="password" placeholder="Mot de passe" required>
	</div>
	<div class="input-block">
		<div class="left-bar"></div>
		<input type="text" name="startgg_id" placeholder="ID Start.gg" required>
	</div>
	<button type="submit" class="btn-register">S'inscrire</button>
</form>

<?php require('foot.php'); ?>