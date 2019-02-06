<?php
//_____________________________________________________________________________
/**
 * Affichage des articles d'un blog
 * 
 * Affichage des infos sur le blog.
 * Affichage des articles tri�s avec photos et pagination
 * 
 * Cette page re�oit des param�tres dans l'url :
 * - la signature de cryptage
 * - la cl� du blog � afficher
 * - la cl� d'un article. Si cette cl� est �gale � 0, on affiche tous les articles
 *   du blog. Sinon on affiche uniquement l'article identifi�. 
 * - le num�ro de la page � afficher pour la pagination. Si ce param�tre n'existe pas, 
 * il est initialis� � 0.
 * - la date de publication pour la s�lection des articles. Si ce param�tre n'existe pas, 
 * il est initialis� � 0.
 * Les param�tres sont pass�s crypt�s.
 * 
 * @param	integer	$_GET['x']		Signature | ID du blog | ID article | no page | AMJ
 */
//_____________________________________________________________________________
ob_start();			// Buff�risation des sorties
session_start();	// d�marrage session

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

// V�rification de la pr�sence et de la validit� des param�tres
$tmp = fp_getURL();
$iMax = count($tmp);
if ($iMax < 2) {
	exit((IS_DEBUG) ? 'Erreur GET - '.__LINE__.' - '.basename(__FILE__): '');
}
$IDBlog = (int) $tmp[0];
$IDArticle = (int) $tmp[1];
$IDPage = ($iMax > 2) ? (int) $tmp[2] : 0;	// Param�tre facultatif
$aMJ = ($iMax > 3) ? (int) $tmp[3] : 0;	// Param�tre facultatif

fp_bdConnecter();	// Ouverture base de donn�es

// On met � jour le compteur de visites du blog
$sql = "INSERT INTO blogs_visites SET 
		bvIDBlog = $IDBlog,
		bvDate = ".date('Ymd').",
		bvHeure = '".date('H:i:s')."',
		bvIP = '".fp_getIP()."'";
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

// R�cup�ration des infos sur le blog affich�
$sql = "SELECT blogs.*, count( arID ) AS NbArticles, max( arDate ) AS Dernier
		FROM blogs, articles
		WHERE blID = $IDBlog
		AND arIDBlog = blID
		AND arPublier = 1
		GROUP BY 1"; 
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

$blogs = mysqli_fetch_assoc($R);	// R�cup�ration de la s�lection
if ($blogs === FALSE) {  // Le blog n'existe pas : redirection sur la page d'accueil
	header('Location: index.php');
	exit();
}
mysqli_free_result($R);

// R�cup�ration du nombre de visites pour le blog
$sql = "SELECT count(*)
		FROM blogs_visites
		WHERE bvIDBlog = $IDBlog";
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

$tmp = mysqli_fetch_array($R);	// R�cup�ration de la s�lection
if ($tmp === FALSE) {
	// Il n'y a pas de visites pour le blog
	$nbVisites = 0;
} else {
	$nbVisites = $tmp[0];
}
mysqli_free_result($R);

//_____________________________________________________________________________
//
//	Affichage du haut de la page
//_____________________________________________________________________________
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - '.fp_protectHTML($blogs['blTitre']);
// Le link suivant permet � Firefox d'afficher une icone � droite de l'url
// pour une inscription automatique du flux RSS dans les 'live bookmark'
// ou la lecture directe du flux avec Firefox 2
$remplace['@_RSS_@'] = '<link rel="alternate" type="application/rss+xml" href="'.ADRESSE_RSS.'rss_'.$IDBlog.'.xml">';
$remplace['@_REP_@'] = '..';

// Lecture du modele debut.html, remplacement motifs et affichage
fp_modeleTraite('debut_public', $remplace);

ob_flush();	// On vide le buffer pour que le navigateur charge 
			// les fichiers li�s (CSS et JS) pendant que la page 
			// se fait sur le serveur
			
//_____________________________________________________________________________
//
//	Affichage des infos sur le blog
//_____________________________________________________________________________
// On affiche le titre du blog, la photo de l'auteur si il y en a une,
// le r�sume du blog, 
// l'auteur, 
// le nombre de visites
// le nombre d'aricles, la date du dernier, un lien pour afficher la liste des articles
// des liens pour s'inscrire au fil RSS et � l'alerte mail
// quand un nouvel article est cr�� dans la blog.
// Les param�tres des liens sont crypt�s (IDBlog)

$remplace = array();
$remplace['@_TITRE_@'] = fp_protectHTML($blogs['blTitre']);
// Si il y a une photo ...
if ($blogs['blPhoto'] != '') {
	$url = REP_UPLOAD.$blogs['blID'].'.'.$blogs['blPhoto'];
	$remplace['@_PHOTO_@'] = '<img src="'.$url.'" hspace="5" align="right">';
} else {
	$remplace['@_PHOTO_@'] = '';
}
$remplace['@_RESUME_@'] = fp_protectHTML($blogs['blResume'], true);
$remplace['@_AUTEUR_@'] = fp_protectHTML($blogs['blAuteur']);
$remplace['@_VISITES_@'] = "$nbVisites  depuis le ".fp_amjJma($blogs['blDate']);
$remplace['@_ARTICLES_@'] = "{$blogs['NbArticles']} (derni�re publication le ".fp_amjJma($blogs['Dernier']);
$url = fp_makeURL('blog_alerte.php', $IDBlog);
$remplace['@_LIEN_MAIL_@'] = "javascript:FP.ouvrePopUp('$url')";
$url = fp_makeURL('blog_rss.php', $IDBlog);
$remplace['@_LIEN_RSS_@'] = "javascript:FP.ouvrePopUp('$url')";

