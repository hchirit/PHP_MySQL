<?php
//_____________________________________________________________________________
/**
 * Mise � jour d'un blog.
 * 
 * Cette page est appel�e 2 fois pour faire le traitement de mise � jour :
 * - le premier passage permet la saisie des infos dans un formulaire
 * - le deuxi�me passage correspond � la soumission du formulaire de saisie,
 *   � la v�rification de la saisie et � la mise � jour de la base de donn�es.
 * 
 * Le deuxi�me passage est d�fini par l'existence de l'index bntValider dans le
 * tableau $_POST. 
 * 
 * @param	array	$_POST		Donn�es des formulaires soumis
 */
//_____________________________________________________________________________
ob_start();			// Buff�risation des sorties
session_start();	// d�marrage session

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

fp_verifSession();		// V�rification session utilisateur

$IDBlog = $_SESSION['IDBlog'];	// Identifiant du blog � traiter
								// si �gal 0, c'est un traitement de cr�ation,
								// sinon c'est un traitement de modification.
								
$erreurs = array();		// Tableau des messages d'erreur des zones invalides

// Initialisation des champs de saisie
$blTitre = $blResume = $blAuteur = $blPhoto = $blMail = '';
$blPseudo = $blPasse = '';
$blNbArticlesPage = 1;
$blTri = 0;

fp_bdConnecter();	// Ouverture base de donn�es


//_____________________________________________________________________________
//
// Traitement soumission du formulaire de saisie
//
// Bien que ce ce traitement soit la seconde chose � faire, le code
// doit �tre avant celui qui traite la saisie pour qu'en cas d'erreur
// les �l�ments saisis au premier passage puissent �tre r�affich�s.
//_____________________________________________________________________________
if (isset($_POST['btnSupprimer'])) {
	// On supprime le blog et tous les �l�ments qui lui sont rattach�s
	include('bibli_delete_bd.php');	
	fp_delete_blog($IDBlog);
	// On efface les variables de session car l'utilisateur
	// n'a plus de blog et n'est donc plus r�f�renc�
	// et on redirige sur la page d'index de l'application.
	// Il suffit pour cel� de "vider" la variable de session IDBlog
	// et d'appeler la fonction fp_verifSession.
	$_SESSION['IDBlog'] = '';
	fp_verifSession();  // fin PHP
}

