# returnurl — Methoden-Doku
**Datei:** `classes/option/fields/returnurl.php` · **LOC:** 132 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`returnurl` ist ein sehr schlanker Option-Feld-Handler (erweitert `field_base`) fuer eine versteckte Return-URL, auf die nach einer Buchungsaktion zurueckgesprungen wird. Als `MOD_BOOKING_OPTION_FIELD_NECESSARY` markiert (immer instanziiert), NORMAL-Save, Header `MOD_BOOKING_HEADER_GENERAL`. Kein eigenes sichtbares Form-Element ausser einem Hidden-Feld; keine eigene Persistenz ueber die Option-Spalte hinaus. Die `use`-Importe `actions_info`/`subbookings_info` werden nicht verwendet (Altlast). Kollaborateure: `fields_info` (`get_class_name`).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Uebernimmt die `returnurl` vom Formular in die zu speichernde Option, mit Leerstring als Fallback.
- **Seiteneffekte:** Ermittelt den Feldnamen via `fields_info::get_class_name(static::class)` und setzt `$newoption->{$key}` auf den Formularwert bzw. `''`. Keine DB-Zugriffe, kein Change-Tracking. Implementiert die Default-Save-Logik direkt (statt `parent::prepare_save_field` aufzurufen).
- **Rueckgabe:** Immer `[]`. PHPDoc `@return string` ist falsch.
- **Bewertung:** B — korrekt, aber redundant: die Methode dupliziert exakt die Logik von `field_base::prepare_save_field` (mit Default `''`), statt sie zu delegieren.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Registriert ein verstecktes `returnurl`-Feld.
- **Seiteneffekte:** Mutiert `$mform`: `addElement('hidden', 'returnurl')`, `setType('returnurl', PARAM_LOCALURL)`. Kein Header (trotz `$applyheader`-Parameter), da Hidden-only.
- **Rueckgabe:** void.
- **Bewertung:** A — minimal und korrekt; `PARAM_LOCALURL` beschraenkt die URL serverseitig auf site-lokale Ziele und verhindert Open-Redirect-Missbrauch ueber das Feld.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories` = NECESSARY, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.47–81).

## Bewertungs-Resümee
Ein triviales Hidden-Field zur Steuerung des Redirect-Ziels. Sicherheitsseitig sauber dank `PARAM_LOCALURL`. Einzige Beanstandungen sind kosmetisch: `prepare_save_field` dupliziert die Basisklassen-Logik statt zu delegieren, der `@return string`-Doc-Mismatch, und zwei ungenutzte `use`-Importe (`actions_info`, `subbookings_info`). Klassen-Score **B / P3**.
