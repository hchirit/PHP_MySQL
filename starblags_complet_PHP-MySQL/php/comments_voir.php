<?php
//_____________________________________________________________________________
/**
 * Affichage des commentaires d'un article
 * 
 * Cette page reçoit en paramètre dans l'url la clé d'un article (cryptée).
 * 
 * @param	integer	$_GET['x']	Clé de l'article dont on veut les commentaires
 */
//_____________________________________________________________________________
ob_start();		// Bufférisation des sorties

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

$IDArticle = (int) fp_getURL();	// Récupération des paramètres URL 

fp_bdConnecter();	// Ouverture base de données

// Récupération du titre de l'article
$sql = "SELECT arTitre
		FROM articles
		WHERE arID = $IDArticle";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
$enr = mysqli_fetch_assoc($R);	// Récupération de la sélection
mysqli_free_result($R);
	
if ($enr === FALSE) {  // L'article n'existe pas : fin du script
	exit();
}

//_____________________________________________________________________________
//
//	Affichage du haut de la page
//_____________________________________________________________________________
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Commentaires';
$remplace['@_TITRE_@'] = 'Commentaires';
$remplace['@_SOUS_TITRE_@'] = $enr['arTitre'];

// Lecture du modele debut_pop.html, remplacement motifs et affichage
fp_modeleTraite('debut_pop', $remplace);

//_____________________________________________________________________________
//
//	Traitement de l'affichage des commentaires
//_____________________________________________________________________________
// Requête de sélection
$sql = "SELECT *
		FROM commentaires
		WHERE coIDArticle = $IDArticle
		ORDER BY coDate, coHeure";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête

// Affichage de la sélection	
while ($enr = mysqli_fetch_assoc($R)) {  // Boucle de lecture de la sélection
	// On utilise :
	// - un entete H4
	// - un bloc avec le texte du commentaire.
	echo '<h4>',
	        fp_protectHTML($enr['coAuteur']), ' - ',
	        fp_amjJma($enr['coDate']), ' - ',
	        $enr['coHeure'],
        '</h4>',
        '<div class="commentTexte">',
			fp_protectHTML($enr['coTexte'], TRUE),
		'</div>';
		
}
mysqli_free_result($R);
	
//_____________________________________________________________________________
//
//	Fin de page
//_____________________________________________________________________________
// On fait un formulaire pour avoir un bouton
echo '<form name="form1" method="post" action="">';

fp_htmlBoutons(-1, 'B|btnFermer|Fermer|self.close();opener.focus()');

echo '</form>',
	'</div>',	// fin du bloc blcPopPage
	'</body></html>';

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>