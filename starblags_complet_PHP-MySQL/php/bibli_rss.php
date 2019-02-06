<?php
//_____________________________________________________________________________
//
//	BIBLIOTHEQUE DE FONCTIONS POUR LA GESTION DES FICHIERS RSS
//_____________________________________________________________________________
//
// Il faut d�finir 4 variables globales pour que les fonctions utilis�es
// par le parser XML PHP puissent travailler correctement.
$_TraiteElement = FALSE;	// Bool�en indiquant si on entre dans le traitement d'un �l�ment item
$_Items = array();		// Tableau avec le contenu des �l�ments item pars�s
$_Item = '';			// Contenu d'un �l�ment item pars�

/**
 * Mise � jour d'un fichier RSS suite � une cr�ation d'article
 * 
 * @param	integer	$IDArticle	Cl� de l'article � traiter
 * @global	array	$_Items		Tableau du contenu de tous les �l�ments pars�s
 */
function fp_rss_maj($IDArticle) {
	global $_Items;
	
	// S�lection des informations pour la cr�ation du flux RSS
	$sql = "SELECT articles.*, blTitre, blResume
			FROM blogs, articles
			WHERE blID = arIDBlog
			AND arID = $IDArticle";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	$enr = mysqli_fetch_assoc($R);
	
	$newItem = fp_rss_makeItem($enr);	// Cr�ation de l'item correspondant � l'article
	
	// Si il existe d�j� un fichier RSS pour le blog, on r�cup�re les items d�j� 
	// contenus dans le flux et on ajoute le nouvel item en d�but de liste.
	// On ne met que 5 items dans un flux.
	// Le fichier s'appelle rss_[idblog].xml et se trouve dans le r�pertoire rss.
	$fichierRSS = '../rss/rss_'.$enr['arIDBlog'].'.xml';
	if (file_exists($fichierRSS)) {
		fp_rss_getItems($fichierRSS);	// le contenu des items est mis dans $_Items

		while (count($_Items) > 4) {
			array_pop($_Items);
		}
	}
	
	// On cr�e un nouveau fichier RSS. Si il en existait d�j� un
	// il est ecras�.
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
		// Erreur d'ouvertue : on arr�te le script
		ob_end_clean();
		exit('Erreur cr�ation RSS - '.__LINE__);
	}
	
	if (@fwrite($fichier, $flux) === FALSE) {	// Ecriture du fichier
		// Erreur d'�criture : on arr�te le script
		ob_end_clean();
		exit('Erreur cr�ation RSS - '.__LINE__);
	}
	
	@fclose($fichier);	// Fermeture du fichier
	

	// On met � jour l'article trait� pour ne pas les retrouver
	// dans le prochain flux RSS
	$sql = "UPDATE articles 
			SET arRSS = 1
			WHERE arID = $IDArticle";
	
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te		
}
//_____________________________________________________________________________
/**
 * Cr�ation d'un item d'un flux RSS
 * 
 * @param	array	$enr		Enregistrement de la BD avec les infos � traiter
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
	// on pourrait aussi garder les tags HTML �ventuels en mettant les zones dans
	// une section CDATA, mais pas s�r qu'elle soit prise en compte par les lecteurs.
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
 * Protection d'une chaine pour le fichier RSS : pas de tags ni d'entit�s HTML
 * 
 * @param	text	$texte		Texte � prot�ger
 */
function fp_rss_protect($texte) {
	$texte = strip_tags($texte);
	$texte = html_entity_decode($texte);
	// Entit� sp�cifique FCKEditor
	$texte = str_replace('&rsquo;', "'", $texte);
	return $texte; 
}
//_____________________________________________________________________________
//
// 		FONCTIONS POUR LA GESTION DU PARSER XML de PHP
//_____________________________________________________________________________
/**
 * Extraction des �l�ments item d'un fichier RSS.
 * 
 * Les �l�ments item sont extraits et leur contenu renvoy� sous la forme
 * d'un tableau. Le contenu lui m�me de l'item n'est pas pars�.
 * On utilise les fonctions PHP permettant de parser les fichiers XML pour
 * extraire les �l�ments item.
 * A la fin de ce traitement le contenu des �lements item du fichier RSS
 * se trouvent dans le tableau global $_Items.
 * 
 * @param	$string	$fichierRSS		Nom complet du fichier RSS � traiter
 */
