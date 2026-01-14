<?php /* Connexion API Start.gg */
function startgg_query($query) {
	$ch = curl_init('https://api.start.gg/gql/alpha');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Authorization: Bearer a4ad1289292a17e46e4565bea43c38b3',	// <-- À remplacer par ton token Start.gg
		'Content-Type: application/json'
	]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
	$response = curl_exec($ch);
	if (curl_errno($ch)) throw new Exception('Erreur cURL : ' . curl_error($ch));
	curl_close($ch);

	$data = json_decode($response, true);
	if (isset($data['errors'])) throw new Exception(json_encode($data['errors']));
	return $data['data'];
}

?>