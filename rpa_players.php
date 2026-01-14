<?php include('rpa_head.php'); 

if (isset($_POST['update_stats'])) {
	// Récupère la liste de tous les joueurs
	$players = $pdo->query("SELECT player_id FROM rps_players")->fetchAll(PDO::FETCH_COLUMN);

	foreach ($players as $player_id) {
		// Récupère toutes les lignes associées à ce joueur
		$stmt = $pdo->prepare("
			SELECT 
				COUNT(DISTINCT id_tournoi) AS nb_tournois,
				SUM(set_count) AS nb_sets,
				SUM(game_count) AS nb_matchs
			FROM rps_tournois_players
			WHERE id_joueur = ?
		");
		$stmt->execute([$player_id]);
		$stats = $stmt->fetch(PDO::FETCH_ASSOC);

		// Valeurs par défaut si aucune donnée
		$nb_tournois = $stats['nb_tournois'] ?? 0;
		$nb_sets = $stats['nb_sets'] ?? 0;
		$nb_matchs = $stats['nb_matchs'] ?? 0;

		// Met à jour la table principale
		$upd = $pdo->prepare("
			UPDATE rps_players
			SET nb_tournois = ?, nb_sets = ?, nb_matchs = ?
			WHERE player_id = ?
		");
		$upd->execute([$nb_tournois, $nb_sets, $nb_matchs, $player_id]);
	}

	echo "<div class='msg_succes'>Statistiques mises à jour avec succès !</div>";
} ?>

<section class="players-section">
	<h1>Liste des joueurs enregistrés</h1>

	<table class="table-admin">
		<thead>
			<tr>
				<th></th>
				<th>Joueur</th>
				<th>Tournois</th>
				<th>Sets</th>
				<th>Matchs</th>
				<th>ELO</th>
				<th>Profil start.gg</th>
			</tr>
		</thead>
		<tbody>
			<?php
			// Récupération des joueurs
			$stmt = $pdo->query("SELECT prefix, gamer_tag, player_id, nb_tournois, nb_sets, nb_matchs, user_slug FROM rps_players ORDER BY gamer_tag ASC");
			$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (empty($players)) {
				echo '<tr><td colspan="7">Aucun joueur enregistré.</td></tr>';
			} else {
				$index = 1;
				foreach ($players as $p) {
					echo '<tr>';
					echo '<td>' . $index++ . '</td>';

					// Prefix optionnel
					$nom_affiche = ($p['prefix']) ? '<strong>[' . htmlspecialchars($p['prefix']) . ']</strong> ' . htmlspecialchars($p['gamer_tag']) : htmlspecialchars($p['gamer_tag']);
					echo '<td>' . $nom_affiche . '</td>';

					echo '<td>' . (int)$p['nb_tournois'] . '</td>';
					echo '<td>' . (int)$p['nb_sets'] . '</td>';
					echo '<td>' . (int)$p['nb_matchs'] . '</td>';
					echo '<td>1000</td>'; // ELO par défaut

					// Lien start.gg
					if (!empty($p['user_slug'])) {
						echo '<td><a href="https://www.start.gg/' . htmlspecialchars($p['user_slug']) . '" target="_blank" rel="noopener">Voir profil</a></td>';
					} else {
						echo '<td><span style="opacity:0.6;">N/A</span></td>';
					}

					echo '</tr>';
				}
			}
			?>
		</tbody>
	</table>

	<form method="post" style="text-align:center; margin-top:20px;">
		<button type="submit" name="update_stats" class="big-btn">Mettre à jour les statistiques</button>
	</form>
</section>

<?php include('foot.php'); ?>
