<?php
//_____________________________________________________________________________
/**
 * Mise à jour des fichiers XML pour les flux RSS avec tous les articles des blogs.
 * 
 * Cette page est appelée 2 fois pour faire le traitement de mise à jour :
 * - le premier passage permet la validation du début du traitement
 * - le deuxième passage correspond au traitement proprement dit de génération
 *   d'un fichier XML au format RSS 2.0.
 * 
 * Cette page est à lancer par l'administrateur du site. Elle devrait normalement
 * être protégée par un mot de passe, mais ici pour simplifier elle ne l'est pas.
 * 
 * Le deuxième passage est défini par l'existence de l'index bntValider dans le 
 * tableau $_POST. 
 * 
 * @param	array	$_POST				Données des formulaires soumis
 */
//_____________________________________________________________________________
ob_start();			// Bufférisation des sorties

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

fp_bdConnecter();	// Ouverture base de données

$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Génération RSS';
$remplace['@_RSS_@'] = '';
$remplace['@_REP_@'] = '..';

// Lecture du modele debut.html, remplacement motifs et affichage
fp_modeleTraite('debut_public', $remplace);

//_____________________________________________________________________________
//
// Premier passage pour lancement du traitement
//_____________________________________________________________________________
if (!isset($_POST['btnValider'])) {
	//	Affichage du texte et du bouton de lancement du traitement
	echo '<form method="post" action="rss_maj.php">',
			'<table cellspacing="0" cellpadding="2" class="majTable">',
				'<tr>',
					'<td>',
						'Cette page permet de mettre à jour les fichiers de flux RSS des blogs du site.',
					'</td>',
				'</tr>';
		
	fp_htmlBoutons(1, 'S|btnValider|Mettre à jour');

	//	Fin de page
	echo 	'</table>',
		'</form>';
	
	include('../modeles/fin.html');  // Fin de la page
	ob_end_flush();	// Fermeture du buffer => envoi du contenu au navigateur
	exit();			// Fin du script pour le premier passage
}

//_____________________________________________________________________________
//
// Traitement de mise à jour des fichier XML pour les flux RSS
//_____________________________________________________________________________
//
// Sélection des articles à publier dans le flux.
//		
$sql = 'SELECT articles.*, blTitre, blResume
		FROM blogs, articles
		WHERE blID = arIDBlog
		AND arPublier = 1
		ORDER BY arIDBlog, arDate DESC, arHeure DESC';
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	
// Si il n'y a pas d'article à traiter, on arrête le script
$nbArticles = mysqli_num_rows($R);

if ( $nbArticles == 0) {
	echo '<p class="majTable">Il n\'y a pas d\'article.</p>';
	
	include('../modeles/fin.html');  // Fin de la page
	
	ob_end_flush();	// Fermeture du buffer => envoi du contenu au navigateur
	exit();			// Fin du script pour le premier passage
}

//
// Traitement de la sélection des articles. 
// Pour chaque blog on génére un fichier de flux RSS.
//
$RSS = array();	// Pour gérer la rupture sur les blogs
$RSS['IDBlog'] = -1;
$RSS['titre'] = '';
$RSS['resume'] = '';
$RSS['items'] = '';

while ($enr = mysqli_fetch_assoc($R)) {
	// Si il y a une ruprture sur le blog, on génére le fichier de flux
	// pour le dernier blog traité puis on initialise le flux pour 
	// le nouveau blog.
	if ($RSS['IDBlog'] != $enr['arIDBlog']) {
		if ($RSS['IDBlog'] != -1) {
			fpl_creerRSS($RSS);
		}
		
		$RSS['IDBlog'] = $enr['arIDBlog'];
		$RSS['titre'] = $enr['blTitre'];
		$RSS['resume'] = $enr['blResume'];
		$RSS['items'] = '';
	}
	
	// Création de l'élément item du flux
	$RSS['items'] .= fpl_creerItem($enr);
}

// On génére le fichier de flux du dernier blog traité
fpl_creerRSS($RSS);

//_____________________________________________________________________________
// On met à jour les articles traités pour ne pas les retrouver
// dans le prochain flux RSS
$sql = 'UPDATE articles 
		SET arRSS = 1
		WHERE arPublier = 1';

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	
//	Fin de page
echo '<p class="majTable">La mise à jour des fichiers de flux RSS est terminée.</p>';

