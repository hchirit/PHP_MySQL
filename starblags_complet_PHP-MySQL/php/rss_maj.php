<?php
//_____________________________________________________________________________
/**
 * Mise � jour des fichiers XML pour les flux RSS avec tous les articles des blogs.
 * 
 * Cette page est appel�e 2 fois pour faire le traitement de mise � jour :
 * - le premier passage permet la validation du d�but du traitement
 * - le deuxi�me passage correspond au traitement proprement dit de g�n�ration
 *   d'un fichier XML au format RSS 2.0.
 * 
 * Cette page est � lancer par l'administrateur du site. Elle devrait normalement
 * �tre prot�g�e par un mot de passe, mais ici pour simplifier elle ne l'est pas.
 * 
 * Le deuxi�me passage est d�fini par l'existence de l'index bntValider dans le 
 * tableau $_POST. 
 * 
 * @param	array	$_POST				Donn�es des formulaires soumis
 */
//_____________________________________________________________________________
ob_start();			// Buff�risation des sorties

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

fp_bdConnecter();	// Ouverture base de donn�es

$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - G�n�ration RSS';
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
						'Cette page permet de mettre � jour les fichiers de flux RSS des blogs du site.',
					'</td>',
				'</tr>';
		
	fp_htmlBoutons(1, 'S|btnValider|Mettre � jour');

	//	Fin de page
	echo 	'</table>',
		'</form>';
	
	include('../modeles/fin.html');  // Fin de la page
	ob_end_flush();	// Fermeture du buffer => envoi du contenu au navigateur
	exit();			// Fin du script pour le premier passage
}

//_____________________________________________________________________________
//
// Traitement de mise � jour des fichier XML pour les flux RSS
//_____________________________________________________________________________
//
// S�lection des articles � publier dans le flux.
//		
$sql = 'SELECT articles.*, blTitre, blResume
		FROM blogs, articles
		WHERE blID = arIDBlog
		AND arPublier = 1
		ORDER BY arIDBlog, arDate DESC, arHeure DESC';
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	
// Si il n'y a pas d'article � traiter, on arr�te le script
$nbArticles = mysqli_num_rows($R);

if ( $nbArticles == 0) {
	echo '<p class="majTable">Il n\'y a pas d\'article.</p>';
	
	include('../modeles/fin.html');  // Fin de la page
	
	ob_end_flush();	// Fermeture du buffer => envoi du contenu au navigateur
	exit();			// Fin du script pour le premier passage
}

//
// Traitement de la s�lection des articles. 
// Pour chaque blog on g�n�re un fichier de flux RSS.
//
$RSS = array();	// Pour g�rer la rupture sur les blogs
$RSS['IDBlog'] = -1;
$RSS['titre'] = '';
$RSS['resume'] = '';
$RSS['items'] = '';

while ($enr = mysqli_fetch_assoc($R)) {
	// Si il y a une ruprture sur le blog, on g�n�re le fichier de flux
	// pour le dernier blog trait� puis on initialise le flux pour 
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
	
	// Cr�ation de l'�l�ment item du flux
	$RSS['items'] .= fpl_creerItem($enr);
}

// On g�n�re le fichier de flux du dernier blog trait�
fpl_creerRSS($RSS);

//_____________________________________________________________________________
// On met � jour les articles trait�s pour ne pas les retrouver
// dans le prochain flux RSS
$sql = 'UPDATE articles 
		SET arRSS = 1
		WHERE arPublier = 1';

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	
//	Fin de page
echo '<p class="majTable">La mise � jour des fichiers de flux RSS est termin�e.</p>';

include('../modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur

//_____________________________________________________________________________
//
// 							FONCTIONS
//_____________________________________________________________________________
/**
 * Cr�ation d'un fichier RSS
 * 
 * @param	array	$RSS	Tableau associatif des �l�ments pour cr�er le fichier
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
	// et se trouve dans le r�pertoire rss.
	$nomFichier = '../rss/rss_'.$RSS['IDBlog'].'.xml';
	
	$fichier = @fopen($nomFichier, 'w');	// ouverture du fichier
	if ($fichier === FALSE) {
		// Erreur d'�criture : on arr�te le script
		echo '<p class="majTable">Une erreur s\'est produite dans l\'ouverture du fichier ',
			$nomFichier,'.</p>';
		include('../modeles/fin.html');  // Fin de la page
		ob_end_flush();	// Fermeture du buffer => envoi du contenu au navigateur
		exit();			// Fin du script
	}
	
	if (@fwrite($fichier, $flux) === FALSE) {	// Ecriture du fichier
		// Erreur d'�criture : on arr�te le script
		echo '<p class="majTable">Une erreur s\'est produite dans l\'�criture du fichier ',
			$nomFichier,'.</p>';
		include('../modeles/fin.html');  // Fin de la page
		ob_end_flush();	// Fermeture du buffer => envoi du contenu au navigateur
		exit();			// Fin du script
	}
	
	@fclose($fichier);	// Fermeture du fichier
}
//_____________________________________________________________________________
/**
 * Cr�ation d'un item d'un flux RSS
 * 
 * @param	array	$enr		Enregistrement de la BD avec les infos � traiter
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
	// on pourrait aussi garder les tags HTML �ventuels en mettant les zones dans
	// une section CDATA, mais pas s�r qu'elle soit prise en compte par les lecteurs.
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
 * Protection d'une chaine pour le fichier RSS : pas de tags ni d'entit�s HTML
 * 
 * @param	text	$texte		Texte � prot�ger
 */
function fpl_protectRSS($texte) {
	$texte = strip_tags($texte);
	$texte = html_entity_decode($texte);
	// Entit� sp�cifique FCKEditor
	$texte = str_replace('&rsquo;', "'", $texte);
	return $texte; 
}
?>