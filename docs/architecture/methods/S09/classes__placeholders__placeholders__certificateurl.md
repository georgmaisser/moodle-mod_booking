# certificateurl — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/certificateurl.php` · **LOC:** 100 · **Subsystem:** S09 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`certificateurl` ist ein Platzhalter (`extends \mod_booking\placeholders\placeholder_base`), der `[certificateurl]` durch die Download-URL eines ausgestellten Zertifikats (`tool_certificate`) ersetzt. Anders als die uebrigen Platzhalter dieser Gruppe arbeitet er nicht ueber `optionid`/`cmid`, sondern wertet das per `$rulejson` durchgereichte **Event-Payload** einer Booking-Rule aus (Signatur enthaelt einen zusaetzlichen `string $rulejson`-Parameter). Persistenz: lesender Zugriff auf `tool_certificate_issues`. Kollaborateure: globales `$DB`, `tool_certificate\template`. Kein Request-Cache (`placeholders_info`) — jeder Aufruf macht den DB-Roundtrip neu.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Liefert die Ausstellungs-Datei-URL eines Zertifikats. `$rulejson` wird per `json_decode` geparst; nur fortgefahren, wenn `datafromevent` vorhanden ist. Es wird verlangt, dass `eventname === '\mod_booking\event\bookingoption_completed'` ist **und** `tool_certificate\certificate` existiert — sonst leerer String. Aus `event->other->certid` wird via `$DB->get_record('tool_certificate_issues', ['id' => $certid])` der Issue-Record geholt, daraus `template::instance($templateid)` und schliesslich `$template->get_issue_file_url($record)`.
- **Seiteneffekte:** Eine DB-Lese-Abfrage auf `tool_certificate_issues`; instanziiert ein `tool_certificate`-Template.
- **Rueckgabe:** Die Zertifikats-Datei-URL als String; leerer String bei fehlendem/falschem Event, fehlender `certid` oder nicht installiertem `tool_certificate`.
- **Bewertung:** C — funktional plausibel, aber zwei robuste-Code-Luecken: (1) `$DB->get_record(...)` kann `false` liefern (Issue zwischenzeitlich geloescht / `certid` ungueltig), danach greift `$record->templateid` auf ein Nicht-Objekt zu → PHP-Warning/Fatal (siehe Finding). (2) Kein `placeholders_info`-Cache wie bei den Geschwistern — bei Mehrfachvorkommen des Platzhalters im selben Text laeuft die DB-Abfrage mehrfach. Die `class_exists`-Guard fuer `tool_certificate` ist korrekt gesetzt (Soft-Dependency).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Steuert, ob der Platzhalter aufgerufen wird. Konstant `true`.
- **Seiteneffekte:** keine.
- **Rueckgabe:** `true`.
- **Bewertung:** A — triviale Konstante.

## Bewertungs-Resümee
Eigenstaendiger, event-getriebener Zertifikats-Platzhalter mit sauberer Soft-Dependency-Pruefung, aber fehlender Defensive gegen einen nicht gefundenen Issue-Record (`get_record` → `false` → Fatal beim Property-Zugriff) und ohne den sonst ueblichen Request-Cache. Datenverlust droht nicht, aber ein fehlender/geloeschter Issue kann den Mail-/Rule-Render fatal abbrechen. Klassen-Score **B / P2** (leicht ueber den Geschwistern wegen des ungeguardeten DB-Records).
