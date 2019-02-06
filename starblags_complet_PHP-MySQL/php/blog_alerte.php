<?php
//_____________________________________________________________________________
/**
 * Saisie d'une adresse e-mail pour prévenir quand nouvel article dans un blog
 * 
 * Cette page est appelée 2 fois pour faire le traitement :
 * - le premier passage permet la saisie de l'adresse email
 * - le deuxième passage correspond à la soumission du formulaire de saisie,
 *   à la vérification de la saisie, à la mise à jour de la base de données et
 * 	 à l'envoi pour validation d'un mail à l'adresse saisie.
 * 
 * Le deuxième passage est défini par l'existence de l'index bntValider dans le
 * tableau $_POST. 
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

$alMail = '';
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
	// à jour de la base de données puis on affiche un message.
	$erreurs = fpl_verifZones();
	if (count($erreurs) == 0) {
		fpl_majBase($IDBlog);	// Mise à jour BD
		// Affichage d'un message invitant l'utilisateur
		// à valider le mail qui lui a été envoyé.
		$remplace = array();
		$remplace['@_TITLE_@'] = 'StarBlags - Alerte Blog';
		$remplace['@_TITRE_@'] = 'M\'alerter des nouveaux articles';
		$remplace['@_SOUS_TITRE_@'] = $enr['blTitre'];
		
		// Lecture du modele debut_pop.html, remplacement motifs et affichage
		fp_modeleTraite('debut_pop', $remplace);

		echo '<form>',
				'<p>',
					'Un mail de confirmation a été envoyé à l\'adresse que vous avez donnée.',
				'</p>',
				'<p>',
					'Votre demande ne sera pas activée tant que vous n\'aurez pas répondu à ce mail.',
				'</p>';
			
		fp_htmlBoutons(-1, 'B|btnFermer|Fermer|self.close();opener.focus()');

		//	Fin de page
		echo '</form>',
			'</div>',
			'</body></html>';
		exit();	// Fin du script
	}
		
	// Si on passe ici c'est que des zones de saisie ne sont pas valides.
	// On va réafficher toutes les zones du formulaire et les messages d'erreur.
	// On commence par enlever éventuellement les protections automatiques
	// de caractères faite par PHP, puis on extrait les variables de $_POST
	fp_stripPOST();
	$alMail = $_POST['alMail'];
}
//_____________________________________________________________________________
//
// Traitement saisie du formulaire
//_____________________________________________________________________________
//	Affichage du haut de la page
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Alerte Blog';
$remplace['@_TITRE_@'] = 'M\'alerter des nouveaux articles';
$remplace['@_SOUS_TITRE_@'] = $enr['blTitre'];

// Lecture du modele debut_pop.html, remplacement motifs et affichage
fp_modeleTraite('debut_pop', $remplace);

// Affichage des erreurs de saisie précédentes
if (count($erreurs) > 0) {
	fp_htmlErreurs($erreurs);
}

//	Affichage du formulaire de saisie :
//  Nom de l'auteur
//  Texte du commentaire
$url = fp_makeURL('blog_alerte.php', $IDBlog);
echo '<form method="post" action="', $url, '">',
		'<table>';

fp_htmlSaisie('T', 'alMail', $alMail, 'E-mail', 60, 100);

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
	
	if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_\.\-]*@[a-zA-Z][a-zA-Z0-9_\.\-]*\.[a-zA-Z]{2,6}$/', $_POST['alMail'])) {
		$erreurs['alMail'] = 'La zone E-mail doit être une adresse e-mail valide.';
	}
	
	return $erreurs;
}
//_____________________________________________________________________________
/**
 * Mise à jour de la base de données - Envoi du mail pour confirmation
 * 
 * @param	integer	$IDBlog		Clé du blog où on pose une laerte
 * @global	array	$_POST		Les zones de saisie du formulaire
 */
function fpl_majBase($IDBlog) {
	$alMail = fp_protectSQL($_POST['alMail']);
	
	// On vérifie que l'utilisateur n'a pas déjà une alerte
	$sql = "SELECT count(*) 
			FROM alertes_lecteurs
			WHERE alMail = '$alMail'
			AND alIDBlog = $IDBlog";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);
	$enr = mysqli_fetch_array($R);
	if ($enr[0] > 0) {
		return;  // Déjà une alerte
	}
	
	// Création d'un enregistrement dans la table alertes_lecteurs			
	$sql = "INSERT INTO alertes_lecteurs SET
			alMail = '$alMail', 
			alIDBlog = $IDBlog, 
			alValide = 0";
			
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);
	$url = fp_makeURL('retour_mail.php', mysqli_insert_id());	
	// On envoie un mail à l'adresse saisie pour que l'utilisateur
	// valide son inscription en prouvant la validité de l'adresse mail
	$texte = 'Bonjour<br><br>Merci de confirmer votre inscription aux alertes StarBlagS<br>'
			.'<a href="'.ADRESSE_SITE.'php_final/retour_mail.php?id='.$url.'">'
			.'en cliquant ici</a><br><br>StarBlagS';
			
	fp_mail($alMail, 'Alerte StarBlagS', $texte);
}
?>