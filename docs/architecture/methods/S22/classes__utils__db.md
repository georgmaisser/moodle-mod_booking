# db — Methoden-Doku
**Datei:** `classes/utils/db.php` · **LOC:** 159 · **Subsystem:** S22 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S22_db_layer.md)

## Klassenueberblick
`mod_booking\utils\db` ist eine zustandslose Sammlung von Ad-hoc-DB-Lesehelfern: eigene Buchungen des aktuellen Users, Badge-Auswahl fuer einen Kurs sowie zwei Mengen-Diff-Helfer (Aktivitaetsabschluss bzw. Badge-Besitz gegen die Buchungsteilnehmer). Sie traegt keinen Zustand und kein Caching; alle Methoden sind Instanzmethoden, nutzen aber keine `$this`-Properties (koennten statisch sein). Kollaborateure: `$DB`, `$USER`; gelesene Tabellen: `booking_answers`, `booking_options`, `booking`, `course`, `course_modules`, `modules`, `badge`, `badge_issued`, `course_modules_completion`.

## Methoden

### `public function mybookings()` — public
- **Zweck:** Liefert alle Buchungseintraege des aktuellen Users (`$USER->id`) ueber sichtbare Booking-Course-Modules mit Kurs-/Instanz-/Options-Metadaten. **Seiteneffekte:** `$DB->get_records_sql($sql, [$USER->id], 0, 0)`. **Rueckgabe:** array (keyed nach `ba.id`). **Bewertung:** B — verschachtelte Subquery auf `{modules}` statt eines Joins und keinerlei Status-/Waitinglist-Filter auf `booking_answers` (liefert auch reservierte/Warteliste-/Notify-Me-Eintraege); funktional fuer „meine Buchungen" pragmatisch, semantisch grob.

### `public function getbadges(?int $courseid = null)` — public
- **Zweck:** Soll die aktiven/aktivierten Badges eines Kurses als `id => name`-Menue liefern. **Seiteneffekte:** `$DB->get_records_sql_menu($sql, $params)` bei gesetztem `$courseid`, sonst `[]`. **Rueckgabe:** array. **Bewertung:** C — zwei Defekte: (1) Die SQL filtert ueberhaupt nicht nach Kurs (`WHERE b.status = 1 OR b.status = 3`, kein `:courseid`-Platzhalter) → es werden site-weit alle Badges geliefert, nicht die des Kurses. (2) Es wird trotzdem `$params['courseid']` uebergeben; Moodles strikte Parameter-Pruefung erwartet zu null Platzhaltern null Parameter, sodass der Aufruf mit gesetztem `$courseid` voraussichtlich eine `dml_exception` (unbenutzter Named-Param) wirft. Zudem mischt die ungeklammerte `status = 1 OR status = 3`-Bedingung schlecht mit etwaigen weiteren Konditionen.

### `public function getusersactivity($cmid = null, $optionid = null, $completed = false)` — public
- **Zweck:** Vergleicht die Teilnehmer einer Option (`booking_answers`) mit den Usern, die einen Completion-Eintrag im Course-Module haben; gibt je nach `$completed` die Schnittmenge (haben abgeschlossen) oder die Differenz (haben nicht abgeschlossen) zurueck. **Seiteneffekte:** `$DB->get_records('course_modules_completion', ['coursemoduleid' => $cmid])`; `$DB->get_records('booking_answers', ['optionid' => $optionid])`. **Rueckgabe:** array von User-Ids. **Bewertung:** C — `course_modules_completion` wird ohne `completionstate`-Filter gelesen, ein Eintrag mit `completionstate = 0` (nicht abgeschlossen) zaehlt hier als „completed"; zudem werden `booking_answers` ohne Status-/Waitinglist-Filter genommen (Warteliste/Reservierungen flieessen als „Teilnehmer" ein), was die Diff-Mengen verfaelscht.

### `public function getusersbadges($badgeid = null, ?int $optionid = null)` — public
- **Zweck:** Liefert die User-Ids, die sowohl ein bestimmtes Badge besitzen als auch Teilnehmer der Option sind (Schnittmenge, dedupliziert). **Seiteneffekte:** `$DB->get_records('badge_issued', ['badgeid' => $badgeid])`; `$DB->get_records('booking_answers', ['optionid' => $optionid])`. **Rueckgabe:** array von User-Ids. **Bewertung:** B — wie oben kein Status-Filter auf `booking_answers`; `array_unique(array_intersect(...))` ist korrekt, das `unique` jedoch nur noetig, weil Mehrfach-Buchungseintraege je User moeglich sind.

## Bewertungs-Resümee
Heterogene Ad-hoc-Query-Sammlung ohne Zustand. Hauptproblem ist `getbadges` (kein Kurs-Filter trotz Parameter; uebergebener, unbenutzter Named-Param riskiert eine `dml_exception`). Die Diff-Helfer ignorieren den Buchungsstatus (Warteliste zaehlt als Teilnehmer) und `completionstate`, was zu semantisch falschen Mengen fuehren kann. Methoden koennten statisch sein. Klassen-Score **C / P3**.
