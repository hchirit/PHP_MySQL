<?php
//_____________________________________________________________________________
//
//	BIBLIOTHEQUE DE FONCTIONS
//_____________________________________________________________________________
//
//				DEFINITION DE CONSTANTES
//
// Utiliser des constantes est particuli�rement interressant avec PHP.
// En plus de fournir un moyen mn�motechnique pour stocker des donn�es,
// les constantes sont "super-globales" : elles peuvent �tre utilis�es
// dans les fonctions sans d�claration.
//_____________________________________________________________________________
define ('BD_SERVEUR', 'localhost');		// Adresse de la base de donn�es
define ('BD_NOM', 'starblags');			// Nom de la base de donn�es
define ('BD_USER', 'starblags_user');	// Nom utilisateur base de donn�es
define ('BD_PASS', 'starblags_passe');	// Mot de pase base de donn�es

// On d�finit la m�thode de protection des zones pass�es � la base
// dans les r�qu�tes. On le fait ici une fois pour toutes, plut�t
// qu'� chaque appel de la fonction de protection.
if (function_exists('mysqli_real_escape_string')) {
	define('BD_PROTECT', 'mysqli_real_escape_string');
} elseif (function_exists('mysqli_escape_string')) {
	define('BD_PROTECT', 'mysqli_escape_string');
} else if (get_magic_quotes_gpc() == 0) {
	define('BD_PROTECT', 'addslashes');
}

define ('MAIL_SENDER', 'mon.adresse@monsite..fr');
define ('MAIL_SMTP', 'smtp.monsiste.fr');

define ('ADRESSE_SITE', 'http://localhost/starblags/');
define ('ADRESSE_PAGE', ADRESSE_SITE.'php/');
define ('ADRESSE_RSS', ADRESSE_SITE.'rss/');

define('IS_DEBUG', TRUE);	// Indicateur pour affichage des messages d'erreur
							// Si TRUE, les messages sont affich�s avec des
							// informations compl�tes.
							// Quand l'appli est test�e et mise en exploitation
							// cette constante devrait �tre d�finie avec FALSE.

define('REP_UPLOAD', '../upload/');	// Nom du r�pertoire d'upload pour les images

// cl� utilis�e pour le chiffrement/d�chiffrement et la g�n�ration de la cl� de hashage (signature)
// Obtention :
// $key = openssl_random_pseudo_bytes(20, $cstrong);
// echo bin2hex($key);
define('CLE_CRYP', '27a78cdf5f39c95d511ee4cdc1769fc763f4c0ac');

// Liens pour bandeau des pages
define('LIEN_AUCUN', 0);	// Pas de lien
define('LIEN_NA_LA', 1);	// Nouvel Article et Liste Article
define('LIEN_MB_NA', 2);	// Mon Blog et Nouvel Article
define('LIEN_MB_LA', 3);	// Mon Blog et Liste Article
define('LIEN_FORM', 4);		// Formulaire d'authentification
define('LIEN_MB_LA_NA', 5);	// Mon Blog et Liste Article et Nouvel Article

//set_magic_quotes_runtime(0);	// On d�sactive la protection automatique des caract�res


// Si l'option de configuration 'default_charset' (d�finie dans php.ini) n'est pas vide, par d�faut,
// PHP envoie un header Content-Type avec la valeur de cette option (cet header indique au client
// le charset de la page g�n�r�e).
// Dans cette application, les pages g�n�r�es sont encod�es en iso-8859-1.

// 1�re solution pour que le header Content-Type envoy� par PHP contienne le bon charset : fixer 
// l'option de configuration 'default_charset' � la valeur iso-8859-1 dans le fichier php.ini
// et red�marrer le serveur

// 2�me solution : il est possible de modifier (ou �craser) le header Content-Type envoy� par
// PHP en appelant la fonction la header().
// Pour utiliser cette 2�me solution, il vous suffit de d�commenter l'instruction suivante.
// Avantage de cette 2�me solution : on n'a pas � modifier la configuration du serveur.
header('Content-Type: text/html; charset=iso-8859-1');

// On d�finit PAGE_INDEX si il n'est pas d�j� d�fini.
if (!defined('PAGE_INDEX')) {
	define('PAGE_INDEX', false);
}
//_____________________________________________________________________________
//
//				BASE DE DONNEES
//_____________________________________________________________________________
//___________________________________________________________________
/**
 * Connexion � une base de donn�es MySQL.
 * D�finit le jeu de caract�res (ici latin1) utilis� lors des �changes de donn�es entre 
 * et le client (module PHP) et le serveur de base de donn�es. 
 *
 * Le connecteur obtenu par la connexion est stock� dans une
 * variable global : $GLOBALS['bd']. Cel� permet une r�utilisation
 * facile de l'identifiant de connexion dans les fonctions.
 *
 * En cas d'erreur de connexion le script est arr�t�.
 *
 */
