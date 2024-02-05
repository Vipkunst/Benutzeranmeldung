<?php

require_once 'Sessionverwaltung.php'; // Pfad zur Sessionverwaltung Klasse anpassen
use App\Auth\Sessionverwaltung;
use App\Auth\Benutzeranmeldung;

$zugriffspruefung = new Zugriffspruefung( [ 'verlangteBenutzergruppen'=>$verlangteBenutzergruppen ?? NULL ]);

class Zugriffspruefung {
	
	public $sessionVerwaltung=NULL;

	public function __construct($params=['verlangteBenutzergruppen' => [1]] ) {

		// Erstellen Sie die Sessionverwaltung-Instanz 
		$this->sessionVerwaltung = new Sessionverwaltung( ['benutzeranmeldung' => ['pdostring' => 'sqlite:../data/auth.db'] ] );
		
		
		$meldung="";
		// Prüfen Sie den Zugriff
		if( !isset($params["verlangteBenutzergruppen"]) ){
			//OK keine Prüfung der Gruppenmitgliedschaft
		}
		else 	if( $params["verlangteBenutzergruppen"] == NULL ){
			//OK keine Prüfung der Gruppenmitgliedschaft
		}
		else if (!$this->sessionVerwaltung->pruefeZugriff($params)) {
		    // Zugriff verweigert
		     if( $this->sessionVerwaltung->getBenutzername() != NULL){     
			$meldung= "Zugriff verweigert. Diese Seite ist nur für Mitglieder der Gruppen '".implode ($this->sessionVerwaltung->getGruppennamen($params["verlangteBenutzergruppen"]),", ")."' zugänglich.";
		    }
		    else{
			//Nicht angemeldet
			$meldung="Bitte melden Sie sich für den Zugriff auf diese Seite an.";
			$meldung.= $this->sessionVerwaltung->zeigeAnmeldeFormular();
		    }
		    
		    
		    
		    
		}
		

		// Wenn der Zugriff erlaubt ist, fahren Sie mit dem restlichen Teil der Seite fort
		if($meldung!="") $this->anmelde_fehler_seite($meldung);		
	}
	
	public function getAbmeldeLinkHref(){
		if($this->sessionVerwaltung==NULL){
			return "";
		}
		return $this->sessionVerwaltung->abmeldeLinkHref() ;
	}
	
	function anmelde_fehler_seite($meldung){
		echo "<!DOCTYPE html>
	<html lang='de'>
	<head>
	    <meta charset='UTF-8'>
	    <title>Admin-Bereich</title>
	</head>
	<body>
	    <h1>Willkommen</h1>
	    <p>$meldung</p>
	    <br>".
	    $this->getAbmeldeLinkHref()
	."</body>
	</html>";
	die();
	}	
}

?>