include('../modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur

//_____________________________________________________________________________
//
// 							FONCTIONS
//_____________________________________________________________________________
/**
 * Création d'un fichier RSS
 * 
 * @param	array	$RSS	Tableau associatif des éléments pour créer le fichier
 * 							soit IDBlog, titre, resume, items
 */
function fpl_creerRSS($RSS) {
	// Url du site
	$url = fp_makeURL(ADRESSE_PAGE.'articles_voir.php', $RSS['IDBlog'],0);
	
	$flux = '<?xml version="1.0" encoding="iso-8859-15" ?>
			<rss version="2.0">
				<channel>
					<title>StarBlagS : '.$RSS['titre'].'</title>
					<link>'.$url.'</link>
					<description>Les derniers articles du blog</description>
					<language>fr</language>
					<lastBuildDate>'.date('r').'</lastBuildDate>
					<ttl>1500</ttl>'
					.$RSS['items']
				.'</channel>
			</rss>';

	// Ecriture du fichier avec le flux RSS.
	// Le fichier s'appelle rss_[idblog].xml
	// et se trouve dans le répertoire rss.
	$nomFichier = '../rss/rss_'.$RSS['IDBlog'].'.xml';
	
	$fichier = @fopen($nomFichier, 'w');	// ouverture du fichier
	if ($fichier === FALSE) {
		// Erreur d'écriture : on arrête le script
		echo '<p class="majTable">Une erreur s\'est produite dans l\'ouverture du fichier ',
			$nomFichier,'.</p>';
		include('../modeles/fin.html');  // Fin de la page
		ob_end_flush();	// Fermeture du buffer => envoi du contenu au navigateur
		exit();			// Fin du script
	}
	
	if (@fwrite($fichier, $flux) === FALSE) {	// Ecriture du fichier
		// Erreur d'écriture : on arrête le script
		echo '<p class="majTable">Une erreur s\'est produite dans l\'écriture du fichier ',
			$nomFichier,'.</p>';
		include('../modeles/fin.html');  // Fin de la page
		ob_end_flush();	// Fermeture du buffer => envoi du contenu au navigateur
		exit();			// Fin du script
	}
	
	@fclose($fichier);	// Fermeture du fichier
}
//_____________________________________________________________________________
/**
 * Création d'un item d'un flux RSS
 * 
 * @param	array	$enr		Enregistrement de la BD avec les infos à traiter
 */
function fpl_creerItem($enr) {
	$url = fp_makeURL(ADRESSE_PAGE.'articles_voir.php', $enr['arIDBlog'], $enr['arID']);
	// Date de publication
	$h = substr($enr['arHeure'], 0, 2);
	$mi = substr($enr['arHeure'], -2);
	$s = 0;
	$a = substr($enr['arDate'], 0, 4);
	$m = substr($enr['arDate'], 4, 2);
	$j = substr($enr['arDate'], 6, 2);
	$date = date( 'r', mktime($h, $mi, $s, $m, $j, $a));
	// Suppression des tags html qui se trouveraient dans le titre ou le texte
	// on pourrait aussi garder les tags HTML éventuels en mettant les zones dans
	// une section CDATA, mais pas sûr qu'elle soit prise en compte par les lecteurs.
	// Exemple : <description><![CDATA[$texte ...]]></description>
	$titre = fpl_protectRSS($enr['arTitre']);
	$texte = fpl_protectRSS($enr['arTexte']);
	$texte = substr($texte, 0, 95);
	
	return "<item>
				<title>$titre</title>
				<description>$texte ...</description>
				<link>$url</link>
				<pubDate>$date</pubDate>
			</item>";
}
//_____________________________________________________________________________
/**
 * Protection d'une chaine pour le fichier RSS : pas de tags ni d'entités HTML
 * 
 * @param	text	$texte		Texte à protéger
 */
function fpl_protectRSS($texte) {
	$texte = strip_tags($texte);
	$texte = html_entity_decode($texte);
	// Entité spécifique FCKEditor
	$texte = str_replace('&rsquo;', "'", $texte);
	return $texte; 
}
?>