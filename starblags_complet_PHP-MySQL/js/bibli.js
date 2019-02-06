/**
 * Objet FP.
 * 
 * On crée un objet FP pour ne pas polluer l'espace global JavaScript. On créer
 * ainsi un espace de nom qui permettra d'éviter des collisions avec d'autres
 * bibliothéques éventuelles.
 *
 * Cet objet est écrit en notation littérale, ce qui permet d'émuler le design
 * pattern Singleton.
 */
 
var FP = {
	winPopUp: null,	//Handler fenêtre popup
	//_____________________________________________________________________________	
	/**
	* Ouverture d'une fenêtre popup
	*
	* @param	string	sUrl	Url de la page à afficher dans la fénêtre
	* @param	integer	nLarge	Largeur de la fenêtre (si null : 600 pixels)
	* @param	integer	nHaut	Hauteur de la fenêtre (si null : moitié de l'écran)
	*/
	ouvrePopUp: function(sUrl, nLarge, nHaut) {
		if (sUrl == '' || sUrl == null) {
			return;
		}
		
		this.fermePopUp();	// On ferme le popup si déjà ouvert
		
		// Calcul des options de la fenêtre
		nLarge = nLarge || 700;
		nHaut = nHaut || screen.height/2;
		var sOption = 'scrollbars,resizable,width=' + nLarge 
					+ ',height=' + nHaut
					+ ',left=' + (((screen.width - nLarge) / 2) - 10)
					+ ',top=' + (((screen.height - nHaut) / 2) - 30);
	
		// Ouverture popup	
		this.winPopUp = window.open(sUrl, 'starblagspop', sOption);
		this.winPopUp.focus();
	},
	//_____________________________________________________________________________
	/**
	* Fermeture des fenêtres popup ouvertes
	*/
	fermePopUp: function() {
		if (this.winPopUp != null && !this.winPopUp.closed) {
			this.winPopUp.close();
		}
	},
	//_____________________________________________________________________________
	/**
	* Positionne le curseur dans la première zone de saisie d'un formulaire
	*/
	setCurseur: function() {
		if (document.forms.length == 0) {
			return;
		}
		var aE = document.forms[0].elements;		
		for (var i = 0, E = null; (E = aE[i]); i++) {
			if (E.disabled || E.readOnly) {
				continue;
			}
			if (!E.type) {
				continue;
			}
			var Type = E.type.toLowerCase();
			if (Type == 'hidden' || Type == 'button' || Type == 'submit' || Type == 'reset') {
				continue;
			}
			if (Type.indexOf('select') != -1) {
				continue;
			}
			try {
				E.focus();
			} catch(e) {
				continue;
			}
			return;
		}
	},
	//_____________________________________________________________________________
	/**
	* Change le style d'une ligne de liste quand elle est survolée
	*
	* @param	object	oTR		Objet DOM <TR> survolé
	* @param	bollean	bOver	Indicateur de début (true) ou de fin (false) de survol
	*/
	listeOver: function(oTR, bOver) {
		oTR.className = (bOver) ? 'liste_ligne_over' : 'liste_ligne';
	},
	//_____________________________________________________________________________
	/**
	* Affichage / masquage d'un bloc
	*
	* @param	string	IDBloc	Nom du bloc à traiter
	*/
	switchVisu: function(IDBloc) {
		var B = document.getElementById(IDBloc);
		if (B !== null) {
			B.style.display = (B.style.display == 'block') ? 'none' : 'block';
		}
	},

	//_____________________________________________________________________________
	/**
	* Affichage d'un calendrier avec un repère aux dates de parution des articles.
	*
	* On utilise la technique AJAX :
	* - la demande du calendier est envoyé au serveur sans soumission de formulaire
	* - le contenu du calendrier est généré par PHP puis envoyé à la page
	* - JavaScript récupère le calendrier et l'affiche dans un bloc.
	* La gestion cette technique se fait par un objet.
	*
	* @param	integer	IDBlog	Identifiant du blog contenant les articles
	* @param	integer	AM		Année et mois à afficher (AAAAMM)
	* @param	string	IDBloc	ID du bloc pour l'affichage du calendrier
	*/
	affCalendrier: function(IDBlog, AM, IDBloc) {
		// On switche l'affichage du bloc
		this.switchVisu(IDBloc);
		// Si le bloc est visible, on lance le traitement Ajax
		if (document.getElementById(IDBloc).style.display == 'block') {
			this.calendrier(IDBlog, AM, IDBloc);
		}
	},
	calendrier: function(IDBlog, AM, IDBloc) {
		var Url = 'php/calendrier_ajax.php?a=' + IDBlog + '&b=' + AM + '&c=' + IDBloc;
		if (this.Ajax.getInstance()) {
			this.Ajax.get(Url, IDBloc);
		}
	},

	//_____________________________________________________________________________
	/**
	* Affichage du bloc des tags - réduit ou complet
	*
	* Le nom de blocs est figé : blcTagReduit, blcTagComplet.
	*
	* @param	object	Src	Element sur lequel on a cliqué pour déclencher la fonction
	*/
	affTags: function(Src) {
		this.switchVisu('blcTagReduit');
		this.switchVisu('blcTagComplet');
		if (document.getElementById('blcTagReduit').style.display == 'none') {
			Src.innerHTML = 'Tags [-]';
		} else {
			Src.innerHTML = 'Tags [+]';
		}
	},
	//_____________________________________________________________________________
	/**
	 * Objet Ajax.
	 *
	 * Cet objet est écrit en notation littérale, ce qui permet d'émuler le design
	 * pattern Singleton.
	 * Les fonctionnalités impléméntentées sont réduites par rapport aux capacités
	 * globlale de l'objet XMLHttpRequest :
	 * - on ne prend en charge que des demandes GET
	 * - en mode asynchrone
	 * - on ne gère que l'état de retour 4
	 */
	Ajax: {
		XHR: null,	// Objet XMLHTTPRequest
		RequeteEnCours: false,
		Traitement: null,	// Fonction à exécuter quand la liaison est terminée - etat 4
		
		Erreur: function(ErrNum, ErrTexte) {
			alert('Erreur dans la connexion au serveur Web\n' + ErrNum + ' : ' + ErrTexte);
		},
		
		getInstance: function() {
			if (this.RequeteEnCours) {
				alert('Traitement en cours ...');
				return false;
			}
			if (this.XHR != null) {
				this.RequetEnCoures = false;
				this.XHR.onreadystatechange = function() { };
				this.Traitement = null;
				return true;
			}
		
			if (window.XMLHttpRequest) {
				this.XHR = new XMLHttpRequest();
				return true;
			}
			
			if (!window.ActiveXObject) return false;
			
			var msxmls = new Array('Msxml2.XMLHTTP.5.0', 
									'Msxml2.XMLHTTP.4.0', 
									'Msxml2.XMLHTTP.3.0',
									'Msxml2.XMLHTTP',
									'Microsoft.XMLHTTP');
			
			for (var i = 0; i < msxmls.length; i++) {
				try {
					this.XHR = new ActiveXObject(msxmls[i]);
				} catch (e) {
					this.XHR = null;
				}
			}
		
			return (this.XHR == null);
		},
	
		get: function (sUrl, Traitement) {
			if (this.RequeteEnCours) {
				alert('Traitement en cours ...');
				return false;
			}
			this.Traitement = Traitement;
			this.XHR.open('GET', sUrl, true);
			// Pour que la closure soit correctement effectuée, il faut
			// utiliser une variable et l'appel de fonction ci-après
			var Copie = this;
			this.XHR.onreadystatechange = function() {
				Copie.traiteResultat(Copie);
			}
			this.XHR.send(null);
		},
	
		traiteResultat: function(ObjCopie) {
			if (ObjCopie.XHR.readyState != 4) return;
			if (ObjCopie.XHR.status != 200) {
				ObjCopie.Erreur(ObjCopie.XHR.status, ObjCopie.XHR.statusText);
			} else {
				if (typeof ObjCopie.Traitement == 'function') {
					ObjCopie.Traitement(ObjCopie.XHR.responseText);
				} else {
				 	document.getElementById(ObjCopie.Traitement).innerHTML = ObjCopie.XHR.responseText;
				}
			}
			ObjCopie.RequeteEnCours = false;
			ObjCopie.onreadystatechange = function() { };
			ObjCopie.Traitement = null;
		}
	}

};	// Fin du singleton - espace de nom FP


// Gestionnaires d'événements onload et onunload des pages
if (document.addEventListener) {  // Modéle DOM2
	FP.addEvent = function(Objet, Evt, Fonction) {
		Objet.addEventListener(Evt, Fonction, false);
	};
	FP.delEvent = function(Objet, Evt, Fonction) {
		Objet.removeEventListener(Evt, Fonction, false);
	};

} else if (document.attachEvent) {  // Modéle IE5+
	FP.addEvent = function(Objet, Evt, Fonction) {
		Objet.attachEvent('on' + Evt, Fonction);
	};
	FP.delEvent = function(Objet, Evt, Fonction) {
		Objet.detachEvent('on' + Evt, Fonction);
	};
	
} else {	// On ne gère pas IE4 et autres NS4
	FP.addEvent = FP.delEvent = function() {
		return null;
	};
}

// Gestionnaires d'événements onload et onunload des pages
FP.addEvent(window, 'load', FP.setCurseur);
FP.addEvent(window, 'unload', FP.fermePopUp);