<?php
require('fonctions.php');
require('config.php');

/*====================================================*/
/* === RÉCUPÉRATION DU TOURNOI (ID START.GG) ======== */
/*====================================================*/
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	header('Location: rpa_tournois_add.php?msg=' . urlencode('Erreur : identifiant de tournoi manquant.'));
	exit;
}

$id_tournoi = (int)$_GET['id'];
$message = '';
$joueurs_ajoutes = [];

/*====================================================*/
/* === REQUÊTE API : LISTE DES JOUEURS DU TOURNOI ==== */
/*====================================================*/
$query = <<<GRAPHQL
{
	tournament(id: $id_tournoi) {
		events {
			entrants(query: { perPage: 500 }) {
				nodes {
					standing {
						placement
					}
					participants {
						player {
							id
							gamerTag
							prefix
							user {
								slug
								genderPronoun
							}
						}
					}
				}
			}
		}
	}
}
GRAPHQL;

try {
	$data = startgg_query($query);
	$nodes = $data['tournament']['events'][0]['entrants']['nodes'] ?? [];

	if (!$nodes) {
		throw new Exception('Aucun joueur trouvé pour ce tournoi.');
	}

	$nb_ajoutes = 0;

	foreach ($nodes as $entrant) {
		$place = $entrant['standing']['placement'] ?? null;
		$set_count = 0;
		$game_count = 0;

		foreach ($entrant['participants'] as $p) {
			$player = $p['player'];
			if (!$player) continue;

			$player_id = $player['id'];
			$gamer_tag = $player['gamerTag'] ?? 'Inconnu';
			$prefix = $player['prefix'] ?? null;
			$user_slug = $player['user']['slug'] ?? null;
			$gender = $player['user']['genderPronoun'] ?? null;

			// Vérifie si le joueur existe déjà
			$stmt = $pdo->prepare("SELECT id FROM rps_players WHERE player_id = ?");
			$stmt->execute([$player_id]);
			$exists = $stmt->fetch();

			if (!$exists) {
				// Insère le joueur dans rps_players
				$insert = $pdo->prepare("INSERT INTO rps_players 
					(player_id, gamer_tag, prefix, user_slug, gender_pronoun, date_ajout)
					VALUES (?, ?, ?, ?, ?, NOW())");
				$insert->execute([$player_id, $gamer_tag, $prefix, $user_slug, $gender]);

				$nb_ajoutes++;
				$joueurs_ajoutes[] = $gamer_tag;
			}

			// Ajoute ou met à jour le lien tournoi-joueur avec classement
			$link = $pdo->prepare("INSERT INTO rps_tournois_players 
				(id_joueur, id_tournoi, place, set_count, game_count)
				VALUES (?, ?, ?, ?, ?)
				ON DUPLICATE KEY UPDATE
					place = VALUES(place),
					set_count = VALUES(set_count),
					game_count = VALUES(game_count)");
			$link->execute([$player_id, $id_tournoi, $place, $set_count, $game_count]);
		}
	}

	// Mise à jour du statut du tournoi
	$pdo->prepare("UPDATE rps_tournois_urls SET maj_players = 1 WHERE id_startgg = ?")->execute([$id_tournoi]);

	$message = "<div class='msg_succes'>Import réussi : $nb_ajoutes joueurs ajoutés<br>"
		. htmlspecialchars(implode(', ', $joueurs_ajoutes)) . "</div>";

} catch (Exception $e) {
	$message = "<div class='msg_erreur'>Échec de l’import : " . htmlspecialchars($e->getMessage()) . "</div>";
}

/*====================================================*/
/* === REDIRECTION VERS LA PAGE PRINCIPALE ========== */
/*====================================================*/
header('Location: rpa_tournois_add.php?msg=' . urlencode($message));
exit;
?>
