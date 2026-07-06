# event_log_table — Methoden-Doku
**Datei:** `classes/table/event_log_table.php` · **LOC:** 124 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`event_log_table` ist eine reine Praesentations-/Spalten-Klasse, die von `local_wunderbyte_table\wunderbyte_table` erbt. Sie rendert die Zeilen des Booking-Event-Logs: pro Spalte eine `col_*`-Methode, die einen rohen DB-Wert in eine Anzeigerepraesentation uebersetzt. Eigene Persistenz hat sie nicht — die SQL/Spalten werden vom aufrufenden Reporting-Code (WB-Table-Setup) definiert; hier wird nur formatiert. Kollaborateure: das jeweils im Datensatz hinterlegte Moodle-Event (`$values->eventname` als Klassenname, via statischem `::restore()` rekonstruiert), `singleton_service` fuer User-Lookups, `userdate()`.

## Methoden

### `public function col_eventname($values)` — public
- **Zweck:** Rekonstruiert das Moodle-Event aus dem Datensatz und gibt dessen lokalisierten Namen (`get_name()`) zurueck. **Seiteneffekte:** entfernt vor dem `restore()` die Tabellen-/Zusatzfelder (`id`, `origin`, `ip`, `realuserid`, `user`, `uniqueid`, `username`), die kein gueltiger Event-Property-Schluessel sind; ruft `$values->eventname::restore((array)$values, [])` (dynamischer Klassenname aus DB-Wert). **Rueckgabe:** Event-Anzeigename (String). **Bewertung:** C — kein `try/catch` (im Gegensatz zu `col_description`); ist `eventname` ein nicht (mehr) existierender/ungueltiger Klassenname oder schlaegt `restore()` fehl, fataler Fehler in der Zeile. Dynamischer Klassenaufruf aus DB-Daten.

### `public function col_description($values)` — public
- **Zweck:** Liefert die menschenlesbare Event-Beschreibung. **Seiteneffekte:** identisches `unset()`-Vorspiel wie `col_eventname`, dann `restore()` + `get_description()`; faengt `Throwable` und faellt auf das rohe `$values->other`-Feld zurueck. **Rueckgabe:** Beschreibung (String). **Bewertung:** B — defensiv durch `try/catch`; die `unset()`-Liste ist 1:1 zu `col_eventname` dupliziert (gemeinsamer Helper waere sauberer).

### `public function col_timecreated($values)` — public
- **Zweck:** Formatiert den Unix-Timestamp `timecreated` via `userdate()`. **Seiteneffekte:** keine. **Rueckgabe:** lokalisiertes Datum/Zeit (String). **Bewertung:** A. (Der Doc-Block „context information column" ist ein Copy-Paste-Rest und beschreibt die Methode falsch — kosmetisch.)

### `public function col_userid($values)` — public
- **Zweck:** Uebersetzt `userid` in „Vorname Nachname". **Seiteneffekte:** `singleton_service::get_instance_of_user($values->userid)` (gecachter User-Lookup). **Rueckgabe:** voller Name (String). **Bewertung:** B — pro Zeile ein User-Lookup; durch den Singleton-Cache abgemildert, aber bei vielen distinkten Usern N Lookups.

### `public function col_relateduserid($values)` — public
- **Zweck:** Uebersetzt `relateduserid` (Empfaenger) in „Vorname Nachname"; leer, wenn nicht gesetzt. **Seiteneffekte:** bei gesetztem Wert `singleton_service::get_instance_of_user(...)`. **Rueckgabe:** voller Name oder `''`. **Bewertung:** A — sauberer Leere-Guard.

## Bewertungs-Resümee
Schlanke Spalten-Renderer-Klasse ohne eigenen Zustand. Hauptschwaeche: das ungesicherte dynamische `restore()` in `col_eventname` (kein `try/catch` wie beim Gegenstueck `col_description`), plus die duplizierte `unset()`-Logik. Per-Zeile-User-Lookups sind durch den Singleton-Cache vertretbar. Funktional unkritisch. Klassen-Score **B / P3**.
