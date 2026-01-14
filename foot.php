	</div>

	<footer>
		<a href="https://kaiten-matsuri.fr" target="_blank"><img src="img/part_km.png" alt="Kaiten Matsuri"></a>
		<a href="https://wtfood-metz.fr" target="_blank"><img src="img/part_wtf.png" alt="WTFood Metz"></a>
		<?php if(isset($_SESSION['rps_user']['role']) AND $_SESSION['rps_user']['role'] == 'admin') echo '<a href="rpa_index.php"><img src="img/admin.png"></a>'; ?>
	</footer>
	
	<script>
		document.addEventListener('DOMContentLoaded', () => {
			const msg = document.getElementById('logout-msg');
			if (msg) {
				setTimeout(() => {
					msg.classList.add('fade-out');
					setTimeout(() => msg.remove(), 600); // suppression après fade
				}, 4000); // visible 4s
			}
		});
	</script>
	
</body>
</html>
