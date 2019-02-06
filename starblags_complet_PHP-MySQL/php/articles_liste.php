<?php
//_____________________________________________________________________________
/**
 * Liste des articles d'un blog.
 * 
 * Cette page recherche les articles d'un blog et les affiche sous la forme
 * d'une listes. Chaque ligne est clickable pour donner acc�s � l'article list�
 * de fa�on � pouvoir le modifier.
 * 
 * L'identifiant du blog dont les articles sont � lister est stock� dans
 * la variable de session $_SESSION['IDBlog']
 */
//_____________________________________________________________________________
ob_start();			// Buff�risation des sorties
session_start();	// d�marrage session

//_____________________________________________________________________________
//
// Initialisations
//_____________________________________________________________________________
include_once('bibli.php');

fp_verifSession();		// V�rification session utilisateur

$IDBlog = $_SESSION['IDBlog'];

fp_bdConnecter();	// Ouverture base de donn�es

$sql = "SELECT *
		FROM articles
		WHERE arIDBlog = $IDBlog 
		ORDER BY arID";
		
$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	
//_____________________________________________________________________________
//
// Affichage de la page
//_____________________________________________________________________________
				
//	Affichage du haut de la page
$remplace = array();
$remplace['@_TITLE_@'] = 'StarBlags - Articles';
$remplace['@_LIENS_@'] = fp_htmlBandeau(LIEN_MB_NA);
$remplace['@_TITRE_@'] = 'Les articles de mon blog ...';

// Lecture du modele debut_prive.html, remplacement motifs et affichage
fp_modeleTraite('debut_prive', $remplace);

echo '<table cellspacing="2" cellpadding="4" class="majTable">',
		'<tr>',
			'<td class="liste_titre">Titre</td>',
			'<td class="liste_titre">Date cr�ation</td>',
			'<td class="liste_titre">Heure</td>',
		'</tr>';
	
// Boucle de lecture des rang�es s�lectionn�es
// et affichage des lignes articles.
while ($enr = mysqli_fetch_assoc($R)) {
	// Quand la souris passe au dessus d'une ligne de la liste
	// on change la couleur de fond de la ligne et le curseur utilis�.
	// On r�alise le changelent avec un gestionnaire d'�venement javascript
	// qui change la classe de style affect�e au tag <tr>.
	// Quand on clique sur la ligne, c'est encore javascript qui
	// redirige sur la page de mise � jour article.
	
	// Les param�tres du lien sont crypt�s (IDArticle)
	$url = fp_makeURL('article_maj.php', $enr['arID']);
	
	echo '<tr onmouseover="FP.listeOver(this, true)" 
			onmouseout="FP.listeOver(this, false)" 
			onclick="location.replace(\'', $url, '\')">';
			
	echo 	'<td>', fp_protectHTML($enr['arTitre']), '</td>',
			'<td>', fp_amjJma($enr['arDate']), '</td>',
			'<td>', fp_protectHTML($enr['arHeure']), '</td>',
		'</tr>';
}

//	Fin de page
echo '</table>';

include('../modeles/fin.html');  // Fin de la page

ob_end_flush();  // Fermeture du buffer => envoi du contenu au navigateur
?>