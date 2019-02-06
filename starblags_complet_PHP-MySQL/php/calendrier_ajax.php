<?php
//_____________________________________________________________________________
/**
 * Génération d'un calendrier perpétuel.
 *
 * Ce script est destiné à être appelé avec la technique Ajax. Il renvoie un
 * calendrier, pour un mois et un blog donné, avec la mise en évidence des jours
 * où des articles ont été publiés.
 * Le script génére le code HTML du calendrier ert le renvoie au client.
 *
 * @param	integer	$_GET['a']	ID du blog à traiter
 * @param	integer	$_GET['b']	Mois à afficher sous la forme AAAAMM
 * @param	string	$_GET['c']	ID du bloc d'affichage chez le client
 */
ob_start();
// Pour empêcher la réponse d'être mise en cache
$cacheDate = gmdate('D, d M Y H:i:s').' GMT';
header('Expires: '.$cacheDate);
header('Last-Modified: '.$cacheDate);
header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0');
header('Pragma: no-cache');
// Fixe le type de caractères utilisé. Sinon par défaut UTF-8 => pb accents
header('Content-Type: text/html; charset=ISO-8859-1');

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
require_once('bibli.php');

if (!isset($_GET['a']) || !isset($_GET['b']) || !isset($_GET['c'])) {
	exit();
}

$IDBlog = (int) $_GET['a'];
if ($IDBlog < 0 || $IDBlog > 9999999) {
	exit();
}
$aM = $_GET['b'];
$an = (int) substr($aM, 0, 4);
if ($an < 2000) {
	exit();
}
$mois = (int) substr($aM, -2);
if ($mois < 1 || $mois > 12) {
	exit();
}
$IDBloc = $_GET['c'];

$premier = fpl_getPremierJour($mois, $an);
$nbJours = fpl_getNbJoursMois($mois, $an);

