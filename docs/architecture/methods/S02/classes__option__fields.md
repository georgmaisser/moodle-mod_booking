# fields — Methoden-Doku
**Datei:** `classes/option/fields.php` · **LOC:** 67 · **Subsystem:** S02 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S02_*.md)

## Klassenueberblick
`fields` ist ein **Interface** (kein Konkret-Code), das den Vertrag fuer alle Option-Form-Feldklassen im Namespace `mod_booking\option\fields\*` definiert. Es legt die zwei zentralen Lifecycle-Hooks fest, ueber die das `fields_info`-Orchester jedes Feld einbindet: Form-Aufbau und Speichern. Keine Logik, keine Seiteneffekte.

## Methoden

### `prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static (Interface-Vertrag)
- **Zweck:** Vertrag — interpretiert den Formwert eines Feldes und reicht ihn an das neue Optionsobjekt zum Speichern/Aktualisieren weiter.
- **Parameter:** `$formdata` (by-ref, Roh-Formdaten), `$newoption` (by-ref, zu befuellende Option), `$updateparam` (Update-Modus), `$returnvalue` optional.
- **Rueckgabe:** `array` (Warnungen/Errors; leer = ok). Hinweis: PHPDoc sagt `string`, Signatur deklariert `array` — Doc-Drift.
- **Seiteneffekte:** keine (Interface).
- **Aufrufkette:** implementiert von allen `option/fields/*`-Klassen; gerufen vom Speicher-Loop in `fields_info`.
- **Bewertung:** A — schlanker, klarer Vertrag.

### `instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig)` — public static (Interface-Vertrag)
- **Zweck:** Vertrag — fuegt die mform-Elemente des Feldes zum Optionsformular hinzu.
- **Parameter:** `$mform` (by-ref), `$formdata` (by-ref), `$optionformconfig`.
- **Rueckgabe:** void.
- **Seiteneffekte:** keine (Interface).
- **Aufrufkette:** implementiert von `option/fields/*`; gerufen beim Formaufbau durch `fields_info`.
- **Bewertung:** A.

## Anmerkung
Konkrete Feldklassen erweitern in der Praxis `field_base` und implementieren zusaetzliche Signaturen (z.B. `set_data`, `$applyheader`-Parameter), die ueber dieses Minimal-Interface hinausgehen — siehe `actions.php`. Das Interface bildet also nur den kleinsten gemeinsamen Nenner ab.