function fp_bdConnecter() {
	$bd = mysqli_connect(BD_SERVEUR, BD_USER, BD_PASS, BD_NOM);

	if ($bd !== FALSE) {
		$GLOBALS['bd'] = $bd;
		if (!mysqli_set_charset($bd, "latin1")) {
			$msg = '<div style="margin: 20px auto; width: 350px;">'
					.'<h4>Erreur lors du chargement du charset latin1</h4>'
					.'<p>Erreur MySQL num�ro : '.mysqli_errno($bd)
					.'<br>'.mysqli_error($bd)
					.'</div>';
			fp_bdErreurExit($msg);
		}
		return;					// Sortie connexion et mysqli_set_charset OK
	}

	// Erreur de connexion
	// Collecte des informations facilitant le debugage
	// Remarque : on ne peut pas utiliser la fonction "normale" d'erreur
	// base de donn�es car les erreurs de connexions utilisent des
	// fonctions mysqli specifiques.
	$msg = '<div style="margin: 20px auto; width: 350px;">'
			.'<h4>Erreur de connexion base MySQL</h4>'
			.'BD_SERVEUR : '.BD_SERVEUR
			.'<br>BD_USER : '.BD_USER
			.'<br>BD_PASS : '.BD_PASS
			.'<br>BD_NOM : '.BD_NOM
			.'<p>Erreur MySQL num�ro : '.mysqli_connect_errno($bd)
			.'<br>'.mysqli_connect_error($bd)
			.'</div>';

	fp_bdErreurExit($msg);
}

//___________________________________________________________________
/**
 * Arr�t du script si erreur base de donn�es.
 * La fonction arr�te le script, avec affichage de message d'erreur
 * si on est en phase de d�veloppement.
 *
 * @param string    $msg    Message affich� ou stock�.
 */
function fp_bdErreurExit($msg) {
	ob_end_clean();     // Supression de tout ce qui a pu �tre d�ja g�n�r�

	// Si on est en phase de d�bugage, on affiche le message d'erreur
	// et on arr�te le script.
	if (IS_DEBUG) {
		echo '<!DOCTYPE html><html><head><meta charset="ISO-8859-1"><title>',
			'Erreur base de donn�es</title></head><body>',
			$msg,
			'</body></html>';
		exit();				// Sortie : fin du script
	}

	// Si on est en phase de production on stocke les
	// informations de d�buggage dans un fichier d'erreurs
	// et on affiche un message sibyllin.
	$buffer = date('d/m/Y H:i:s')."\n$msg\n";
	error_log($buffer, 3, 'erreurs_bd.txt');

	// Dans un vrai site, il faudrait faire une page avec
	// la ligne graphique du site. Pas fait ici pour simplifier.
	echo '<!DOCTYPE html><html><head><meta charset="ISO-8859-1"><title>',
			'Starblags</title></head><body>',
			'<h1>Stablags est overbook&eacute;</h1>,
			<h3>Merci de r&eacute;essayez dans un moment</h3>',
			'</body></html>';
	exit();				// Sortie : fin du script
}

//___________________________________________________________________
/**
 * Gestion d'une erreur de requ�te � la base de donn�es.
 *
 * @param string	$sql	requ�te SQL provoquant l'erreur
 */
function fp_bdErreur($sql) {
	$errNum = mysqli_errno($GLOBALS['bd']);
	$errTxt = mysqli_error($GLOBALS['bd']);

	// Collecte des informations facilitant le debugage
	$msg = '<h4>Erreur de requ&ecirc;te</h4>'
			."<pre><b>Erreur mysql :</b> $errNum"
			."<br> $errTxt"
			."<br><br><b>Requ&ecirc;te :</b><br> $sql"
			.'<br><br><b>Pile des appels de fonction</b>';

	// R�cup�ration de la pile des appels de fonction
	$msg .= '<table border="1" cellspacing="0" cellpadding="2">'
			.'<tr><td>Fonction</td><td>Appel&eacute;e ligne</td>'
			.'<td>Fichier</td></tr>';

	// http://www.php.net/manual/fr/function.debug-backtrace.php
	$appels = debug_backtrace();
	for ($i = 0, $iMax = count($appels); $i < $iMax; $i++) {
		$msg .= '<tr align="center"><td>'
				.$appels[$i]['function'].'</td><td>'
				.$appels[$i]['line'].'</td><td>'
				.$appels[$i]['file'].'</td></tr>';
	}

	$msg .= '</table></pre>';

	fp_bdErreurExit($msg);
}

//_____________________________________________________________________________
//
//				GESTION DE CODE HTML
//_____________________________________________________________________________
/**
 * Les 3 fonctions suivantes (fp_modeleTraite, fp_modeleGet et fp_modeleAffiche)
 * g�rent les traitements des mod�les. On �clate la gestion en 3 fonctions pour
 * plus de modularit� du code, et pour une �volution plus facile si le principe
 * des mod�les �tait revu.
 */
