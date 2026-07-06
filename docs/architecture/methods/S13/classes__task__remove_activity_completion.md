# remove_activity_completion — Methoden-Doku
**Datei:** `classes/task/remove_activity_completion.php` · **LOC:** 94 · **Subsystem:** S13 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S13_*.md)

## Klassenueberblick
`remove_activity_completion` ist ein `\core\task\scheduled_task`, der abgeschlossene Buchungs-Antworten (`booking_answers.completed = 1`) zuruecksetzt, sobald sie aelter als die optionsbezogene Schwelle `removeafterminutes` sind, und danach den Moodle-Completion-Status der Aktivitaet auf `COMPLETION_INCOMPLETE` aktualisiert. Persistenz: liest `booking_answers`/`booking_options`/`booking`/`course`, schreibt `booking_answers` und ueber `completion_info::update_state` die Core-Completion-Tabellen. Kollaborateure: `$DB`, `completion_info`, `get_coursemodule_from_instance`, `completionlib.php`.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`taskremoveactivitycompletion`). **Seiteneffekte:** `get_string`. **Rueckgabe:** `string`. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Findet per JOIN-Query alle faelligen, abgeschlossenen Antworten und setzt sie auf incomplete; aktualisiert anschliessend bei aktivierter Completion den Aktivitaetsstatus. **Seiteneffekte:** ein `get_records_sql` (Kandidaten), dann pro Zeile: `get_record('course')`, `new completion_info`, `get_coursemodule_from_instance`, `get_record('booking_answers')`, `get_record('booking')`, `update_record('booking_answers')`, `count_records('booking_answers')`, ggf. `completion->update_state`. **Bewertung:** C — korrekte Grundlogik, aber pro Treffer 4 redundante Einzel-Reads (course/cm/booking werden auch fuer dieselbe booking-Instanz/denselben Kurs nicht memoisiert; die `booking_answers`-Zeile wird per `id` nochmals voll geladen, obwohl die Select-Query bereits id/userid liefert) — klassisches N+1 (P2 bei vielen faelligen Zeilen). Die SQL nutzt `:now` parametrisiert, multipliziert `removeafterminutes * 60` aber per Spaltenausdruck — korrekt. Bedingung `$booking->enablecompletion > $countcompleted` setzt incomplete, sobald die verbleibende Zahl abgeschlossener Antworten unter die geforderte Schwelle faellt — plausibel, aber `enablecompletion` als Zahl-Schwelle interpretiert (Annahme, dass das Feld die geforderte Mindestanzahl traegt).

## Bewertungs-Resümee
Funktional korrekter Cleanup-Task mit sauberer Kandidaten-Query, aber ineffizientem Pro-Zeilen-Render (mehrfache Einzel-Reads ohne Caching, redundantes Nachladen der bereits selektierten Antwort). N+1-Charakter ist der einzige relevante Schwachpunkt. Klassen-Score **C / P2**.
