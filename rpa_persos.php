<?php include('rpa_head.php'); ?>

<section class="persos-section">
	<h2>Tous les personnages</h2>

	<table class="table-admin">
		<thead>
			<tr>
				<th></th>
				<th>Nom</th>
				<th>Image</th>
			</tr>
		</thead>
		<tbody>
			<?php
			// Récupère tous les personnages triés par nom
			$stmt = $pdo->query("SELECT nom, icon_url FROM rps_personnages ORDER BY nom ASC");
			$persos = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// Si aucun personnage trouvé
			if (empty($persos)) {
				echo '<tr><td colspan="3">Aucun personnage enregistré.</td></tr>';
			} else {
				$index = 1;
				foreach ($persos as $p) {
					echo '<tr>';
					echo '<td>' . $index++ . '</td>';
					echo '<td>' . htmlspecialchars($p['nom']) . '</td>';
					
					// Si l'image existe, l'afficher, sinon texte "Aucune image"
					if (!empty($p['icon_url'])) {
						echo '<td><img src="' . htmlspecialchars($p['icon_url']) . '" alt="' . htmlspecialchars($p['nom']) . '" style="width:50px;height:50px;object-fit:contain;"></td>';
					} else {
						echo '<td><span style="opacity:0.6;">Aucune image</span></td>';
					}
					
					echo '</tr>';
				}
			}
			?>
		</tbody>
	</table>
</section>

<?php include('foot.php'); ?>
