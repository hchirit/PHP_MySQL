<?php
//_____________________________________________________________________________
//
//	BIBLIOTHEQUE DE FONCTIONS POUR LA GESTION DES FICHIERS RSS
//_____________________________________________________________________________
//
// Il faut définir 4 variables globales pour que les fonctions utilisées
// par le parser XML PHP puissent travailler correctement.
$_TraiteElement = FALSE;	// Booléen indiquant si on entre dans le traitement d'un élément item
$_Items = array();		// Tableau avec le contenu des éléments item parsés
$_Item = '';			// Contenu d'un élément item parsé

/**
 * Mise à jour d'un fichier RSS suite à une création d'article
 * 
 * @param	integer	$IDArticle	Clé de l'article à traiter
 * @global	array	$_Items		Tableau du contenu de tous les éléments parsés
 */
function fp_rss_maj($IDArticle) {
	global $_Items;
	
	// Sélection des informations pour la création du flux RSS
	$sql = "SELECT articles.*, blTitre, blResume
			FROM blogs, articles
			WHERE blID = arIDBlog
			AND arID = $IDArticle";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	$enr = mysqli_fetch_assoc($R);
	
	$newItem = fp_rss_makeItem($enr);	// Création de l'item correspondant à l'article
	
	// Si il existe déjà un fichier RSS pour le blog, on récupère les items déjà 
	// contenus dans le flux et on ajoute le nouvel item en début de liste.
	// On ne met que 5 items dans un flux.
	// Le fichier s'appelle rss_[idblog].xml et se trouve dans le répertoire rss.
	$fichierRSS = '../rss/rss_'.$enr['arIDBlog'].'.xml';
	if (file_exists($fichierRSS)) {
		fp_rss_getItems($fichierRSS);	// le contenu des items est mis dans $_Items

		while (count($_Items) > 4) {
			array_pop($_Items);
		}
	}
	
	// On crée un nouveau fichier RSS. Si il en existait déjà un
	// il est ecrasé.
	$url = fp_makeURL(ADRESSE_PAGE.'articles_voir.php', $enr['arIDBlog'],0);
	
	$flux = '<?xml version="1.0" encoding="iso-8859-15" ?>
			<rss version="2.0">
				<channel>
					<title>StarBlagS : '.$enr['blTitre'].'</title>
					<link>'.$url.'</link>
					<description>Les derniers articles du blog</description>
					<language>fr</language>
					<lastBuildDate>'.date('r').'</lastBuildDate>
					<ttl>1500</ttl>'
					.$newItem
					.implode('', $_Items)
				.'</channel>
			</rss>';

	// Ecriture du fichier avec le flux RSS.
	$fichier = @fopen($fichierRSS, 'w');	// ouverture du fichier
	if ($fichier === FALSE) {
		// Erreur d'ouvertue : on arrête le script
		ob_end_clean();
		exit('Erreur création RSS - '.__LINE__);
	}
	
	if (@fwrite($fichier, $flux) === FALSE) {	// Ecriture du fichier
		// Erreur d'écriture : on arrête le script
		ob_end_clean();
		exit('Erreur création RSS - '.__LINE__);
	}
	
	@fclose($fichier);	// Fermeture du fichier
	

	// On met à jour l'article traité pour ne pas les retrouver
	// dans le prochain flux RSS
	$sql = "UPDATE articles 
			SET arRSS = 1
			WHERE arID = $IDArticle";
	
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête		
}
//_____________________________________________________________________________
/**
 * Création d'un item d'un flux RSS
 * 
 * @param	array	$enr		Enregistrement de la BD avec les infos à traiter
 */
