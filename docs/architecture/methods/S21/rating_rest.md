# rating_rest — Methoden-Doku
**Datei:** `rating_rest.php` · **LOC:** 68 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler AJAX-Endpunkt (keine Klasse). Nimmt eine Bewertung (`value`) eines Nutzers fuer eine Buchungsoption entgegen, persistiert sie in `booking_ratings` (sofern erlaubt) und gibt den aktualisierten Durchschnitt als JSON zurueck. Kollaborateure: `singleton_service::get_instance_of_booking_option` (Berechtigungspruefung `can_rate()`), `$DB` (Insert + AVG-Aggregat), `$USER`, `$OUTPUT`. Persistenz: Tabelle `booking_ratings`.

## Request-/Permission-Flow
1. `require_once config.php` + `locallib.php`; definiert `MOD_BOOKING_RATING_AJAX_SCRIPT` (reiner Marker, setzt NICHT die Moodle-Konstante `AJAX_SCRIPT`).
2. Pflicht-Parameter `id` (Course Module ID), `optionid`, `value` — alle `PARAM_INT`.
3. `get_course_and_cm_from_cmid($id, 'booking')` + `require_course_login($course, false, $cm)` — Kurs-Login als Gate.
4. `echo $OUTPUT->header()` — gibt VOR dem JSON die volle Theme-Page-Header-Ausgabe aus (siehe Bewertung/Findings).
5. Baut `$record` (userid/optionid/rate), holt die Option ueber den Singleton-Service.
6. `try`: nur wenn `$bookingdata->can_rate()` true ist, `insert_record('booking_ratings', $record, false, false)`; jede `Exception` wird als „Duplikat" interpretiert (`$isinserted = true`).
7. Aggregat-Query `SELECT IFNULL(AVG(rate), 1) AS rate FROM {booking_ratings} WHERE optionid = ?`.
8. `echo json_encode(['rate' => (int)$avg->rate, 'duplicate' => $isinserted])`.

## Bewertung
- **Seiteneffekte:** DB-Insert in `booking_ratings` (bedingt durch `can_rate()`), HTML-Header-Ausgabe, JSON-Ausgabe.
- **Rueckgabe:** JSON `{rate:int, duplicate:bool}`.
- **Bewertung:** C —
  1. **JSON-Hygiene:** `$OUTPUT->header()` (Z.41) emittiert die komplette Theme-/HTML-Page vor dem `json_encode`; ohne gesetztes `AJAX_SCRIPT`/`header('Content-Type: application/json')` ist die Antwort HTML+JSON gemischt — fragil und auf Client-seitiges Heraussplitten angewiesen.
  2. **Fehler-als-Kontrollfluss:** Doppel-Erkennung haengt davon ab, dass der Insert eine Exception wirft (impliziert einen Unique-Index auf `booking_ratings`); jede andere DB-Exception wird faelschlich als „duplicate" gemeldet.
  3. **Semantik:** Ist `can_rate()` false, wird stillschweigend nichts eingefuegt, aber `duplicate=false` zurueckgegeben — Client kann „abgelehnt" nicht von „neu gespeichert" unterscheiden.
  4. `value` wird nur als `PARAM_INT` validiert, nicht gegen die zulaessige Skala der Option begrenzt.

## Bewertungs-Resümee
Funktional schlanker Rating-Endpunkt mit korrektem `can_rate()`-Gate, aber mehreren Robustheits-/Hygiene-Schwaechen: vermischte HTML+JSON-Ausgabe, exception-basierte Duplikatlogik und nicht unterscheidbare Ablehnung. Klassen-Score **C / P3**.
