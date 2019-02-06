<?php
//_____________________________________________________________________________
/**
 * Donner une note � un article
 * 
 * Cette page est appel�e 2 fois pour faire le traitement :
 * - le premier passage permet la saisie de la note
 * - le deuxi�me passage correspond � la soumission du formulaire de saisie,
 *   � la v�rification de la saisie et � la mise � jour de la base de donn�es.
 * 
 * Le deuxi�me passage est d�fini par l'existence de l'index bntValider dans le
 * tableau $_POST. 
 * 
 * @param	integer	$_GET['x']	Cl� de l'article � commenter - crypt�
 */
//_____________________________________________________________________________
ob_start();		// Buff�risation des sorties

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

$IDArticle = (int) fp_getURL();	// R�cup�ration des param�tres URL 

fp_bdConnecter();	// Ouverture base de donn�es

// R�cup�ration du titre de l'article
$sql = "SELECT arTitre
		FROM articles
		WHERE arID = $IDArticle";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
$enr = mysqli_fetch_assoc($R) or exit();	// R�cup�ration de la s�lection
mysqli_free_result($R);

$anNom = '';
$anNote = 0;
$erreurs = array();		// Tableau des messages d'erreur des zones invalides

//_____________________________________________________________________________
//
// Traitement soumission du formulaire de saisie
//
// Bien que ce ce traitement soit la seconde chose � faire, le code
// doit �tre avant celui qui traite la saisie pour qu'en cas d'erreur
// les �l�ments saisis puissent �tre r�affich�s.
//_____________________________________________________________________________
if (isset($_POST['btnValider'])) {
	// On v�rifie si les zones saisies sont valides. Si oui on fait la mise
	// � jour de la base de donn�es puis on ferme la fen�tre avec JavaScript.
	$erreurs = fpl_verifZones();
	if (count($erreurs) == 0) {
		fpl_majBase($IDArticle);	// Mise � jour BD
		// Fermeture fen�tre.
		// La fermeture est un peu sp�ciale : on force la page appelante
		// � se recharger avec opener.location.reload()
		// L'�v�nement onunload de la page appelante est d�clecnch�
		// et les fen�tres popup encore ouvertes sont automatiquement
		// ferm�es (donc cette fen�tre). En proc�dant ainsi on recharge
		// la page des articles avec le nombre de commentaires mis � jour.
		// Si on ne veut pas de ce rechargement de la page appelante, il
		// suffit de remplacer opener.location.reload() par self.close();
		// Un autre solution consisterait � faire la mise � jour du nombre
		// de commentaires avec JavaScript. Plus complexe � g�rer. Si vous
		// �tes interress� par la technique demandez moi.
		echo '<html>',
				'<head>',
					'<title>x</title>',
				'</head>',
				'<body>',
					'<script type="text/javascript">',
					'opener.location.reload();',
					'self.close();',
					'</script>',
				'</body>',
			'</html>';
		exit();	// Fin du script
	}
		
	// Si on passe ici c'est que des zones de saisie ne sont pas valides.
	// On va r�afficher toutes les zones du formulaire et les messages d'erreur.
	// On commence par enlever �ventuellement les protections automatiques
	// de caract�res faite par PHP, puis on extrait les variables de $_POST
	fp_stripPOST();
	$anNom = $_POST['anNom'];
	$anNote = $_POST['anNote'];
}
//_____________________________________________________________________________
//
// Traitement saisie du formulaire
//_____________________________________________________________________________
//	Affichage du haut de la page
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Article';
$remplace['@_TITRE_@'] = 'Noter un article';
$remplace['@_SOUS_TITRE_@'] = $enr['arTitre'];

// Lecture du modele debut_pop.html, remplacement motifs et affichage
fp_modeleTraite('debut_pop', $remplace);

// Affichage des erreurs de saisie pr�c�dentes
if (count($erreurs) > 0) {
	fp_htmlErreurs($erreurs);
}

//	Affichage du formulaire de saisie :
//  Nom de l'auteur
//  Texte du commentaire

// Les param�tres du lien sont crypt�s (IDArticle)
$url = fp_makeURL('article_noter.php', $IDArticle);
	
echo '<form method="post" action="', $url, '">',
		'<table>';

fp_htmlSaisie('T', 'anNom', $anNom, 'Pseudo', 60, 60);
fp_htmlSaisie('S', 'anNote', $anNote, 'Note', 1, range(0, 10));

fp_htmlBoutons(2, 'B|btnFermer|Fermer|self.close();opener.focus()', 'S|btnValider|Valider');

//	Fin de page
echo 	'</table>',
	'</form>',
	'</div>',
	'</body></html>';

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur

//_____________________________________________________________________________
//
// 							FONCTIONS
//_____________________________________________________________________________
/**
 * V�rification de la validit� des zones de saisie
 * 
 * @global	array	$_POST	ZOnes de saisie du formulaire
 * 
 * @return	array	Tableau associatif avec les messages d'erreurs (index = nom de zone)
 */
function fpl_verifZones() {
	$erreurs = array();
	
	if (!preg_match('/^\S./', $_POST['anNom'])) {
		$erreurs['anNom'] = 'La zone Pseudo ne doit pas commencer par un espace ou �tre vide.';
	}
	
	$_POST['anNote'] = (int) $_POST['anNote'];
	if ($_POST['anNote'] < 0 || $_POST['anNote'] > 10) {
		exit();	//-->> piratage ??
	}
	return $erreurs;
}
//_____________________________________________________________________________
/**
 * Mise � jour de la base de donn�es
 * 
 * @param	integer	$IDArticle	Cl� de l'article � traiter
 * @global	array	$_POST		Les zones de saisie du formulaire
 */
function fpl_majBase($IDArticle) {
	$sql = "INSERT INTO articles_notes SET
			anIDArticle = $IDArticle,
			anDate = ".date('Ymd').",
			anHeure = '".date('H:i')."',
			anIP = '".fp_getIP()."', 
			anNom = '".fp_protectSQL($_POST['anNom'])."', 
			anNote = {$_POST['anNote']}";
  
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);
}
?>