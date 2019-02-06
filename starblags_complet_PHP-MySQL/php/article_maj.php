<?php
//_____________________________________________________________________________
/**
 * Mise à jour d'un article.
 * 
 * Cette page est appelée 2 fois pour faire le traitement de mise à jour :
 * - le premier passage permet la saisie des infos dans un formulaire
 * - le deuxième passage correspond à la soumission du formulaire de saisie,
 *   à la vérification de la saisie et à la mise à jour de la base de données.
 * 
 * Au premier passage, on reçoit l'identifiant de l'article dans l'url. Dans le
 * cas d'une modification d'article (identifiant supérieur à 0), on vérifie que
 * l'article appartient bien au blog de l'utilisateur pour éviter que A modifie
 * les articles de B.
 * Au deuxième passage, l'identifiant article se trouve dans la variable de
 * session $_SESSION['IDArticle'];
 * 
 * Le deuxième passage est défini par l'existence de l'index bntValider ou de
 * l'index btnSupprimer dans le tableau $_POST. 
 * 
 * @param	integer	$_GET['x']	Identifiant de l'article à traiter
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

$IDBlog = $_SESSION['IDBlog'];	// Identifiant du blog de l'article
$erreurs = array();		// Tableau des messages d'erreur des zones invalides

// Initialisation des champs de saisie
$arTitre = $arTexte = $txtTags = '';
$arComment = $arPublier = 1;
$arRSS = 0;

fp_bdConnecter();	// Ouverture base de données

// Si l'identifiant article se trouve dans l'url, on vérifie sa validité.
// Si OK on le stocke dans la variable de session $_SESSION['IDArticle'].
if (isset($_GET['x'])) {
	$_SESSION['IDArticle'] = fp_getURL();
}

$IDArticle = $_SESSION['IDArticle'];	// Identifiant de l'article à traiter
								// si égal 0, c'est un traitement de création,
								// sinon c'est un traitement de modification.

//_____________________________________________________________________________
//
// Traitement soumission du formulaire de saisie
//
// Bien que ce ce traitement soit la seconde chose à faire, le code
// doit être avant celui qui traite la saisie pour qu'en cas d'erreur
// les éléments saisis au premier passage puissent être réaffichés.
//_____________________________________________________________________________
if (isset($_POST['btnSupprimer'])) {
	// On supprime l'article et les éléments qui lui sont rattachés
	include('bibli_delete_bd.php');
	fp_delete_article($IDArticle);
	// On redirige sur la page de liste des articles du blog
	$_SESSION['IDArticle'] = 0;
	header('Location: articles_liste.php');
	exit();  // fin PHP
}

if (isset($_POST['btnValider'])) {
	// On vérifie si les zones saisies sont valides. Si oui on fait la mise
	// à jour de la base de données puis on redirige sur la page 
	// de téléchargment de photo.
	$erreurs = fpl_verifZones();
	if (count($erreurs) == 0) {
		fpl_majBase($IDBlog, $IDArticle);	// Mise à jour BD
		// On initialise la variable de session $_SESSION['UploadFrom']
		// à 'article' pour que la page de téléchargement sache ce 
		// qu'elle doit traiter
		$_SESSION['UploadFrom'] = 'article';
		header('Location: upload.php');
		exit();  // fin PHP
	}
		
	// Si on passe ici c'est que des zones de saisie ne sont pas valides.
	// On va réafficher toutes les zones du formulaire et les messages d'erreur.
	// On commence par enlever éventuellement les protections automatiques
	// de caractères faite par PHP, puis on extrait les variables de $_POST
	fp_stripPOST();
	$arRSS = $_POST['arRSS'];
	$arTitre = $_POST['arTitre'];
	$arTexte = $_POST['arTexte'];
	$arComment = $_POST['arComment'];
	$arPublier = $_POST['arPublier'];
}
//_____________________________________________________________________________
//
// Lecture de la table articles si nécessaire
// La table est lue uniquement si on est dans un traitement de modification
// (IDArticle > 0) et que le tableau des erreurs est vide. Si ce tableau n'est 
// pas vide c'est qu'une saisie a déjà été faite et il ne faut pas effacer
// les infos saisies par celles venant de la BD. 
//_____________________________________________________________________________
if ($IDArticle > 0 && count($erreurs) == 0) {
	$sql = "SELECT *
			FROM articles
			WHERE arID = $IDArticle";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	$enr = mysqli_fetch_assoc($R);	// Récupération de la sélection
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
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
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
$remplace['@_TITRE_@'] = ($IDArticle == 0) ? 'Je crée un article...' : 'Je mets à jour un article ...';

// Lecture du modele debut_prive.html, remplacement motifs et affichage
fp_modeleTraite('debut_prive', $remplace);

// Affichage des erreurs de saisie précédentes
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
fp_htmlSaisie('T', 'txtTags', $txtTags, 'Tags (mots-clés)');
 
// Si on est dans un traitement de création d'article,
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
 * Vérification de la validité des zones de saisie
 * 
 * @global	array	$_POST	ZOnes de saisie du formulaire
 * 
 * @return	array	Tableau associatif avec les messages d'erreurs (index = nom de zone)
 */
