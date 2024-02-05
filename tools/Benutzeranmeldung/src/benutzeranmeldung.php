<?php
/**
 * @file benutzeranmeldung.php
 * Klasse für die Benutzeranmeldung, Registrierung und Verwaltung von Benutzergruppen innerhalb des App\Auth Namespace.
 */

namespace App\Auth;

use PDO;
use PDOException;

class Benutzeranmeldung {
    private $db;
    private $errno = 1;
    
    private $aktiverbenutzername = NULL;
    private $aktivebenutzergruppe = NULL;


    public static $errorcodes = [
        0 => 'OK',
        1 => 'Unbekannter Fehler',
        2 => 'Fehler beim Initialisieren der Datenbank',
	3 => 'Benutzername oder E-Mail werden bereits verwendet',
	4 => 'Invalid Argument',
	5 => 'Kann neuen Benutzer nicht in die Datenbank eintragen',
	6 => 'Fehler beim Abfragen der Benutzer aus der Datenbank',
	7 => 'Fehler beim Löschen des Benutzers',
    ];

public function __construct($params) {
    if (!isset($params['pdostring'])) {
	$this->setErrno(4);
        throw new \InvalidArgumentException('Der erforderliche "pdostring" fehlt in den Parametern beim Erstellen von Benutzeranmeldung.');
    }
    try {
        $this->db = new \PDO($params['pdostring']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $retval = $this->erstelleDatenbank();
    } catch (PDOException $e) {
	$this->setErrno(2);
        // Fehlerbehandlung, z.B. eine Log-Nachricht hinterlassen
        error_log('Datenbankverbindung ('.$params['pdostring'].') fehlgeschlagen: ' . $e->getMessage());
        throw $e; // Optional: Weitergeben des Fehlers nach oben
    }
    
    if($retval!=false){
	 $this->setErrno(0);
    }
    
}

    private function setErrno($errno) {
	 $this->errno = $errno;
    }

    public function getErrno() {
	return $this->errno;
    }

private function erstelleDatenbank() {
    // Prüfe, ob die Tabelle bereits existiert
    $tabelleExistiert = $this->pruefeTabelleExistiert('benutzer');
    
    // Wenn die Tabelle nicht existiert, erstelle sie und füge den Admin-Benutzer hinzu
    if (!$tabelleExistiert) {
        $sqlTable = "CREATE TABLE IF NOT EXISTS benutzer (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            benutzername TEXT NOT NULL UNIQUE,
            passwort TEXT NOT NULL,
            erstellt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            letzteAnmeldung TIMESTAMP,
            gruppe INTEGER DEFAULT 1
        )";

        if ($this->db->exec($sqlTable) === false) {
            $this->setErrno(3);
            return false;
        }

        // Registriere den Admin-Benutzer, da die Tabelle gerade erstellt wurde
        $this->registriereBenutzer('admin@local', 'admin', 'Admin', 8);
    }

    return true;
}

private function pruefeTabelleExistiert($tableName) {
    try {
        $result = $this->db->query("SELECT 1 FROM $tableName LIMIT 1");
    } catch (PDOException $e) {
        // Die Tabelle existiert nicht
        return false;
    }

    // Die Tabelle existiert
    return $result !== false;
}

	public function registriereBenutzer($email, $benutzername, $passwort, $gruppe=1) {
	    // Überprüfen, ob der Benutzername oder die E-Mail bereits verwendet wird
	    $sql = "SELECT 1 FROM benutzer WHERE benutzername = ? OR email = ?";
	    $stmt = $this->db->prepare($sql);
	    $stmt->execute([$benutzername, $email]);
	    if ($stmt->fetch()) {    
		// E-Mail oder Benutzername bereits vergeben
		$this->setErrno(3);
		return false;
	    }

	    try {
		$passwortHash = password_hash($passwort, PASSWORD_DEFAULT);
		$sql = "INSERT INTO benutzer (email, benutzername, passwort, gruppe) VALUES (?, ?, ?, ?)";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([$email, $benutzername, $passwortHash, $gruppe]);
		return true;
	    } catch (PDOException $e) {
		$this->setErrno(5);
		// Loggen Sie den Fehler für die Fehlersuche
		error_log('Fehler bei der Registrierung: ' . $e->getMessage());
		return false;
	    }
	    
	    return true;
	}

