<?php
include('rpa_head.php');

/*=========================================================*/
/*	= RÉCUP ID DU TOURNOI =	*/
/*=========================================================*/
$id_tournoi = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_tournoi <= 0) {
	echo "<p>Tournoi introuvable.</p>";
	include('foot.php');
	exit;
}

/*=========================================================*/
/*	= STANDING : SET W/L & GAME W/L =	*/
/*=========================================================*/

$stmt_standing = $pdo->prepare("
	SELECT
		p.player_id,
		p.prefix,
		p.gamer_tag,
		tp.place,
		tp.id_joueur AS entrant_id,

		/*========== SET WINS ==========*/
		(
			SELECT COUNT(*)
			FROM rps_tournois_sets s
			WHERE s.id_tournoi = tp.id_tournoi
			AND s.winner_id = tp.id_joueur
		) AS set_wins,

		/*========== SET LOSSES ==========*/
		(
			SELECT COUNT(*)
			FROM rps_tournois_sets s
			WHERE s.id_tournoi = tp.id_tournoi
			AND s.winner_id != tp.id_joueur
			AND (s.id_j1 = tp.id_joueur OR s.id_j2 = tp.id_joueur)
		) AS set_losses,

		/*========== GAME WINS ==========*/
		(
			SELECT COUNT(*)
			FROM rps_tournois_games g
			INNER JOIN rps_tournois_sets s ON g.id_set = s.id_set
			WHERE s.id_tournoi = tp.id_tournoi
			AND g.winner_id = tp.id_joueur
		) AS game_wins,

		/*========== GAME LOSSES ==========*/
		(
			SELECT COUNT(*)
			FROM rps_tournois_games g
			INNER JOIN rps_tournois_sets s ON g.id_set = s.id_set
			WHERE s.id_tournoi = tp.id_tournoi
			AND g.winner_id != tp.id_joueur
			AND (s.id_j1 = tp.id_joueur OR s.id_j2 = tp.id_joueur)
		) AS game_losses

	FROM rps_tournois_players tp
	LEFT JOIN rps_players p ON tp.id_joueur = p.player_id
	WHERE tp.id_tournoi = ?
	ORDER BY tp.place ASC
");

$stmt_standing->execute([$id_tournoi]);
$standing = $stmt_standing->fetchAll(PDO::FETCH_ASSOC);


/*=========================================================*/
/*	= PERSONNAGES LES PLUS JOUÉS =	*/
/*=========================================================*/

$stmt_persos = $pdo->prepare("
	SELECT 
		p.id,
		p.nom,
		p.icon_url,
		(
			SELECT COUNT(*) 
			FROM rps_tournois_games g
			WHERE g.perso_j1 = p.id
			AND g.id_set IN (
				SELECT id_set FROM rps_tournois_sets WHERE id_tournoi = ?
			)
		)
		+
		(
			SELECT COUNT(*) 
			FROM rps_tournois_games g
			WHERE g.perso_j2 = p.id
			AND g.id_set IN (
				SELECT id_set FROM rps_tournois_sets WHERE id_tournoi = ?
			)
		) AS nb
	FROM rps_personnages p
	HAVING nb > 0
	ORDER BY nb DESC, p.nom ASC
");

$stmt_persos->execute([$id_tournoi, $id_tournoi]);
$persos = $stmt_persos->fetchAll(PDO::FETCH_ASSOC);

/*=========================================================*/
/*	= RÉCAP COMPLET MATCHS PAR ROUND =	*/
/*=========================================================*/

$stmt_sets = $pdo->prepare("
	SELECT 
		s.id_set,
		s.numero_round,
		s.id_j1,
		s.id_j2,
		s.score_j1,
		s.score_j2,
		s.winner_id,
		p1.prefix AS prefix1, p1.gamer_tag AS tag1,
		p2.prefix AS prefix2, p2.gamer_tag AS tag2
	FROM rps_tournois_sets s
	LEFT JOIN rps_players p1 ON p1.player_id = s.id_j1
	LEFT JOIN rps_players p2 ON p2.player_id = s.id_j2
	WHERE s.id_tournoi = ?
	ORDER BY s.numero_round ASC, s.id_set ASC
");
$stmt_sets->execute([$id_tournoi]);
$sets = $stmt_sets->fetchAll(PDO::FETCH_ASSOC);

/*=========================================================*/
/*	= CHARGER LES GAMES PAR SET =	*/
/*=========================================================*/

$stmt_games = $pdo->prepare("
	SELECT 
		g.id,
		g.id_set,
		g.num_game,
		g.winner_id,
		g.stage,
		g.perso_j1,
		g.perso_j2,
		pc1.icon_url AS icon_j1,
		pc2.icon_url AS icon_j2
	FROM rps_tournois_games g
	LEFT JOIN rps_personnages pc1 ON pc1.id = g.perso_j1
	LEFT JOIN rps_personnages pc2 ON pc2.id = g.perso_j2
	WHERE g.id_set = ?
	ORDER BY g.num_game ASC
");

?>

<section class="tournoi_container">

	<!--==============================-->
	<!--======== TABLE STANDING ======-->
	<!--==============================-->
	<table class="table-admin tbl_standing">
		<thead>
			<tr>
				<th>Place</th>
				<th>Nom</th>
				<th>Sets (W/L)</th>
				<th>Games (W/L)</th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($standing as $row): ?>
				<?php
				/*========= Pourcentages sets =========*/
				$set_total = max(1, $row['set_wins'] + $row['set_losses']);
				$set_pct = round(($row['set_wins'] / $set_total) * 100);

				/*========= Pourcentages games =========*/
				$game_total = max(1, $row['game_wins'] + $row['game_losses']);
				$game_pct = round(($row['game_wins'] / $game_total) * 100);
				?>
				<tr>
					<td><?php echo $row['place']; ?></td>
					<td><?php if($row['prefix']) echo htmlspecialchars('['.trim($row['prefix']).'] '); echo htmlspecialchars($row['gamer_tag']); ?></td>

					<td>
						<?php echo $row['set_wins'] . " / " . $row['set_losses']; ?>
						<div class="standing_percent"><?php echo $set_pct; ?>%</div>
					</td>

					<td>
						<?php echo $row['game_wins'] . " / " . $row['game_losses']; ?>
						<div class="standing_percent"><?php echo $game_pct; ?>%</div>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>


	<!--====================================-->
	<!--======= TABLE PERSOS JOUÉS =========-->
	<!--====================================-->
	<table class="table-admin tbl_persos">
		<thead>
			<tr>
				<th>Personnage</th>
				<th>Nb</th>
			</tr>
		</thead>

		<tbody>
			<?php foreach ($persos as $p): ?>
			<tr>
				<td class="perso_cell">
					<?php if (!empty($p['icon_url'])): ?>
						<img src="<?php echo $p['icon_url']; ?>" class="perso_icon">
					<?php endif; ?>
					<?php echo htmlspecialchars($p['nom']); ?>
				</td>
				<td><?php echo $p['nb']; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</section>

<!--========================================-->
<!--======== RECAP COMPLET DES MATCHS ======-->
<!--========================================-->

<table class="table-admin recap_matchs">
	<thead>
		<tr>
			<th colspan="2" class="recap_title">Récapitulatif complet des matchs</th>
		</tr>
	</thead>

	<tbody>
		<?php
		$round_actuel = null;

		foreach ($sets as $set):

			/* affichage du round une seule fois */
			if ($round_actuel !== $set['numero_round']):
				$round_actuel = $set['numero_round'];
		?>
			<tr class="round_row" data-round="<?php echo htmlspecialchars($round_actuel); ?>">
				<th colspan="2" class="round_title toggle_round">
					<?php echo htmlspecialchars($round_actuel); ?>
					<span class="round_arrow">▼</span>
				</th>
			</tr>
		<?php endif; ?>

		<tr class="match_row round_content round_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $round_actuel); ?>">
			<!--============================-->
			<!--===== COLONNE SETS ========-->
			<!--============================-->
			<td class="match_summary">
				<div class="match_line">
					<span class="player1">
						<?php if($set['prefix1']) echo htmlspecialchars('['.trim($set['prefix1']).'] '); echo htmlspecialchars($set['tag1']); ?>
					</span>

					<span class="score">
						<?php echo $set['score_j1']; ?> - <?php echo $set['score_j2']; ?>
					</span>

					<span class="player2">
						<?php echo htmlspecialchars(trim($set['prefix2'] . ' ' . $set['tag2'])); ?>
					</span>
				</div>
			</td>

			<!--============================-->
			<!--====== COLONNE GAMES ======-->
			<!--============================-->
			<td class="games_summary">
				<?php
				$stmt_games->execute([$set['id_set']]);
				$games = $stmt_games->fetchAll(PDO::FETCH_ASSOC);

				foreach ($games as $g):
					$win1 = ($g['winner_id'] == $set['id_j1']);
					$win2 = ($g['winner_id'] == $set['id_j2']);
				?>
					<div class="game_line">
						<div class="game_num">G<?php echo $g['num_game']; ?></div>

						<div class="game_p1 <?php echo $win1 ? 'win' : ''; ?>">
							<?php if ($g['icon_j1']): ?>
								<img src="<?php echo $g['icon_j1']; ?>" class="perso_icon_small">
							<?php endif; ?>
							<?php echo $win1 ? "Victoire" : "Défaite"; ?>
						</div>

						<div class="game_p2 <?php echo $win2 ? 'win' : ''; ?>">
							<?php if ($g['icon_j2']): ?>
								<img src="<?php echo $g['icon_j2']; ?>" class="perso_icon_small">
							<?php endif; ?>
							<?php echo $win2 ? "Victoire" : "Défaite"; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</td>
		</tr>

		<?php endforeach; ?>
	</tbody>
</table>
<script>
document.addEventListener('DOMContentLoaded', () => {

	/*====================================================*/
	/*	= GESTION DU DÉPLOIEMENT DES ROUNDS =	*/
	/*====================================================*/

	// Sélecteur : toutes les lignes cliquables "Round X"
	document.querySelectorAll('.toggle_round').forEach(btn => {

		btn.addEventListener('click', () => {

			// Récupération du nom du round (valeur du <tr data-round="...">)
			const parent_tr = btn.closest('.round_row');
			const round_name = parent_tr.dataset.round;

			// Normalisation du nom pour retrouver les lignes associées
			const norm = round_name.replace(/[^a-zA-Z0-9]/g, '_');

			// Sélection de toutes les lignes du round
			const rows = document.querySelectorAll('.round_' + norm);

			// Arrow
			const arrow = btn.querySelector('.round_arrow');

			// Déterminer si replié → afficher / cacher
			const must_open = Array.from(rows).some(r => r.style.display === 'none');

			if (must_open) {
				// OUVRIR
				rows.forEach(r => r.style.display = 'table-row');
				arrow.textContent = '▲';
			} else {
				// FERMER
				rows.forEach(r => r.style.display = 'none');
				arrow.textContent = '▼';
			}
		});
	});

});
</script>

<?php include('foot.php'); ?>
