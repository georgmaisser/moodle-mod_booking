# teachers_form — Methoden-Doku
**Datei:** `teachers_form.php` · **LOC:** 126 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Definiert die Klasse `mod_booking_teachers_form extends moodleform` — eine Checkbox-Liste der einer Buchungsoption zugeordneten Lehrer/Teilnehmer, mit optionalen Aktions-Buttons (Aktivitaetsabschluss bestaetigen, Editiermodus). Das File selbst fuehrt zudem `require_once(config.php)` + `require_login(0,false)` auf Top-Level aus (untypisch fuer eine reine Form-Datei). Persistenz: liest `booking_teachers` pro Lehrer; schreibt nichts (Submit-Verarbeitung erfolgt beim aufrufenden Skript). Kollaborateure: `singleton_service`, `completion_info`, `$DB`, `$CFG`. Customdata: `cm`, `teachers`, `option`, `optionid`, `id`, `edit`.

## Methoden

### `public function definition()` — public
- **Zweck:** Baut das Formular: pro Lehrer ein `advcheckbox` `user[<id>]` mit Vollname-Link aufs Profil und einem Completion-Haekchen-Praefix; darunter ein Checkbox-Controller (Select-all) und je nach Recht die Aktions-Buttons.
- **Ablauf:**
  - Iteriert `$this->_customdata['teachers']`; pro User `imagealt`-Default, dann **pro Lehrer ein** `$DB->get_record('booking_teachers', ['optionid'=>..., 'userid'=>...])` zur Bestimmung von `completed` → N+1-Query ueber die Lehrerliste.
  - `$checkmark` = `&#x2713;` wenn `$userdata->completed == '1'`, sonst `&nbsp;`.
  - `add_checkbox_controller($option->id + 1)` (Gruppen-Nummer = optionid+1).
  - Else-Zweig (keine Lehrer): statisches `nousers`-HTML.
  - Button-Bereich nur bei `mod/booking:updatebooking` am Modul-Context: laedt via `singleton_service::get_instance_of_booking_option($cm->id, $option->id)`, dann den Kurs-Record und `completion_info`; zeigt den „Aktivitaetsabschluss bestaetigen"-Submit nur wenn Automatic-Completion aktiv **und** `enablecompletion > 0`; immer den `turneditingon`-Submit; zusaetzlich Cancel.
  - Hidden-Felder `id`, `optionid`, `edit` (alle PARAM_INT).
- **Seiteneffekte:** Liest `booking_teachers` (N+1), `course`, baut `completion_info`; reine Form-Definition, keine Schreibzugriffe. HTML mit eingebetteten Profil-Links (Vollname via `fullname()`).
- **Bewertung:** C — funktioniert, hat aber drei Schwaechen: (1) **N+1** durch `get_record('booking_teachers')` je Lehrer in der Schleife (siehe Findings); (2) **kein false-Guard** auf `$userdata` — existiert kein `booking_teachers`-Record fuer das Paar optionid/userid, wirft `$userdata->completed` einen Property-Access auf `false` (siehe Findings); (3) der Label-HTML wird per String-Konkatenation mit `$CFG->wwwroot` und `fullname()` gebaut (fullname ist escaped, aber Vermischung von Markup und Daten ist fragil).

## Bewertungs-Resümee
Solide, aber typische Legacy-Form: pro-Zeile-DB-Lookup statt Vorab-Map und fehlender Null-Check auf den Teacher-Record. Funktional meist unkritisch, weil die uebergebene Lehrerliste i.d.R. aus `booking_teachers` selbst stammt — aber bei Inkonsistenz crash-anfaellig. Klassen-Score **C / P3**.
