# checklist_generator — Methoden-Doku
**Datei:** `classes/checklist/checklist_generator.php` · **LOC:** 273 · **Subsystem:** S17 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`checklist_generator` erzeugt zu einer einzelnen `booking_option` eine druckbare Vorbereitungs-Checkliste als PDF. Das HTML-Geruest stammt entweder aus der globalen Config `booking/checklisthtml` oder, falls leer, aus einem hartkodierten Default-Template (`get_default_checklist_html()`). Platzhalter der Form `[[...]]` werden per `strtr()` durch Optionswerte ersetzt und ueber `checklist_pdf` (TCPDF-Subklasse) als Datei-Download ausgeliefert. Keine eigene Persistenz; liest nur Config + Optionsdaten. Kollaborateure: `booking_option` (Property `option`, `teachers`, `optiontimes`, `return_array_of_sessions()`), `checklist_pdf`, `get_config`, `userdate`, `format_text`, `moodle_url`. `singleton_service`/`stdClass`/`TCPDF` werden importiert, aber im sichtbaren Code nicht direkt verwendet.

## Methoden

### `public function __construct(\mod_booking\booking_option $bookingoption)` — public
- **Zweck:** Haelt die Buchungsoption als Quelle aller Platzhalterwerte und setzt die Default-Orientierung `'P'` (Portrait). **Seiteneffekte:** keine. **Bewertung:** A.

### `public function generate_pdf()` — public
- **Zweck:** Orchestriert den gesamten Ablauf: Config-HTML laden, ggf. Default-Template waehlen, Platzhalter ersetzen, PDF rendern/ausliefern. **Seiteneffekte:** `get_config('booking','checklisthtml')`; ruft `download_pdf_from_html()`, das via `Output(..., 'D')` einen HTTP-Download anstoesst (beendet damit den Request-Flow). **Rueckgabe:** void. **Bewertung:** B — die Leere-Pruefung `empty(trim(strip_tags($checklisthtml)))` verwirft ein Template, das nur aus Tags/Bildern ohne Text besteht (z.B. reines `<img>`-Logo), und faellt dann auf den Default zurueck — pragmatisch, aber kantig.

### `private function get_concatenated_dates(): string` — private
- **Zweck:** Verkettet alle Session-Datumsstrings der Option zu einem `<br>`-getrennten String. **Seiteneffekte:** `$this->bookingoption->return_array_of_sessions()`. **Rueckgabe:** String (leer bei keinen Sessions). **Bewertung:** A.

### `private function get_placeholder_replacements(): array` — private
- **Zweck:** Baut die Map `[[platzhalter]] => Wert` fuer `strtr()`; deckt Id, Text, Kapazitaet, Ort, Zeiten, Beschreibung, Lehrende, Kontakt, Kurs-URL u.a. ab. **Seiteneffekte:** `userdate()`, `format_text()`, `moodle_url`, `get_teachers_names()`, `get_responsible_contact()`, `get_concatenated_dates()`. **Rueckgabe:** assoziatives Array. **Bewertung:** C — inkonsistente Null-Absicherung: fast alle Werte nutzen `?? ''`, aber `'[[location]]' => $this->bookingoption->option->location` (Z.102) hat keinen Fallback und loest bei fehlender Property einen Warning/Notice aus. Zudem sind viele `?? ''` wirkungslos, weil sie an Ausdruecken haengen, die bei fehlender Property bereits vorher einen Notice werfen (z.B. `userdate($...->coursestarttime) ?? ''`). Mehrere `property_exists`-Pruefungen nur fuer `course_url`, aber nicht fuer die direkt gelesenen Felder.

### `public function download_pdf_from_html(string $html)` — public
- **Zweck:** Instanziiert `checklist_pdf`, schaltet Print-Header/-Footer ab, schreibt das HTML und liefert die Datei als Download `checklist.pdf` aus. **Seiteneffekte:** Erzeugt PDF-Objekt, `writeHTML(...)`, `Output('checklist.pdf', 'D')` (sendet HTTP-Header + Body, terminiert den Flow). **Rueckgabe:** void. **Bewertung:** B — der Dateiname ist fest `checklist.pdf` (nicht optionsspezifisch); `SetPrintFooter(false)` deaktiviert die Footer-Logik von `checklist_pdf` komplett, sodass die dortige Logo-Fusszeile in diesem Pfad nie greift.

### `private function get_teachers_names($bookingoption)` — private
- **Zweck:** Liefert Liste `"Vorname Nachname"` aller Lehrenden der Option. **Seiteneffekte:** liest `$bookingoption->teachers`. **Rueckgabe:** Array von Strings (leer bei keinen Lehrenden). **Bewertung:** A.

### `private function get_responsible_contact($bookingoption)` — private
- **Zweck:** Gibt den verantwortlichen Kontakt der Option zurueck oder den Literal-Fallback `'Not specified'`. **Seiteneffekte:** keine. **Rueckgabe:** String. **Bewertung:** B — `'Not specified'` ist ein hartkodierter englischer String ohne `get_string()`/Sprachfaehigkeit.

### `protected function get_default_checklist_html(): string` — protected
- **Zweck:** Liefert das eingebaute Tabellen-Template (Seminar-Infos + Vorbereitungs-/Zwei-Wochen-/Abschluss-Checks) mit `[[...]]`-Platzhaltern und lokalisierten Ueberschriften. **Seiteneffekte:** mehrere `get_string(..., 'booking')`-Aufrufe. **Rueckgabe:** HTML-String. **Bewertung:** B — die einzelnen Checklisten-Eintraege („Check 1" … „SubCheck 5") sind Platzhalter-Text ohne Lokalisierung; funktional ein Demo-Geruest.

### `private function cleanup_filename(string $filename): string` — private
- **Zweck:** Soll Dateinamen saeubern (Leerzeichen→Unterstrich, Sonderzeichen weg, Mehrfach-Unterstriche kollabieren). **Seiteneffekte:** keine. **Rueckgabe:** bereinigter String. **Bewertung:** D — toter Code: die Methode wird nirgends aufgerufen; `download_pdf_from_html()` verwendet den festen Namen `checklist.pdf`. Vermutlich Rest einer geplanten optionsspezifischen Benennung.

## Bewertungs-Resümee
Funktionierender, aber roher Export-Renderer. Hauptschwaechen: inkonsistente Null-/Property-Absicherung in der Platzhalter-Map (echtes Notice-Risiko bei `[[location]]`, Z.102), hartkodierte englische/Demo-Strings, fester Dateiname und ungenutzte `cleanup_filename()`-Methode. Keine Datenverlust-/Sicherheitsdefekte, aber Robustheits- und i18n-Maengel rechtfertigen P2. Klassen-Score **B / P2**.
