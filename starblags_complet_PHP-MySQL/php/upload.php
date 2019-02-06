<?php
//_____________________________________________________________________________
/**
 * T�l�chargement d'un fichier sur le serveur
 * 
 * Cette page peut �tre appel�e par blog_maj.php ou par article_maj.php.
 * La variable de session $_SESSION['UploadFrom'] permet de conna�tre
 * la page appelante.
 * 
 * Le traitement est compos� de 2 phases :
 * - une page avec un formulaire pour choisir le fichier � t�l�charger
 * - le t�l�chargement proprement dit du fichier.
 * La deuxi�me phase est d�finie par l'existence de l'index bntUpload dans le
 * tableau $_POST. 
 * 
 * Suivant la page appelante, le formulaire n'a pas les m�mes zones de saisie.
 * 
 * Dans le cas o� la page appelante est article_maj.php, le traitement boucle
 * pour que l'utilisateur puisse t�l�charger plusieurs images.
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

// On v�rifie la validit� de la variable de session $_SESSION['UploadFrom']
// Si la variable n'est pas valide on redirige sur la page d'accueil
if ($_SESSION['UploadFrom'] != 'blog' && $_SESSION['UploadFrom'] != 'article') {
	header('Location: ../index.php');
	exit();  // fin PHP
}

$erreurs = array();		// Tableau des messages d'erreur des zones invalides
fp_bdConnecter();	// Ouverture base de donn�es

//_____________________________________________________________________________
//
// Traitement soumission du formulaire de saisie
//
// Bien que ce ce traitement soit la seconde chose � faire, le code
// doit �tre avant celui qui traite la saisie pour 
// - qu'en cas d'erreur les �l�ments saisis au premier passage puissent 
// �tre r�affich�s.
// - qu'en cas de traitement article on puisse boucler pour t�l�charger
// plusieurs fichiers.
//_____________________________________________________________________________
if (isset($_POST['btnUpload'])) {
	// On v�rifie si les zones saisies sont valides. 
	// Si oui on fait le traitement de t�l�chargement et de mise
	// � jour de la base de donn�es.
	$erreurs = fpl_verifZones();
	if (count($erreurs) == 0) {
		$erreurs = fpl_traiteUpload();
	}
	
	// Si traitement blog, on redirige sur la page liste des articles
	if ($_SESSION['UploadFrom'] == 'blog' && count($erreurs == 0)) {
		header('Location: articles_liste.php');
		exit();  // fin PHP
	}
}

//_____________________________________________________________________________
//
// Traitement saisie du formulaire
//_____________________________________________________________________________				
//	Affichage du haut de la page
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - T�l�chargement';
$remplace['@_LIENS_@'] = fp_htmlBandeau(LIEN_MB_LA);

if ($_SESSION['UploadFrom'] == 'blog') {
	$remplace['@_TITRE_@'] = 'T�l�charger une photo associ�e � mon blog';
} else {
	$remplace['@_TITRE_@'] = 'T�l�charger une photo associ�e � un article';
}
// Lecture du modele debut_prive.html, remplacement motifs et affichage
fp_modeleTraite('debut_prive', $remplace);

// Affichage des erreurs de saisie pr�c�dentes
if (count($erreurs) > 0) {
	fp_htmlErreurs($erreurs);
}

// Affichage du formulaire
echo '<form enctype="multipart/form-data" method="post" action="upload.php">',
		'<table cellspacing="0" cellpadding="2" class="majTable">',
			'<tr>',
				'<td colspan="2">',
					'S�lectionnez un fichier � t�l�charger sur le serveur<br>',
					'<input type="hidden" name="MAX_FILE_SIZE" value="51200">',
					'<input type="file" name="txtFile" size="80" class="saisie">',
				'</td>',
			'</tr>';
	
// SI on t�l�charge des images li�es � un article, il faut d�finir
// leur place par rapport au texte, et saisir une l�gende.
if ($_SESSION['UploadFrom'] == 'article') {
	$places = array(	0 => 'en haut du texte',
						1 => '� droite du texte',
						2 => 'en bas du texte',
						3 => '� gauche du texte');
	$phPlace = 2;
	$phLegende = '';
	
	echo '<tr>',
			'<td colspan="2">&nbsp;</td>',
		'</tr>';
	fp_htmlSaisie('T', 'phLegende', $phLegende, 'L�gende');
	fp_htmlSaisie('R', 'phPlace', $phPlace, 'Image plac�e', 2, $places);
}

fp_htmlBoutons(2, 'S|btnUpload|T�l�charger');

//	Fin de page
echo '</table>',
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
 * @global	array	$_FILES	Zones de t�l�lchargement de fichier
 * 
 * @return	array	Tableau associatif avec les messages d'erreurs (index = nom de zone)
 */
