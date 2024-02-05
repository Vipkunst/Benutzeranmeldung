<?php

namespace App\Auth;
use App\Auth\Benutzeranmeldung;

class Sessionverwaltung {
    private $benutzeranmeldungParams;
    private $benutzeranmeldung;
    private $errno = 1;

    private $benutzername = NULL;
    private $benutzergruppe = NULL;
    private $ipaddr = NULL;

    public static $errorcodes = [
        0 => 'OK',
        1 => 'Unbekannter Fehler',
        2 => 'Keine aktive Anmeldung vorhanden',
	3 => 'IP-Adresse passt nicht zur aktiven Session',
        4 => 'Invalid Argument',
	5 => 'Keine ausreichende Berechtigung für diesen Zugriff',
	6 => 'Fehler beim Erstellen der Benutzeranmeldung',
    ];

    public function __construct($params) {
        if (!isset($params['benutzeranmeldung'])) {
            $this->setErrno(4);
            throw new \InvalidArgumentException('Benutzeranmeldung-Objekt erforderlich.');
        }

        $this->benutzeranmeldungParams = $params['benutzeranmeldung'];

        session_start(); // Startet oder setzt die aktuelle Session fort

        // Prüfe, ob Session-Daten gesetzt sind
        if (isset($_SESSION['benutzername'], $_SESSION['benutzergruppe'], $_SESSION['ipaddr'])) {
            $this->benutzername = $_SESSION['benutzername'];
            $this->benutzergruppe = $_SESSION['benutzergruppe'];
            $this->ipaddr = $_SESSION['ipaddr'];
            $this->setErrno(0); // OK
        }
	
	$this->pruefeAbmeldung();
    }

    private function setErrno($errno) {
        $this->errno = $errno;
    }

    public function getErrno() {
        return $this->errno;
    }

public function getErrmsg() {
    // Überprüft, ob ein Eintrag für den aktuellen Fehlercode existiert und gibt die entsprechende Nachricht zurück.
    // Gibt 'Unbekannter Fehler' zurück, wenn der Fehlercode nicht definiert ist.
    if (array_key_exists($this->errno, self::$errorcodes)) {
        return self::$errorcodes[$this->errno];
    } else {
        return self::$errorcodes[1]; // Rückgabe 'Unbekannter Fehler', falls der Fehlercode nicht existiert.
    }
}

public function getGruppennamen($gruppenids){
	$this->erstelleBenutzeranmeldung();
	return $this->benutzeranmeldung->getGruppennamen($gruppenids);
}

public function erstelleBenutzeranmeldung(){
	if( $this->benutzeranmeldung !== NULL ){
		return true;
	}
	
	require_once '../../src/benutzeranmeldung.php'; // Pfad zur Klasse anpassen


	$this->benutzeranmeldung = new Benutzeranmeldung( $this->benutzeranmeldungParams);	

	if( $this->benutzeranmeldung->getErrno() === 0){
		$this->setErrno(0);
		return true;
	}
	else{
		$this->setErrno(6); //Fehler beim Erstellen der Benutzeranmeldung
		return false;
	}
}

public function getBenutzername(){
	return $this->benutzername;
}

public function zeigeAnmeldeFormular() {
	$this->erstelleBenutzeranmeldung();
	return $this->benutzeranmeldung->erstelleAnmeldeFormular();
}

public function getAlleBenutzer() {
	$this->erstelleBenutzeranmeldung();
	return $this->benutzeranmeldung->getAlleBenutzer();
}

public function loescheBenutzer($id) {
	$this->erstelleBenutzeranmeldung();
	return $this->benutzeranmeldung->loescheBenutzer($id);
}




