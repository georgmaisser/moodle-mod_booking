# maxoverbooking — Methoden-Doku
**Datei:** `classes/option/fields/maxoverbooking.php` · **LOC:** 134 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`maxoverbooking` ist der Option-Feld-Handler (`field_base`-Subklasse) fuer die maximale Ueberbuchung/Wartelistengroesse einer Buchungsoption (`maxoverbooking`-Spalte). Wie `maxanswers` setzt er als Seiteneffekt das `limitanswers`-Flag und ist damit eng mit jenem Feld gekoppelt. Das Feld wird komplett ausgeblendet, wenn die globale Plugin-Einstellung `turnoffwaitinglist` aktiv ist. Die Klasse besitzt keine eigene `set_data`-Methode (erbt von `field_base`). Persistenz: Spalten `maxoverbooking` und `limitanswers`. Kollaborateure: `field_base`, `fields_info`, `MoodleQuickForm`; globale Config `booking/turnoffwaitinglist`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Setzt `$newoption->limitanswers = 1`, falls `maxoverbooking` nicht leer ist, speichert dann den `maxoverbooking`-Wert (via Basis mit Default 0) und sammelt Aenderungen.
- **Seiteneffekte:** Mutiert `$newoption` (`maxoverbooking`, ggf. `limitanswers`); ruft `check_for_changes` (Settings-Load + Change-Tracking).
- **Rueckgabe:** Changes-Array.
- **Bewertung:** B — `limitanswers` wird wie bei `maxanswers` nur ein-, nie ausgeschaltet; die Reihenfolge (Flag vor Basis-Save) ist invertiert gegenueber `maxanswers`, funktional aber aequivalent.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Fuegt — nur wenn `turnoffwaitinglist` deaktiviert ist — ein `text`-Element `maxoverbooking` (Typ `PARAM_INT`) mit Help-Button hinzu.
- **Seiteneffekte:** Mutiert `$mform`; optional Header; liest globale Config via `get_config('booking', 'turnoffwaitinglist')`.
- **Bewertung:** A — saubere Config-Gate-Verzweigung.

### Triviale Properties
Sechs statische Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.40–76).

## Bewertungs-Resümee
Wartelisten-/Ueberbuchungs-Handler, eng an `maxanswers` gekoppelt ueber das gemeinsame `limitanswers`-Flag und ueblicherweise per `turnoffwaitinglist`-Config schaltbar. Gleiche Schwaeche wie `maxanswers`: `limitanswers` wird nur gesetzt, nie zurueckgesetzt, und die Verantwortung ist auf zwei Felder verteilt. Funktional unkritisch im Normalfall. Klassen-Score **B / P3**.
