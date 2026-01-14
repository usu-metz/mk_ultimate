<?php
require('config.php');
require('fonctions.php');

/*===========================================*/
/* === VALIDATION DU PARAMÈTRE ============ */
/*===========================================*/
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	$message = "<div class='msg_erreur'>Identifiant de tournoi manquant ou invalide.</div>";
	header('Location: rpa_tournois_add.php?msg=' . urlencode($message));
	exit;
}

$id_startgg = (int)$_GET['id'];
$force = isset($_GET['force']) && $_GET['force'] == 1;

/*===========================================*/
/* === VÉRIFICATION SI DÉJÀ IMPORTÉ ======= */
/*===========================================*/
$stmt_check = $pdo->prepare("SELECT maj_matchs FROM rps_tournois_urls WHERE id_startgg = ?");
$stmt_check->execute([$id_startgg]);
$maj_matchs = $stmt_check->fetchColumn();

if ($maj_matchs == 1 && !$force) {
	$message = "<div class='msg_info'>Les matchs de ce tournoi ont déjà été importés.<br>
	Ajoute <code>?force=1</code> à l’URL pour forcer un nouvel import.</div>";
	header('Location: rpa_tournois_add.php?msg=' . urlencode($message));
	exit;
}

/*===========================================*/
/* === RÉCUPÉRATION DU TOURNOI ============ */
/*===========================================*/
$queryTournament = <<<GRAPHQL
{
	tournament(id: $id_startgg) {
		id
		name
		events { id name }
	}
}
GRAPHQL;

