<?php include('rpa_head.php'); ?>

<section class="stages-section">
	<h2>Tous les stages</h2>

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
			// Récupère tous les stages triés par nom
			$stmt = $pdo->query("SELECT nom, icon_url FROM rps_stage ORDER BY nom ASC");
			$stages = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (empty($stages)) {
				echo '<tr><td colspan="3">Aucun stage enregistré.</td></tr>';
			} else {
				$index = 1;
				foreach ($stages as $s) {
					echo '<tr>';
					echo '<td>' . $index++ . '</td>';
					echo '<td>' . htmlspecialchars($s['nom']) . '</td>';

					if (!empty($s['icon_url'])) {
						echo '<td><img src="' . htmlspecialchars($s['icon_url']) . '" alt="' . htmlspecialchars($s['nom']) . '" class="thumb-stage"></td>';
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

<!-- Fenêtre modale pour afficher l'image en grand -->
<div id="img_modal" class="img-modal">
	<span class="close-modal">&times;</span>
	<img id="modal_img" src="" alt="Aperçu stage">
</div>

<script>
// Affichage modale image
document.addEventListener('DOMContentLoaded', () => {
	const modal = document.getElementById('img_modal');
	const modalImg = document.getElementById('modal_img');
	const closeBtn = document.querySelector('.close-modal');

	document.querySelectorAll('.thumb-stage').forEach(img => {
		img.addEventListener('click', () => {
			modal.style.display = 'flex';
			modalImg.src = img.src;
			modalImg.alt = img.alt;
		});
	});

	closeBtn.addEventListener('click', () => {
		modal.style.display = 'none';
	});

	modal.addEventListener('click', e => {
		if (e.target === modal) modal.style.display = 'none';
	});
});
</script>

<?php include('foot.php'); ?>
