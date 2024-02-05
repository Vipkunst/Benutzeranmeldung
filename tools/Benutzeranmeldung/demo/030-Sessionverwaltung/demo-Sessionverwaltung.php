<?php
//$verlangteBenutzergruppen = [1,2,4,8];
require_once '../../src/zugriffpruefung.php'; // Pfad zur Sessionverwaltung Klasse anpassen


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


<h2>Willkommen</h2>

<ul>
<li><a href='demo-Sessionverwaltung.php'>demo-Sessionverwaltung ohne Anmeldung.php</a></lI>
<li><a href='demo-Sessionverwaltung_nur_angemeldet.php'>demo-Sessionverwaltung_nur_angemeldet.php</a></lI>
<li><a href='demo-Sessionverwaltung_nur_Moderatoren.php'>demo-Sessionverwaltung_nur_Moderatoren.php</a></lI>
<li><a href='demo-Sessionverwaltung_nur_Administratoren.php'>demo-Sessionverwaltung_nur_Administratoren.php</a></lI>
</ul>
<br><br>
Angemeldet als Benutzer <b><?php echo $zugriffspruefung->sessionVerwaltung->getBenutzername(); ?></b>.
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?php echo $zugriffspruefung->getAbmeldeLinkHref(); ?>
</body>
</html>