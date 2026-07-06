# bo_subcondition — Methoden-Doku
**Datei:** `classes/bo_availability/bo_subcondition.php` · **LOC:** 137 · **Subsystem:** S03 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S03_availability.md)

## Klassenueberblick
`bo_subcondition` ist das **Interface** fuer Verfuegbarkeitsbedingungen im Subbooking-Kontext. Es ist ein reduzierter Geschwister-Vertrag zu `bo_condition`: jede Methode traegt zusaetzlich einen `$subbookingid`-Parameter, dafuer entfallen `hard_block()`, `return_sql()`, `render_page()`, `get_id()`, `get_name()` und `is_skippable()`. Persistenz: keine. Die Datei laedt per `require_once` `bo_info.php` (Kommentar: noetig, seit `bo_subinfo` die duplizierten `bo_info`-Konstanten nicht mehr selbst definiert), was eine Reihenfolge-Abhaengigkeit der Konstanten erzeugt. Kollaborateure: `booking_option_settings`, `MoodleQuickForm`, `stdClass` (importiert, im Interface aber ungenutzt), Konsument ist `bo_subinfo`.

## Methoden

### `public function is_json_compatible(): bool` — public
- **Zweck:** Unterscheidet JSON-faehige von hartkodierten Subconditions. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_shown_in_mform(): bool` — public
- **Zweck:** Gibt an, ob die Subcondition Form-Elemente zeigt. **Seiteneffekte:** keine. **Bewertung:** A.

### `public function is_available(booking_option_settings $settings, int $subbookingid, int $userid, bool $not)` — public
- **Zweck:** Kernpruefung der Verfuegbarkeit fuer ein konkretes Subbooking; `$not` invertiert mit derselben „realer-NOT-Wert"-Semantik wie in `bo_condition`. **Seiteneffekte:** keine im Vertrag. **Rueckgabe:** untypisiert (PHPDoc: bool). **Bewertung:** B — fehlender `bool`-Rueckgabetyp in der Signatur, obwohl PHPDoc und Schwesterinterface ihn deklarieren.

### `public function get_description(booking_option_settings $settings, $subbookingid, $userid, $full, $not)` — public
- **Zweck:** Liefert beschreibenden Text zur Subbooking-Restriktion; `$full` trennt Staff- von Studenten-Sicht. **Seiteneffekte:** keine. **Rueckgabe:** untypisiert (PHPDoc: Info-String). **Bewertung:** B — fehlende Typisierung der Parameter `$subbookingid/$userid/$full/$not` und des Rueckgabewerts.

### `public function add_condition_to_mform(MoodleQuickForm &$mform, int $optionid, $subbookingid)` — public
- **Zweck:** Fuegt die Form-Elemente der Subcondition zum mform hinzu. **Seiteneffekte:** mutiert `$mform` per Referenz. **Rueckgabe:** void (PHPDoc). **Bewertung:** B — `$subbookingid` ohne Typ-Hint, inkonsistent zum getypten `$optionid`.

### `public function render_button(booking_option_settings $settings, int $subbookingid, int $userid = 0, bool $full = false, bool $not = false, bool $fullwidth = true): array` — public
- **Zweck:** Liefert Template-Name + Daten zum Rendern des Subbooking-Buttons. **Seiteneffekte:** keine im Vertrag. **Rueckgabe:** `[templatename, data]`. **Bewertung:** A — vollstaendig getypt.

## Bewertungs-Resümee
Kompakter, klar abgeleiteter Subbooking-Vertrag, der `bo_condition` spiegelt und um `$subbookingid` ergaenzt. Schwaechen: mehrere Methoden sind im Gegensatz zu `bo_condition` nur teilweise typisiert (fehlende Rueckgabe-/Parameter-Typen bei `is_available`/`get_description`/`add_condition_to_mform`), und der `require_once bo_info.php`-Workaround signalisiert eine fragile Konstanten-Lade-Reihenfolge. Funktional unkritisch. Klassen-Score **A / P3**.
