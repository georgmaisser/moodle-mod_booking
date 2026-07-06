# elective_modal — Methoden-Doku
**Datei:** `classes/output/elective_modal.php` · **LOC:** 164 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`elective_modal` ist ein Renderable/Templatable-DTO fuer das Elective-Auswahl-Modal (Buchung mehrerer Optionen als Kombination mit Kredit-/Reihenfolge-Logik). Es liest die im MUC-Cache `electivebookingorder` gespeicherte (vom User zusammengestellte) Auswahlreihenfolge, mappt diese auf die uebergebenen Rohdaten, sortiert optional nach erzwungener Lehrer-Reihenfolge und baut den HTML-Bestaetigungs-Button samt Disabled-/Enabled-Zustand. Persistenz: keine eigene, liest Cache `mod_booking/electivebookingorder`. Kollaborateure: `cache`, `html_writer`, `booking_settings`, `mod_booking\elective` (Kombinierbarkeit + Restkredit), `$USER`.

## Methoden

### `public function __construct(booking_settings $booking, array $rawdata)` — public
- **Zweck:** Baut den kompletten Modal-Zustand: holt die gecachte Auswahlreihenfolge (`arrayofoptions`) fuer die `cmid`, verfaellt sie bei abgelaufener `expirationtime` (und loescht dann den Cache-Eintrag), mappt jede gemerkte Option-id auf `(array)$rawdata[$item]`, sortiert bei `enforceteacherorder` per `usort` nach `sortorder`, setzt `notbookablemessage` falls die Kombination laut `elective::is_bookable_combination` unzulaessig ist und baut den Bestaetigungs-Button (deaktiviert bei `consumeatonce==1` mit Restkredit ≠ 0 oder leerer Auswahl). **Seiteneffekte:** `cache::make('mod_booking','electivebookingorder')` lesen und ggf. `delete($cmid)`; liest `$USER->id`; frueher Return (kein Aufbau) wenn `$rawdata` leer. **Bewertung:** C — `$rawdata[$item]` (Z.96) wird ohne `isset`-Guard indiziert: steht im Cache eine Option-id, die nicht (mehr) in `$rawdata` enthalten ist, gibt es einen Undefined-Key-Warning und ein leeres Array-Element; ausserdem viel Logik im Konstruktor (DTO mit Geschaeftsregeln).

### `public function return_as_array()` — public
- **Zweck:** Liefert den DTO-Zustand (`modalbuttonclass`, `confirmbutton`, `arrayofoptions`, `isteacherorderforced`, `notbookablemessage`) als flaches Array. **Rueckgabe:** assoziatives Array. **Bewertung:** A.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Templatable-Vertrag; delegiert vollstaendig an `return_as_array()`. **Rueckgabe:** Array fuer Mustache. **Bewertung:** A.

### Triviale Properties
Fuenf oeffentliche Properties (`confirmbutton`, `modalbuttonclass`, `arrayofoptions`, `isteacherorderforced`, `notbookablemessage`, Z.44–57) als Werte-Halter.

## Bewertungs-Resümee
Funktionales DTO, das mehr als reine Datenhaltung leistet (Cache-Lesung, Sortierung, Button-State, Kreditregeln im Konstruktor). Haupt-Schwachpunkt: der ungeschuetzte Zugriff `$rawdata[$item]` bei stale Cache-Eintraegen. Sonst klar und ueberschaubar. Klassen-Score **B / P3**.
