<?php require('config.php');
include('fonctions.php');
if(!isset($_SESSION['rps_user']) OR $_SESSION['rps_user']['role'] != 'admin') { header('Location: index.php?msg_noadmin'); } ?>

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

	<header>
		<a href="index.php" class="titre_back">↶</a>
		<h1 class="titre">Administration</h1>
	</header>

	<div class="corps">
		<nav id="a_menu">
			<a href="rpa_tournois_add.php">Tournoi</a>
			<a href="rpa_players.php">Joueurs</a>
			<a href="rpa_persos.php">Personnages</a>
			<a href="rpa_stages.php">Stages</a>
		</nav>