//_____________________________________________________________________________
/**
 * Lecture d'un fichier modele, remplacement des motifs et affichage
 *
 * @param string	$fichier	Nom du fichier � lire (sans r�pertoire ni extension)
 * @param array		$remplace	Tableau associatif des remplacements
 * @return string	Contenu du fichier
 */
function fp_modeleTraite($fichier, $remplace) {
	$modele = fp_modeleGet($fichier);	// R�cup�ration du modele
	fp_modeleAffiche($modele, $remplace);
}
//_____________________________________________________________________________
/**
 * Lecture d'un fichier modele
 *
 * @param string	$fichier	Nom du fichier � lire (sans r�pertoire ni extension)
 * @return string	Contenu du fichier
 */
function fp_modeleGet($fichier) {
	// Suivant la page en cours, le chemin d'acc�s au fichier mod�le est diff�rent:
	// - la page index.php doit r�f�rencer modeles/$fichier
	// - les autres pages doivent r�f�rencer ../modeles/$fichier
	// Pour faire cette diff�rence on utilise $_SERVER['PHP_SELF'] qui contient
	// le chemin d'acc�s et le nom du fichier en cours d'�x�cution (par exemple
	// /starblags/index.php ou /starblags/php/articles_voir.php ou ...
	// On ne pourrait pas utiliser __FILE__ qui contient le nom du fichier dans
	// lequel se situe l'instruction (ici bibli.php).
	if (basename($_SERVER['PHP_SELF']) == 'index.php') {
		$fichier = "modeles/$fichier.html";
	} else {
		$fichier = "../modeles/$fichier.html";
	}
	$pointeur = @fopen($fichier, 'r');	// ouverture du fichier
	// Traitement si erreur
	if ($pointeur === false) {
		ob_end_clean();  // Effacement de la sortie d�j� buff�ris�e
		echo '<html><head><title>Erreur application</title></head><body>',
			'Le fichier ', $fichier, ' ne peut pas �tre ouvert.',
			'</body></html>';
		exit();  // Arr�t total du traitement
	}
	$buffer = fread($pointeur, filesize($fichier));	// Lecture
	fclose($pointeur);	// fermeture du fichier
	return $buffer;
}
//_____________________________________________________________________________
/**
 * Remplacement des motifs d'un modele et affichage
 *
 * @param string	$modele		Texte du mod�le
 * @param array		$remplace	Tableau associatif des remplacements
 */
function fp_modeleAffiche($modele, $remplace) {
	// Comme la fonction str_replace accepte des tableaux, pour faire
	// les remplacements dans les mod�les on utilise un tableau associatif :
	// - la cl� est l'expression � remplacer
	// - la valeur est la valeur de remplacement
	// De cette fa�on la fonction ne sera appel�e qu'une fois par modele.
	// On utilisera la fonction array_keys($remplace) pour r�cup�rer un tableau
	// des cl�s et array_values($remplace) pour r�cup�rer un tableau des valeurs.

	echo str_replace(array_keys($remplace), array_values($remplace), $modele);

	// Il peut sembler "bizarre" d'avoir une fonction avec cette seule ligne.
	// L'explication tient dans la facilit� qu'on aurra plus tard si on a
	// � changer le traitement. Les appels � la fonction devraient rester les
	// m�mes, seule le code de la fonction serait � changer.
}

//_____________________________________________________________________________
/**
 * Renvoie le code html des liens de mise � jour pour le bandeau des pages priv�es
 *
 * @param	integer	$type		Type des liens � g�n�rer. Voir constantes LIEN_xxx
 * @return	string	Code HTML des liens
 */
function fp_htmlBandeau($type) {
	$liens = array(	'NA' => '<a href="'.fp_makeURL('article_maj.php', 0).'">Nouvel article</a>',
					'LA' => '<a href="articles_liste.php">Mes articles</a>',
					'MB' => '<a href="blog_maj.php">Mon blog</a>');
	$blocLien = '';
	if ($type == LIEN_MB_LA_NA) {
		return $liens['MB'].
				'<br>'.$liens['LA'].
				'<div style="padding-top: 8px">'
				.$liens['NA'].
				'</div>';
	}

	if ($type == LIEN_NA_LA) {
		return "{$liens['NA']}<br>{$liens['LA']}";
	}

	if ($type == LIEN_MB_NA) {
		return "{$liens['MB']}<br>{$liens['NA']}";
	}

	if ($type == LIEN_MB_LA) {
		return "{$liens['MB']}<br>{$liens['LA']}";
	}
	return '';
}
//_____________________________________________________________________________
/**
 * Affiche le code html d'une ligne de boutons de formulaire.
 *
 * Cette fonction accepte un nombre variable de param�tres.
 * Seul le premier est d�fini dans la d�finition de la fonction.
 * Les param�tres suivants d�finissent les boutons.
 * La d�finition d'un bouton se fait dans une zone alpha de la forme :
 *  Type|Nom|Valeur|JavaScript
 * 	Type	type du bouton
 * 		S : submit
 * 		R : reset
 * 		B : button
 * 	Nom		nom du bouton  (attribut name)
 * 	Valeur	valeur du bouton (attribut value)
 * 	JavaScript	fonction JavaScript pour �v�n�ment onclick
 *
 * Exemple : fp_htmlBoutons(2, 'B|btnRetour|Liste des sujets|history.back()', 'S|btnValider|Valider'
 *
 * @param	integer	$colspan	Nombre de colonnes de tableau � joindre. Si -1 pas dans un tableau
 * @param	string	Ind�fini	D�finition d'un bouton. Il peut y avoir
 * 								autant de d�finitions que d�sir�.
 */