    public function pruefeZugriff($params) {
	if( $this->benutzername!=NULL){
		//OK
	}
	//Versuche Benutzeranmeldung zu erzeugen
	else if( $this->erstelleBenutzeranmeldung() != true){
		 $this->setErrno(6); // Fehler beim Erstellen der Benutzeranmeldung
	}
	else if( $this->benutzeranmeldung-> pruefeAnmeldeFormular() == TRUE ){
		$this->benutzername = $this->benutzeranmeldung->getAktivenBenutzer() ;
		$_SESSION['benutzername'] = $this->benutzername;
		$this->benutzergruppe = $this->benutzeranmeldung->getBenutzergruppe($this->benutzername );
		$_SESSION['benutzergruppe'] =  $this->benutzergruppe;
		$this->ipaddr = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? NULL;
		$_SESSION['ipaddr']	=$this->ipaddr;
	}
    
        if ($this->errno != 0) {
            // Keine aktive Session vorhanden
            $this->setErrno(2);
            return false;
        }

        // Prüfe, ob die aufrufende IP-Adresse übereinstimmt, um Session-Hijacking zu vermeiden
       // if ($_SERVER['REMOTE_ADDR'] != $this->ipaddr) {
        //    $this->setErrno(3); // IP-Adresse passt nicht zur aktiven Session
         //   return false;
        //}

        // Prüfe, ob die Benutzergruppe die erforderlichen Rechte hat
        if (!isset($params['verlangteBenutzergruppen']) || !in_array($this->benutzergruppe, $params['verlangteBenutzergruppen'])) {
            $this->setErrno(5); // Keine ausreichende Berechtigung für diesen Zugriff
            return false;
        }

        $this->setErrno(0); // OK
        return true;
    }


	public function abmeldeLinkHref($linkname="abmelden") {
		if ( $this->benutzername != NULL ){
			return "<a href='".$this->abmeldeLink()."'>$linkname</a>";
		}
		else{
			return "";
		}
	}
	
	public function abmeldeLink() {
		return $this->get_current_url()."?abmelden";
	}

	public function pruefeAbmeldung() {
		if( isset($_GET["abmelden"]) ){
			$this->abmelden();
		}
	}

	public function abmelden() {
		// Überprüfen, ob eine Session gestartet wurde
		if (session_status() == PHP_SESSION_ACTIVE) {
			// Löschen aller Session-Variablen
			$_SESSION = [];

			// Falls die Session-Cookies gelöscht werden sollen
			if (ini_get("session.use_cookies")) {
			    $params = session_get_cookie_params();
			    setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			    );
			}

			// Die Session zerstören
			session_destroy();

			$this->benutzername = NULL;
			$this->benutzergruppe = NULL;
			$this->ipaddr = NULL;
		}
	}    
	
	function get_current_url() {
	    // Initialisiere $url mit false für den Fall, dass keine URL ermittelt werden kann.
	    $url = false;

	    // Prüfe, ob das Skript in einem Webserver-Kontext mit einer definierten Server-Adresse läuft.
	    if (isset($_SERVER['SERVER_ADDR'])) {
		// Bestimme, ob die Verbindung über HTTPS läuft.
		$is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
		// Setze das Protokoll entsprechend: 'https' für sichere, 'http' für unsichere Verbindungen.
		$protocol = $is_https ? 'https' : 'http';

		// Ermittle den Hostname entweder aus HTTP_HOST (falls vorhanden) oder SERVER_ADDR.
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_ADDR'];

		// Ermittle den Port aus SERVER_PORT.
		$port = $_SERVER['SERVER_PORT'];

		// Ermittle den Pfad und den Query-String der Anfrage.
		$path_query = $_SERVER['REQUEST_URI'];

		// Prüfe, ob der Hostname bereits einen Port enthält (z.B. "localhost:8080").
		$hostHasPort = preg_match('/:\d+$/', $host);

		// Füge den Port nur zur URL hinzu, wenn er nicht der Standardport für das Protokoll ist und der Hostname keinen Port enthält.
		$portPart = '';
		if (!$hostHasPort) {
		    if ($is_https && $port != 443) {
			$portPart = ':' . $port;
		    } elseif (!$is_https && $port != 80) {
			$portPart = ':' . $port;
		    }
		}

		// Setze die URL zusammen.
		$url = sprintf('%s://%s%s%s',
		    $protocol,
		    $host,
		    $portPart,
		    $path_query
		);
	    }

	    // Gebe die ermittelte URL oder false zurück, falls keine URL ermittelt werden konnte.
	    return $url;
	}

}
?>