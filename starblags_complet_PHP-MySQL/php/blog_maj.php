<?php
//_____________________________________________________________________________
/**
 * Mise à jour d'un blog.
 * 
 * Cette page est appelée 2 fois pour faire le traitement de mise à jour :
 * - le premier passage permet la saisie des infos dans un formulaire
 * - le deuxième passage correspond à la soumission du formulaire de saisie,
 *   à la vérification de la saisie et à la mise à jour de la base de données.
 * 
 * Le deuxième passage est défini par l'existence de l'index bntValider dans le
 * tableau $_POST. 
 * 
 * @param	array	$_POST		Données des formulaires soumis
 */
//_____________________________________________________________________________
ob_start();			// Bufférisation des sorties
session_start();	// démarrage session

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

fp_verifSession();		// Vérification session utilisateur

$IDBlog = $_SESSION['IDBlog'];	// Identifiant du blog à traiter
								// si égal 0, c'est un traitement de création,
								// sinon c'est un traitement de modification.
								
$erreurs = array();		// Tableau des messages d'erreur des zones invalides

// Initialisation des champs de saisie
$blTitre = $blResume = $blAuteur = $blPhoto = $blMail = '';
$blPseudo = $blPasse = '';
$blNbArticlesPage = 1;
$blTri = 0;

fp_bdConnecter();	// Ouverture base de données


//_____________________________________________________________________________
//
// Traitement soumission du formulaire de saisie
//
// Bien que ce ce traitement soit la seconde chose à faire, le code
// doit être avant celui qui traite la saisie pour qu'en cas d'erreur
// les éléments saisis au premier passage puissent être réaffichés.
//_____________________________________________________________________________
if (isset($_POST['btnSupprimer'])) {
	// On supprime le blog et tous les éléments qui lui sont rattachés
	include('bibli_delete_bd.php');	
	fp_delete_blog($IDBlog);
	// On efface les variables de session car l'utilisateur
	// n'a plus de blog et n'est donc plus référencé
	// et on redirige sur la page d'index de l'application.
	// Il suffit pour celà de "vider" la variable de session IDBlog
	// et d'appeler la fonction fp_verifSession.
	$_SESSION['IDBlog'] = '';
	fp_verifSession();  // fin PHP
}

if (isset($_POST['btnValider'])) {
	// On vérifie si les zones saisies sont valides. Si oui on fait la mise
	// à jour de la base de données puis on redirige sur la page 
	// de téléchargment de photo.
	$erreurs = fpl_verifZones($IDBlog);
    if (count($erreurs) == 0) {
		fpl_majBase($IDBlog);	// Mise à jour BD
		// On initialise la variable de session $_SESSION['UploadFrom']
		// à 'blog' pour que la page de téléchargement sache ce 
		// qu'elle doit traiter
		$_SESSION['UploadFrom'] = 'blog';
		header('Location: upload.php');
		exit();  // fin PHP
	}
		
	// Si on passe ici c'est que des zones de saisie ne sont pas valides.
	// On va réafficher toutes les zones du formulaire et les messages d'erreur.
	// On commence par enlever éventuellement les protections automatiques
	// de caractères faite par PHP, puis on extrait les variables de $_POST
	fp_stripPOST();
	$blTitre = $_POST['blTitre'];
	$blResume = $_POST['blResume'];
	$blAuteur = $_POST['blAuteur'];
	$blPhoto = $_POST['blPhoto'];
	$blMail = $_POST['blMail'];
	$blPseudo = $_POST['blPseudo'];
	$blNbArticlesPage = $_POST['blNbArticlesPage'];
	$blTri = $_POST['blTri'];
}
//_____________________________________________________________________________
//
// Lecture de la table blogs si nécessaire
// La table est lue uniquement si on est dans un traitement de modification
// (IDBlog > 0) et que le tableau des erreurs est vide. Si ce tableau n'est 
// pas vide c'est qu'une saisie a déjà été faite et il ne faut pas effacer
// les infos saisies par celles venant de la BD. 
//_____________________________________________________________________________
if ($IDBlog > 0 && count($erreurs) == 0) {
	$sql = "SELECT *
			FROM blogs
			WHERE blID = $IDBlog";

	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	$enr = mysqli_fetch_assoc($R);	// Récupération de la sélection
	if ($enr === FALSE) {
		exit();  // Le blog n'existe pas : fin du script
	}

	$blTitre = $enr['blTitre'];
	$blResume = $enr['blResume'];
	$blAuteur = $enr['blAuteur'];
	$blPhoto = $enr['blPhoto'];
	$blMail = $enr['blMail'];
	$blPseudo = $enr['blPseudo'];
	$blNbArticlesPage = $enr['blNbArticlesPage'];
	$blTri = $enr['blTri'];	
}
//_____________________________________________________________________________
//
// Traitement saisie du formulaire
//_____________________________________________________________________________
$presentation = array(	1 => 'un article par page',
						2 => 'deux articles par page',
						3 => 'trois articles par page',
						4 => 'quatre articles par page',
						5 => 'cinq articles par page');