function fp_rss_makeItem($enr) {
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
	$titre = fp_rss_protect($enr['arTitre']);
	$texte = fp_rss_protect($enr['arTexte']);
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
function fp_rss_protect($texte) {
	$texte = strip_tags($texte);
	$texte = html_entity_decode($texte);
	// Entité spécifique FCKEditor
	$texte = str_replace('&rsquo;', "'", $texte);
	return $texte; 
}
//_____________________________________________________________________________
//
// 		FONCTIONS POUR LA GESTION DU PARSER XML de PHP
//_____________________________________________________________________________
/**
 * Extraction des éléments item d'un fichier RSS.
 * 
 * Les éléments item sont extraits et leur contenu renvoyé sous la forme
 * d'un tableau. Le contenu lui même de l'item n'est pas parsé.
 * On utilise les fonctions PHP permettant de parser les fichiers XML pour
 * extraire les éléments item.
 * A la fin de ce traitement le contenu des élements item du fichier RSS
 * se trouvent dans le tableau global $_Items.
 * 
 * @param	$string	$fichierRSS		Nom complet du fichier RSS à traiter
 */
function fp_rss_getItems($fichierRSS) {
	// Ouverture du fichier RSS
	$fichier = @fopen($fichierRSS, 'r');	// Ouverture du fichier
	if ($fichier === FALSE) return;			// Erreur ouverture => fin
	
	// Création d'un parser XML
	$parser = xml_parser_create();
	// Définition des fonctions gérant l'ouvreture et la fermeture des tags
	xml_set_element_handler($parser, 'fp_rss_startElement', 'fp_rss_endElement');
	// Définition de la fonction gérant les données
	xml_set_character_data_handler($parser, 'fp_rss_characterData');
	
	// Lecture du fichier RSS, par paquet de 4KB
	while ($buffer = fread($fichier, 4096)) {
		// On parse chacun des paquets
		xml_parse($parser, $buffer, feof($fichier)) or exit('Erreur XML'); 
	}
	
	// Fermeture du fichier RSS
	fclose($fichier);
	
	// Libère les ressources mémoire du parser
	xml_parser_free($parser);
}
//_____________________________________________________________________________
/**
 * Gestion de l'ouverture d'un élément XML par le parser
 * 
 * Cette fonction est une fonction callback pour le parser XML PHP. Elle est
 * déclenchée quand le parser rencontre l'ouverture d'un élément XML du fichier.
 * Dans notre cas particulier on ne s'interresse qu'à l'élément item.
 * 
 * @param	object	$parser			Référence au parseur XML
 * @param	string	$element		Nom de l'élement ouvert (automatiquement en majuscule)
 * @param	array	$attributs		Attributs éventuels du tag (nomAtt=>valeur)
 * @global	boolean	$_TraiteElement	Indique si il faut traiter l'élément ouvert
 */
function fp_rss_startElement($parser, $element, $attributs) {
	global $_TraiteElement;
	// Si le tag traité est item on positionne l'indicateur
	if ($element == 'ITEM') {
		$_TraiteElement = TRUE;
	}
}
//_____________________________________________________________________________
/**
 * Gestion des données parsées
 * 
 * Cette fonction est une fonction callback pour le parser XML PHP. Elle est
 * déclenchée quand le parser rencontre des données à traiter (ie après
 * l'ouverture d'un élément XML du fichier).
 * Dans notre cas particulier on ne s'interresse qu'à l'élément item.
 * Le contenu de l'élément item en cours de parsing est stocké dans la variable
 * globale $_Item. Le contenu d'un élément item n'est pas parsé.
 * 
 * @param	object	$parser			Référence au parseur XML
 * @param	string	$contenu		Contenu à traiter renvoyé par le parser
 * @global	boolean	$_TraiteElement	Indique si il faut traiter l'élément ouvert
 * @global	string	$_Item			Contenu de l'élément parsé
 */
function fp_rss_characterData($parser, $contenu) {
	global $_TraiteElement, $_Item;
	if ($_TraiteElement) {
		$_Item .= $contenu;
	}
}
//_____________________________________________________________________________
/**
 * Gestion de la fermeture d'un élément XML par le parser
 * 
 * Cette fonction est une fonction callback pour le parser XML PHP. Elle est
 * déclenchée quand le parser rencontre la fermeture d'un élément XML du fichier.
 * Dans notre cas particulier on ne s'interresse qu'à l'élément item.
 * 
 * @param	object	$parser			Référence au parseur XML
 * @param	string	$element		Nom de l'élement ouvert (automatiquement en majuscule)
 * @global	boolean	$_TraiteElement	Indique si il faut traiter l'élément ouvert
 * @global	string	$_Item			Contenu du dernier élément item parsé
 * @global	array	$_Items			Tableau du contenu de tous les éléments parsés
 */
function fp_rss_endElement($parser, $element) {
	global $_TraiteElement, $_Item, $_Items;
	if ($element == 'ITEM') {
		$_Items[] = '<item>'.$_Item.'</item>';
		$_Item = '';
		$_TraiteElement = FALSE;
	}
}
?>