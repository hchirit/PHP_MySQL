<?php
//_____________________________________________________________________________
/**
 * Affichage des articles li�s � un tag
 * 
 * Affichage des articles tri�s par date de parution avec photos et pagination * 
 * Cette page re�oit des param�tres dans l'url :
 * - la signature de cryptage
 * - le nom du tag recherch�
 * - le nombre d'articles li�s au tag
 * - le num�ro de la page � afficher pour la pagination. Si ce param�tre n'existe pas, 
 * il est initialis� � 0.
 * Les param�tres sont pass�s crypt�s.
 * 
 * @param	string	$_GET['a']		mot-cl� � rechercher
 */
//_____________________________________________________________________________
ob_start();			// Buff�risation des sorties

// Fixe le type de caract�res utilis�. Sinon par d�faut UTF-8 => pb accents
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

fp_bdConnecter();			// Ouverture base de donn�es

$sql = "SELECT arTitre, arDate, arHeure
		FROM articles
		WHERE  arTitre LIKE '%$mot%'";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

if (mysqli_num_rows($R) == 0)exit('');	// Aucun article dans le blog
		
echo '<b>Les articles correspondants :</b><br>';
	
while($enr = mysqli_fetch_assoc($R)) {
	echo fp_protectHTML($enr['arTitre']),
		' (', fp_amjJma($enr['arDate']), ' - ',
		fp_protectHTML($enr['arHeure']), ')<br>';
}

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>