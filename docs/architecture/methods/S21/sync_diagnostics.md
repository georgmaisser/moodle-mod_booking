# sync_diagnostics — Methoden-Doku
**Datei:** `sync_diagnostics.php` · **LOC:** 69 · **Subsystem:** S21 · **Klassen-Score:** A
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler AJAX-Endpoint (kein Klassen-Deklarant), aufgerufen vom AMD-Modul `amd/src/sync_diagnostics.js`. Rendert die letzten Enrolment-Sync-Versuche einer Buchungsoption als HTML-Tabelle und liefert sie JSON-verpackt (`{html: ...}`) zurueck. Kollaborateur: `mod_booking\local\sync\booking_enrolment::get_recent_attempts_for_option()`, `html_table`/`html_writer`.

## Request-/Permission-Flow
1. **Parameter:** `cmid` (`required_param`), `optionid` (`required_param`), `limit` (`optional_param`, Default 30).
2. **Auth (Z.31-37):** `get_coursemodule_from_id('booking', $cmid, 0, false, MUST_EXIST)` → `get_course` → `require_login($course, false, $cm)` → `require_sesskey()` → `require_capability('mod/booking:updatebooking', $context)`. Vollstaendige Gate-Kette (Login + Sesskey + Capability).
3. **Clamping (Z.39):** `$limit = max(1, min(100, $limit))` — Limit defensiv auf 1..100 begrenzt.
4. **Daten (Z.40):** `booking_enrolment::get_recent_attempts_for_option($optionid, $limit)`.
5. **Render (Z.42-66):** Bei leerer Liste eine `text-muted`-Meldung (`syncmanagementempty`); sonst eine `html_table` mit Spalten Time/Source/User/Action/Reason. Jede Zeile escaped Felder via `s()`, `userdate()`, `fullname()`; `rulesource` faellt auf `#<syncruleid>` zurueck, Reason haengt optional die Message an.
6. **Output (Z.68-69):** `Content-Type: application/json`, `json_encode(['html' => $html])`.

## Bewertung der Einzelschritte
- **Auth-Kette:** Vorbildlich vollstaendig (Sesskey + Capability + Limit-Clamp). Bewertung A.
- **Escaping:** Alle dynamischen Tabellen-Zellen werden mit `s()` escaped bzw. ueber Core-Helfer (`fullname`, `userdate`) gerendert — kein XSS-Vektor. Bewertung A.
- **Output-Form:** Manuelles `header()` + `json_encode` statt der externen WS-API; fuer einen schlanken Lazy-Fetch akzeptabel, da alle Gates manuell gesetzt sind.

## Bewertungs-Resümee
Sauberer, defensiv abgesicherter AJAX-Endpoint: vollstaendige Login/Sesskey/Capability-Kette, Limit-Clamping und durchgehendes Escaping. Keine funktionalen oder Sicherheits-Auffaelligkeiten. Klassen-Score **A**.
