<?php
//_____________________________________________________________________________
/**
 * G�n�ration d'un calendrier perp�tuel.
 *
 * Ce script est destin� � �tre appel� avec la technique Ajax. Il renvoie un
 * calendrier, pour un mois et un blog donn�, avec la mise en �vidence des jours
 * o� des articles ont �t� publi�s.
 * Le script g�n�re le code HTML du calendrier ert le renvoie au client.
 *
 * @param	integer	$_GET['a']	ID du blog � traiter
 * @param	integer	$_GET['b']	Mois � afficher sous la forme AAAAMM
 * @param	string	$_GET['c']	ID du bloc d'affichage chez le client
 */
ob_start();
// Pour emp�cher la r�ponse d'�tre mise en cache
$cacheDate = gmdate('D, d M Y H:i:s').' GMT';
header('Expires: '.$cacheDate);
header('Last-Modified: '.$cacheDate);
header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0');
header('Pragma: no-cache');
// Fixe le type de caract�res utilis�. Sinon par d�faut UTF-8 => pb accents
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

$nomMois = array(1 => 'Janvier', 'F�vrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Ao�t', 'Septembre', 'Octobre', 'Novembre', 'D�cembre');
$nomJours = array(1 => 'L', 'M', 'M', 'J', 'V', 'S', 'D');

list($mPrecedent, $aPrecedent) = fpl_getNewMois($mois, $an, -1);
list($mSuivant, $aSuivant) = fpl_getNewMois($mois, $an, 1);

//_____________________________________________________________________________
//
// Recherches des dates auxquelles on trouve des articles
//_____________________________________________________________________________
// Le tableau $dates contient un index � chacun des jours
// pour lequel il y a un article
$dates = array();
$dateDebut = fpl_makeAMJ($an, $mois, 1);
$dateFin = fpl_makeAMJ($an, $mois, $nbJours);

fp_bdConnecter();	// Ouverture base de donn�es

$sql = "SELECT DISTINCT arDate
		FROM articles
		WHERE arIDBlog = $IDBlog
		AND arDate BETWEEN $dateDebut AND $dateFin
		ORDER BY arDate";

$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

while ($enr = mysqli_fetch_assoc($R)) {	// Boucle de lecture de la s�lection
	$jour = (int) substr($enr['arDate'], -2);
	$dates[$jour] = $enr['arDate'];
}

mysqli_free_result($R);
mysqli_close($GLOBALS['bd']);

//_____________________________________________________________________________
//
// Affichage du calendrier
//_____________________________________________________________________________
// Le calendrier est affich� dans un tableau de 7 colonne et n lignes.
// La premi�re ligne du tableau est compoos�e du nom du mois et de l'ann�e affich�e,
// entour�s par des fl�ches permettant de passer aux mois pr�c�dents ou souvants.
// La deuxi�me ligne est compos�e des 3 premiers caract�res des noms des jours.
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

// La premi�re ligne du calendrier est particuli�re :
// il faut tenir compte des derniers jours du mois pr�c�dent
$nbPrecedent = fpl_getNbJoursMois($mPrecedent, $aPrecedent);

echo '<tr>';

for( $i = 1, $colonne = 1; $i < $premier; $i ++, $colonne ++) {
	echo '<td>&nbsp;</td>';
}

// On affiche maintenant les jours du mois
// $colonne permet de faire une nouvelle ligne quand la semaine est finie
for ($i = 1; $i <= $nbJours; $i ++, $colonne ++) {
	if (! isset($dates[$i])) {
		// Pas d'articles � cette date
		echo '<td>', $i, '</td>';
	} else {
		// Des articles � cette date. Lien sur la page d'affichage des articles
		// Les param�tres du lien sont crypt�s (IDBlog|IDArticle|No Page| Date)
		$url = fp_makeURL('php/articles_voir.php', $IDBlog, 0, 0, $dates[$i]);
		echo '<td><a href="',$url,'">', $i, '</a></td>';
	}

	if ($colonne == 7) {
		echo '</tr><tr>';
		$colonne = 0;
	}
}

// On termine l'affichage de la derni�re ligne si besoin
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
* @param integer		$mois		Num�ro du mois � traiter
* @param integer		$an			Ann�e � traiter
*
* @return integer		Nombre de jours dans le mois
*/
function fpl_getNbJoursMois($mois, $an) {
	//Traitement des mois de 30 jours
	if ($mois == 4 || $mois == 6 || $mois == 9 || $mois == 11) {
		return 30;
	}
	// Si ce n'est pas f�vrier, le mois a 31 jours
	if ($mois != 2) {
		return 31;
	}
	// C'est f�vrier.
	// Si ann�e pas multiple de 4, pas bissextile, 28 jours
	if ($an % 4 != 0) {
		return 28;
	}
	// si ann�e multiple de 100 ou de 400, pas bissextile, 28 jours
	if ($an % 100 == 0 || $an % 400 == 0) {
		return 28;
	}
	return 29;
}
//_____________________________________________________________________________
/**
* Renvoie le num�ro du premier jour du mois 1-Lundi, 7-Dimanche
*
* @param integer	$mois		Num�ro du mois � traiter
* @param integer	$an			Ann�e � traiter
*
* @return integer	Num�ro du premier jour du mois
*/
function fpl_getPremierJour($mois, $an) {
	// Les fonctions date et mktime permettent de trouver
	// le premier jour d'un mois sous la forme anglo-saxonne
	// dans laquelle la semaine commence le dimanche (jour num�ro 0)
	$premier = date('w', mktime(0, 0, 0, $mois, 1, $an));
	// Nous devons adapter le jour trouv� � la forme fran�aise
	// dans laquelle la semaine commence le lundi, et dans laquelle
	// le dicmanche est le jour num�ro 7
	if ($premier == 0) {
		$premier = 7;
	}
	return $premier;
}
//_____________________________________________________________________________
/**
* Ajoute ou retranche ou nombre de mois � une date mois/ann�e
*
* @param integer	$mois		Num�ro du mois de d�part
* @param integer	$an			Ann�e de d�part
* @param integer	$ecart		Nombre de mois � ajouter (positif) ou retrancher (n�gatif)
*
* @return array		Tableau avec le mois et l'ann�e
*/
function fpl_getNewMois($mois, $an, $ecart) {
	$date = date('Ym', mktime(0, 0, 0, $mois + $ecart, 1, $an));
	$mois = intval(substr($date, -2));
	$an = intval(substr($date, 0, -2));
	return array($mois, $an);
}
//_____________________________________________________________________________
/**
* Compose une date au format AMJ � partir de J M A
*
* @param integer	$an			Ann�e
* @param integer	$mois		Num�ro du mois
* @param integer	$jour		Num�ro du jour dans le mois
*
* @return integer	Date au format AAAAMMJJ
*/
function fpl_makeAMJ($an, $mois, $jour) {
	return ($an * 10000) + ($mois * 100) + $jour;
}
?>