try {
	$data = startgg_query($queryTournament);
	if (empty($data['tournament'])) throw new Exception("Aucune donnée de tournoi retournée.");

	$tournament = $data['tournament'];
	$nb_sets_importes = 0;
	$nb_games_importes = 0;

	foreach ($tournament['events'] as $event) {
		$page = 1;
		$totalPages = 1;

		do {
			$query_sets = <<<GRAPHQL
			{
				event(id: {$event['id']}) {
					sets(page: $page, perPage: 30) {
						pageInfo { totalPages total }
						nodes {
							id
							fullRoundText
							displayScore
							winnerId
							startAt
							slots {
								id
								entrant {
									id
									name
									participants { player { id } }
								}
								standing { stats { score { value } } }
							}
							games { id }
						}
					}
				}
			}
			GRAPHQL;

			$data_page = startgg_query($query_sets);
			$eventData = $data_page['event'] ?? null;
			if (!$eventData || empty($eventData['sets']['nodes'])) break;

			$totalPages = $eventData['sets']['pageInfo']['totalPages'] ?? 1;

			foreach ($eventData['sets']['nodes'] as $set) {
				$id_set = $set['id'];
				$display_score = $set['displayScore'] ?? null;
				$entrant_j1 = $set['slots'][0]['entrant']['id'] ?? null;
				$entrant_j2 = $set['slots'][1]['entrant']['id'] ?? null;
				$winner_entrant = $set['winnerId'] ?? null;
				$id_j1 = $set['slots'][0]['entrant']['participants'][0]['player']['id'] ?? null;
				$id_j2 = $set['slots'][1]['entrant']['participants'][0]['player']['id'] ?? null;

				/*==============================*/
				/*== MAPPING winnerId → playerId */
				/*==============================*/
				if ($winner_entrant == $entrant_j1) {
					$winner_id = $id_j1;	// player_id réel
				} elseif ($winner_entrant == $entrant_j2) {
					$winner_id = $id_j2;	// player_id réel
				} else {
					$winner_id = null;
				}
				$round = $set['fullRoundText'] ?? null;
				$date_match = isset($set['startAt']) ? date('Y-m-d H:i:s', $set['startAt']) : null;

				$score_j1 = $set['slots'][0]['standing']['stats']['score']['value'] ?? null;
				$score_j2 = $set['slots'][1]['standing']['stats']['score']['value'] ?? null;
				$best_of = isset($set['games']) ? count($set['games']) : null;

				/*----------------------------------------------------*/
				/* Insertion du SET                                  */
				/*----------------------------------------------------*/
				$stmt = $pdo->prepare("
					INSERT INTO rps_tournois_sets
					(id_tournoi, id_set, numero_round, id_j1, id_j2, score_j1, score_j2, winner_id, best_of, display_score, date_match)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
					ON DUPLICATE KEY UPDATE
						display_score = VALUES(display_score),
						score_j1 = VALUES(score_j1),
						score_j2 = VALUES(score_j2),
						winner_id = VALUES(winner_id),
						best_of = VALUES(best_of)
				");
				$stmt->execute([
					$id_startgg,
					$id_set,
					$round,
					$id_j1,
					$id_j2,
					$score_j1,
					$score_j2,
					$winner_id,
					$best_of,
					$display_score,
					$date_match
				]);
				$nb_sets_importes++;

				/*====================================================*/
				/* === REQUÊTE : GAMES DU SET ======================= */
				/*====================================================*/
				$query_games = <<<GRAPHQL
				{
					set(id: $id_set) {
						games {
							id
							orderNum
							winnerId
							stage { name }
							selections {
								entrant {
									id
									name
									participants { player { id } }
								}
								character { name }
							}
						}
					}
				}
				GRAPHQL;

				try {
					$data_game = startgg_query($query_games);
					$games = $data_game['set']['games'] ?? [];

					foreach ($games as $game) {
						$num_game = $game['orderNum'] ?? null;
						$stage = $game['stage']['name'] ?? null;
						$winner_entrant = $game['winnerId'] ?? null;

						/*==============================*/
						/*== MAPPING GAMES WINNER =====*/
						/*==============================*/
						if ($winner_entrant == $entrant_j1) {
							$winner = $id_j1;	// player_id réel
						} elseif ($winner_entrant == $entrant_j2) {
							$winner = $id_j2;	// player_id réel
						} else {
							$winner = null;
						}

						// Identification des personnages
						$nom_perso_j1 = $nom_perso_j2 = null;
						if (!empty($game['selections'])) {
							foreach ($game['selections'] as $sel) {
								$entrant_player_id = $sel['entrant']['participants'][0]['player']['id'] ?? null;
								$char_name = $sel['character']['name'] ?? null;

								if ($entrant_player_id == $id_j1) $nom_perso_j1 = $char_name;
								elseif ($entrant_player_id == $id_j2) $nom_perso_j2 = $char_name;
							}
						}

						// Conversion noms → ID depuis rps_personnages
						$id_perso_j1 = $id_perso_j2 = null;

						if ($nom_perso_j1) {
							$stmt_p1 = $pdo->prepare("SELECT id FROM rps_personnages WHERE LOWER(nom) = LOWER(?) LIMIT 1");
							$stmt_p1->execute([$nom_perso_j1]);
							$id_perso_j1 = $stmt_p1->fetchColumn();
						}

						if ($nom_perso_j2) {
							$stmt_p2 = $pdo->prepare("SELECT id FROM rps_personnages WHERE LOWER(nom) = LOWER(?) LIMIT 1");
							$stmt_p2->execute([$nom_perso_j2]);
							$id_perso_j2 = $stmt_p2->fetchColumn();
						}

						$stmt_game = $pdo->prepare("
							INSERT INTO rps_tournois_games
							(id_set, num_game, stage, winner_id, perso_j1, perso_j2)
							VALUES (?, ?, ?, ?, ?, ?)
							ON DUPLICATE KEY UPDATE
								winner_id = VALUES(winner_id),
								stage = VALUES(stage),
								perso_j1 = VALUES(perso_j1),
								perso_j2 = VALUES(perso_j2)
						");
						$stmt_game->execute([
							$id_set,
							$num_game,
							$stage,
							$winner,
							$id_perso_j1,
							$id_perso_j2
						]);
						$nb_games_importes++;
					}
				} catch (Exception $e) {
					continue;
				}
			}

			$page++;
			usleep(400000);
		} while ($page <= $totalPages);
	}

	// ✅ Mise à jour du tournoi
	$stmt_flag = $pdo->prepare("
		UPDATE rps_tournois_urls 
		SET maj_matchs = 1, last_import = NOW(), nb_sets = ?, nb_games = ? 
		WHERE id_startgg = ?
	");
	$stmt_flag->execute([$nb_sets_importes, $nb_games_importes, $id_startgg]);

	/*====================================================*/
	/* === MISE À JOUR DES COMPTEURS DE JOUEURS ========= */
	/*====================================================*/

	// Récupère tous les joueurs liés à ce tournoi
	$stmt_players = $pdo->prepare("SELECT id_joueur FROM rps_tournois_players WHERE id_tournoi = ?");
	$stmt_players->execute([$id_startgg]);
	$players = $stmt_players->fetchAll(PDO::FETCH_COLUMN);

	foreach ($players as $id_joueur) {
		// Comptage des sets joués
		$stmt_sets = $pdo->prepare("
			SELECT COUNT(*) FROM rps_tournois_sets 
			WHERE id_tournoi = ? AND (id_j1 = ? OR id_j2 = ?)
		");
		$stmt_sets->execute([$id_startgg, $id_joueur, $id_joueur]);
		$set_count = (int)$stmt_sets->fetchColumn();

		// Comptage des games jouées
		$stmt_games = $pdo->prepare("
			SELECT COUNT(g.id) 
			FROM rps_tournois_games g
			INNER JOIN rps_tournois_sets s ON g.id_set = s.id_set
			WHERE s.id_tournoi = ? 
			AND (s.id_j1 = ? OR s.id_j2 = ?)
		");
		$stmt_games->execute([$id_startgg, $id_joueur, $id_joueur]);
		$game_count = (int)$stmt_games->fetchColumn();

		// Mise à jour du joueur
		$stmt_update = $pdo->prepare("
			UPDATE rps_tournois_players
			SET set_count = ?, game_count = ?
			WHERE id_tournoi = ? AND id_joueur = ?
		");
		$stmt_update->execute([$set_count, $game_count, $id_startgg, $id_joueur]);
	}
	
	/*====================================================*/
	/* === MISE À JOUR DES COMPTEURS GLOBAUX JOUEURS ==== */
	/*====================================================*/
	foreach ($players as $id_joueur) {
		// Récupère les stats du joueur sur ce tournoi
		$stmt_local = $pdo->prepare("
			SELECT set_count, game_count
			FROM rps_tournois_players
			WHERE id_tournoi = ? AND id_joueur = ?
		");
		$stmt_local->execute([$id_startgg, $id_joueur]);
		$stats_local = $stmt_local->fetch(PDO::FETCH_ASSOC);

		$set_count_local = (int)($stats_local['set_count'] ?? 0);
		$game_count_local = (int)($stats_local['game_count'] ?? 0);

		// Incrémente les compteurs globaux dans rps_players
		$stmt_update_global = $pdo->prepare("
			UPDATE rps_players
			SET 
				nb_sets = nb_sets + ?, 
				nb_matchs = nb_matchs + ?, 
				nb_tournois = nb_tournois + 1
			WHERE player_id = ?
		");
		$stmt_update_global->execute([$set_count_local, $game_count_local, $id_joueur]);
	}

	$message = "<div class='msg_succes'>Import terminé : {$nb_sets_importes} sets et {$nb_games_importes} games ajoutés.</div>";
	header('Location: rpa_tournois_add.php?msg=' . urlencode($message));
	exit;

} catch (Exception $e) {
	$message = "<div class='msg_erreur'>Erreur lors de l’import : " . htmlspecialchars($e->getMessage()) . "</div>";
	header('Location: rpa_tournois_add.php?msg=' . urlencode($message));
	exit;
}
?>
