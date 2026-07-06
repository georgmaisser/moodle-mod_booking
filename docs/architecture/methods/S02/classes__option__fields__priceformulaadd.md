# priceformulaadd — Methoden-Doku
**Datei:** `classes/option/fields/priceformulaadd.php` · **LOC:** 117 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`priceformulaadd` ist eine sehr duenne Feldklasse (`extends field_base`) im Optionsformular-Feld-Framework (S02). Sie repraesentiert den **additiven Wert der Preisformel** einer Buchungsoption. Sie definiert kein eigenes Formularelement (das uebernimmt `mod_booking\price`) und ueberschreibt nur `prepare_save_field`, um den Default-Speicherwert festzulegen. Persistenz: ueber die Basis-Logik in die zugehoerige Options-Spalte (Default 0). Statische Marker: `$save = NORMAL`, `$header = PRICE`, `$fieldcategories = [STANDARD]`. Kollaborateur: `field_base` (geerbte Save-Logik).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Delegiert an `parent::prepare_save_field` mit Default-Returnvalue **0** — d.h. fehlt der Formularwert, wird der Additiv-Wert auf 0 gesetzt (neutral fuer Addition). **Seiteneffekte:** ueber die Basisklasse: setzt `$newoption->{priceformulaadd}` auf den Formularwert oder 0. **Rueckgabe:** das (leere) Change-Array der Basisklasse. **Bewertung:** B — korrekter, neutraler Default; trivialer Wrapper.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Bewusst leer — das Formularelement wird in `mod_booking\price` erzeugt (Kommentar Z.115). **Seiteneffekte:** keine. **Bewertung:** A — dokumentierter No-op.

### Triviale Properties
Sechs statische Marker-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.40–76).

## Bewertungs-Resümee
Minimaler, korrekt parametrisierter Feld-Wrapper (Default-Additiv 0, Formular ausgelagert). Keine Auffaelligkeiten. Klassen-Score **B / P3**.