$nomMois = array(1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
$nomJours = array(1 => 'L', 'M', 'M', 'J', 'V', 'S', 'D');

list($mPrecedent, $aPrecedent) = fpl_getNewMois($mois, $an, -1);
list($mSuivant, $aSuivant) = fpl_getNewMois($mois, $an, 1);

//_____________________________________________________________________________
//
// Recherches des dates auxquelles on trouve des articles
//_____________________________________________________________________________
// Le tableau $dates contient un index à chacun des jours
// pour lequel il y a un article
$dates = array();
$dateDebut = fpl_makeAMJ($an, $mois, 1);
$dateFin = fpl_makeAMJ($an, $mois, $nbJours);

fp_bdConnecter();	// Ouverture base de données

$sql = "SELECT DISTINCT arDate
		FROM articles
		WHERE arIDBlog = $IDBlog
		AND arDate BETWEEN $dateDebut AND $dateFin
		ORDER BY arDate";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête

while ($enr = mysqli_fetch_assoc($R)) {	// Boucle de lecture de la sélection
	$jour = (int) substr($enr['arDate'], -2);
	$dates[$jour] = $enr['arDate'];
}

mysqli_free_result($R);
mysqli_close($GLOBALS['bd']);

//_____________________________________________________________________________
//
// Affichage du calendrier
//_____________________________________________________________________________
// Le calendrier est affiché dans un tableau de 7 colonne et n lignes.
// La première ligne du tableau est compoosée du nom du mois et de l'année affichée,
// entourés par des fléches permettant de passer aux mois précédents ou souvants.
// La deuxième ligne est composée des 3 premiers caractères des noms des jours.
$urlPrecedent = $aPrecedent * 100 + $mPrecedent;
$urlPrecedent = "FP.calendrier($IDBlog, $urlPrecedent, '$IDBloc')";
$urlSuivant = $aSuivant * 100 + $mSuivant;
$urlSuivant = "FP.calendrier($IDBlog, $urlSuivant, '$IDBloc')";

echo '<table border="0" cellspacing="0" cellpadding="0" width="100%">',
		'<tr>',
			'<td colspan="7" class="calendrierTete">',
				'<img src="images/fleche_g.gif" width="16" height="16" ',
				'align="absmiddle" style="cursor: pointer" onclick="',$urlPrecedent,'"> ',
				$nomMois[$mois], ' ', $an,
				' <img src="images/fleche_d.gif" width="16" height="16" ',
				'align="absmiddle" style="cursor: pointer" onclick="', $urlSuivant, '"> ',
			'</td>',
		'</tr>',
		'<tr>';

foreach($nomJours as $j) {
	echo '<td class="calendrierTete">',	$j,	'</td>';
}

echo '</tr>';

// La première ligne du calendrier est particulière :
// il faut tenir compte des derniers jours du mois précédent
$nbPrecedent = fpl_getNbJoursMois($mPrecedent, $aPrecedent);

echo '<tr>';

for( $i = 1, $colonne = 1; $i < $premier; $i ++, $colonne ++) {
	echo '<td>&nbsp;</td>';
}

// On affiche maintenant les jours du mois
// $colonne permet de faire une nouvelle ligne quand la semaine est finie
for ($i = 1; $i <= $nbJours; $i ++, $colonne ++) {
	if (! isset($dates[$i])) {
		// Pas d'articles à cette date
		echo '<td>', $i, '</td>';
	} else {
		// Des articles à cette date. Lien sur la page d'affichage des articles
		// Les paramètres du lien sont cryptés (IDBlog|IDArticle|No Page| Date)
		$url = fp_makeURL('php/articles_voir.php', $IDBlog, 0, 0, $dates[$i]);
		echo '<td><a href="',$url,'">', $i, '</a></td>';
	}

	if ($colonne == 7) {
		echo '</tr><tr>';
		$colonne = 0;
	}
}

// On termine l'affichage de la dernière ligne si besoin
if ($colonne > 1) {
	for ($i = $colonne; $i < 8; $i ++) {
		echo '<td>&nbsp;</td>';
	}
}

echo '</tr></table>';

ob_end_flush();

//_____________________________________________________________________________
//
// 							FONCTIONS
//_____________________________________________________________________________
/**
* Renvoie le nombre de jours dans un mois
*
* @param integer		$mois		Numéro du mois à traiter
* @param integer		$an			Année à traiter
*
* @return integer		Nombre de jours dans le mois
*/
function fpl_getNbJoursMois($mois, $an) {
	//Traitement des mois de 30 jours
	if ($mois == 4 || $mois == 6 || $mois == 9 || $mois == 11) {
		return 30;
	}
	// Si ce n'est pas février, le mois a 31 jours
	if ($mois != 2) {
		return 31;
	}
	// C'est février.
	// Si année pas multiple de 4, pas bissextile, 28 jours
	if ($an % 4 != 0) {
		return 28;
	}
	// si année multiple de 100 ou de 400, pas bissextile, 28 jours
	if ($an % 100 == 0 || $an % 400 == 0) {
		return 28;
	}
	return 29;
}
//_____________________________________________________________________________
/**
* Renvoie le numéro du premier jour du mois 1-Lundi, 7-Dimanche
*
* @param integer	$mois		Numéro du mois à traiter
* @param integer	$an			Année à traiter
*
* @return integer	Numéro du premier jour du mois
*/
function fpl_getPremierJour($mois, $an) {
	// Les fonctions date et mktime permettent de trouver
	// le premier jour d'un mois sous la forme anglo-saxonne
	// dans laquelle la semaine commence le dimanche (jour numéro 0)
	$premier = date('w', mktime(0, 0, 0, $mois, 1, $an));
	// Nous devons adapter le jour trouvé à la forme française
	// dans laquelle la semaine commence le lundi, et dans laquelle
	// le dicmanche est le jour numéro 7
	if ($premier == 0) {
		$premier = 7;
	}
	return $premier;
}
//_____________________________________________________________________________
/**
* Ajoute ou retranche ou nombre de mois à une date mois/année
*
* @param integer	$mois		Numéro du mois de départ
* @param integer	$an			Année de départ
* @param integer	$ecart		Nombre de mois à ajouter (positif) ou retrancher (négatif)
*
* @return array		Tableau avec le mois et l'année
*/
function fpl_getNewMois($mois, $an, $ecart) {
	$date = date('Ym', mktime(0, 0, 0, $mois + $ecart, 1, $an));
	$mois = intval(substr($date, -2));
	$an = intval(substr($date, 0, -2));
	return array($mois, $an);
}
//_____________________________________________________________________________
/**
* Compose une date au format AMJ à partir de J M A
*
* @param integer	$an			Année
* @param integer	$mois		Numéro du mois
* @param integer	$jour		Numéro du jour dans le mois
*
* @return integer	Date au format AAAAMMJJ
*/
function fpl_makeAMJ($an, $mois, $jour) {
	return ($an * 10000) + ($mois * 100) + $jour;
}
?>