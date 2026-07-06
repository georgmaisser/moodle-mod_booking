# template — Methoden-Doku
**Datei:** `classes/option/fields/template.php` · **LOC:** 309 · **Subsystem:** S02 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S02_option_fields.md)

## Klassenueberblick
`template` ist der Field-Handler (erbt `field_base`), der das Instanziieren einer neuen Buchungsoption aus einer Options-Vorlage steuert. Vorlagen sind `booking_options`-Records mit `bookingid = 0`. Die Klasse rendert ein Dropdown der verfuegbaren Templates (mit PRO-Gating auf genau eine Vorlage in der Free-Version), und kopiert beim Umschalten die kompletten Feldwerte der gewaehlten Vorlage in das aktuelle Formular. Sie wird im NORMAL-Save ausgefuehrt und sitzt unter dem General-Header. Persistenz: liest `booking_options` (Templates) direkt via `$DB`; schreibt selbst nichts. Kollaborateure: `fields_info` (set_data/Header), `dates`, `wb_payment` (PRO-Gate), `MoodleQuickForm_duration`, `booking_option_settings`.

## Methoden

### `public static function prepare_save_field(stdClass &$formdata, stdClass &$newoption, int $updateparam, $returnvalue = null): array` — public static
- **Zweck:** Pflicht-Override; reiner Pass-through an die Basis. **Seiteneffekte:** `parent::prepare_save_field(...)` mit `''`. **Rueckgabe:** Basis-Changes-Array. **Bewertung:** A — keine eigene Save-Logik (Template ist nur ein Bestueck-Mechanismus fuer das Formular).

### `public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array $optionformconfig, $fieldstoinstanciate = [], $applyheader = true)` — public static
- **Zweck:** Baut das Template-Auswahl-Dropdown (nur fuer NEUE Optionen, `empty($formdata['id'])`). **Seiteneffekte:** `global $DB`; `$DB->get_records('booking_options', ['bookingid' => 0], '', 'id, text, json', 0, 0)`; JSON-Decode je Template fuer `templatename`-Anzeige; `usort` nach Anzeigename; optional `fields_info::add_header_to_mform`; registriert NoSubmit-Button `btn_changetemplate` (versteckt, JS-Reload-Trigger); fuegt `select optiontemplateid` hinzu. PRO-Gate: ohne aktivierte PRO-Version wird auf das erste Template reduziert und ein Hinweis-Element eingefuegt. **Rueckgabe:** void (mehrere early returns: bei vorhandener id und bei keinen Templates). **Bewertung:** B — solide; `usort` + JSON-Decode pro Template ist bei vielen Vorlagen leicht teuer, aber Template-Mengen sind klein. Der Submit-Button-Label `'xxx'` ist ein Platzhalter (kosmetisch, da `d-none`).

### `public static function set_data(stdClass &$data, booking_option_settings $settings)` — public static
- **Zweck:** Zwei Aufgaben: (1) beim Editieren eines bestehenden Templates `templatename` aus dem JSON in die Formulardaten heben; (2) beim Umschalten der Vorlage (`btn_changetemplate`) die komplette Vorlage als Datenbasis in `$data` kopieren. **Seiteneffekte:** Fuer den Umschalt-Pfad: baut ein `$templateoption`-Objekt (`fromtemplate => true`, kopiert cmid/bookingid, `id`/`optionid` = gewaehltes Template, `copyoptionid = 0` zur Loop-Vermeidung), ruft `fields_info::set_data($templateoption)` (laedt alle Felder der Vorlage), uebernimmt dann alle nicht-excludeten Keys in `$data` und nullt alle `optiondateid`-Keys. Wirft am Ende **unbedingt** `moodle_exception('errorloadingtemplate')`. **Rueckgabe:** void (mutiert `$data`). **Bewertung:** C — der finale, immer geworfene `moodle_exception` ist Control-Flow-Missbrauch: er verwirft die soeben in `$data` geschriebenen Werte aus Sicht des aufrufenden Save-Pfades (die Mutation wirkt nur, falls ein uebergeordneter Handler die Exception faengt und `$data` weiterverwendet). Ohne diesen Kontext sind die vorangehenden Schreibzugriffe toter Aufwand. Stark verschachtelt und schwer zu testen.

### `public static function definition_after_data(MoodleQuickForm &$mform, $formdata)` — public static
- **Zweck:** Nach dem Datenladen die aus der Vorlage stammenden Werte tatsaechlich in die Form-Elemente schreiben (Typ-gerechte Konvertierung). **Seiteneffekte:** Liest `$mform->_defaultValues`, ueberschreibt damit `$formdata`; nur wenn `btn_changetemplate` gesetzt: iteriert alle Default-Werte und `setValue` je existierendem Element; konvertiert `date_time_selector` via `dates::timestamp_to_array`, `duration` via einer Wegwerf-`MoodleQuickForm_duration`-Instanz und `seconds_to_unit`. **Rueckgabe:** void. **Bewertung:** C — greift auf das private/interne `_defaultValues` und instanziiert ein Dummy-Duration-Element nur fuer dessen Methode — beides fragile Kopplung an QuickForm-Interna; doppelter `elementExists`-Check (Z.283/284) ist redundant.

### Triviale Properties
Sechs statische Konfigurations-Properties (`$id`, `$save = NORMAL`, `$header = GENERAL`, `$fieldcategories`, `$alternativeimportidentifiers`, `$incompatiblefields`, Z.51–83).

## Bewertungs-Resümee
Funktional wichtiger, aber strukturell heikler Field-Handler. Die Template-Anwendung haengt an drei fragilen Mechanismen: dem unbedingt geworfenen `errorloadingtemplate` als Steuerfluss, dem Zugriff auf QuickForm-Interna (`_defaultValues`) und einer Dummy-Element-Instanziierung. Kein direkter Datenverlust im Normalbetrieb, aber hoher Wartungs- und Test-Aufwand und nicht-offensichtliches Verhalten. Klassen-Score **C / P2**.