$tri = array(	0 => 'les articles les plus anciens en premier',
				1 => 'les articles les plus récents en premier');
				
//	Affichage du haut de la page
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Blog';

if ($IDBlog == 0) {
	$remplace['@_LIENS_@'] = '';
	$remplace['@_TITRE_@'] = 'Je crée mon blog ...';
} else {
	$remplace['@_LIENS_@'] = fp_htmlBandeau(LIEN_NA_LA);	
	$remplace['@_TITRE_@'] = 'Je mets à jour mon blog ...';
}
fp_modeleTraite('debut_prive', $remplace);

// Affichage des erreurs de saisie précédentes
if (count($erreurs) > 0) {
	fp_htmlErreurs($erreurs);
}

//	Affichage du formulaire de saisie :
echo '<form method="post" action="blog_maj.php">
		<table cellspacing="0" cellpadding="2" class="majTable">';

fp_htmlSaisie('T', 'blTitre', $blTitre, 'Titre du blog');
fp_htmlSaisie('A', 'blResume', $blResume, 'Résumé', 80, 5);
fp_htmlSaisie('S', 'blNbArticlesPage', $blNbArticlesPage, 'Présentation', 1, $presentation);
fp_htmlSaisie('R', 'blTri', $blTri, 'Ordre', 2, $tri);

echo '<tr>',
		'<td colspan="2">&nbsp;</td>',
	'</tr>';

fp_htmlSaisie('T', 'blAuteur', $blAuteur, 'Votre nom');
fp_htmlSaisie('T', 'blMail', $blMail, 'E-mail', 80);
fp_htmlSaisie('T', 'blPseudo', $blPseudo, 'Pseudo', 15, 10);
fp_htmlSaisie('T', 'blPasse', $blPasse, 'Passe', 15);

if ($IDBlog > 0) {
	echo '<tr>',
			'<td>&nbsp;</td>',
			'<td class="petit">Laissez la zone vide pour garder votre mot de passe actuelle.</td>',
		'</tr>';
}
 
// Si on est dans un traitement de création de blog,
// on affiche un bouton : valider.
// Si on est dans un traitement de modification de blog,
// on affiche deux bourons : supprimer et valider
if ($IDBlog == 0) { 
	fp_htmlBoutons(2, 'S|btnValider|Valider');
} else {
	fp_htmlBoutons(2, 'S|btnSupprimer|Supprimer', 'S|btnValider|Valider');
}

//	Fin de page
echo 	'</table>',
	'</form>';

