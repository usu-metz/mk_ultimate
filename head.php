<?php require('config.php');
include('fonctions.php'); ?>

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
		<h1 class="titre"><?php echo $titre_top; ?></h1>
	</header>

	<div class="corps">
