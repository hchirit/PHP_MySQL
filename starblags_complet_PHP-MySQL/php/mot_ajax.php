<?php
//_____________________________________________________________________________
/**
 * Affichage des articles liés à un tag
 * 
 * Affichage des articles triés par date de parution avec photos et pagination * 
 * Cette page reçoit des paramètres dans l'url :
 * - la signature de cryptage
 * - le nom du tag recherché
 * - le nombre d'articles liés au tag
 * - le numéro de la page à afficher pour la pagination. Si ce paramètre n'existe pas, 
 * il est initialisé à 0.
 * Les paramètres sont passés cryptés.
 * 
 * @param	string	$_GET['a']		mot-clé à rechercher
 */
//_____________________________________________________________________________
ob_start();			// Bufférisation des sorties

// Fixe le type de caractères utilisé. Sinon par défaut UTF-8 => pb accents
header('Content-Type: text/html; charset=ISO-8859-1');

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
require_once('bibli.php');

if (!isset($_GET['a'])) {
	exit();
}

$mot = trim($_GET['a']);

if ($mot == '') {
	exit('');
}

fp_bdConnecter();			// Ouverture base de données

$sql = "SELECT arTitre, arDate, arHeure
		FROM articles
		WHERE  arTitre LIKE '%$mot%'";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête

if (mysqli_num_rows($R) == 0)exit('');	// Aucun article dans le blog
		
echo '<b>Les articles correspondants :</b><br>';
	
while($enr = mysqli_fetch_assoc($R)) {
	echo fp_protectHTML($enr['arTitre']),
		' (', fp_amjJma($enr['arDate']), ' - ',
		fp_protectHTML($enr['arHeure']), ')<br>';
}

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>