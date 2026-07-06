# locallib — Methoden-Doku
**Datei:** `locallib.php` · **LOC:** 203 · **Subsystem:** S22 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S22_*.md)

## Klassenueberblick
Keine Klasse — Sammlung von sieben freistehenden prozeduralen Helferfunktionen im globalen Namespace (`booking_*` / `get_rendered_*` / `optiondate_*`). Wird via `require_once` aus Entry-Scripts und `lib.php` geladen. Aufgabenfelder: Bestaetigungsseite einer Buchung, Ableitung der Option-Start/Enddaten aus Optiondates, Render von Optiondate-Customfields und der vollen Event-Beschreibung, Duplizieren von Customfields und Status-String einer Option. Persistenz/Tabellen: `booking_options`, `booking_optiondates`, `booking_customfields`. Kollaborateure: `singleton_service`, `booking_utils`, `bookingoption_description`-Output, `rules_info`, `$DB`, `$OUTPUT`, `$PAGE`-Renderer.

## Methoden

### `function booking_confirm_booking($optionid, $user, $cm, $url)` — global
- **Zweck:** Rendert eine separate Bestaetigungsseite (Option-Text, Zeitraum, Buchungs-Policy) mit `$OUTPUT->confirm()`-Buttons (Ja → `view.php` mit `answer/confirm/sesskey/id`, Nein → `$url`). **Seiteneffekte:** direkter Seiten-Output (Header/Footer); laedt Option via `singleton_service`. **Bewertung:** B — funktional; Z.66 enthaelt ein nicht geschlossenes `<p>`-Tag (kosmetisch); `bookingpolicy` wird via `format_text` sicher gerendert.

### `function booking_updatestartenddate($optionid)` — global
- **Zweck:** Synchronisiert `coursestarttime`/`courseendtime` der Option mit MIN/MAX der zugehoerigen `booking_optiondates`; bei fehlenden Daten auf 0. **Seiteneffekte:** `$DB->get_record_sql(MIN/MAX ...)`, `$DB->update_record('booking_options', ...)`, danach `rules_info::execute_rules_for_option($optionid)`. **Rueckgabe:** void. **Bewertung:** B — Guard `booking_option_has_optiondates` verhindert ueberfluessige Updates; korrekt. Hinweis: wenn alle Optiondates entfernt werden, greift der Guard nicht (keine Zeilen) — Start/Enddatum werden dann nicht auf 0 zurueckgesetzt; in der Praxis uebernimmt das der Optiondates-Save-Pfad.

### `function get_rendered_customfields($optiondateid)` — global
- **Zweck:** Liefert HTML-Liste (`<p><i>cfgname:</i> value</p>`) aller Customfields eines Optiondate. **Seiteneffekte:** `$DB->get_records('booking_customfields', ['optiondateid' => ...])`. **Rueckgabe:** HTML-String (leer wenn keine). **Bewertung:** B — `$customfield->value` wird ohne `format_text`/`s()`-Escaping ausgegeben; Werte sind admin-/teacher-gepflegt, daher geringes Risiko, aber kein Escaping (P3).

### `function get_rendered_eventdescription(int $optionid, int $cmid, int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, bool $forbookeduser = false): string` — global
- **Zweck:** Baut ein `bookingoption_description`-DTO und delegiert je nach `descriptionparam` an den passenden mod_booking-Renderer (ICAL / MAIL / CALENDAR / Default Website). **Seiteneffekte:** `$PAGE->get_renderer('mod_booking')`; DTO-Konstruktion liest Optiondaten. **Rueckgabe:** gerendertes HTML/Text. **Bewertung:** A — klare Dispatch-Logik, vier explizite Render-Varianten.

### `function optiondate_duplicatecustomfields($oldoptiondateid, $newoptiondateid)` — global
- **Zweck:** Kopiert alle Customfields eines Optiondate auf ein neues Optiondate (Duplizieren von Sessions). **Seiteneffekte:** `$DB->get_records(...)` + je Datensatz `$DB->insert_record('booking_customfields', ...)`. **Rueckgabe:** void. **Bewertung:** B — korrekt; bei vielen Customfields N Einzel-Inserts (kein Bulk), aber Mengen klein.

### `function booking_getoptionstatus($starttime = 0, $endtime = 0)` — global
- **Zweck:** Liefert lokalisierten Status-String einer Option (`active` / `terminated` / `notstarted`) relativ zu `time()`. **Seiteneffekte:** keine (nur `get_string`). **Rueckgabe:** String (leer bei 0/0 oder Sonderfall). **Bewertung:** B — einfache Zeitvergleiche; Randfall `starttime == endtime == time()` faellt auf '' durch (vernachlaessigbar).

## Bewertungs-Resümee
Gut verstaendliche, kohaerente Helfer-Sammlung; jede Funktion hat eine klare Einzelverantwortung. Keine kritischen Bugs — nur kleinere Punkte: fehlendes Escaping in `get_rendered_customfields`, ein offenes `<p>`-Tag in `booking_confirm_booking` und der theoretische Reset-Randfall in `booking_updatestartenddate`. Klassen-Score **B / P3**.
