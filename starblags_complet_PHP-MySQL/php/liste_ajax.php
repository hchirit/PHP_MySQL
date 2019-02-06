<?php
//_____________________________________________________________________________
/**
 * G�n�ration de la liste des articles d'un auteur.
 * 
 * Ce script est destin� � �tre appel� avec la technique Ajax. Il renvoie 
 * le code HTML d'une liste de s�lection avec les articles du blog dont l'ID
 * est pass� en param�tre.
 * 
 * @param	integer	$_GET['a']	ID du blog � traiter
 */
ob_start();
// Pour emp�cher la r�ponse d'�tre mise en cache
$cacheDate = gmdate('D, d M Y H:i:s').' GMT';
header('Expires: '.$cacheDate);
header('Last-Modified: '.$cacheDate);
header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0');
header('Pragma: no-cache');
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

$IDBlog = (int) $_GET['a'];

fp_bdConnecter();	// Ouverture base de donn�es

$sql = "SELECT arID, arTitre, arDate, arHeure
		FROM articles
		WHERE arIDBlog = $IDBlog 
		ORDER BY arDate DESC, arHeure DESC";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

if (mysqli_num_rows($R) == 0) {	// Aucun article dans le blog
	exit('Aucun article');	
}

echo '<b>Les articles du blog :</b><br>';
	
while($enr = mysqli_fetch_assoc($R)) {
	echo fp_protectHTML($enr['arTitre']),
		' (', fp_amjJma($enr['arDate']), ' - ',
		fp_protectHTML($enr['arHeure']), ')<br>';
}

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>