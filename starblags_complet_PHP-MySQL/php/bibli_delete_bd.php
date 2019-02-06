<?php
//_____________________________________________________________________________
//
//	BIBLIOTHEQUE DE FONCTIONS POUR INTEGRITE REFERENTIELLE
//		DES SUPPRESSIONS D'ELEMENTS DE LA BASE DE DONNEES
//_____________________________________________________________________________

//_____________________________________________________________________________
/**
 * Suppression d'un blog et des �l�ments qui lui sont rattach�s
 * 
 * @param	integer	$IDBlog		Cl� du blog � supprimer
 */
function fp_delete_blog($IDBlog) {
	// On r�cup�re les ID des articles attach�s au blog � supprimer
	// et on supprime les articles du blog un � un
	$sql = "SELECT arID FROM articles WHERE arIDBlog = $IDBlog";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	while ($enr = mysqli_fetch_assoc($R)) {
		fp_delete_article($enr['arID']);
	}
	
	// Suppressions des demandes d'alerte
	$sql = "DELETE FROM alertes_lecteurs WHERE alIDBlog = $IDBlog";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
		
	// Suppression des visites faite au blog
	$sql = "DELETE FROM blogs_visites WHERE bvIDBlog = $IDBlog";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	
	// Suppression du blog
	$sql = "DELETE FROM blogs WHERE blID = $IDBlog";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
}
//_____________________________________________________________________________
/**
 * Suppression d'un article et des �l�ments qui lui sont rattach�s
 * Remarque : les photos et images t�l�charg�es ne sont pas supprim�es.
 * 
 * @param	integer	$IDArticle	Cl� de l'article � supprimer
 */
function fp_delete_article($IDArticle) {
	// Suppression des notes donn�es � l'article
	$sql = "DELETE FROM articles_notes WHERE anIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	
	// Suppression des commentaires
	$sql = "DELETE FROM commentaires WHERE coIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
	
	// Suppression des photos
	$sql = "DELETE FROM photos WHERE phIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

	// Suppression des alertes faites
	$sql = "DELETE FROM alertes_faites WHERE afIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te

	// Suppression des tags li�s
	$sql = "DELETE FROM tags_articles WHERE taIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te
			
	// Suppression de l'article
	$sql = "DELETE FROM articles WHERE arID = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Ex�cution requ�te	
}
?>