<?php
//_____________________________________________________________________________
/**
 * Ajouter un commentaire à un article
 * 
 * Cette page est appelée 2 fois pour faire le traitement :
 * - le premier passage permet la saisie du commentaire
 * - le deuxième passage correspond à la soumission du formulaire de saisie,
 *   à la vérification de la saisie et à la mise à jour de la base de données.
 * 
 * Le deuxième passage est défini par l'existence de l'index bntValider dans le
 * tableau $_POST. 
 * 
 * @param	integer	$_GET['x']	Clé de l'article à commenter - cryptée
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

$coAuteur = $coTexte = '';
$erreurs = array();		// Tableau des messages d'erreur des zones invalides

//_____________________________________________________________________________
//
// Traitement soumission du formulaire de saisie
//
// Bien que ce ce traitement soit la seconde chose à faire, le code
// doit être avant celui qui traite la saisie pour qu'en cas d'erreur
// les éléments saisis puissent être réaffichés.
//_____________________________________________________________________________
if (isset($_POST['btnValider'])) {
	// On vérifie si les zones saisies sont valides. Si oui on fait la mise
	// à jour de la base de données puis on ferme la fenêtre avec JavaScript.
	$erreurs = fpl_verifZones();
	if (count($erreurs) == 0) {
		fpl_majBase($IDArticle);	// Mise à jour BD
		// Fermeture fenêtre.
		// La fermeture est un peu spéciale : on force la page appelante
		// à se recharger avec opener.location.reload()
		// L'événement onunload de la page appelante est déclecnché
		// et les fenêtres popup encore ouvertes sont automatiquement
		// fermées (donc cette fenêtre). En procédant ainsi on recharge
		// la page des articles avec le nombre de commentaires mis à jour.
		// Si on ne veut pas de ce rechargement de la page appelante, il
		// suffit de remplacer opener.location.reload() par self.close();
		// Un autre solution consisterait à faire la mise à jour du nombre
		// de commentaires avec JavaScript. Plus complexe à gérer. Si vous
		// êtes interressé par la technique demandez moi.
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
	// On va réafficher toutes les zones du formulaire et les messages d'erreur.
	// On commence par enlever éventuellement les protections automatiques
	// de caractères faite par PHP, puis on extrait les variables de $_POST
	fp_stripPOST();
	$coAuteur = $_POST['coAuteur'];
	$coTexte = $_POST['coTexte'];
}
//_____________________________________________________________________________
//
// Traitement saisie du formulaire
//_____________________________________________________________________________
//	Affichage du haut de la page
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Commentaires';
$remplace['@_TITRE_@'] = 'Saisie d\'un commentaire';
$remplace['@_SOUS_TITRE_@'] = $enr['arTitre'];

// Lecture du modele debut_pop.html, remplacement motifs et affichage
fp_modeleTraite('debut_pop', $remplace);

// Affichage des erreurs de saisie précédentes
if (count($erreurs) > 0) {
	fp_htmlErreurs($erreurs);
}

//	Affichage du formulaire de saisie :
//  Nom de l'auteur
//  Texte du commentaire

// Les paramètres du lien sont cryptés (IDArticle)
$url = fp_makeURL('comment_ajouter.php', $IDArticle);

echo '<form method="post" action="', $url, '">',
		'<table>';

fp_htmlSaisie('T', 'coAuteur', $coAuteur, 'Pseudo', 60, 60);
fp_htmlSaisie('A', 'coTexte', $coTexte, 'Commentaire', 60, 6);

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
 * Vérification de la validité des zones de saisie
 * 
 * @global	array	$_POST	ZOnes de saisie du formulaire
 * 
 * @return	array	Tableau associatif avec les messages d'erreurs (index = nom de zone)
 */
function fpl_verifZones() {
	$erreurs = array();
	
	if (!preg_match('/^\S./', $_POST['coAuteur'])) {
		$erreurs['coAuteur'] = 'La zone Pseudo ne doit pas commencer par un espace ou être vide.';
	}
	if (!preg_match('/^\S./', $_POST['coTexte'])) {
		$erreurs['coTexte'] = 'La zone Commentaire ne doit pas commencer par un espace ou être vide.';
	}
	
	return $erreurs;
}
//_____________________________________________________________________________
/**
 * Mise à jour de la base de données
 * 
 * @param	integer	$IDArticle	Clé de l'article à traiter
 * @global	array	$_POST		Les zones de saisie du formulaire
 */
function fpl_majBase($IDArticle) {
	$sql = "INSERT INTO commentaires SET
			coIDArticle = $IDArticle, 
			coAuteur = '".fp_protectSQL($_POST['coAuteur'])."',
			coTexte = '".fp_protectSQL($_POST['coTexte'])."',
			coDate = ".date('Ymd').",
			coHeure = '".date('H:i')."'";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);
}
?>