function fp_rss_getItems($fichierRSS) {
	// Ouverture du fichier RSS
	$fichier = @fopen($fichierRSS, 'r');	// Ouverture du fichier
	if ($fichier === FALSE) return;			// Erreur ouverture => fin
	
	// Cr�ation d'un parser XML
	$parser = xml_parser_create();
	// D�finition des fonctions g�rant l'ouvreture et la fermeture des tags
	xml_set_element_handler($parser, 'fp_rss_startElement', 'fp_rss_endElement');
	// D�finition de la fonction g�rant les donn�es
	xml_set_character_data_handler($parser, 'fp_rss_characterData');
	
	// Lecture du fichier RSS, par paquet de 4KB
	while ($buffer = fread($fichier, 4096)) {
		// On parse chacun des paquets
		xml_parse($parser, $buffer, feof($fichier)) or exit('Erreur XML'); 
	}
	
	// Fermeture du fichier RSS
	fclose($fichier);
	
	// Lib�re les ressources m�moire du parser
	xml_parser_free($parser);
}
//_____________________________________________________________________________
/**
 * Gestion de l'ouverture d'un �l�ment XML par le parser
 * 
 * Cette fonction est une fonction callback pour le parser XML PHP. Elle est
 * d�clench�e quand le parser rencontre l'ouverture d'un �l�ment XML du fichier.
 * Dans notre cas particulier on ne s'interresse qu'� l'�l�ment item.
 * 
 * @param	object	$parser			R�f�rence au parseur XML
 * @param	string	$element		Nom de l'�lement ouvert (automatiquement en majuscule)
 * @param	array	$attributs		Attributs �ventuels du tag (nomAtt=>valeur)
 * @global	boolean	$_TraiteElement	Indique si il faut traiter l'�l�ment ouvert
 */
function fp_rss_startElement($parser, $element, $attributs) {
	global $_TraiteElement;
	// Si le tag trait� est item on positionne l'indicateur
	if ($element == 'ITEM') {
		$_TraiteElement = TRUE;
	}
}
//_____________________________________________________________________________
/**
 * Gestion des donn�es pars�es
 * 
 * Cette fonction est une fonction callback pour le parser XML PHP. Elle est
 * d�clench�e quand le parser rencontre des donn�es � traiter (ie apr�s
 * l'ouverture d'un �l�ment XML du fichier).
 * Dans notre cas particulier on ne s'interresse qu'� l'�l�ment item.
 * Le contenu de l'�l�ment item en cours de parsing est stock� dans la variable
 * globale $_Item. Le contenu d'un �l�ment item n'est pas pars�.
 * 
 * @param	object	$parser			R�f�rence au parseur XML
 * @param	string	$contenu		Contenu � traiter renvoy� par le parser
 * @global	boolean	$_TraiteElement	Indique si il faut traiter l'�l�ment ouvert
 * @global	string	$_Item			Contenu de l'�l�ment pars�
 */
function fp_rss_characterData($parser, $contenu) {
	global $_TraiteElement, $_Item;
	if ($_TraiteElement) {
		$_Item .= $contenu;
	}
}
//_____________________________________________________________________________
/**
 * Gestion de la fermeture d'un �l�ment XML par le parser
 * 
 * Cette fonction est une fonction callback pour le parser XML PHP. Elle est
 * d�clench�e quand le parser rencontre la fermeture d'un �l�ment XML du fichier.
 * Dans notre cas particulier on ne s'interresse qu'� l'�l�ment item.
 * 
 * @param	object	$parser			R�f�rence au parseur XML
 * @param	string	$element		Nom de l'�lement ouvert (automatiquement en majuscule)
 * @global	boolean	$_TraiteElement	Indique si il faut traiter l'�l�ment ouvert
 * @global	string	$_Item			Contenu du dernier �l�ment item pars�
 * @global	array	$_Items			Tableau du contenu de tous les �l�ments pars�s
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