function fp_htmlBoutons($colspan) {
	if ($colspan == -1) {
		echo '<p align="right">';
	} else {
		echo '<tr>',
				'<td colspan="', $colspan, '">&nbsp;</td>',
			'</tr>',
			'<tr>',
				'<td colspan="'.$colspan.'" align="right">';
	}

	for ($i = 1, $nbArg = func_num_args(); $i < $nbArg; $i++) {
		$bouton = func_get_arg($i);
		$description = explode('|', $bouton);

		if ($description[0] == 'S') {
			$description[0] = 'submit';
		} elseif ($description[0] == 'R') {
			$description[0] = 'reset';
		} elseif ($description[0] == 'B') {
			$description[0] = 'button';
		} else {
			continue;
		}

		if (!isset($description[3])) {
			$description[3] = '';
		}

		echo '&nbsp;&nbsp;',
				'<input type="', $description[0], '" ',
				'name="', $description[1], '" ',
				'value="', $description[2], '" ',
				'class="bouton" ',
				( ($description[3] == '') ? '>' : 'onclick="'.$description[3].'">');
	}

	echo ($colspan == -1) ? '</p>' : '</td></tr>';
}
//_____________________________________________________________________________
/**
 * Affiche le code html d'une ligne de tableau �cran de saisie.
 *
 * Le code html g�n�r� est de la forme
 * <tr><td> libelle </td><td> zone de saisie </td></tr>
 *
 * Seuls les 3 premiers param�tres sont obligatoires. Les autres d�pendent
 * du type de la zone.
 * Le libell� de la zone est prot�g� pour un affichage HTML
 * Si la valeur de la zone est du texte, il est prot�g� pour un affichage HTML
 *
 * @param	string	$type	type de la zone
 * 							A : textarea
 * 							AN : textarea uniquement en affichage
 * 							C : case � cocher
 * 							H : hidden
 * 							P : password
 * 							R : bouton radio
 * 							S : select (liste)
 * 							T : text
 * 							TN : text  uniquement en affichage
 * @param	string	$nom	nom de la zone (attribut name)
 * @param	mixed	$valeur	valeur de la zone (attribut value)
 * 							Pour le type S, c'est l'�l�ment s�lectionn�
 * @param	string	$lib	libell� de la zone
 * @param	integer	$size	si type T ou P : longueur (attribut size)
 * 							si type A : longeur (attribut cols)
 * 							si type S : nombre de lignes affich�es (attribut size)
 * 							si type R : 1 = boutons c�te � c�te / 2 = boutons superpos�s
 * 							si type C : 1 = cases c�te � c�te / 2 = cases superpos�s
 * @param	mixed	$max	si type T ou P : longueur maximum (attribut maxlength)
 * 							si type A : nombre de ligne (attribut rows)
 * 							si type R : tableau des boutons radios (valeur => libell�)
 * 							si type C : tableau des case � cocher (valeur => libell�)
 * 							si type S : tableau des lignes de la liste (valeur => libell�)
 * @param	string	$plus	Suppl�ment (ex : fonction JavaScript gestionnaire d'�v�nement)
 */
