<?php

require_once '../../src/benutzeranmeldung.php'; // Pfad zur Klasse anpassen

use App\Auth\Benutzeranmeldung;

// Erstellen Sie eine Instanz von Benutzeranmeldung mit dem PDO-Verbindungsstring
$anmeldeSystem = new Benutzeranmeldung(['pdostring' => 'sqlite:../data/auth.db']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $benutzername = $_POST['benutzername'] ?? '';
    $passwort = $_POST['passwort'] ?? '';
    // Einfache Validierung der Eingaben
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $meldung = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';	
    } elseif (empty($benutzername) || empty($passwort)) {
        $meldung = 'Bitte füllen Sie alle Felder aus.';	
    } else {
        // Versuch, den neuen Benutzer zu registrieren
        if ($anmeldeSystem->registriereBenutzer($email, $benutzername, $passwort)) {
            $meldung = 'Registrierung erfolgreich! Sie können sich jetzt anmelden.';	    
        } else {
            $meldung = 'Registrierung fehlgeschlagen. Der Benutzername oder die E-Mail-Adresse könnte bereits verwendet werden.';  
        }
    }
}
$meldung="";
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrierung neuer Benutzer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .meldung { color: red; }
    </style>
</head>
<body>
    <h1>Registrierung neuer Benutzer</h1>

    <?php if ($meldung != '') echo "<p class='meldung'>".$meldung."</p>"; ?>
        


    <form action="" method="post">
        <div>
            <label for="email">E-Mail:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="benutzername">Benutzername:</label>
            <input type="text" id="benutzername" name="benutzername" required>
        </div>
        <div>
            <label for="passwort">Passwort:</label>
            <input type="password" id="passwort" name="passwort" required>
        </div>
        <div>
            <button type="submit">Registrieren</button>
        </div>
    </form>
</body>
</html>