if (isset($_POST['btnValider'])) {
	// On v�rifie si les zones saisies sont valides. Si oui on fait la mise
	// � jour de la base de donn�es puis on redirige sur la page 
	// de t�l�chargment de photo.
	$erreurs = fpl_verifZones($IDBlog);
    if (count($erreurs) == 0) {
		fpl_majBase($IDBlog);	// Mise � jour BD
		// On initialise la variable de session $_SESSION['UploadFrom']
		// � 'blog' pour que la page de t�l�chargement sache ce 
		// qu'elle doit traiter
		$_SESSION['UploadFrom'] = 'blog';
		header('Location: upload.php');
		exit();  // fin PHP
	}
		
	// Si on passe ici c'est que des zones de saisie ne sont pas valides.
	// On va r�afficher toutes les zones du formulaire et les messages d'erreur.
	// On commence par enlever �ventuellement les protections automatiques
	// de caract�res faite par PHP, puis on extrait les variables de $_POST
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
// Lecture de la table blogs si n�cessaire
// La table est lue uniquement si on est dans un traitement de modification
// (IDBlog > 0) et que le tableau des erreurs est vide. Si ce tableau n'est 
// pas vide c'est qu'une saisie a d�j� �t� faite et il ne faut pas effacer
// les infos saisies par celles venant de la BD. 
//_____________________________________________________________________________
if ($IDBlog > 0 && count($erreurs) == 0) {
	$sql = "SELECT *
			FROM blogs
			WHERE blID = $IDBlog";

	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	$enr = mysqli_fetch_assoc($R);	// R�cup�ration de la s�lection
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
				1 => 'les articles les plus r�cents en premier');
				
//	Affichage du haut de la page
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Blog';

if ($IDBlog == 0) {
	$remplace['@_LIENS_@'] = '';
	$remplace['@_TITRE_@'] = 'Je cr�e mon blog ...';
} else {
	$remplace['@_LIENS_@'] = fp_htmlBandeau(LIEN_NA_LA);	
	$remplace['@_TITRE_@'] = 'Je mets � jour mon blog ...';
}
fp_modeleTraite('debut_prive', $remplace);

// Affichage des erreurs de saisie pr�c�dentes
if (count($erreurs) > 0) {
	fp_htmlErreurs($erreurs);
}

//	Affichage du formulaire de saisie :
echo '<form method="post" action="blog_maj.php">
		<table cellspacing="0" cellpadding="2" class="majTable">';

fp_htmlSaisie('T', 'blTitre', $blTitre, 'Titre du blog');
fp_htmlSaisie('A', 'blResume', $blResume, 'R�sum�', 80, 5);
fp_htmlSaisie('S', 'blNbArticlesPage', $blNbArticlesPage, 'Pr�sentation', 1, $presentation);
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
 
// Si on est dans un traitement de cr�ation de blog,
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
 * V�rification de la validit� des zones de saisie
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
		$erreurs['blTitre'] = 'La zone Titre ne doit pas commencer par un espace ou �tre vide.';
	}
    echo "2";
	if (!preg_match('/^\S./', $_POST['blResume'])) {
		$erreurs['blResume'] = 'La zone R�sum� ne doit pas commencer par un espace ou �tre vide.';
	}
    echo "3";
	if (!preg_match('/^\S./', $_POST['blAuteur'])) {
		$erreurs['blAuteur'] = 'La zone Votre nom ne doit pas commencer par un espace ou �tre vide.';
	}
    echo "4";

	if (!preg_match('/^[a-zA-Z0-9]{4,10}$/', $_POST['blPseudo'])) {
		$erreurs['blPseudo'] = 'La zone Pseudo doit avoir de 4 � 10 caract�res alphab�tiques et/ou num�riques.';
	}
    echo "5";

	$expReg = '^[a-zA-Z0-9]{4,10}$';
	if ($IDBlog >= 0) {
		$expReg .= '|^$';
	}
	$expReg = '/'.$expReg.'/';
	if (!preg_match($expReg, $_POST['blPasse'])) {
		$erreurs['blPasse'] = 'La zone Passe doit avoir de 4 � 10 caract�res alphab�tiques et/ou num�riques.';
	}
        echo "6";

	if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_\.\-]*@[a-zA-Z][a-zA-Z0-9_\.\-]*\.[a-zA-Z]{2,6}$/', $_POST['blMail'])) {
		$erreurs['blMail'] = 'La zone E-mail doit �tre une adresse e-mail valide.';
	}
        echo "7";


	// On v�rifie si le couple pseudo/passe
	// n'existe pas d�j� dans la base de donn�es.
	$pseudo = fp_protectSQL($_POST['blPseudo']);
	$passe = md5($_POST['blPasse']);
	    echo "8";

	$sql = "SELECT *
			FROM blogs
			WHERE blPseudo = '$pseudo'
			AND blPasse = '$passe'
			AND blID <> $IDBlog";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	$nb = mysqli_num_rows($R);	// R�cup�ration du nombre de rang�es s�lectionn�es
	mysqli_free_result($R);

	if ($nb > 0) {
		$erreurs['identif'] = 'Vous devez choisir un autre Pseudo et/ou un autre mot de passe.';
	}
	
	return $erreurs;
}
//_____________________________________________________________________________
/**
 * Mise � jour de la base de donn�es
 * 
 * @param	integer	$IDBlog		Cl� du blog � traiter
 * @global	array	$_POST		Les zones de saisie du formulaire
 */
function fpl_majBase($IDBlog) {
	// Si la cl� du blog � traiter est 0 on est dans le cas d'une cr�ation,
	// et on fait donc une requ�te d'INSERT.
	// Si la cl� du blog est sup�rieur � 0, on est dans le cas d'une modification,
	// et on fait donc une requ�te d'UPDATE.
	// Noter la facilit� offerte par MySql dans ls yntaxe des requ�tes INSERT 
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
	
	// Dans le cas de la cr�ation d'un nouveau blog, il faut mettre � jour
	// l'identifiant du blog dans la variable de session $_SESSION['IDBlog']
	if ($IDBlog == 0) {
		$_SESSION['IDBlog'] = mysqli_insert_id($GLOBALS['bd']);
	}
}
?>