<?php
//_____________________________________________________________________________
//
//	BIBLIOTHEQUE DE FONCTIONS POUR INTEGRITE REFERENTIELLE
//		DES SUPPRESSIONS D'ELEMENTS DE LA BASE DE DONNEES
//_____________________________________________________________________________

//_____________________________________________________________________________
/**
 * Suppression d'un blog et des éléments qui lui sont rattachés
 * 
 * @param	integer	$IDBlog		Clé du blog à supprimer
 */
function fp_delete_blog($IDBlog) {
	// On récupère les ID des articles attachés au blog à supprimer
	// et on supprime les articles du blog un à un
	$sql = "SELECT arID FROM articles WHERE arIDBlog = $IDBlog";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	while ($enr = mysqli_fetch_assoc($R)) {
		fp_delete_article($enr['arID']);
	}
	
	// Suppressions des demandes d'alerte
	$sql = "DELETE FROM alertes_lecteurs WHERE alIDBlog = $IDBlog";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
		
	// Suppression des visites faite au blog
	$sql = "DELETE FROM blogs_visites WHERE bvIDBlog = $IDBlog";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	
	// Suppression du blog
	$sql = "DELETE FROM blogs WHERE blID = $IDBlog";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
}
//_____________________________________________________________________________
/**
 * Suppression d'un article et des éléments qui lui sont rattachés
 * Remarque : les photos et images téléchargées ne sont pas supprimées.
 * 
 * @param	integer	$IDArticle	Clé de l'article à supprimer
 */
function fp_delete_article($IDArticle) {
	// Suppression des notes données à l'article
	$sql = "DELETE FROM articles_notes WHERE anIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	
	// Suppression des commentaires
	$sql = "DELETE FROM commentaires WHERE coIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
	
	// Suppression des photos
	$sql = "DELETE FROM photos WHERE phIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête

	// Suppression des alertes faites
	$sql = "DELETE FROM alertes_faites WHERE afIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête

	// Suppression des tags liés
	$sql = "DELETE FROM tags_articles WHERE taIDArticle = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête
			
	// Suppression de l'article
	$sql = "DELETE FROM articles WHERE arID = $IDArticle";
	$R = mysqli_query($GLOBALS['bd'], $sql) or fp_bdErreur($sql);  // Exécution requête	
}
?>