function fp_htmlSaisie($type, $nom, $valeur, $lib = '', $size = 80, $max = 255, $plus = '') {
	if (is_string($valeur) && $valeur != '') {
		$valeur = fp_protectHTML($valeur);
	}

	// Zone de type Hidden
	if ($type == 'H') {
		echo '<input type="hidden" name="', $nom, '" value="', $valeur, '">';
		return;
	}

	$lib = fp_protectHTML($lib);

	switch ($type) {
	//--------------- Zone de type Texte
	case 'T':
	case 'TN':
		echo '<tr>',
				'<td align="right">', $lib, '&nbsp;</td>',
				'<td>',
					'<input type="text" name="', $nom, '" ', $plus,
					'size="', $size, '" maxlength="', $max, '" value="', $valeur, '" ',
					(($type == 'T') ? 'class="saisie">' : 'class="saisie_non" readonly>'),
				'</td>',
			'</tr>';
		return;

	//--------------- Zone de type Textarea
	case 'A':
	case 'AN':
		echo '<tr>',
				'<td align="right" valign="top">', $lib, '&nbsp;</td>',
				'<td>',
					'<textarea name="', $nom, '" cols="', $size, '" rows="'.$max.'" ', $plus,
					(($type == 'A') ? 'class="saisie">' : 'class="saisie_non" readonly>'),
					$valeur, '</textarea>',
				'</td>',
			'</tr>';
		return;

	//--------------- Zone de type Password
	case 'P':
		echo '<tr>',
				'<td align="right">', $lib, '&nbsp;</td>',
				'<td>',
					'<input type="password" name="', $nom, '" ', $plus,
					'size="', $size, '" maxlength="', $max, '" value="', $valeur, '" ',
					'class="saisie">',
				'</td>',
			'</tr>';
		return;

	//--------------- Zone de type bouton radio
	//--------------- Zone de type case � cocher
	case 'R':
	case 'C':
		if ($type == 'R') {
			$typeAttr = 'radio';
			$nameAttr = $nom;
		} else {
			$typeAttr = 'checkbox';
			$nameAttr = $nom.'[]';
		}

		echo '<tr>',
				'<td align="right" ', (($size == 2) ? 'valign="top">' : '>'),
					$lib, '&nbsp;',
				'</td>',
				'<td>';

		$nb = 0;
		foreach ($max as $val => $txt) {
			if ($size == 2) {
				$nb ++;
				if ($nb > 1) {
					echo '<br>';
				}
			}
			echo '<input type="', $typeAttr, '" name="', $nameAttr, '" value="', $val, '"',
				( ($valeur == $val) ? ' checked="true">' : '>' ),
				fp_protectHTML($txt), '&nbsp;&nbsp;&nbsp;';
		}
		echo '</td>',
			'</tr>';
		return;

	//--------------- Zone de type Select (liste)
	case 'S':
		echo '<tr>',
				'<td align="right"', ( ($size > 1) ? ' valign="top">' : '>'),
					$lib, '&nbsp;',
				'</td>',
				'<td>',
					'<select name="', $nom, '" size="', $size, '" ', $plus, ' class="saisie">';

		foreach($max as $cle => $val) {
			echo '<option value="', $cle, '"', ( ($cle == $valeur) ? ' selected="yes">' : '>' ),
					$val,
				'</option>';
		}

		echo 		'</select>',
				'</td>',
			'</tr>';
		return;
	}
}

//_____________________________________________________________________________
/**
 * Affichage des messages d'erreur d'un formulaire
 *
 * @param	array	$erreurs	Tableau associatif des erreurs
 */
function fp_htmlErreurs($erreurs) {
	echo '<div id="blcErreurs">';
	if (count($erreurs) == 1) {
		echo 'L\'erreur suivante a �t� d�tect�e ';
	} else {
		echo 'Les erreurs suivantes ont �t� d�tect�es ';
	}
	echo 'dans le formulaire de saisie :';

	foreach($erreurs as $texte) {
		echo '<p class="erreurTexte">',
				fp_protectHTML($texte),
			'</p>';
	}

	echo '</div>';
}
//_____________________________________________________________________________
//
//				FONCTIONS DIVERSES
//_____________________________________________________________________________
/**
 * Protection d'une cha�ne de caract�res pour une utilisation SQL
 *
 * @param	string	$texte		Texte � prot�ger
 * @global	string	BD_PROTECT	Nom de la fonction de protection � utiliser
 *
 * @return	string	Cha�ne prot�g�e
 */
function fp_protectSQL ($texte) {
	if (get_magic_quotes_gpc() == 1) {
		$texte = stripslashes($texte);
	}
	// On est oblig� de passer par une variable car on ne peut pas faire de
	// "constantes fonctions" comme on peut faire des "variables fonctions".
	$fonction = BD_PROTECT;
	return $fonction($GLOBALS['bd'], $texte);
}
//_____________________________________________________________________________
/**
 * Protection d'une cha�ne de caract�res pour un affichage HTML
 *
 * @param	string	$texte	Texte � prot�ger
 * @param	boolean	$bR		TRUE si remplacement des saut de ligne par le tag <br>
 *
 * @return	string	Code HTML g�n�r�
 */
function fp_protectHTML($texte, $bR = FALSE) {
	return ($bR) ? nl2br(htmlentities($texte, ENT_COMPAT, 'ISO-8859-1'))
				: htmlentities($texte, ENT_COMPAT, 'ISO-8859-1');
}
//_____________________________________________________________________________
/**
 * Remplace dans un texte les caract�res % # { } [ ]
 * pour empecher l'execution de code PHP, ASP ou JS
 *
 * @param string	$texte	cha�ne � traiter
 * @return string	cha�ne avec le remplacement
 */
function fp_noScripts($texte) {
	return preg_replace("/<(\%|\?|([[:space:]]*)script)/i", "&lt;\\1", $texte);
}

//_____________________________________________________________________________
/**
 * Enl�ve la protection automatique de caract�res (magic_quotes_gpc) sur $_POST
 *
 * Si la variable de configuration PHP magic_quotes_gpc a la valeur 1
 * certains caract�res des zones de formulaires sont automatiquement prot�g�s.
 * Cel� peut poser probl�me quand on veut par exemple r�afficher les infos
 * saisies. Cette fonction permet d'enlever les caract�res de protection
 * trouv�s dans les l�l�ments du tableau $_POST.
 *
 * @global	array	$_POST	Les �l�ments du formulaire
 */
