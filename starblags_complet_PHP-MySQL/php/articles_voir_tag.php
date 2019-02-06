<?php
//_____________________________________________________________________________
/**
 * Affichage des articles liés à un tag
 * 
 * Affichage des articles triés par date de parution avec photos et pagination * 
 * Cette page reçoit des paramètres dans l'url :
 * - la signature de cryptage
 * - le nom du tag recherché
 * - le nombre d'articles liés au tag
 * - le numéro de la page à afficher pour la pagination. Si ce paramètre n'existe pas, 
 * il est initialisé à 0.
 * Les paramètres sont passés cryptés.
 * 
 * @param	integer	$_GET['x']		Signature | Tag | Nb articles | no page
 */
//_____________________________________________________________________________
ob_start();			// Bufférisation des sorties
session_start();	// démarrage session

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

// Vérification de la présence et de la validité des paramètres
$tmp = fp_getURL();
if (count($tmp) < 2) {
	exit((IS_DEBUG) ? 'Erreur GET - '.__LINE__.' - '.basename(__FILE__): '');
}
$tag = $tmp[0];
$nbArticles = $tmp[1];
$IDPage = (count($tmp) == 3) ? $tmp[2] : 0;	// Paramètre facultatif

$nbArticlesPage = 4;	// On affiche 4 articles par page

fp_bdConnecter();			// Ouverture base de données

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
			// les fichiers liés (CSS et JS) pendant que la page 
			// se fait sur le serveur
			
//_____________________________________________________________________________
//
// Affichage des articles liés au tag
//_____________________________________________________________________________
// Récupération du modèle. Comme le modele est utilisé autant de fois qu'il y a 
// d'articles, on le stocke pour ne pas avoir à refaire à chaque article
// une lecture disque.
$modele = fp_modeleGet('article');

// Requête SQL
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

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête

// Affichage de la sélection 	
while ($articles = mysqli_fetch_assoc($R)) {  // Boucle de lecture de la sélection
	// L'affichage de l'article et des images liées est fait 
	// dans une fonction car on utilise cette affichage dans d'autres pages
	fp_articleAffContenu($articles, $modele);

	// Lien sur le blog de l'auteur
	// Les paramètres du lien sont cryptés (IDBlog|IDArticle)
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
	echo "Articles $articleDebut à $articleFin sur $nbArticles<br>";
}

// Liens de navigation
echo 'Page ';
for ($i = 0, $page = 0; $i < $nbArticles; $i += $nbArticlesPage, $page ++) {
	if ($page == $IDPage) {  // page en cours, pas de lien
		echo '<span id="pageEnCours">', $page + 1, '</span>';
	} else {
		// Les paramètres du lien sont cryptés (Tag|Nb Article|No Page)
		$url = fp_makeUrl('articles_voir_tag.php', $tag, $nbArticles, $page);
		echo '<a href="'.$url.'">', $page + 1, '</a>';
	}
}
echo '</div>';

include('../modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>