<?php require('rpa_head.php'); 

$message = '';
/*===================================*/
/* === TRAITEMENT DU FORMULAIRE ==== */
/*===================================*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lien_startgg'])) {

	$lien = trim($_POST['lien_startgg']);

	// Extraction du slug
	if (preg_match('#start\.gg/tournament/([^/?]+)#i', $lien, $matches)) {
		$slug = $matches[1];
	} else {
		$message = "<div class='msg_erreur'>Lien invalide.</div>";
	}	

	if ($message === '') {

		// === Requête GraphQL ===
		$query = <<<GRAPHQL
		{
			tournament(slug: "$slug") {
				id
				name
				startAt
				events(limit: 1) {
					name
					numEntrants
				}
			}
		}
		GRAPHQL;

		try {
			$data = startgg_query($query);
			$tournoi = $data['tournament'];

			if (!$tournoi) {
				$message = "<div class='msg_erreur'>Tournoi introuvable sur Start.gg.</div>";
			} else {
				$id_startgg = $tournoi['id'];
				$nom = $tournoi['name'];
				$type = $tournoi['events'][0]['name'] ?? null;
				$nb_joueurs = $tournoi['events'][0]['numEntrants'] ?? null;
				$date_tournoi = !empty($tournoi['startAt']) ? date('Y-m-d', $tournoi['startAt']) : null;

				/*=============================*/
				/* === VÉRIFIE SI EXISTANT === */
				/*=============================*/
				$check = $pdo->prepare("SELECT nom FROM rps_tournois_urls WHERE id_startgg = ?");
				$check->execute([$id_startgg]);
				$existant = $check->fetch();

				if ($existant) {
					$message = "<div class='msg_erreur'>Tournoi déjà enregistré : <b>" . htmlspecialchars($existant['nom']) . "</b></div>";
				} else {
					// Insertion
					$stmt = $pdo->prepare("INSERT INTO rps_tournois_urls 
						(url, id_startgg, nom, date_tournoi, type, nb_joueurs, date_ajout, last_import, commentaire)
						VALUES (?, ?, ?, ?, ?, ?, NOW(), NULL, '')");
					$stmt->execute([$lien, $id_startgg, $nom, $date_tournoi, $type, $nb_joueurs]);

					$message = "<div class='msg_succes'>Tournoi ajouté : <b>$nom</b> ($type) – $nb_joueurs joueurs</div>";
				}
			}

		} catch (Exception $e) {
			$message = "<div class='msg_erreur'>Erreur Start.gg : " . htmlspecialchars($e->getMessage()) . "</div>";
		}
	}
}

/*===================================*/
/* === RÉCUPÉRATION DES TOURNOIS ==== */
/*===================================*/
$tournois = $pdo->query("SELECT * FROM rps_tournois_urls ORDER BY date_tournoi DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?= $message ?>
<?php if (isset($_GET['msg'])) { echo $_GET['msg']; } ?>

<h2>Gestions des tournois</h2>

<form method="post" action="" class="form_admin" id="form_tournoi">
	<div class="form_titre">Ajout d'un tournoi</div>
	<div class="input-block">
		<div class="left-bar"></div>
		<input type="url" name="lien_startgg" id="lien_startgg" placeholder="Lien start.gg" required>
	</div>
	<button type="submit" class="btn-admin" id="btn_submit" disabled>Enregistrer</button>
</form>

<!-- ============================== -->
<!-- ===== TABLEAU DES DONNÉES ==== -->
<!-- ============================== -->
<?php if ($tournois): ?>
	<table class="table-admin">
		<thead>
			<tr>
				<th></th>
				<th></th>
				<th>Nom du tournoi</th>
				<th>Date tournoi</th>
				<th>Type / Event</th>
				<th>Joueurs</th>
				<th>Lien</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($tournois as $t): ?>
				<tr>
					<td>
						<?php if ((int)$t['maj_players'] === 0): ?>
							<a href="rpa_tournois_players_add.php?id=<?= $t['id_startgg'] ?>">
								<img src="img/add_players.png" class="icon">
							</a>
						<?php else: ?>
							<a href="rpa_tournois_players_add.php?id=<?= $t['id_startgg'] ?>&?force=1"><img src="img/ok.png" class="icon"></a>
						<?php endif; ?>
					</td>
					<td>
						<?php if ((int)$t['maj_players'] === 0): ?>
							<img src="img/add_fights.png" class="icon grayscale">
						<?php elseif ((int)$t['maj_players'] === 1 && (int)$t['maj_matchs'] === 0): ?>
							<a href="rpa_tournois_matchs_add.php?id=<?= $t['id_startgg'] ?>">
								<img src="img/add_fights.png" class="icon">
							</a>
						<?php else: ?>
							<a href="rpa_tournois_matchs_add.php?id=<?= $t['id_startgg'] ?>&force=1"><img src="img/ok.png" class="icon"></a>
						<?php endif; ?>
					</td>
					<td>
						<?php if ((int)$t['maj_players'] === 1 && (int)$t['maj_matchs'] === 1)
							echo '<a href="rpa_tournois.php?id='.$t['id_startgg'].'">'.htmlspecialchars($t['nom']).'</a>';
						else echo htmlspecialchars($t['nom']); ?>
					</td>
					<td><?= $t['date_tournoi'] ? date('d/m/Y', strtotime($t['date_tournoi'])) : '-' ?></td>
					<td><?= htmlspecialchars($t['type']) ?></td>
					<td><?= htmlspecialchars($t['nb_joueurs']) ?></td>
					<td><a href="<?= htmlspecialchars($t['url']) ?>" target="_blank"><img src="img/lien.png" class="icon"></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>
	<p>Aucun tournoi enregistré pour le moment.</p>
<?php endif; ?>


<script>
// Vérification en direct du lien
document.addEventListener('DOMContentLoaded', () => {
	const input = document.getElementById('lien_startgg');
	const btn = document.getElementById('btn_submit');
	const regex = /^https?:\/\/(www\.)?start\.gg\/tournament\/[a-zA-Z0-9-_]+/i;

	input.addEventListener('input', () => {
		const ok = regex.test(input.value.trim());
		input.style.borderColor = ok ? 'limegreen' : 'crimson';
		btn.disabled = !ok;
	});
});
</script>

<?php require('foot.php'); ?>