function fp_stripPOST() {
	if (get_magic_quotes_gpc() == 0) {
		return;
	}
	foreach($_POST as $cle => $zone) {
		$_POST[$cle] = stripslashes($zone);
	}
}
//_____________________________________________________________________________
/***
* Composition d'une URL avec cryptage des param�tres
*
* Les param�tres de l'URL sont mis les uns � la suite des autres, s�par�s par
* le caract�re | (pipe). Apr�s chiffrement et ajout d'une signature, la cha�ne
* binaire est encod�e en MIME base64 et prot�g�e pour les caract�res sp�ciaux 
* d'URL avec la fonction urlencode(). 
* Elle est ajout�e � l'URL avec comme nom x. On obtient ainsi
* par exemple : mapage.php?x=hCIqkCp3KpgFufxfSTqbb7%2F9jMkB2aWM%2FPztsqI0C3KIukvcDKW2uGsD3Le4Xs
*
* @param	string 	$url		D�but de l'url (ex : mapage.php)
* @param	mixed 	$x			Param�tres de l'url. Ils sont d'un nombre ind�termin�
* @global	string	CLE_CRYP	Cle de cryptage
*
* @return string	URL crypt�e
*/
function fp_makeURL($url, $x) {
	$args = func_get_args();
	$params = $args[1];
	for($i = 2, $iMax = count($args); $i < $iMax; $i ++) {
		$params .= '|'.$args[$i];
	}
	// R�cup�ration de la cl� g�n�r�e avec la fonction openssl_random_pseudo_bytes()
	$key = bin2hex(CLE_CRYP);
	// chiffrement
	$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
	$iv = openssl_random_pseudo_bytes($ivlen);
	$ciphertext_raw = openssl_encrypt($params, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
	// G�n�ration d'une valeur de cl� de hachage en utilisant la m�thode HMAC
	$hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
	// Encodage de la cha�ne binaire en MIME base64
	$ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );
	return $url.'?x='.urlencode($ciphertext);

}
//_____________________________________________________________________________
/**
 * D�cryptage d'un param�tre GET et renvoi des valeurs contenues
 *
 * Cette fonction est en quelque sorte l'inverse de de fp_makeURL.
 * Elle r�cup�re la variable $_GET['x']. 
 * On n'a pas besoin de d�coder la cha�ne avec urldecode() car PHP le fait automatiquement.
 * On commence par d�coder la chaine en MIME base64, on obtient une chaine binaire.
 * Ensuite, apr�s extraction des diff�rentes parties de la cha�ne avec substr(),
 * la signature est v�rifi�e, et si elle est bonne, elle est d�chiffr�e.
 * Si on trouve plusieurs valeurs, on les renvoie sous la forme d'un tableau
 * Le script est arr�t� si
 * - le param�tre x est absent
 * - la signature n'est pas bonne
 *
 * @global	array	$_GET['x']	Param�tre de la page
 *
 * @return	mixed	Si plusieurs valeurs renvoie un tableau, sinon un scalaire
 */
