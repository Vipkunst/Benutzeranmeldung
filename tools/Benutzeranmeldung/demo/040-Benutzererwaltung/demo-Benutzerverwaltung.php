<?php
$verlangteBenutzergruppen = [8];
require_once '../../src/zugriffpruefung.php'; // Pfad zur Sessionverwaltung Klasse anpassen

$msg="";
	if( !isset( $_GET["funktion"]) OR !isset( $_GET["id"])  ){
		//Nichts zu tun
	}
	else if( $_GET["funktion"]=="loeschen"){
		//Lösche den Benutzer
		If( $zugriffspruefung->sessionVerwaltung->loescheBenutzer($_GET["id"]) ){
			$msg= "Benutzer gelöscht<br>";
		}
		else{
			$msg= "Fehler beim Löschen des Benutzer<br>";
		}
	}

$alleBenutzer = $zugriffspruefung->sessionVerwaltung->getAlleBenutzer();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzerliste</title>
    <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
        }
    </style>
</head>
<body>

<?php echo $msg ?>

<h2>Benutzerliste</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Benutzername</th>
        <th>Email</th>
        <th>Gruppe</th>
        <th>Aktionen</th>
    </tr>
    <?php foreach ($alleBenutzer as $benutzer): ?>
        <tr>
            <td><?= htmlspecialchars($benutzer['id']) ?></td>
            <td><?= htmlspecialchars($benutzer['benutzername']) ?></td>
            <td><?= htmlspecialchars($benutzer['email']) ?></td>
            <td><?= htmlspecialchars($zugriffspruefung->sessionVerwaltung->getGruppennamen([$benutzer['gruppe']])[0]) ?></td>
            <td>
                <a href="?funktion=bearbeiten&id=<?= $benutzer['id'] ?>">Bearbeiten</a> |
                <a href="?funktion=loeschen&id=<?= $benutzer['id'] ?>" onclick="return confirm('Sind Sie sicher?');">Löschen</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<br><br>
Angemeldet als Benutzer <b><?php echo $zugriffspruefung->sessionVerwaltung->getBenutzername(); ?></b>.
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?php echo $zugriffspruefung->getAbmeldeLinkHref(); ?>
</body>
</html>