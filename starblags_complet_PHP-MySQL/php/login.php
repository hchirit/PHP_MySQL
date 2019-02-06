<?php
//_____________________________________________________________________________
/**
 * Vérification du login
 * 
 * On recoit les données du formulaire se trouvant dans le bandeau des 
 * pages publics (pseudo, mot de passe et bouton de soumission).
 * 
 * Les boutons que l'on reçoit (soit btnNouveau, soit bntLogin) vont nous
 * permettre de savoir quel traitement effectuer.
 * 
 * Dans le cas où on reçoit btnNouveau, on initilise une session 'vide' et
 * on redirige sur la page de mise à jour d'un blog. 
 *  
 * Dans le cas où on reçoit btnLogin, on vérifie l'existence d'un blog 
 * avec le pseudo et le mot de passe. 
 * - Si le blog existe on initialise une session avec l'identifiant du blog 
 * et on redirige sur la page de mise à jour d'un blog. 
 * - Si le blog n'existe pas on affiche une page d'erreur.
 * 
 * @param	string	$_POST['txtPseudo']		Pseudo de l'utilisateur
 * @param	string	$_POST['txtPasse']		Mot de passe de l'utilisateur
 * @param	string	$_POST['btnLogin']		Bouton si accés à un blog existant
 * @param	string	$_POST['btnNouveau']	Bouton si création d'un blog
 */
//_____________________________________________________________________________
ob_start();		// Bufférisation des sorties

// Initialisation de la session
// Toutes les variables de session doivent être initialisées ici
// pour que l'on sache quelles sont ces variables.
// Si l'initialisation n'est pas centralisée, on se retrouve
// rapidement avec des variables déclarées un peu partout
// dans l'application, avec des difficultés pour savoir à quoi elles servent.
// On peut aussi utiliser une fonction. L'important est de faire 
// une intialisation de toutes les variables à un seul endroit

// Pour éviter une sorte de piratage par "vol de session" on démarre
// une session, on la détruit et on en démarre une nouvelle.
session_start();
session_destroy();
session_start();

$_SESSION['IDBlog'] = 0;		// Identifiant du blog traité
$_SESSION['IDArticle'] = 0;		// Identifiant de l'article traité
$_SESSION['UploadFrom'] = '';	// permet à la page de téléchargement de savoir
								// quelle page l'appelée et le traiement à faire
$_SESSION['UploadNum'] = 0;		// Compteur pour les téléchargements

// Création d'un nouveau blog : on redirige sur la page de mise à jour du blog		
if (isset($_POST['btnNouveau'])) {
	header('Location: blog_maj.php');
	exit();  // fin PHP
}
//_____________________________________________________________________________
//
// Vérification de l'existence d'un blog avec les éléments saisis
//_____________________________________________________________________________
include_once('bibli.php');

fp_bdConnecter();	// Ouverture base de données

$pseudo = fp_protectSQL($_POST['txtPseudo']);
$passe = md5($_POST['txtPasse']);

$sql = "SELECT * 
		FROM blogs
		WHERE blPseudo = '$pseudo' 
		AND blPasse = '$passe'";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête

// Si le blog existe, on stocke l'identifiant dans une variable de session
// puis on redirige sur la page de définiation du blog.
if (mysqli_num_rows($R) == 1) {
	$enr = mysqli_fetch_assoc($R);
	$_SESSION['IDBlog'] = $enr['blID'];
	header('Location: blog_maj.php');
	exit();  // fin PHP
}

//_____________________________________________________________________________
//
// Affichage d'une page d'erreur
// Si on passe ici c'est que le blog n'existe pas.
// On affiche une page avec une message d'erreur.
// L'utilisateur peut se réidentifier ou revenir à l'acceuil.
//_____________________________________________________________________________
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Login';
$remplace['@_RSS_@'] = '';
$remplace['@_REP_@'] = '..';

// Lecture du modele debut.html, remplacement motifs et affichage
fp_modeleTraite('debut_public', $remplace);

echo '<h1>Erreur d\'identification</h1>',
	'<div style="align: center; padding: 10px; height: 200px;">',
		'<p>',
			'Le pseudo et le mot de passe fourni ne correspondent pas à un blog.',
		'</p>',
		'<p>',
			'Merci de réessayer avec les identifiants corrects.',
		'</p>',
	'</div>';

include('../modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>