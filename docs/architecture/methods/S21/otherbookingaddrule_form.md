# otherbookingaddrule_form — Methoden-Doku
**Datei:** `otherbookingaddrule_form.php` · **LOC:** 113 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
`otherbookingaddrule_form extends moodleform` — das Eingabeformular fuer eine `booking_other`-Regel (Auswahl einer Option aus der *verbundenen* Buchungsinstanz + Nutzer-Limit). Wird von `otherbookingaddrule.php` instanziiert. Keine eigene Persistenz; liest beim Aufbau die Optionsliste der verbundenen Instanz. Kollaborateur: `$DB`, `moodleform`. `_customdata['optionid']` steuert die Optionsliste.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut das Formular: ein `select` `otheroptionid` (Optionen der verbundenen Buchungsinstanz), ein `text` `userslimit` (numeric), ein hidden `bookingotherid`, plus Action-Buttons. Die Optionsliste wird per Subquery geladen: alle `booking_options`, deren `bookingid` der `conectedbooking` der Instanz der uebergebenen Option entspricht. **Seiteneffekte:** eine `get_records_sql`-Abfrage; Mutiert `$this->_form` (addElement/setType/addRule/addHelpButton). **Bewertung:** C — funktional korrekt; Schwaechen: Spalten-/Code-Name `conectedbooking` (Tippfehler im Schema, hier korrekt referenziert), Button-Label nutzt den unpassenden String `savenewtagtemplate` (Tag-Template-Kontext statt Other-Booking), und `userslimit` hat keine Server-seitige `required`-Regel (nur client-`numeric`).

### `public function validation($data, $files)` — public
- **Zweck:** Server-seitige Formvalidierung. **Rueckgabe:** immer `[]` (keine Fehler). **Seiteneffekte:** keine. **Bewertung:** C — leerer Override; keinerlei Validierung (z.B. `userslimit >= 0`, gueltige `otheroptionid`). De-facto No-op, koennte entfallen.

### `public function get_data()` — public
- **Zweck:** Holt die Formdaten. **Rueckgabe:** `parent::get_data()` unveraendert. **Seiteneffekte:** keine. **Bewertung:** D (methodisch) — vollstaendig redundanter Override, der nur `parent::get_data()` durchreicht; toter Boilerplate ohne Zweck.

## Bewertungs-Resümee
Minimal-Form mit funktionierendem Optionsfilter ueber die verbundene Instanz, aber drei Schwaechen: redundanter `get_data`-Override, leere `validation()`, und ein fehlplatziertes Button-Label. Funktional unkritisch. Klassen-Score **C / P3**.
