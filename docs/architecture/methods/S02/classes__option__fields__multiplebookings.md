# multiplebookings — Methoden-Doku
**Datei:** `classes/option/fields/multiplebookings.php` · **LOC:** 240 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`multiplebookings` ist ein Optionsfeld (`field_base`-Subklasse), das die „Erneut buchen"-Politik einer Buchungsoption steuert: ob und wann ein Nutzer, der bereits gebucht hat, dieselbe Option erneut buchen darf. Drei Modi (Konstanten): `MODE_DISABLED` (0, nie), `MODE_AFTER_DURATION` (1, nach fixer Wartezeit `allowtobookagainafter` ab Erstbuchung), `MODE_AFTER_LAST_SLOT` (2, nach Ende des letzten gebuchten Slots). Der Wert wird **im JSON-Feld** der Option gespeichert (nicht in einer eigenen Spalte). Zusaetzlich liefert die Klasse mit `book_again_due()` die zentrale Runtime-Entscheidung („single source of truth"), die von der `alreadybooked`-Availability-Bedingung, dem Rebooking-Flow und dem Slotbooking-Move-Tab geteilt wird. Save-Timing `MOD_BOOKING_EXECUTION_NORMAL`. Kollaborateure: `booking_option` (JSON-Helfer), `singleton_service`, `slot_mover::last_booked_slot_end`, `fields_info`, `MoodleQuickForm`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Schreibt Modus und Wartezeit ins JSON von `$newoption`, **bevor** das JSON persistiert wird. Pro Modus werden `multiplebookings` und `allowtobookagainafter` konsistent gesetzt (bei AFTER_LAST_SLOT und DISABLED wird die Dauer auf 0 genullt). **Seiteneffekte:** zwei `booking_option::add_data_to_json($newoption, ...)`-Aufrufe je Zweig; danach `parent::prepare_save_field(..., '')`. **Rueckgabe:** immer leeres Array (`[]`). **Bewertung:** B — saubere Modus-Normalisierung; gibt bewusst kein Change-Array zurueck (kein Change-Tracking fuer dieses JSON-Feld) — Kommentar zur Reihenfolge (vor JSON-Save) ist korrekt und wichtig.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt das Modus-`select` (drei Strings) und das `duration`-Element `allowtobookagainafter` ein; letzteres ist per `hideIf` nur im Modus AFTER_DURATION sichtbar. **Seiteneffekte:** ggf. Header-Injektion; mutiert `$mform`. **Rueckgabe:** void. **Bewertung:** A — UI sauber an die Modus-Semantik gekoppelt.

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Belegt die Formularfelder beim Erstladen aus dem JSON der Option (frisch aus DB via `get_value_of_json_by_key`, bewusst nicht aus `$settings`, da dort Kampagnen den Wert ueberschreiben koennten). **Seiteneffekte:** Early-Return wenn `$data->multiplebookings` schon gesetzt; sonst zwei `booking_option::get_value_of_json_by_key($settings->id, ...)`-Reads; mutiert `$data`. **Rueckgabe:** void. **Bewertung:** B — der Kommentar erklaert die DB-statt-Settings-Quelle gut; `global $DB` wird deklariert aber nicht direkt genutzt (die Reads laufen ueber den Helfer) — kosmetisch.

### `public static function book_again_due(int $optionid, stdClass $answer): bool` — public static
- **Zweck:** Zentrale Entscheidung, ob der Inhaber von `$answer` (gebuchte Answer, `waitinglist = BOOKED`) jetzt erneut buchen darf. Liest den Modus aus `$settings->jsonobject`. AFTER_DURATION: `(timebooked + allowtobookagainafter) <= time()`, wobei `timebooked` auf `timecreated` zurueckfaellt. AFTER_LAST_SLOT: `slot_mover::last_booked_slot_end($answer)` muss > 0 und <= now sein. Sonst false. **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings` (gecacht); im Slot-Modus `slot_mover::last_booked_slot_end`. **Rueckgabe:** bool. **Bewertung:** A — schlanke, gut dokumentierte Single-Source-of-Truth; defensive Fallbacks (`?? `-Ketten) fuer fehlende Felder.

### Triviale Properties / Konstanten
Drei Modus-Konstanten (`MODE_DISABLED`/`MODE_AFTER_DURATION`/`MODE_AFTER_LAST_SLOT`, Z.41–54) sowie sechs statische Registry-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.60–92).

## Bewertungs-Resümee
Gut strukturiertes Feld mit klarer Modus-Semantik und einer sauber zentralisierten Runtime-Methode (`book_again_due`), die Duplikation ueber drei Aufrufer vermeidet. JSON-Persistenz korrekt vor dem Save geschrieben. Nur Kosmetik (unbenutztes `global $DB`). Klassen-Score **B / P3**.