// Lecture du modele blog_02.html, remplacement du titre et affichage
fp_modeleTraite('blog_02', $remplace);

//_____________________________________________________________________________
//
// Affichage des articles du blog
//_____________________________________________________________________________
// G�n�ration de la liste de tous les articles pour acc�s rapide.
// La liste est un bloc cach� � l'affichage initial de la page
$sql = "SELECT arID, arTitre, arDate, arHeure
		FROM articles
		WHERE arIDBlog = $IDBlog 
		AND arPublier = 1 
		ORDER BY arDate DESC, arHeure DESC";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

echo '<div id="blcListeArticles">',
		'<h2>Les articles du blog</h2>';
	
while($enr = mysqli_fetch_assoc($R)) {
	// Les changements de couleur au survol de la souris sont faits avec les styles. 
	// Voir #blcListeArticles, #blcListeArticles a, et #blcListeArticles a:hover
	// Les param�tres du lien sont crypt�s (IDBlog|IDarticle)
	$url = fp_makeURL('articles_voir.php', $IDBlog, $enr['arID']);
	
	echo '<a href="', $url, '">',
			fp_amjJma($enr['arDate']), ' - ',
			fp_protectHTML($enr['arHeure']), ' : ',
			fp_protectHTML($enr['arTitre']), 
		'</a>';
}
echo '</div>';
//_____________________________________________________________________________
// Affichage d�taill� des articles
// R�cup�ration du mod�le. Comme le modele est utilis� autant de fois qu'il y a 
// d'articles, on le stocke pour ne pas avoir � refaire � chaque article
// une lecture disque.
$modele = fp_modeleGet('article');

// Requ�te SQL
$tri = ($blogs['blTri'] == 0) ? 'ASC' : 'DESC';
$posDebut = $IDPage * $blogs['blNbArticlesPage'];
$nb = $blogs['blNbArticlesPage'];

// La requ�te SQL est diff�rente suivant qu'on demande l'affichage 
// - d'un seul article, 
// - de tous les articles, 
// - des articles � une date pr�cise, .
if ($IDArticle > 0) {	// Un seul article
	$sql = "SELECT articles.*, count(coID) as NbComments
			FROM articles
			LEFT OUTER JOIN commentaires ON coIDArticle = arID
			WHERE  arID = $IDArticle
			AND arPublier = 1
			GROUP BY 1";
} elseif ($aMJ == 0) {	// Tous les articles
	$sql = "SELECT articles.*, count(coID) as NbComments
			FROM articles
			LEFT OUTER JOIN commentaires ON coIDArticle = arID
			WHERE arIDBlog = $IDBlog 
			AND arID >= $IDArticle
			AND arPublier = 1
			GROUP BY 1
			ORDER BY arDate $tri, arHeure $tri
			LIMIT $posDebut, $nb";
} else {	// Articles paru � une date pr�cise
	$sql = "SELECT articles.*, count(coID) as NbComments
			FROM articles
			LEFT OUTER JOIN commentaires ON coIDArticle = arID
			WHERE arIDBlog = $IDBlog 
			AND arDate = $aMJ
			AND arPublier = 1
			GROUP BY 1
			ORDER BY arHeure $tri";
}
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);

// Affichage de la s�lection 	
while ($articles = mysqli_fetch_assoc($R)) {  // Boucle de lecture de la s�lection
	// L'affichage de l'article et des images li�es est fait 
	// dans une fonction car on utilise cette affichage dans d'autres pages
	fp_articleAffContenu($articles, $modele);
}

mysqli_free_result($R);
//_____________________________________________________________________________
//
// Gestion de la pagination
//_____________________________________________________________________________
// Si on affiche un seul article, ou les articles d'une date pr�cise, il n'y a 
// pas de pagination, mais un lien pour afficher tous les articles du blog. 
// On fait cette affichage puis on arr�te le traitement PHP qui est termin�.
if ($IDArticle > 0 || $aMJ > 0) {
	// Les param�tres du lien sont crypt�s (IDBlog|IDArticle)
	$url = fp_makeUrl('articles_voir.php', $IDBlog, 0);
	echo '<div id="blcPagination">',
		'<a href="', $url, '">Voir tous les articles</a>',
		'</div>';
		
	include('../modeles/fin.html');  // Fin de la page
	ob_end_flush();	// Fermeture du buffer => envoi du contenu au navigateur
	exit();			// Fin PHP
}

// Si on affiche la liste des articles du blog, on g�re la pagination
// suivant le nombre de pages d�fini dans le blog.
// Cette partie pourrait �tre am�lior�e car on a le m�me traitment
// dans la page articles_voir_tag.php

echo '<div id="blcPagination">';

$nbArticles = $blogs['NbArticles'];
$nbParPage = $blogs['blNbArticlesPage'];
$articleDebut = ($IDPage * $nbParPage) + 1;
$articleFin = ($IDPage * $nbParPage) + $nbParPage;
if ($articleFin > $nbArticles) {
	$articleFin = $nbArticles;
}

if ($articleDebut == $articleFin) {
	echo "Article $articleDebut sur $nbArticles<br>";
} else {
	echo "Articles $articleDebut � $articleFin sur $nbArticles<br>";
}

// Liens de navigation
echo 'Page ';
for ($i = 0, $page = 0; $i < $nbArticles; $i += $nbParPage, $page ++) {
	if ($page == $IDPage) {  // page en cours, pas de lien
		echo '<span id="pageEnCours">', $page + 1, '</span>';
	} else {
		// Les param�tres du lien sont crypt�s (IDBlog|IDArticle|No Page)
		$url = fp_makeUrl('articles_voir.php', $IDBlog, 0, $page);
		echo '<a href="'.$url.'">', $page + 1, '</a>';
	}
}
echo '</div>';

include('../modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>