function fpl_verifZones() {
	$erreurs = array();
print_r($_FILES['txtFile']);
	$extension = substr($_FILES['txtFile']['name'], -4);
	
	if ($extension != '.gif' && $extension != '.jpg' && $extension != '.png') {
		$erreurs['txtFile'] = 'Seuls les fichiers avec une extension .gig ou .jpg ou .png sont autoris�s.';
	} elseif($_FILES['txtFile']['size'] == 0) {
		$erreurs['txtFile'] = 'Le fichier est vide ou d�passe le maximum autoris� de 50 ko.';
	}				

	return $erreurs;
}
//_____________________________________________________________________________
/**
 * Traitement de t�l�chargement
 * 
 * @global	array	$_FILES	Zones de t�l�lchargement de fichier
 * 
 * @return	array	Tableau associatif avec les messages d'erreurs (index = nom de zone)
 */
function fpl_traiteUpload() {
	$erreurs = array();
	$origine = $_FILES['txtFile']['tmp_name'];
		
	// Si on t�l�charge une image associ�e � un blog, le nom de l'image est 
	// l'identifiant du blog, suivi de l'extension de l'image.
	// Si ont�l�charge une image associ�e � un article, le nom de l'image est
	// l'identifiant de l'article, suivi du caract�re _, suivi d'un num�ro chrono
	// pris dans $_SESSION['UploadNum'], suivi de l'extension de l'image.
	$extension = substr($_FILES['txtFile']['name'], -4);
	if ($_SESSION['UploadFrom'] == 'blog') {
		$destination = REP_UPLOAD."{$_SESSION['IDBlog']}{$extension}";
	} else {
		$_SESSION['UploadNum'] ++;
		$destination = REP_UPLOAD."{$_SESSION['IDArticle']}_{$_SESSION['UploadNum']}{$extension}";
	}

	// Copie du fichier t�l�charger sur le disque du serveur.
	// Si des erreurs sont rencontr�es on sort de la fonction.
	if (! @is_uploaded_file($origine)) {
		$erreurs['txtFile'] = 'Erreur pendant le transfert.';
		return $erreurs;
	}
	if (! @move_uploaded_file($origine, $destination)) {
		$erreurs['txtFile'] = 'Erreur pendant le transfert.';
		return $erreurs;
	}
		
	// Mise � jour de la base de donn�es.
	
	// Si on traite une image li�e � un blog, on met � jour la table blog.
	// Si on traite une image li�e � un article, on cr�e un enregistrement
	// dans la table photo.
	$extension = substr($extension, 1);
	
	if ($_SESSION['UploadFrom'] == 'blog') {
		$sql = "UPDATE blogs SET blPhoto = '$extension'
				WHERE blID = ".$_SESSION['IDBlog'];
	} else {
		$sql = "INSERT INTO photos SET
				phIDArticle = {$_SESSION['IDArticle']},
				phNumero = {$_SESSION['UploadNum']},
				phPlace = '".fp_protectSQL($_POST['phPlace'])."', 
				phLegende = '".fp_protectSQL($_POST['phLegende'])."', 
				phExt = '$extension'";
	}
 
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);
	return $erreurs;		
}
?>