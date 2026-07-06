# bookingreportlink — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/bookingreportlink.php` · **LOC:** 103 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`bookingreportlink` ist ein Platzhalter (`extends \mod_booking\placeholders\placeholder_base`), der `[bookingreportlink]` durch einen HTML-Link auf den Buchungsreport (`/mod/booking/report.php?id=<cmid>&optionid=<optionid>`) ersetzt — i.d.R. fuer Lehrende/Manager gedacht. Keine eigene Persistenz; nutzt den statischen Request-Cache `placeholders_info::$placeholders`. Im Gegensatz zu den anderen Link-Platzhaltern wird hier **kein** `singleton_service`-Settings-Load benoetigt; der Link wird direkt aus den Parametern gebaut. Kollaborateure: `moodle_url`/`html_writer`, `placeholders_info`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Baut den Report-Link. Voraussetzung sind **beide** Parameter `$cmid` und `$optionid` (sonst Fehlerstring). Cache-Key `"$classname-$optionid"`; bei Treffer Sofort-Return. Sonst `moodle_url('/mod/booking/report.php', ['id' => $cmid, 'optionid' => $optionid])` als `html_writer::link`.
- **Seiteneffekte:** Schreibt Ergebnis in `placeholders_info::$placeholders[$cachekey]`.
- **Rueckgabe:** HTML-`<a>`-Link; bei leerem `$cmid` oder `$optionid` der Fehlerstring `sthwentwrongwithplaceholder`.
- **Bewertung:** B — schlank und korrekt; kein Settings-Load noetig. Sicherheitlich zu beachten: Der Platzhalter erzeugt den Report-Link bedingungslos, ohne Capability-Pruefung — der Schutz liegt vollstaendig bei `report.php` selbst (der Link an einen unberechtigten Empfaenger fuehrt dort zu einem Zugriffsfehler, nicht zu einem Leak). Fuer einen reinen URL-String akzeptabel.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Steuert, ob der Platzhalter aufgerufen wird. Konstant `true`.
- **Seiteneffekte:** keine.
- **Rueckgabe:** `true`.
- **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Der einfachste Link-Platzhalter der Gruppe: direkte URL-Konstruktion aus Parametern, korrekter Request-Cache, sauberes Gating auf `$cmid`+`$optionid`. Keine funktionalen Maengel. Klassen-Score **B / P3**.
