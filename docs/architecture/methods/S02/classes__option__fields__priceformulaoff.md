# priceformulaoff — Methoden-Doku
**Datei:** `classes/option/fields/priceformulaoff.php` · **LOC:** 117 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`priceformulaoff` ist ein extrem schlanker Option-Feld-Handler (erweitert `field_base`) fuer das Flag „Preisformel fuer diese Option deaktivieren". Beide aktiven Hooks sind nahezu leer: das eigentliche Form-Element und die Speicherung werden in `\mod_booking\price` erledigt; diese Klasse existiert primaer als Registry-Eintrag (Header `MOD_BOOKING_HEADER_PRICE`, NORMAL-Save), damit der Feld-Iterator das Feld kennt und es in die Sortier-/Save-Reihenfolge einreiht. Reine statische Klasse ohne eigene Persistenz; Kollaborateure: `field_base` (Default-Save-Logik), implizit `price`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Delegiert die Speicherung vollstaendig an `parent::prepare_save_field`, ueberschreibt nur den Default-Wert (`0`) so, dass er der Default-Konvention der Price-Field-Klasse entspricht.
- **Seiteneffekte:** Setzt (ueber den Parent) `$newoption->priceformulaoff` auf den Formularwert bzw. `0` bei Leerwert. Keine DB-Zugriffe.
- **Rueckgabe:** `array` — das Ergebnis des Parents, das stets `[]` ist (kein Change-Tracking). PHPDoc behauptet `string`, der Code liefert `array` (gleicher kosmetischer Doc-Mismatch wie in der gesamten Feld-Familie).
- **Bewertung:** A — minimal, korrekt; einzige Funktion ist das Setzen des `0`-Defaults.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Bewusst leer — das Formularelement wird laut Inline-Kommentar in `\mod_booking\price` aufgebaut, nicht hier.
- **Seiteneffekte:** Keine.
- **Rueckgabe:** void.
- **Bewertung:** B — funktional eine bewusste No-op; die Verantwortung fuer das Feld liegt verstreut zwischen dieser Registry-Klasse und `price`, was die Auffindbarkeit erschwert.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.44–76).

## Bewertungs-Resümee
Eine reine Registry-/Marker-Klasse: beide Hooks sind leer bzw. delegieren an `field_base`, weil die fachliche Logik in `\mod_booking\price` lebt. Kein eigener Zustand, keine DB-Zugriffe, kein Fehlerpotential. Einziger Schoenheitsfehler ist die verteilte Verantwortung (Feld hier registriert, aber dort definiert/gespeichert) sowie der pauschale PHPDoc-`@return string`-Mismatch der Feld-Familie. Klassen-Score **B / P3**.
