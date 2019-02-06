<?php
//_____________________________________________________________________________
/**
 * Page d'accueil du moteur de blogs
 * 
 * Affichage des 3 blogs les plus visités
 * Affichages des 3 articles les mieux notés
 * Affichage des blogs hébergés
 */
//_____________________________________________________________________________
ob_start();			// Bufférisation des sorties
session_start();	// démarrage session

include_once('php/bibli.php');

fp_bdConnecter();	// Ouverture base de données

//_____________________________________________________________________________
//
//	Affichage du haut de la page
//_____________________________________________________________________________

// Lecture du modele debut.html, remplacement du titre et affichage
// Comme la fonction str_replace accepte des tableaux, pour faire 
// les remplacements dans les modèles on va utiliser un tableau associatif : 
// - la clé est l'expression à remplacer 
// - la valeur est la valeur de remplacement
// De cette façon la fonction ne sera appelée qu'une fois par modele.
// On utilisera la fonction array_keys($remplace) pour récupèrer un tableau
// des clés et array_values($remplace) pour récupèrer un tableau des valeurs.
// La fonction str_replace sera appelée par :
// str_replace(array_keys($remplace), array_values($remplace), $modele);
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Accueil';
$remplace['@_RSS_@'] = '';
$remplace['@_REP_@'] = '.';
fp_modeleTraite('debut_public', $remplace);

ob_flush();	// On vide le buffer pour que le navigateur charge 
			// les fichiers liés (CSS et JS) pendant que la page 
			// se fait sur le serveur
				
$remplace = array();
//_____________________________________________________________________________
//
//	Traitement du nuage de tags.
//_____________________________________________________________________________
// On récupère tous les tags existants, mais on n'affiche dans un premier temps
// que les tags dont le nombre de références dans les articles est supérieur ou 
// égal à 2.
// L'utilisateur peut afficher tous les tags référencés en cliquant sur un lien +
// On génére deux blocs : un avec un nombre réduit de tags, et un avec tous les 
// tags. On masque ou affiche l'un des deux blocs selon le choix de l'utilisateur.

$sql = "SELECT taNom, count(*) as Nb
		FROM tags_articles
		GROUP BY taNom
		ORDER BY taNom";
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	
// Récupération de la sélection dans un tableau.
// Permet de définir les mini et maxi pour calculer un ratio
$tags = array();
while ($enr = mysqli_fetch_assoc($R)) {	// Boucle de lecture de la sélection
	$tags[$enr['taNom']] = $enr['Nb'];
}
	
// Calcul mini, maxi, ratio
$sizeMini = 90;	// Taille de police mini en pourcentage
$sizeMaxi = 250;	// Taille de police maxi en pourcentage
$mini = min($tags);
$maxi = max($tags);
$Ratio = $maxi - $mini;
if ($Ratio == 0) {
	$Ratio = 1;
}
$Ratio = ($sizeMaxi - $sizeMini) / $Ratio;
$score = 2;
$blocReduit = $blocComplet = '';
	
// Affichage du nuage de tags
foreach($tags as $nom => $nb) {
	$size = $sizeMini + (($nb - $mini) * $Ratio);
	// Les paramètres du lien sont cryptés (Tag|Nb articles liés)
	$url = fp_makeURL('php/articles_voir_tag.php', $nom, $nb);
	$html = '<a href="'.$url.'" style="font-size: '.$size.'%;" '
			.'title="Voir les articles liés - '.$nb.'">'.$nom.'</a> '; 
		
	$blocComplet .= $html;
	if ($nb >= $score) {
		$blocReduit .= $html;
	}
}
if ($blocReduit == '') {
	$blocReduit = $blocComplet;
}

mysqli_free_result($R);	

$remplace['@_TAGS_REDUIT_@'] = $blocReduit;
$remplace['@_TAGS_COMPLET_@'] = $blocComplet;

//_____________________________________________________________________________
//
//	Traitement des 3 blogs les plus visités
//_____________________________________________________________________________
$sql = 'SELECT blID, blTitre, count(*) as Nb
		FROM blogs, blogs_visites
		WHERE bvIDBlog = blID
		GROUP BY blID, blTitre
		ORDER BY Nb DESC
		LIMIT 3';

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	
// Affichage de la sélection
$nb = 0;
while ($enr = mysqli_fetch_assoc($R)) {	// Boucle de lecture de la sélection
	// Affichage d'une ligne du tableau
	// Une cellule avec le nom du blog qui sert de lien pour le consulter
	// Une cellule avec le nombre de visites
	$nb++;
	// Les paramètres du lien sont cryptés (IDBlog|IDArticle)
	$url = fp_makeURL('php/articles_voir.php', $enr['blID'], 0);		
	$remplace["@_BLOG{$nb}_@"] = '<a href="'.$url.'">'.fp_protectHTML($enr['blTitre']).'</a>';
	$remplace["@_VISITE{$nb}_@"] = $enr['Nb'];
}
mysqli_free_result($R);
	