function fp_getURL() {
	if (!isset($_GET['x'])) {
		exit((IS_DEBUG) ? 'Erreur GET - '.__LINE__ : '');
	}
	$key = bin2hex(CLE_CRYP);
	$c = base64_decode($_GET['x']);
	$ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
	$iv = substr($c, 0, $ivlen);
	$hmac = substr($c, $ivlen, $sha2len=32);
	$ciphertext_raw = substr($c, $ivlen+$sha2len);
	$calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
	if (! hash_equals($hmac, $calcmac))//PHP 5.6+ timing attack safe comparison
	{
		exit((IS_DEBUG) ? 'Erreur GET - '.__LINE__ : '');
	}
	$params = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
	$params = explode('|', $params);
	// Si plusieurs valeurs on renvoie un tableau avec les valeurs
	if (count($params) > 1) {
		return $params;
	}
	// Si une seule valeur on renvoie cette valeur uniquement
	return $params[0];
}
//_____________________________________________________________________________
/***
* Retourne une date jma en amj
*
* @param string 	$date 	format jj/mm/aaaa ou jj-mm-aaaa
* 							ou jj.mm.aaaa ou jj mm aaaa ou jjmmaaaa
*
* @return integer format aaaammjj
*/
function fp_jmaAmj($date) {
	if (strpos($date , '/') !== FALSE) {
		return preg_replace("/(\d{2})\/(\d{2})\/(\d{4})/","\\3\\2\\1",$date);
	}
	if (strpos($date , '-') !== FALSE) {
		return preg_replace("/(\d{2})\-(\d{2})\-(\d{4})/","\\3\\2\\1",$date);
	}
	if (strpos($date , '.') !== FALSE) {
		return preg_replace("/(\d{2})\.(\d{2})\.(\d{4})/","\\3\\2\\1",$date);
	}
	if (strpos($date , ' ') !== FALSE) {
		return preg_replace("/(\d{2}) (\d{2}) (\d{4})/","\\3\\2\\1",$date);
	}
	return preg_replace("/(\d{2})(\d{2})(\d{4})/","\\3\\2\\1",$date);
}
//_____________________________________________________________________________
/***
* Retourne une date amj en j/m/a
*
* @param integer 	$date 	format aaaammjj
*
* @return string cha�ne jj/mm/aaaa
*/
function fp_amjJma($date) {
	if ($date == 0) {
		return '';
	}
	return preg_replace("/(\d{4})(\d{2})(\d{2})/","\\3/\\2/\\1",$date);
}
//_____________________________________________________________________________
/**
* V�rification de la session d'un utilisateur.
*
* A utiliser dans les pages de mise � jour pour s'assurer qu'une session
* est bien initialis�e pour l'utiliseur.
* Si ce n'est pas le cas, l'utilisateur est redirig� sur la page d'acceuil.
*
* @global	array	$_SESSION	variables de ssession
*/
function fp_verifSession() {
	$ok = TRUE;
	if (!isset($_SESSION['IDBlog']) || !is_numeric($_SESSION['IDBlog'])) {
		$ok = FALSE;
	}
	if (!isset($_SESSION['IDArticle']) || !is_numeric($_SESSION['IDArticle'])) {
		$ok = FALSE;
	}
	if (!isset($_SESSION['UploadFrom'])) {
		$ok = FALSE;
	}
	if (!isset($_SESSION['UploadNum']) || !is_numeric($_SESSION['UploadNum'])) {
		$ok = FALSE;
	}

	if (!$ok) {
		session_destroy();
		$_SESSION = array();
		header('Location: ../index.php');
		exit();  // fin PHP
	}
}
//_____________________________________________________________________________
/**
 * Envoi d'un mail au format HTML.
 *
 * @param	string	$destinataire	Adresse mail du destinataire
 * @param	string	$objet			Objet du mail
 * @param	string	$texte			Texte du mail
 */
function fp_mail($destinataire, $objet, $texte) {
	@ini_set('SMTP', MAIL_SMTP);
	@ini_set('sendmail_from', MAIL_SENDER);

	$finLigne = "\r\n";

	// Composition de l'ent�te du mail
	$enTete = 'From: "StarBlags" <'.MAIL_SENDER.'>'.$finLigne
			.'Reply-To: '.MAIL_SENDER.$finLigne
			.'MIME-Version: 1.0'.$finLigne
			.'Content-Type: text/html; charset=iso-8859-1'.$finLigne
			.'X-Priority: 1'.$finLigne
			.'X-Mailer: PHP / '.phpversion().$finLigne;

	// Envoi du mail
	if (!mail($destinataire, $objet, $texte, $enTete)) {
		$msg = __LINE__.' - '.basename(__FILE__)
			.' - Un mail n\'a pas pu �tre envoy�.<br>'
			.'<a href="mailto:'.MAIL_SENDER.'">'
			.'Pr�venez l\'administrateur du site.</a>';
		exit($msg);
	}
}
//_____________________________________________________________________________
/**
 * R�cup�ration de l'adresse IP du visiteur
 *
 * @return	string	Adresse Ip du visiteur ou '' si impossible � d�terminer
 */
function fp_getIP() {
    $iP = '';
    $proxys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
                    'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
                    'HTTP_VIA', 'HTTP_X_COMING_FROM',
                    'HTTP_COMING_FROM', 'REMOTE_ADDR');

    foreach($proxys as $prox) {
        if (isset($_SERVER[$prox])) {
            $iP = $_SERVER[$prox];
            break;
        }
    }

    $exps = array();
    $ok = preg_match('/^[0-9]{1,3}(.[0-9]{1,3}){3,3}/', $iP, $exps);

    if($ok && (count($exps) > 0)) {
    	return $exps[0];
    }

    return '';
}
//_____________________________________________________________________________
/**
 * Affichage du contenu d'un article
 *
 * Si il n'y a pas d'images li�es, le texte est simplement
 * affich� � la suite de l'ent�te.
 * Si il y a des images li�es :
 * - on utilise un bloc pour les images du haut
 * - on utilise un bloc pour les images du bas
 * - pour les images de gauche, de droite et pour le texte
 * on utilise un tableau. C'est le plus simple pour �viter
 * des "bidouilles" pour l'alignement vertical des images.
 *
 *	 ____________________________________________________
 *  | bloc ent�te                                        |
 *  |____________________________________________________|
 *  | bloc image haut (si n�cessaire)                    |
 *  |____________________________________________________|
 *   ____________________________________________________
 *  | cellule |  texte                         | cellule |
 *  | images  |                                | images  |
 *  | gauche  |                                | droite  |
 *  | ________|________________________________|_________|
 *   ____________________________________________________
 *  | bloc image bas (si n�cessaire)                     |
 *  |____________________________________________________|
 *   ____________________________________________________
 *  | bloc liens commentaire, note, etc.                 |
 *  |____________________________________________________|
 *
 *
 * @param	array	$articles	Enregistrement table articles
 * @global	const	REP_UPLOAD	R�pertoire de t�l�chargement des images
 */
