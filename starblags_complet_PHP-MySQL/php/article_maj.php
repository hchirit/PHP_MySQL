<?php
//_____________________________________________________________________________
/**
 * Mise � jour d'un article.
 * 
 * Cette page est appel�e 2 fois pour faire le traitement de mise � jour :
 * - le premier passage permet la saisie des infos dans un formulaire
 * - le deuxi�me passage correspond � la soumission du formulaire de saisie,
 *   � la v�rification de la saisie et � la mise � jour de la base de donn�es.
 * 
 * Au premier passage, on re�oit l'identifiant de l'article dans l'url. Dans le
 * cas d'une modification d'article (identifiant sup�rieur � 0), on v�rifie que
 * l'article appartient bien au blog de l'utilisateur pour �viter que A modifie
 * les articles de B.
 * Au deuxi�me passage, l'identifiant article se trouve dans la variable de
 * session $_SESSION['IDArticle'];
 * 
 * Le deuxi�me passage est d�fini par l'existence de l'index bntValider ou de
 * l'index btnSupprimer dans le tableau $_POST. 
 * 
 * @param	integer	$_GET['x']	Identifiant de l'article � traiter
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

$IDBlog = $_SESSION['IDBlog'];	// Identifiant du blog de l'article
$erreurs = array();		// Tableau des messages d'erreur des zones invalides

// Initialisation des champs de saisie
$arTitre = $arTexte = $txtTags = '';
$arComment = $arPublier = 1;
$arRSS = 0;

fp_bdConnecter();	// Ouverture base de donn�es

// Si l'identifiant article se trouve dans l'url, on v�rifie sa validit�.
// Si OK on le stocke dans la variable de session $_SESSION['IDArticle'].
if (isset($_GET['x'])) {
	$_SESSION['IDArticle'] = fp_getURL();
}

$IDArticle = $_SESSION['IDArticle'];	// Identifiant de l'article � traiter
								// si �gal 0, c'est un traitement de cr�ation,
								// sinon c'est un traitement de modification.

//_____________________________________________________________________________
//
// Traitement soumission du formulaire de saisie
//
// Bien que ce ce traitement soit la seconde chose � faire, le code
// doit �tre avant celui qui traite la saisie pour qu'en cas d'erreur
// les �l�ments saisis au premier passage puissent �tre r�affich�s.
//_____________________________________________________________________________
if (isset($_POST['btnSupprimer'])) {
	// On supprime l'article et les �l�ments qui lui sont rattach�s
	include('bibli_delete_bd.php');
	fp_delete_article($IDArticle);
	// On redirige sur la page de liste des articles du blog
	$_SESSION['IDArticle'] = 0;
	header('Location: articles_liste.php');
	exit();  // fin PHP
}

if (isset($_POST['btnValider'])) {
	// On v�rifie si les zones saisies sont valides. Si oui on fait la mise
	// � jour de la base de donn�es puis on redirige sur la page 
	// de t�l�chargment de photo.
	$erreurs = fpl_verifZones();
	if (count($erreurs) == 0) {
		fpl_majBase($IDBlog, $IDArticle);	// Mise � jour BD
		// On initialise la variable de session $_SESSION['UploadFrom']
		// � 'article' pour que la page de t�l�chargement sache ce 
		// qu'elle doit traiter
		$_SESSION['UploadFrom'] = 'article';
		header('Location: upload.php');
		exit();  // fin PHP
	}
		
	// Si on passe ici c'est que des zones de saisie ne sont pas valides.
	// On va r�afficher toutes les zones du formulaire et les messages d'erreur.
	// On commence par enlever �ventuellement les protections automatiques
	// de caract�res faite par PHP, puis on extrait les variables de $_POST
	fp_stripPOST();
	$arRSS = $_POST['arRSS'];
	$arTitre = $_POST['arTitre'];
	$arTexte = $_POST['arTexte'];
	$arComment = $_POST['arComment'];
	$arPublier = $_POST['arPublier'];
}
//_____________________________________________________________________________
//
// Lecture de la table articles si n�cessaire
// La table est lue uniquement si on est dans un traitement de modification
// (IDArticle > 0) et que le tableau des erreurs est vide. Si ce tableau n'est 
// pas vide c'est qu'une saisie a d�j� �t� faite et il ne faut pas effacer
// les infos saisies par celles venant de la BD. 
//_____________________________________________________________________________
if ($IDArticle > 0 && count($erreurs) == 0) {
	$sql = "SELECT *
			FROM articles
			WHERE arID = $IDArticle";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	$enr = mysqli_fetch_assoc($R);	// R�cup�ration de la s�lection
	if ($enr === FALSE) {
		exit();		// L'article n'existe pas : fin du script
	}
	
	$arRSS = $enr['arRSS'];
	$arTitre = $enr['arTitre'];
	$arTexte = $enr['arTexte'];
	$arComment = $enr['arComment'];
	$arPublier = $enr['arPublier'];	
	mysqli_free_result($R);
	
	// Recherche des tags
	$sql = "SELECT taNom
			FROM tags_articles
			WHERE taIDArticle = $IDArticle
			ORDER BY taNom";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	while($enr = mysqli_fetch_assoc($R)) {
		$txtTags .= ' '.$enr['taNom'];
	}
	$txtTags = substr($txtTags, 1);
	mysqli_free_result($R);			
}
//_____________________________________________________________________________
//
// Traitement saisie du formulaire
//_____________________________________________________________________________
$commentaires = array(	0 => 'interdits',
						1 => 'permis');
$publier = array(	0 => 'Non',
					1 => 'Oui');
					
//	Affichage du haut de la page
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Article';
$remplace['@_LIENS_@'] = fp_htmlBandeau(LIEN_MB_LA);
$remplace['@_TITRE_@'] = ($IDArticle == 0) ? 'Je cr�e un article...' : 'Je mets � jour un article ...';

// Lecture du modele debut_prive.html, remplacement motifs et affichage
fp_modeleTraite('debut_prive', $remplace);

// Affichage des erreurs de saisie pr�c�dentes
if (count($erreurs) > 0) {
	fp_htmlErreurs($erreurs);
}

//	Affichage du formulaire de saisie :
$url = fp_makeURL('article_maj.php', $IDArticle);
	
echo '<form method="post" action="', $url, '">';

fp_htmlSaisie('H', 'arRSS', $arRSS);

echo '<table cellspacing="0" cellpadding="2" class="majTable">';

fp_htmlSaisie('T', 'arTitre', $arTitre, 'Titre article');
fp_htmlSaisie('A', 'arTexte', $arTexte, 'Texte', 80, 30);
fp_htmlSaisie('R', 'arComment', $arComment, 'Commentaires', 1, $commentaires);
fp_htmlSaisie('R', 'arPublier', $arPublier, 'Publier', 1, $publier);
fp_htmlSaisie('T', 'txtTags', $txtTags, 'Tags (mots-cl�s)');
 
// Si on est dans un traitement de cr�ation d'article,
// on affiche un bouton : valider.
// Si on est dans un traitement de modification d'article,
// on affiche deux bourons : supprimer et valider
if ($IDArticle == 0) { 
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
 * @global	array	$_POST	ZOnes de saisie du formulaire
 * 
 * @return	array	Tableau associatif avec les messages d'erreurs (index = nom de zone)
 */