//_____________________________________________________________________________
// 
//	Traitement des 3 articles les mieux notés
//_____________________________________________________________________________
$sql = 'SELECT arID, arTitre, arIDBlog, sum(anNote) as Note
		FROM articles, articles_notes
		WHERE anIDArticle = arID
		GROUP BY arID, arTitre, arIDBlog
		ORDER BY Note DESC
		LIMIT 3';
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête

// Affichage de la sélection
$nb = 0; 
while ($enr = mysqli_fetch_assoc($R)) {	// Boucle de lecture de la sélection
	// Affichage d'une ligne article
	// Une cellule avec le titre de l'article qui sert de lien pour sa consultation
	// Une cellule avec la note de l'article
	$nb ++;
	// Les paramètres du lien sont cryptés (IDBlog|IDArticle)
	$url = fp_makeURL('php/articles_voir.php', $enr['arIDBlog'], $enr['arID']);
	$remplace["@_ARTICLE{$nb}_@"] = '<a href="'.$url.'">'.fp_protectHTML($enr['arTitre']).'</a>';
	$remplace["@_NOTE{$nb}_@"] = $enr['Note'];
}
mysqli_free_result($R);

// Lecture du modele hitparade.html, remplacement des motifs et affichage
fp_modeleTraite('hitparade', $remplace);

//_____________________________________________________________________________
//
// Affichage des blogs
//_____________________________________________________________________________
// Récupération du modèle. Comme le modele est utilisé autant de fois qu'il y a 
// de blogs, on le stocke pour ne pas avoir à refaire à chaque blogs une lecture
// disque.
$modele = fp_modeleGet('blog_01');

// Requête SQL
$sql = 'SELECT blID, blTitre, blAuteur, blDate, blResume, 
				count( arID ) AS Nb, max( arDate ) AS Dernier
		FROM blogs
		LEFT OUTER JOIN articles ON arIDBlog = blID AND arPublier = 1
		GROUP BY 1, 2, 3, 4, 5
		ORDER BY 2'; 
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête

// Affichage de la sélection
while ($enr = mysqli_fetch_assoc($R)) {  // Boucle de lecture de la sélection
	// Une ligne avec le nom du blog, l'auteur et la date de création
	// Une ligne avec le résumé, 
	// Une ligne avec le nombre d'articles (lien de consultation),
	// la date du dernier article publié et l'icône pour le calendrier des parutions
	$remplace = array();
	$remplace['@_TITRE_@'] = fp_protectHTML($enr['blTitre']);
	$remplace['@_AUTEUR_@'] = fp_protectHTML($enr['blAuteur']).' - '.fp_amjJma($enr['blDate']);
	$remplace['@_RESUME_@'] = fp_protectHTML($enr['blResume']);
			
	if ($enr['Nb'] == 0) {	
		// pas d'article dans le blog
		$remplace['@_LIEN_@'] = '#';
		$remplace['@_NB_ARTICLES_@'] = '';
		$remplace['@_DATE_@'] = 'Aucun article';
	} else {
		// Affichage du nombre d'articles dans le blog et de la date dernière parution
		// Les paramètres du lien sont cryptés (IDBlog|IDArticle)
		$remplace['@_LIEN_@'] = fp_makeURL('php/articles_voir.php', $enr['blID'], 0);	
		$remplace['@_NB_ARTICLES_@'] = $enr['Nb'].( ($enr['Nb'] > 1) ? ' articles':' article');
		$remplace['@_DATE_@'] = fp_amjJma($enr['Dernier']);
	}	

	fp_modeleAffiche($modele, $remplace);	// Remplacement et affichage du modele

	/*
	// Affichage d'une icone calendrier avec lien sur un script JavaScript-Ajax
	// qui permet d'afficher un calendrier mensuel avec les jours pour lesquels il y
	// a eu parution d'articles. Le script JavaScript masque ou affiche le bloc de
	// contenu, et déclenche une requête Ajax si nécessaire.
	// L'ID du bloc pour l'affichage est composé du nom blcCalendrier suivi de l'ID 
	// du blog concerné.	
	$url = fp_makeURL('php/articles_voir.php', $enr['blID'], 0);
	$IDBloc = 'blcCalendrier'.$enr['blID'];
	// Composition de l'appel à la fonction javascript
	$jS = substr($enr['Dernier'], 0, 6);
	$jS = "FP.affCalendrier({$enr['blID']}, $jS, '$IDBloc')";
		
	echo '<p class="petit">',
			'<a class="blogLienArticle" href="', $url, '" title="Voir les articles du blog">', 
			$enr['Nb'], ' article', ( ($enr['Nb'] > 1) ? 's</a> - ':'</a> - '),
			fp_amjJma($enr['Dernier']), '&nbsp;&nbsp;&nbsp;',
			'<a class="blogLienCalendrier" title="Calendrier des articles du blog" ',
			'href="javascript:',$jS,'"></a>',
		'</p>',
		'<div id="', $IDBloc, '" class="calendrier"></div>';
	*/
}
mysqli_free_result($R);
	
include('modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>