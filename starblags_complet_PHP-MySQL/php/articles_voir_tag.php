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
 * @param	integer	$_GET['x']		Signature | Tag | Nb articles | no page
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
if (count($tmp) < 2) {
	exit((IS_DEBUG) ? 'Erreur GET - '.__LINE__.' - '.basename(__FILE__): '');
}
$tag = $tmp[0];
$nbArticles = $tmp[1];
$IDPage = (count($tmp) == 3) ? $tmp[2] : 0;	// Param�tre facultatif

$nbArticlesPage = 4;	// On affiche 4 articles par page

fp_bdConnecter();			// Ouverture base de donn�es

//_____________________________________________________________________________
//
//	Affichage du haut de la page
//_____________________________________________________________________________
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Articles tag';
$remplace['@_RSS_@'] = '';
$remplace['@_REP_@'] = '..';

// Lecture du modele debut.html, remplacement motifs et affichage
fp_modeleTraite('debut_public', $remplace);

ob_flush();	// On vide le buffer pour que le navigateur charge 
			// les fichiers li�s (CSS et JS) pendant que la page 
			// se fait sur le serveur
			
//_____________________________________________________________________________
//
// Affichage des articles li�s au tag
//_____________________________________________________________________________
// R�cup�ration du mod�le. Comme le modele est utilis� autant de fois qu'il y a 
// d'articles, on le stocke pour ne pas avoir � refaire � chaque article
// une lecture disque.
$modele = fp_modeleGet('article');

// Requ�te SQL
$posDebut = $IDPage * $nbArticlesPage;

$sql = "SELECT articles.*, count(coID) as NbComments
		FROM tags_articles, articles
		LEFT OUTER JOIN commentaires ON coIDArticle = arID
		WHERE  arID = taIDArticle
		AND arPublier = 1
		AND taNom = '".fp_protectSQL($tag)."'
		GROUP BY 1
		ORDER BY arDate DESC, arHeure DESC
		LIMIT $posDebut, $nbArticlesPage";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

// Affichage de la s�lection 	
while ($articles = mysqli_fetch_assoc($R)) {  // Boucle de lecture de la s�lection
	// L'affichage de l'article et des images li�es est fait 
	// dans une fonction car on utilise cette affichage dans d'autres pages
	fp_articleAffContenu($articles, $modele);

	// Lien sur le blog de l'auteur
	// Les param�tres du lien sont crypt�s (IDBlog|IDArticle)
	$url = fp_makeURL('articles_voir.php', $articles['arIDBlog'], 0);
	echo '<div class="blcLienAuteur">',
			'<a href="', $url, '">Le blog de l\'auteur</a>',
		'</div>';	
}
mysqli_free_result($R);
//_____________________________________________________________________________
//
// Gestion de la pagination
//_____________________________________________________________________________
// Tableau pour la gestion de la pagination
echo '<div id="blcPagination">';
	
$articleDebut = ($IDPage * $nbArticlesPage) + 1;
$articleFin = ($IDPage * $nbArticlesPage) + $nbArticlesPage;
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
for ($i = 0, $page = 0; $i < $nbArticles; $i += $nbArticlesPage, $page ++) {
	if ($page == $IDPage) {  // page en cours, pas de lien
		echo '<span id="pageEnCours">', $page + 1, '</span>';
	} else {
		// Les param�tres du lien sont crypt�s (Tag|Nb Article|No Page)
		$url = fp_makeUrl('articles_voir_tag.php', $tag, $nbArticles, $page);
		echo '<a href="'.$url.'">', $page + 1, '</a>';
	}
}
echo '</div>';

include('../modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>