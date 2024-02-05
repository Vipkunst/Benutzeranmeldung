<?php

require_once '../../src/benutzeranmeldung.php'; // Pfad zur Klasse anpassen

use App\Auth\Benutzeranmeldung;

$anmeldeSystem = new Benutzeranmeldung(['pdostring' => 'sqlite:../data/auth.db']);

$meldung = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $benutzername = $_POST['benutzername'] ?? '';
    $passwort = $_POST['passwort'] ?? '';

    if ($anmeldeSystem->pruefeAnmeldeFormular($benutzername, $passwort)) {
        $meldung = 'Anmeldung erfolgreich!';
        // Hier könnten Sie weitere Aktionen durchführen, z.B. eine Session starten
    } else {
        $meldung = 'Anmeldung fehlgeschlagen. Bitte überprüfen Sie Ihre Eingaben.';
    }
}



?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzeranmeldung</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .meldung { color: red; }
    </style>
</head>
<body>
    <h1>Benutzeranmeldung</h1>

    <?php if ($meldung !== ''): ?>
        <p class="meldung"><?= htmlspecialchars($meldung) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="benutzername">Benutzername:</label>
        <input type="text" name="benutzername" id="benutzername" required>
        <label for="passwort">Passwort:</label>
        <input type="password" name="passwort" id="passwort" required>
        <input type="submit" value="Anmelden">
    </form>
</body>
</html>
