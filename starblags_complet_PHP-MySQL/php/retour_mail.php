<?php
//_____________________________________________________________________________
/**
 * Page de gestion des retours mail pour inscription alerte nouvel article.
 * Cette page est appel�e depuis un lien dans le corps du mail de validation.
 * 
 * @param	integer	$_GET['x']		Cl� alertes_lecteurs � valider - crypt�
 */
//_____________________________________________________________________________
ob_start();			// Buff�risation des sorties

include_once('bibli.php');

$IDAlerte = (int) fp_getURL();

fp_bdConnecter();	// Ouverture base de donn�es

// Pour plus de s�curit�, il faudrait v�rifier si l'alerte
// existe bien et si elle est en attente de validation

$sql = "UPDATE alertes_lecteurs SET 
		alValide = 1
		WHERE alID = $IDAlerte";
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

//_____________________________________________________________________________
//
//	Affichage du haut de la page
//_____________________________________________________________________________
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Validation d\'alerte nouvel article';
$remplace['@_RSS_@'] = '';
$remplace['@_REP_@'] = '..';

// Lecture du modele debut.html, remplacement motifs et affichage
fp_modeleTraite('debut_public', $remplace);

echo '<div style="height: 200px; text-align: center; padding: 10px;">',
		'Votre alerte � �t� valid�e.',
	'</div>';

include('../modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>