include('../modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur

//_____________________________________________________________________________
//
// 							FONCTIONS
//_____________________________________________________________________________
/**
 * Vérification de la validité des zones de saisie
 * 
 * @param	integer	$IDBlog	Identifiant du blog
 * @global	array	$_POST	ZOnes de saisie du formulaire
 * 
 * @return	array	Tableau associatif avec les messages d'erreurs (index = nom de zone)
 */
function fpl_verifZones($IDBlog) {
	$erreurs = array();

	$_POST['blNbArticlesPage'] = (int) $_POST['blNbArticlesPage'];
    if ($_POST['blNbArticlesPage'] < 1 
	|| $_POST['blNbArticlesPage'] > 5) 
	{
		exit();	// -->> piratage ??
	}
	$_POST['blTri'] = (int) $_POST['blTri'];	
	if ($_POST['blTri'] < 0 
	||$_POST['blTri'] > 1) 
	{
		exit();	// -->> piratage ??
	}
    echo "1";
	if (!preg_match('/^\S./', $_POST['blTitre'])) {
		$erreurs['blTitre'] = 'La zone Titre ne doit pas commencer par un espace ou être vide.';
	}
    echo "2";
	if (!preg_match('/^\S./', $_POST['blResume'])) {
		$erreurs['blResume'] = 'La zone Résumé ne doit pas commencer par un espace ou être vide.';
	}
    echo "3";
	if (!preg_match('/^\S./', $_POST['blAuteur'])) {
		$erreurs['blAuteur'] = 'La zone Votre nom ne doit pas commencer par un espace ou être vide.';
	}
    echo "4";

	if (!preg_match('/^[a-zA-Z0-9]{4,10}$/', $_POST['blPseudo'])) {
		$erreurs['blPseudo'] = 'La zone Pseudo doit avoir de 4 à 10 caractères alphabétiques et/ou numériques.';
	}
    echo "5";

	$expReg = '^[a-zA-Z0-9]{4,10}$';
	if ($IDBlog >= 0) {
		$expReg .= '|^$';
	}
	$expReg = '/'.$expReg.'/';
	if (!preg_match($expReg, $_POST['blPasse'])) {
		$erreurs['blPasse'] = 'La zone Passe doit avoir de 4 à 10 caractères alphabétiques et/ou numériques.';
	}
        echo "6";

	if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_\.\-]*@[a-zA-Z][a-zA-Z0-9_\.\-]*\.[a-zA-Z]{2,6}$/', $_POST['blMail'])) {
		$erreurs['blMail'] = 'La zone E-mail doit être une adresse e-mail valide.';
	}
        echo "7";


	// On vérifie si le couple pseudo/passe
	// n'existe pas déjà dans la base de données.
	$pseudo = fp_protectSQL($_POST['blPseudo']);
	$passe = md5($_POST['blPasse']);
	    echo "8";

	$sql = "SELECT *
			FROM blogs
			WHERE blPseudo = '$pseudo'
			AND blPasse = '$passe'
			AND blID <> $IDBlog";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	$nb = mysqli_num_rows($R);	// Récupération du nombre de rangées sélectionnées
	mysqli_free_result($R);

	if ($nb > 0) {
		$erreurs['identif'] = 'Vous devez choisir un autre Pseudo et/ou un autre mot de passe.';
	}
	
	return $erreurs;
}
//_____________________________________________________________________________
/**
 * Mise à jour de la base de données
 * 
 * @param	integer	$IDBlog		Clé du blog à traiter
 * @global	array	$_POST		Les zones de saisie du formulaire
 */
function fpl_majBase($IDBlog) {
	// Si la clé du blog à traiter est 0 on est dans le cas d'une création,
	// et on fait donc une requête d'INSERT.
	// Si la clé du blog est supérieur à 0, on est dans le cas d'une modification,
	// et on fait donc une requête d'UPDATE.
	// Noter la facilité offerte par MySql dans ls yntaxe des requêtes INSERT 
	// et UPDATE.
	$sql = "blTitre = '".fp_protectSQL($_POST['blTitre'])."',
			blResume = '".fp_protectSQL($_POST['blResume'])."',
			blAuteur = '".fp_protectSQL($_POST['blAuteur'])."',
			blPseudo = '".fp_protectSQL($_POST['blPseudo'])."',
			blMail = '".fp_protectSQL($_POST['blMail'])."',
			blModele = 1,
			blNbArticlesPage = {$_POST['blNbArticlesPage']},
			blTri = {$_POST['blTri']}";
			
	if ($_POST['blPasse'] != '') {
		$sql .= ", blPasse = '".md5($_POST['blPasse'])."'";
	}
	if ($IDBlog == 0) {
		$sql = "INSERT INTO blogs SET $sql, 
				blPhoto = '',
				blDate = ".date('Ymd').",
				blHeure = '".date('H:i')."'";	
	} else {
		$sql = "UPDATE blogs SET $sql 
				WHERE blID = $IDBlog";
	}	
  
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);
	
	// Dans le cas de la création d'un nouveau blog, il faut mettre à jour
	// l'identifiant du blog dans la variable de session $_SESSION['IDBlog']
	if ($IDBlog == 0) {
		$_SESSION['IDBlog'] = mysqli_insert_id($GLOBALS['bd']);
	}
}
?>