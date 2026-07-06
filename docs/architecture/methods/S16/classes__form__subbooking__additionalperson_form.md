# additionalperson_form — Methoden-Doku
**Datei:** `classes/form/subbooking/additionalperson_form.php` · **LOC:** 283 · **Subsystem:** S16 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S16_forms.md)

## Klassenueberblick
`additionalperson_form` ist die Prepage-`dynamic_form` des Subbookings „zusaetzliche Personen". Der Nutzer waehlt eine Anzahl Personen (0-4) und gibt pro Person Vorname/Nachname/Alter ein; abhaengig von der Auswahl werden die Personen-Felder dynamisch nachgerendert und — wenn etwas auszuwaehlen ist — der Bookit-Button. Die Form persistiert ihren Zustand nicht in der DB, sondern in einem MUC-Cache (`mod_booking/subbookingforms`) unter dem Schluessel `userid_optionid_subbookingid`, von wo der Buchungsflow ihn spaeter ausliest. Persistenz: Cache `mod_booking/subbookingforms`. Kollaborateure: `subbookings_info::get_subbooking_by_area_and_id`, `singleton_service` (Option-Settings, Booking-Answers), `booking_subbookit::render_bookit_button`. Kontext: `context_system`, Zugriff via `mod/booking:conditionforms`.

## Methoden

### `protected function get_context_for_dynamic_submission(): context` — protected
- **Zweck:** Liefert `context_system::instance()`. **Seiteneffekte:** keine. **Rueckgabe:** `context`. **Bewertung:** B — System-Kontext fuer eine option-bezogene Subbooking-Form ist grob, der Access-Check (`conditionforms`) faengt das aber ab.

### `protected function check_access_for_dynamic_submission(): void` — protected
- **Zweck:** Zugriffsschutz. **Seiteneffekte:** `require_capability('mod/booking:conditionforms', context_system::instance())`. **Bewertung:** B — Capability vorhanden, aber gegen System-Kontext (nicht gegen die konkrete Instanz); fuer eine Endnutzer-Buchungs-Prepage potenziell zu weit/zu eng je nach Rollenzuweisung.

### `public function set_data_for_dynamic_submission(): void` — public
- **Zweck:** Laedt den gecachten Formularzustand fuer Anzeige. **Seiteneffekte:** `subbookings_info::get_subbooking_by_area_and_id`; baut Cache-Key aus `$USER->id`/optionid/subbookingid; `cache->get(...)` und `set_data`. **Bewertung:** C — Cache-Key nutzt `$USER->id`, obwohl der Kommentar „Might be a different user!" explizit auf Buchung-im-Auftrag hinweist; in diesem Fall wuerde der falsche (Operator-)Datensatz geladen. Datenkonsistenzrisiko (siehe Findings).

### `public function process_dynamic_submission(): stdClass` — public
- **Zweck:** Persistiert die abgesendeten Daten. **Seiteneffekte:** `get_data()`, `self::store_data_in_cache($data)`. **Rueckgabe:** `stdClass`. **Bewertung:** B — schlank; delegiert an den statischen Cache-Helfer.

### `public function definition(): void` — public
- **Zweck:** Baut die Form dynamisch auf: versteckte id/optionid, statische Beschreibung, NoSubmit-Button `btn_addperson`, Personen-Anzahl-Select (Gruppe), und pro gewaehlter Person ein Feldblock; bei >0 Personen den gerenderten Bookit-Button. **Seiteneffekte:** liest `_ajaxformdata`; bei bereits ausgefuelltem Formular (`subbooking_addpersons` gesetzt) `self::store_data_in_cache` (Cache-Write **im definition()**!); sonst `self::get_data_from_cache`; `singleton_service::get_instance_of_booking_option_settings` + `booking_subbookit::render_bookit_button`. **Bewertung:** C — ein Cache-Write innerhalb von `definition()` (Render-Pfad) ist ein Seiteneffekt am falschen Ort; das verlaesst sich auf das NoSubmit-Reload-Verhalten von dynamic_forms. Die Anzahl gerenderter Personenfelder kommt aus `formdata['subbooking_addpersons'] ?? $data->subbooking_addpersons ?? 0`, was bei korruptem Cache toleriert wird.

### `public function validation($data, $files): array` — public
- **Zweck:** Server-seitige Validierung: prueft Platzverfuegbarkeit (`fullybooked`/`freeonlist` aus den Booking-Answers) gegen die Personenzahl und verlangt pro Person Vorname/Nachname/Alter. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings`, `singleton_service::get_instance_of_booking_answers`, `return_all_booking_information($USER->id)` (DB/Cache-Reads). **Rueckgabe:** `array` der Fehler-Map. **Bewertung:** B — sinnvolle Kapazitaetspruefung; nutzt jedoch wieder `$USER->id` fuer die Reservierungs-Info, was bei Buchung-im-Auftrag von der `set_data`-Annahme abweichen kann.

### `protected function get_page_url_for_dynamic_submission(): moodle_url` — protected
- **Zweck:** Rueckkehr-URL. **Seiteneffekte:** keine. **Rueckgabe:** `new moodle_url('/mod/booking/view.php', ['id' => $this->id])`. **Bewertung:** C — `$this->id` wird in der Klasse **nie gesetzt** (initial `null`), die URL erhaelt also stets `id=null`. Latenter Bug (siehe Findings).

### `public static function store_data_in_cache($data, ?object $user = null)` — public static
- **Zweck:** Schreibt `$data` in den `subbookingforms`-Cache unter `userid_optionid_subbookingid`. **Seiteneffekte:** `subbookings_info::get_subbooking_by_area_and_id` (DB), `cache->set(...)`. **Bewertung:** B — `$user`-Parameter erlaubt zwar einen abweichenden Nutzer, die Form-Aufrufe uebergeben ihn aber nie (immer `$USER`), womit die „different user"-TODOs ungeloest bleiben.

### `public static function get_data_from_cache($subbookingid, ?object $user = null)` — public static
- **Zweck:** Liest den gecachten Zustand. **Seiteneffekte:** `subbookings_info::get_subbooking_by_area_and_id` (DB), `cache->get(...)`. **Rueckgabe:** das gecachte `object` (oder false bei Miss). **Bewertung:** B — Duplikat-Logik zu `set_data_for_dynamic_submission`/`store_data_in_cache` (Cache-Key-Aufbau dreimal kopiert).

### Triviale Properties
`private $id = null` (Z.52) — wird nie zugewiesen und nur in `get_page_url_for_dynamic_submission` gelesen.

## Bewertungs-Resümee
Funktionierende, aber unsaubere Prepage-Form: der nie gesetzte `$this->id` (URL `id=null`), der Cache-Write mitten im `definition()`-Render-Pfad, die dreifach kopierte Cache-Key-Konstruktion und die durch `$USER->id` belegte (laut eigener TODOs unfertige) „Buchung im Auftrag"-Annahme summieren sich. Keine akute Sicherheitsluecke, aber mehrere Datenkonsistenz-/Wartbarkeitsmaengel. Klassen-Score **C / P2**.