function fpl_verifZones() {
	$erreurs = array();
	
	if (!preg_match('/^\S./', $_POST['arTitre'])) {
		$erreurs['arTitre'] = 'La zone Titre ne doit pas commencer par un espace ou �tre vide.';
	}
	if (!preg_match('/^\S./', $_POST['arTexte'])) {
		$erreurs['arTexte'] = 'La zone Texte ne doit pas commencer par un espace ou �tre vide.';
	}
	$_POST['arComment'] = (int) $_POST['arComment'];
	if ($_POST['arComment'] < 0 
	|| $_POST['arComment'] > 1) 
	{
		exit();		//-->> piratage ??
	}
	$_POST['arPublier'] = (int) $_POST['arPublier'];
	if ($_POST['arPublier'] < 0 
	|| $_POST['arPublier'] > 1) 
	{
		exit();		//-->> piratage ??
	}	
	return $erreurs;
}
//_____________________________________________________________________________
/**
 * Mise � jour de la base de donn�es - insert ou update
 * 
 * @param	integer	$IDBlog		Cl� du blog de l'article
 * @param	integer	$IDArticle	Cl� de l'article � traiter
 * @global	array	$_POST		Les zones de saisie du formulaire
 */
function fpl_majBase($IDBlog, $IDArticle) {
	// Si la cl� de l'article � traiter est 0 on est dans le cas d'une cr�ation,
	// et on fait donc une requ�te d'INSERT.
	// Si la cl� de l'article est sup�rieur � 0, on est dans le cas d'une modification,
	// et on fait donc une requ�te d'UPDATE.
	// Noter la facilit� offerte par MySql dans la syntaxe des requ�tes INSERT 
	// et UPDATE.
	
	// On enl�ve les scripts �ventuel du texte qui peut contenir des tags HTML
	$_POST['arTexte'] = fp_noScripts($_POST['arTexte']);
	
	$sql = "arIDBlog = $IDBlog,
			arTitre = '".fp_protectSQL($_POST['arTitre'])."',
			arTexte = '".fp_protectSQL($_POST['arTexte'])."',
			arComment = {$_POST['arComment']},
			arRSS = '".fp_protectSQL($_POST['arRSS'])."',
			arPublier = {$_POST['arPublier']}";
			
	if ($IDArticle == 0) {
		$sql = "INSERT INTO articles SET $sql, 
				arDate = ".date('Ymd').",
				arHeure = '".date('H:i')."'";	
	} else {
		$sql = "UPDATE articles SET $sql 
				WHERE arID = $IDArticle";
	}	
  
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	
	// Dans le cas de la cr�ation d'un nouvel article, il faut mettre � jour
	// l'identifiant de l'article dans la variable de session $_SESSION['IDArticle']
	// et on initialise la variable de session $_SESSION['UploadNum'] (num�ro de photo) � 0.
	// Dans le cas de la modification d'un article, on recherche le plus grand num�ro
	// de photo pour initialiser $_SESSION['UploadNum']
	if ($IDArticle == 0) {
		$_SESSION['IDArticle'] = mysqli_insert_id();
		$_SESSION['UploadNum'] = 0;
	} else {
		$sql = "SELECT max(phNumero)
				FROM photos
				WHERE phIDArticle = {$_SESSION['IDArticle']}";
		$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te 
		$enr = mysqli_fetch_row($R);
		$_SESSION['UploadNum'] = ($enr[0] == null) ? 0 : $enr[0];
	}
	
	// Mise � jour des tags associ�s � l'article
	fpl_majTags($IDArticle, $_POST['txtTags']);

	// Quand un article passe en �tat 'publier' on pr�vient
	// les utilisateurs qui ont mis une alerte mail sur le blog
	// et on modifie le fichier RSS du blog
	if ($_POST['arPublier'] == 1) {
		fpl_alerte($IDBlog, $_SESSION['IDArticle']);
		if ($_POST['arRSS'] == 0) {
			include_once('bibli_rss.php');
			fp_rss_maj($_SESSION['IDArticle']);
		}
	}
}
//_____________________________________________________________________________
/**
 * Mise � jour de la table des tags associ�s � l'article
 * 
 * @param	integer	$IDArticle	Cl� de l'article
 * @param	string	$tags		Les tags saisis dans le formulaire
 */
