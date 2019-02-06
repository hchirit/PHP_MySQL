<?php


class Livre{
    
    private $titre;
    private $auteur;
    private $pages;
    private  $prix;
    private   $cat;
    
    
    public function __construct($titres='',$auteur='',$pages=0,$prix=0,$cat=''){
        $this->titre=$titres;
        if($pages>0 && is_integer($pages)){
            $this->pages=$pages;
        }
        $this->auteur=$auteur;
        if($prix>0){
            $this->prix=$prix;
        }
        
        if($cat=="language"||$cat=="systeme"||$cat=="logiciel"||$cat=="autre")
            //echo $cat;echo'</br>';
            $this->cat=$cat;
        
        
    }
    
   

    public function decrire(){
      
        echo $this->titre; echo'</br>';
         echo    $this->auteur; echo'</br>';
         echo        $this->pages;echo'</br>';
         echo       $this->prix;echo'</br>';
          echo      $this->cat;echo'</br>';
    }
    
    
    
    
}










$livre = new Livre('Beginning PHP 5','boyle',840,41.70,'language');

//$livre->decrire();







function xyz($a,$b,$c){

    for($i=3,$iMax=func_num_args(),$r='';$i<$iMax;$i++){
        $d=explode('=',func_get_arg($i));
        print_r($d);
        $e=($b === $d[1])?'checked>' : '>';
        $r.="<input type='radio' name='$a' value='{$d[1]}' $e {$d[0]}";
    }
    return $c.$r;
}





class Guitare{
    private $modele;
    private $prix;
    private $cordes;
    private $remise;
    
    public function __construct($modele,$prix,$cordes,$remise){
        $this->modele=$modele;
        $this->prix=$prix;
        $this->cordes=$cordes;
        $this->remise=$remise;
    }
    
    public function decrire(){
        
        $c=$this->cordes;
        $r=$this->remise;
        $p=$this->prix;
        if($this->cordes<6 || $this->cordes>12 || 
           $this->remise > $this->prix || $this->prix<0)
        {
            echo 'initialisation non valide';
            exit();
        }
        
         echo $this->modele,'<br>',
              $this->prix,'<br>',
              $this->cordes,'<br>',
              $this->remise,'<br>';
        
    }
    
}


$guitare= new Guitare('Guild D120',639,6,4.25);

$guitare->decrire();









/*
<?php
ob_start();
session_start();
$_SESSION['idAuteur'] = 0;

require('bib_params.php');
require('bib_fonctions.php');

htmlDebut('Liste auteurs', 'bd.css');

$bd = bdConnecter();

$where = $nom = '';
$position = 0;

if (isset($_POST['btnChercher'])) {
	// Si on vient de la page de recherche, on récupère
	// les critères de recherche, on compose la clause
	// WHERE avec LIKE et on la stocke dans une variable
	// de session pour pouvoir la réutiliser.
	if (!estEntier($_POST['radNom'])) {
		header('Location: auteurs_cherche.php');
		exit();	//==> FIN piratage ?
	}

	$position = (int) $_POST['radNom'];
	if (!estEntre($position, 1, 3)) {
		header('Location: auteurs_cherche.php');
		exit();	//==> FIN piratage ?
	}

	$nom = trim($_POST['txtNom']);

	if ($nom != '') {
		$nom = mysqli_real_escape_string($bd, $nom);
		if ($position == 1) {
			$where = "WHERE auNom LIKE '$nom%'";
		} elseif ($position == 2) {
			$where = "WHERE auNom LIKE '%$nom%'";
		} else {
			$where = "WHERE auNom LIKE '%$nom'";
		}
	}

	$_SESSION['where'] = $where;

} elseif (isset($_POST['btnListe'])) {
	// Si on vient le page de mise à jour, on récupère la
	// variable de session pour refaire le select de liste
	$where = $_SESSION['where'];

} else {
	// Si on arrive ici c'est que l'utilisateur n'est pas
	// passé par un des chemins autorisés. On le renvoie
	// sur la page de recherche.
	header('Location: auteurs_cherche.php');
	exit();	//==> FIN piratage ?
}

//-- Requête ----------------------------------------
$sql = "SELECT auID, auNom, auPrenom, auPays
		FROM auteurs
		$where
		ORDER BY auNom, auPrenom";

$r = mysqli_query($bd, $sql) or bdErreur($bd, $sql);

//-- Traitement -------------------------------------
htmlTable(array('Nom', 'Prénom', 'Pays'), 'tab-bd');

while ($enr = mysqli_fetch_assoc($r)) {
	htmlProteger($enr);

	$id = crypterURl($enr['auID']);
	$enr['auNom'] = '<a href="auteurs_maj.php?x='.$id.'">'
						.$enr['auNom'].'</a>';
	unset($enr['auID']);

	htmlLigne($enr);
}

echo '</table>';

//-- Boutons ----------------------------------------
echo '<form method="POST" class="maj" ',
		 	'action="auteurs_cherche.php">',
		'<p class="pagination">',
		'<input type="submit" value="Ajouter" ',
			'name="btnNouveau" formaction="auteurs_maj.php">',
		'<input type="submit" value="Recherche" ',
			'name="btnChercher">',
		'</p>',
	'</form>';

//-- Déconnexion ------------------------------------
mysqli_free_result($r);
mysqli_close($bd);

htmlFin();
?>*/
































?>