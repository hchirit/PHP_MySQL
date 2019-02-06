<?php
//_____________________________________________________________________________
/**
 * Page d'explication sur l'inscription au flux RSS d'un blog
 * 
 * 
 * @param	integer	$_GET['x']	Clé du blog sur lequel on met une alerte - crypté
 */
//_____________________________________________________________________________
ob_start();		// Bufférisation des sorties

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

$IDBlog = (int) fp_getURL();	// Récupèration paramètre URL

fp_bdConnecter();	// Ouverture base de données

// Récupération du titre du blog
$sql = "SELECT blTitre
		FROM blogs
		WHERE blID = $IDBlog";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
$enr = mysqli_fetch_assoc($R);	// Récupération de la sélection
if ($enr === FALSE) {  // Le blog n'existe pas : fin du script
	exit();
}
//_____________________________________________________________________________
//
// Affichage de la page
//_____________________________________________________________________________
//	Affichage du haut de la page
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Blog : flux RSS';
$remplace['@_TITRE_@'] = 'M\'abonner au flux RSS';
$remplace['@_SOUS_TITRE_@'] = $enr['blTitre'];

// Lecture du modele debut_pop.html, remplacement motifs et affichage
fp_modeleTraite('debut_pop', $remplace);

$url = ADRESSE_RSS.'rss_'.$IDBlog.'.xml';

//	Affichage du texte 
echo '<form method="post" action="">',
		'<p>',
			'Les flux RSS (Really Simple Syndication) sont des 
			fils d\'informations en provenance de sites Internet. 
			Plutôt que de naviguer sur plusieurs sites pour	en suivre 
			l\'actualité, les flux RSS vous préviennent dès qu\'un 
			nouveau contenu	est mis en ligne sur l\'un de ces sites.',
		'</p>',
		'<p>',
			'Pour récupérer automatiquement et en temps réel les 
			derniers flux RSS, vous	devez être équipé d\'un logiciel 
			pour la lecture de ces flux, ou d\'un navigateur comme 
			Firefox, Opera 8 ou IE 7.<br>
			<a href="http://directory.google.com/Top/Reference/Libraries/Library_and_Information_Science/Technical_Services/Cataloguing/Metadata/RDF/Applications/RSS/News_Readers/" 
			target="_blank">Une liste de lecteurs sur Google</a>',
		'</p>',
		'<p>',
			'Pour vous abonner au flux RSS de ce blog, copier 
			l\'adresse du flux RSS dans votre lecteur.',
		'</p>',
					
		'<div style="background-color: #fff; border: 1px solid #B90F0F; padding: 3px; margin: 10px 0px">',
			$url,
		'</div>',

		'<p align="center">',
			'Vous pouvez aussi ajouter ce flux à <br>&nbsp;<br>',
			'<a href="http://add.my.yahoo.com/content?lg=fr&url=',$url,'" target="_blank">',
				'<img src="../images/rss_addyahoo.gif" width="91" height="17" border="0" align="top">',
			'</a>&nbsp;',
			'<a href="http://fr.my.msn.com/addtomymsn.armx?id=rss&ut',$url,'&ru=',ADRESSE_SITE,'" target="_blank">',
				'<img src="../images/rss_addmsn.gif" width="91" height="17" border="0" align="top">',
			'</a>&nbsp;',
			'<a target="_blank" href="http://www.newsgator.com/ngs/subscriber/subext.aspx?url=',$url,'">',
				'<img src="../images/rss_addgator.gif" width="91" height="17" border="0" align="top">',
			'</a>&nbsp;',
			'<a target="_blank" href="http://www.netvibes.com/subscribe.php?url=',$url,'">',
				'<img src="../images/rss_addnetvibes.gif" width="91" height="17" border="0" align="top">',
			'</a>&nbsp;',
			'<a href="http://fusion.google.com/add?feedurl=',$url,'" target="_blank">',
				'<img src="../images/rss_addgoogle.gif" width="104" height="17" border="0" align="top">',
			'</a>',
		'</p>';

fp_htmlBoutons(-1, 'B|btnFermer|Fermer|self.close();opener.focus()');

echo '</form>',
	'</div>',
	'</body></html>';

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>