function fpl_majTags($IDArticle, $tags) {
	// Si la cl� article est diff�rente de 0, on effectue une modification.
	// On supprime tous les tags associ�s pr�c�demment � l'article.
	if ($IDArticle > 0) {
		$sql = "DELETE FROM tags_articles WHERE taIDArticle = $IDArticle";
		$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	}
	$IDArticle = $_SESSION['IDArticle'];
	// Traitement des tags saisis.
	// On remplace tous les caract�res qui ne sont pas alphanum�riques
	// par un espace. Puis on d�coupe la cha�ne avec comme s�parateur
	// les espaces. On exclut tous les tags num�riques et ceux qui
	// ont moins de 3 caract�res.
	$tags = preg_replace('/\W/', ' ', $tags);
	$tags = explode(' ', $tags);
	foreach($tags as $mot) {
		if (strlen($mot) < 3) continue;
		if (is_numeric($mot)) continue;
		$sql = "INSERT INTO tags_articles SET
				taIDArticle = $IDArticle,
				taNom = '".strtolower($mot)."'";
		$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te					
	}
}
//_____________________________________________________________________________
/**
 * Envoi d'un mail aux utilisateurs d�sirant �tre averti d'un nouvel article
 * sur le blog.
 * 
 * @param	integer	$IDBlog		Cl� du blog de l'article
 * @param	integer	$IDArticle	Cl� de l'article 
 */
function fpl_alerte($IDBlog, $IDArticle) {
	// Recherche des lecteurs d�sirant �tre alert�s
	// On recherche dans la table alertes_lecteurs les enregistrements
	// avec le blog en cours, et qui n'ont pas d'�quivalent dans la table
	// alertes_faites (ils ont d�j� �t� prevenus). Ceci pour �viter des 
	// alertes multiples si l'auteur de l'article fait des modifications.
	$sql = "SELECT alID, alMail
			FROM alertes_lecteurs
			LEFT JOIN alertes_faites ON alID = afIDLecteur
			WHERE afIDLecteur IS NULL
			AND alIDBlog = $IDBlog
			AND alValide = 1";

	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	if (mysqli_num_rows($R) == 0) {
		// On sort de la fonction
		//car il n'y a pas d'alertes � faire
		return;
	}											
														
	// Objet du mail
	$objet = "Nouvel article StarBlags";
	
	// texte du mail				
	// On recherche le nom du blog
	$sql = "SELECT blTitre
			FROM blogs
			WHERE blID = $IDBlog";
	$R1 = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	$enr = mysqli_fetch_assoc($R1);

	$texte = "Bonjour,<br><br>Il y a un nouvel article dans le blog '"
			.fp_protectHTML($enr['blTitre'])."'<br><br>"
			.'<a href="'.ADRESSE_SITE.'php_fp/'
			.'articles_voir.php?idBlog='.$IDBlog.'&idArticle='.$IDArticle.'">'
			.'Lire l\'article</a><br><br>StarBlags';
				
	// Envoi des mails aux lecteurs et mise � jour de la table alertes_faites
	$date = date('yyyymmdd');
	$heure = date('H:i');
	
	while ($enr = mysqli_fetch_assoc($R)) {
		fp_mail($enr['alMail'], $objet, $texte);
		
		$sql = "INSERT INTO alertes_faites SET
				afIDLecteur = {$enr['alID']},
				afIDArticle = $IDArticle, 
				afDate = $date,
				afHeure = '$heure'";
		$R1 = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te		
	}			
}
?>