function fpl_verifZones() {
	$erreurs = array();
	
	if (!preg_match('/^\S./', $_POST['arTitre'])) {
		$erreurs['arTitre'] = 'La zone Titre ne doit pas commencer par un espace ou être vide.';
	}
	if (!preg_match('/^\S./', $_POST['arTexte'])) {
		$erreurs['arTexte'] = 'La zone Texte ne doit pas commencer par un espace ou être vide.';
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
 * Mise à jour de la base de données - insert ou update
 * 
 * @param	integer	$IDBlog		Clé du blog de l'article
 * @param	integer	$IDArticle	Clé de l'article à traiter
 * @global	array	$_POST		Les zones de saisie du formulaire
 */
function fpl_majBase($IDBlog, $IDArticle) {
	// Si la clé de l'article à traiter est 0 on est dans le cas d'une création,
	// et on fait donc une requête d'INSERT.
	// Si la clé de l'article est supérieur à 0, on est dans le cas d'une modification,
	// et on fait donc une requête d'UPDATE.
	// Noter la facilité offerte par MySql dans la syntaxe des requêtes INSERT 
	// et UPDATE.
	
	// On enlève les scripts éventuel du texte qui peut contenir des tags HTML
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
  
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	
	// Dans le cas de la création d'un nouvel article, il faut mettre à jour
	// l'identifiant de l'article dans la variable de session $_SESSION['IDArticle']
	// et on initialise la variable de session $_SESSION['UploadNum'] (numéro de photo) à 0.
	// Dans le cas de la modification d'un article, on recherche le plus grand numéro
	// de photo pour initialiser $_SESSION['UploadNum']
	if ($IDArticle == 0) {
		$_SESSION['IDArticle'] = mysqli_insert_id();
		$_SESSION['UploadNum'] = 0;
	} else {
		$sql = "SELECT max(phNumero)
				FROM photos
				WHERE phIDArticle = {$_SESSION['IDArticle']}";
		$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête 
		$enr = mysqli_fetch_row($R);
		$_SESSION['UploadNum'] = ($enr[0] == null) ? 0 : $enr[0];
	}
	
	// Mise à jour des tags associés à l'article
	fpl_majTags($IDArticle, $_POST['txtTags']);

	// Quand un article passe en état 'publier' on prévient
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
 * Mise à jour de la table des tags associés à l'article
 * 
 * @param	integer	$IDArticle	Clé de l'article
 * @param	string	$tags		Les tags saisis dans le formulaire
 */
function fpl_majTags($IDArticle, $tags) {
	// Si la clé article est différente de 0, on effectue une modification.
	// On supprime tous les tags associés précédemment à l'article.
	if ($IDArticle > 0) {
		$sql = "DELETE FROM tags_articles WHERE taIDArticle = $IDArticle";
		$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	}
	$IDArticle = $_SESSION['IDArticle'];
	// Traitement des tags saisis.
	// On remplace tous les caractères qui ne sont pas alphanumériques
	// par un espace. Puis on découpe la chaîne avec comme séparateur
	// les espaces. On exclut tous les tags numériques et ceux qui
	// ont moins de 3 caractères.
	$tags = preg_replace('/\W/', ' ', $tags);
	$tags = explode(' ', $tags);
	foreach($tags as $mot) {
		if (strlen($mot) < 3) continue;
		if (is_numeric($mot)) continue;
		$sql = "INSERT INTO tags_articles SET
				taIDArticle = $IDArticle,
				taNom = '".strtolower($mot)."'";
		$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête					
	}
}
//_____________________________________________________________________________
/**
 * Envoi d'un mail aux utilisateurs désirant être averti d'un nouvel article
 * sur le blog.
 * 
 * @param	integer	$IDBlog		Clé du blog de l'article
 * @param	integer	$IDArticle	Clé de l'article 
 */
function fpl_alerte($IDBlog, $IDArticle) {
	// Recherche des lecteurs désirant être alertés
	// On recherche dans la table alertes_lecteurs les enregistrements
	// avec le blog en cours, et qui n'ont pas d'équivalent dans la table
	// alertes_faites (ils ont déjà été prevenus). Ceci pour éviter des 
	// alertes multiples si l'auteur de l'article fait des modifications.
	$sql = "SELECT alID, alMail
			FROM alertes_lecteurs
			LEFT JOIN alertes_faites ON alID = afIDLecteur
			WHERE afIDLecteur IS NULL
			AND alIDBlog = $IDBlog
			AND alValide = 1";

	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	if (mysqli_num_rows($R) == 0) {
		// On sort de la fonction
		//car il n'y a pas d'alertes à faire
		return;
	}											
														
	// Objet du mail
	$objet = "Nouvel article StarBlags";
	
	// texte du mail				
	// On recherche le nom du blog
	$sql = "SELECT blTitre
			FROM blogs
			WHERE blID = $IDBlog";
	$R1 = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	$enr = mysqli_fetch_assoc($R1);

	$texte = "Bonjour,<br><br>Il y a un nouvel article dans le blog '"
			.fp_protectHTML($enr['blTitre'])."'<br><br>"
			.'<a href="'.ADRESSE_SITE.'php_fp/'
			.'articles_voir.php?idBlog='.$IDBlog.'&idArticle='.$IDArticle.'">'
			.'Lire l\'article</a><br><br>StarBlags';
				
	// Envoi des mails aux lecteurs et mise à jour de la table alertes_faites
	$date = date('yyyymmdd');
	$heure = date('H:i');
	
	while ($enr = mysqli_fetch_assoc($R)) {
		fp_mail($enr['alMail'], $objet, $texte);
		
		$sql = "INSERT INTO alertes_faites SET
				afIDLecteur = {$enr['alID']},
				afIDArticle = $IDArticle, 
				afDate = $date,
				afHeure = '$heure'";
		$R1 = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête		
	}			
}
?>