    public function pruefeAnmeldeFormular() {
	$benutzername = $_POST["benutzername"] ?? NULL;
	$passwort = $_POST["passwort"] ?? NULL;
	if( $benutzername == NULL OR $passwort == NULL){
		return false;
	}
        return $this->pruefeAnmeldung($benutzername, $passwort);
    }

    public function pruefeAnmeldung($benutzername, $passwort) {
        $sql = "SELECT passwort FROM benutzer WHERE benutzername = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$benutzername]);
        $benutzer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($benutzer && password_verify($passwort, $benutzer['passwort'])) {
	    $this->aktiverbenutzername = $benutzername;
            $this->aktualisiereLetzteAnmeldung($benutzername);
            return true;
        }
        return false;
    }


    private function aktualisiereLetzteAnmeldung($benutzername) {
        $sql = "UPDATE benutzer SET letzteAnmeldung = CURRENT_TIMESTAMP WHERE benutzername = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$benutzername]);
    }

    public function erstelleAnmeldeFormular() {
        return '<form method="POST">
                    <label for="benutzername">Benutzername:</label>
                    <input type="text" name="benutzername" id="benutzername" required>
                    <label for="passwort">Passwort:</label>
                    <input type="password" name="passwort" id="passwort" required>
                    <input type="submit" value="Anmelden">
                </form>';
    }
    
public function getAktivenBenutzer(){
	return $this->aktiverbenutzername;
}

public function getBenutzergruppe($benutzername) {
    $sql = "SELECT gruppe FROM benutzer WHERE benutzername = ?";
    $stmt = $this->db->prepare($sql);
    
    // Führen Sie die Abfrage aus und binden Sie den Benutzernamen an den Platzhalter
    $stmt->execute( array($benutzername) );
    
    // Holt das Ergebnis der Abfrage
    $ergebnis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Überprüfen Sie, ob ein Ergebnis zurückgegeben wurde und geben Sie die Benutzergruppe zurück
    if ($ergebnis) {
        return $ergebnis['gruppe'];
    } else {
        // Keine Daten gefunden, geben Sie einen Fehler oder null zurück
        return null;
    }
}

    public function weiseBenutzergruppeZu($benutzername, $gruppe) {
        $sql = "UPDATE benutzer SET gruppe = ? WHERE benutzername = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$gruppe, $benutzername]);
    }
    
	public function getGruppennamen($gruppenids) {
	    $gruppennamen = [];
	    
	    if( !is_array($gruppenids)){
		return "";
	    }
	    
	    // Durchlaufe jede übergebene Gruppen-ID
	    foreach ($gruppenids as $id) {
		// Prüfe, ob die ID in den definierten Gruppen existiert
		if (isset(self::$gruppen[$id])) {
		    // Füge den Gruppennamen der ID zum Ergebnisarray hinzu
		    $gruppennamen[] = self::$gruppen[$id];
		} else {
		    // Optional: Behandle den Fall, dass die ID nicht gefunden wird
		    // Zum Beispiel: $gruppennamen[] = "Unbekannt";
		}
	    }

	    // Gebe das Array mit den Gruppennamen zurück
	    return $gruppennamen;
	}

public function getAlleBenutzer() {
    try {
        $sql = "SELECT id, benutzername, email, gruppe FROM benutzer";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Fehler beim Abrufen aller Benutzer: ' . $e->getMessage());
	$this->setErrno(6); //Fehler beim Abfragen der Benutzer aus der Datenbank
        return false;
    }
}

public function loescheBenutzer($id) {
    try {
        $sql = "DELETE FROM benutzer WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return true;
    } catch (PDOException $e) {
        error_log('Fehler beim Löschen des Benutzers: ' . $e->getMessage());
	$this->setErrno(7); //Fehler beim Löschen des Benutzers
        return false;
    }
}

    public static $gruppen = [
        1 => 'Leser',
        2 => 'Schreiber',
        4 => 'Moderatoren',
        8 => 'Administratoren',
    ];
}
?>