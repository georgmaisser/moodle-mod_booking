# bookotheroptions — Methoden-Doku
**Datei:** `classes/bo_actions/action_types/bookotheroptions.php` · **LOC:** 148 · **Subsystem:** S04 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S04_*.md)

## Klassenueberblick
`bookotheroptions` ist ein bo_action-Typ (erweitert `booking_action`), der den User nach einer Buchung automatisch in eine oder mehrere weitere ausgewaehlte Buchungsoptionen einbucht — mit konfigurierbarem Force-/Overbooking-Verhalten. Die Klasse haelt keinen Zustand; sie liefert das Form-Fragment (`add_action_to_mform`) und die Ausfuehrung (`apply_action`). Persistenz erbt sie aus `booking_action::save_action`. Kollaborateure: `singleton_service` (User/Option), `booking_option::user_submit_response`, `$DB` (Option-Liste fuer das Dropdown).

## Methoden

### `public function apply_action(stdClass $actiondata, int $userid = 0)` — public
- **Zweck:** Bucht den (aktuellen oder uebergebenen) User in jede in `$actiondata->bookotheroptionsselect` gewaehlte Option ein. **Seiteneffekte:** ermittelt den User (uebergeben oder `$USER`); je Option `singleton_service::get_instance_of_booking_option($actiondata->cmid, $optionid)` + `user_submit_response($user, 0, 0, $actiondata->bookotheroptionsforce, MOD_BOOKING_VERIFIED)` → schreibt Buchungsantworten, kann Enrolment/Events/Benachrichtigungen ausloesen. **Rueckgabe:** `int` 1 — bricht alle folgenden After-Actions ab. **Bewertung:** B — funktional klar; jede Zieloption nutzt jedoch dasselbe `$actiondata->cmid` (die Option muss zur selben Instanz gehoeren) und es gibt keine Schleifen-Schutz gegen Rekursion (eine Option, die per Aktion wieder die Ursprungsoption bucht, koennte zirkulaer auslosen). Force-Modus wird ungeprueft durchgereicht.

### `public static function add_action_to_mform(&$mform)` — public static
- **Zweck:** Baut das Aktions-Formular: Namensfeld, ein Multi-Select-Autocomplete aller Buchungsoptionen site-weit und ein Force-Select. **Seiteneffekte:** `$DB->get_records_sql(...)` ueber `{booking_options}` JOIN `{booking}` JOIN `{course}` — laedt ALLE Optionen aller Kurse/Instanzen, baut Anzeigelabels (`titleprefix - name (instanz, kurs)`); mutiert `$mform`. **Rueckgabe:** void. **Bewertung:** C — die Abfrage ist unbeschraenkt (kein Course-/Capability-Filter, keine Paginierung): auf grossen Installationen laedt sie zehntausende Optionen in ein einziges Form-Dropdown (Speicher-/Renderlast). Die Labels selbst sind korrekt aufgebaut.

## Bewertungs-Resümee
Klar verstaendlicher Aktionstyp. Zwei Schwaechen: die unbeschraenkte site-weite Options-Abfrage im Formularaufbau (Skalierung) und das ungeschuetzte Force-/Mehrfach-Booking ohne Rekursionsschutz in `apply_action`. Funktional korrekt fuer typische Groessen. Klassen-Score **B / P3**.