function fp_articleAffContenu($articles, $modele) {
	// Recherche de la note de l'article
	$sql = "SELECT sum(anNote)
			FROM articles_notes
			WHERE anIDArticle = {$articles['arID']}";

	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

	$enr = mysqli_fetch_array($R);	// R�cup�ration de la s�lection
	$note = $enr[0];
	mysqli_free_result($R);

	//---------------------------------------------------------------
	// Traitement des images li�es
	// Les tags des cellules images et  l�gendes sont stock�s
	// dans des matrices PHP qui serviront � construire les tableaux HTML.
	// Matrice : $images[place][incr�ment]
	// (Rapel place : 0 = haut, 1 = droite, 2 = bas, 3 = gauche)
	$images = $illus = array();

	$remplace = array();
	$remplace['@_PHOTO_0_@'] = $remplace['@_PHOTO_1_@'] = '';
	$remplace['@_PHOTO_2_@'] = $remplace['@_PHOTO_3_@'] = '';

	// Recherche des images li�es � l'article
	$sql = "SELECT *
			FROM photos
			WHERE phIDArticle = {$articles['arID']}
			ORDER BY phNumero";

	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

	while ($enr = mysqli_fetch_assoc($R)) {  // Boucle de lecture de la s�lection
		$url = REP_UPLOAD."{$articles['arID']}_{$enr['phNumero']}.{$enr['phExt']}";
		$images[$enr['phPlace']][] = '<img src="'.$url.'"><br>'
									.fp_protectHTML($enr['phLegende']);
	}
	mysqli_free_result($R);

	for ($i = 0; $i < 4; $i ++) {
		$jMax = (isset($images[$i])) ? count($images[$i]) : 0;

		for ($j = 0; $j < $jMax; $j++) {
			$remplace["@_PHOTO_{$i}_@"] .= $images[$i][$j];
		}
	}

	//---------------------------------------------------------------
	// Affichage du contenu de l'article
	$remplace['@_DATE_@'] = fp_amjJma($articles['arDate'])." - {$articles['arHeure']}";
 	// En-t�te avec le titre de l'article et sa date de parution
 	// Si l'utilisateur qui affiche la page est le cr�ateur du blog
	// il peut modifier un article en cliquant sur son titre.
	$titre = fp_protectHTML($articles['arTitre']);
	if (isset($_SESSION['IDBlog']) && $_SESSION['IDBlog'] == $articles['arIDBlog']) {
		// Les param�tres du lien sont crypt�s (IDarticle)
		$url = fp_makeURL('article_maj.php', $articles['arID']);
		$titre = '<a href="'.$url.'">'.$titre.'</a>';
	}
	$remplace['@_TITRE_@'] = $titre;
	$remplace['@_TEXTE_@'] = $articles['arTexte'];

	//---------------------------------------------------------------
	// Affiche des liens - fin d'un article.
	// - le nombre de commentaires et lien pour en ajouter,
	// - la note �ventuelle,
	// - le lien pour noter.
	$liens = '';

	// Si il y a des commentaires pour l'article, on affiche un lien
	// pour l'affichage d'une fen�tre popup avec les commentaires
	if ($articles['NbComments'] > 0) {
		// Les param�tres du lien sont crypt�s (IDArticle)
		$url = fp_makeURL('comments_voir.php',$articles['arID']);
		$url = "javascript:FP.ouvrePopUp('$url')";

		$liens .= '<a href="'.$url.'" class="articleLienCom">'
				.$articles['NbComments']
				.( ($articles['NbComments'] == 1) ? ' commentaire</a>':' commentaires</a>');
	}

	// Lien pour la saisie d'un commentaire
	if ($articles['arComment'] == 1) {
		// Les param�tres du lien sont crypt�s (IDArticle)
		$url = fp_makeURL('comment_ajouter.php', $articles['arID']);
		$url = "javascript:FP.ouvrePopUp('$url')";

		$liens .= '<a href="'.$url.'" class="articleLienComAjout">ajouter un commentaire</a>';
	}

    // Note de l'article
	if ($note > 0) {
		$liens .= '<a class="articleNote">'.$note.'</a>';
	}

	// Lien pour noter l'article et fin du tableau
	// Les param�tres du lien sont crypt�s (IDArticle)
	$url = fp_makeURL('article_noter.php', $articles['arID']);
	$url = "javascript:FP.ouvrePopUp('$url')";

	$liens .= '<a href="'.$url.'" class="articleLienNoteAjout">noter</a>';

	$remplace['@_LIENS_@'] = $liens;

	fp_modeleAffiche($modele, $remplace);	// Remplacement et affichage du modele
}
?>
