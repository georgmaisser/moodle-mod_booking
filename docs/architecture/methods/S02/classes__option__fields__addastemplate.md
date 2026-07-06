# addastemplate — Methoden-Doku
**Datei:** `classes/option/fields/addastemplate.php` · **LOC:** 210 · **Subsystem:** S02 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`addastemplate` ist ein Option-Feld-Handler (erweitert `field_base`) aus der Feld-Plugin-Familie der Buchungsoption. Verantwortung: eine Buchungsoption als globale Vorlage (Template) speichern. Eine Template-Option wird dadurch markiert, dass ihre `bookingid` auf `0` gesetzt wird; zusaetzlich wird ein optionaler `templatename` im JSON-Feld der Option abgelegt. Kollaborateure: `booking_option` (JSON-Helfer `add_data_to_json`/`remove_key_from_json`), `singleton_service` (Settings-Lookup fuer Import-Fall), `wb_payment` (PRO-Gate fuer mehr als eine Vorlage), `field_base` (Save-Pipeline), Moodle-mform. Reine statische Klasse ohne Instanzzustand; alle gesetzten `public static`-Properties sind Metadaten der Feld-Registry (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`).

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Interpretiert den Formularwert `addastemplate`; ist er gesetzt, wird die Option zur Vorlage (`bookingid = 0`) und der Vorlagenname konsistent ins JSON geschrieben.
- **Seiteneffekte:** Mutiert `$newoption` (`bookingid`, JSON via `booking_option::add_data_to_json`/`remove_key_from_json`). Im Import-Fall (Feld `templatename` absent, z. B. CSV/Webservice) DB/Cache-Read via `singleton_service::get_instance_of_booking_option_settings($newoption->id ?? 0)`, um den bestehenden Vorlagennamen zu erhalten. Delegiert abschliessend an `parent::prepare_save_field`.
- **Rueckgabe:** `array` (Ergebnis von `parent::prepare_save_field`, i. d. R. leer). PHPDoc behauptet faelschlich `string`, Signatur deklariert `array` — Familien-typischer Doc-Smell.
- **Bewertung:** B — sauber kommentierte Drei-Fall-Logik (Wert gesetzt / geleert / abwesend). Minor: greift bei `bookingid`-Setzung nicht ab, ob `templatename` bei nicht-Template-Speicherung evtl. aus dem JSON entfernt werden muesste (nur der Template-Pfad pflegt das JSON).

### `public static function validation(array $data, array $files, array &$errors)` — public static
- **Zweck:** Erzwingt fuer Template-Speicherung, dass entweder ein Optionstitel (`text`) oder ein `templatename` gesetzt ist.
- **Seiteneffekte:** Mutiert `$errors` (Schluessel `templatename`) bei Verletzung.
- **Rueckgabe:** `array $errors`.
- **Bewertung:** A — kompakte, korrekte Trim/Empty-Pruefung; klare Fehlermeldung.

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut den Template-Header und die Felder „addastemplate" (Select notemplate/asglobaltemplate) plus „templatename" — nur sichtbar mit Capability `mod/booking:manageoptiontemplates` und beim Neuanlegen (`id < 1`) bzw. wenn bereits als Template markiert.
- **Seiteneffekte:** DB-Read `$DB->count_records('booking_options', ['bookingid' => 0])`; `wb_payment::pro_version_is_activated()`. Mutiert `$mform` (Header, Select, Text, Hilfe-Button, Regel, `hideIf`). Ohne PRO und bei bereits >=1 Template wird statt der Felder ein Lizenz-Hinweis (`nolicense`) gerendert.
- **Rueckgabe:** void.
- **Bewertung:** B — gemischte Verantwortung (Capability-Gate + PRO-Gate + Formdefinition), aber linear und nachvollziehbar. `global $DB` korrekt deklariert und genutzt.

### Triviale Properties
Sechs `public static` Registry-Metadaten-Properties (`$id`, `$save`, `$header`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.44–80) ohne Logik.

## Bewertungs-Resümee
Fokussierter Feld-Handler mit sorgfaeltig kommentierter JSON-Pflege fuer den Vorlagennamen und korrektem doppelten Gate (Capability + PRO) im Formular. Schwaechen sind kosmetisch: der familientypische `string`-vs-`array`-PHPDoc-Mismatch und die nur im Template-Pfad gepflegte JSON-Konsistenz. Keine funktionalen oder Sicherheits-Defekte. Klassen-Score **B / P3**.
