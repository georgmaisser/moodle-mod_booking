# priceformulamultiply — Methoden-Doku
**Datei:** `classes/option/fields/priceformulamultiply.php` · **LOC:** 117 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`priceformulamultiply` ist die Schwesterklasse zu `priceformulaadd` — eine sehr duenne Feldklasse (`extends field_base`) im Optionsformular-Feld-Framework (S02), die den **Multiplikator der Preisformel** einer Buchungsoption repraesentiert. Wie die Add-Variante definiert sie kein eigenes Formularelement (das uebernimmt `mod_booking\price`) und ueberschreibt nur `prepare_save_field`, hier mit dem multiplikativ neutralen Default **1**. Statische Marker: `$save = NORMAL`, `$header = PRICE`, `$fieldcategories = [STANDARD]`. Kollaborateur: `field_base` (geerbte Save-Logik).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Delegiert an `parent::prepare_save_field` mit Default-Returnvalue **1** — fehlt der Formularwert, wird der Multiplikator auf 1 gesetzt (neutral fuer Multiplikation). **Seiteneffekte:** ueber die Basisklasse: setzt `$newoption->{priceformulamultiply}` auf den Formularwert oder 1. **Rueckgabe:** das (leere) Change-Array der Basisklasse. **Bewertung:** B — korrekter, neutraler Default (1 statt 0, passend zur Multiplikation); trivialer Wrapper.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Bewusst leer — das Formularelement wird in `mod_booking\price` erzeugt (Kommentar Z.115). **Seiteneffekte:** keine. **Bewertung:** A — dokumentierter No-op.

### Triviale Properties
Sechs statische Marker-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.40–76).

## Bewertungs-Resümee
Minimaler, korrekt parametrisierter Feld-Wrapper, identisch zu `priceformulaadd` bis auf den multiplikativ-neutralen Default 1. Keine Auffaelligkeiten. Klassen-Score **B / P3**.
