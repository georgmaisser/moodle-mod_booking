# option_conditions_info — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/option_conditions_info.php` · **LOC:** 201 · **Subsystem:** S19 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`option_conditions_info` ist eine reine Static-Util-Klasse, die die Bruecke zwischen dem Buchungsoption-Formular und dem Zertifikatsbedingungs-Framework bildet. Sie zeigt im Optionsformular an, welche Zertifikatsbedingungen die Option bereits referenzieren (mit Edit-Links), bietet ein Autocomplete zum Selbst-Taggen der Option an bestehende `taggedoptions`-Bedingungen und persistiert diese Verknuepfungen. Persistenz: Tabellen `booking_cert_cond` (Bedingung) und `booking_cert_cond_item` (Verknuepfung Bedingung↔Item). Kollaborateure: `$DB` (Roh-SQL-Lookups), `html_writer`/`moodle_url` (Anzeige), `taggedoptions` (Speicherlogik via `save_items`).

## Methoden

### `public static function add_static_info_to_mform(MoodleQuickForm &$mform, array $formdata): void` — public static
- **Zweck:** Fuegt dem Optionsformular (a) ein Multi-Autocomplete `taggedconditions` der verfuegbaren taggedoptions-Bedingungen und (b) ein statisches Info-Element mit den die Option bereits adressierenden Bedingungen (als verlinkte Liste) hinzu. **Seiteneffekte:** `get_all_taggedoptions_conditions()` und `get_condition_infos_targeting_option()` (DB); mehrere `addElement`/`setType`; `s()`-Escaping der Namen. **Bewertung:** B — `$taggedconditions = ['Choose'] + $taggedconditions;` injiziert ein hartkodiertes, nicht lokalisiertes „Choose" mit Key 0; ansonsten korrekt (Union `+` bewahrt die id-Keys, da Bedingungs-ids ≥ 1).

### `private static function get_condition_infos_targeting_option(int $optionid): array` — private static
- **Zweck:** Liefert Name + Edit-URL aller Bedingungen, deren `booking_cert_cond_item.itemid` der Option entspricht. **Seiteneffekte:** ein `get_records_sql` (JOIN `booking_cert_cond` + LEFT JOIN `context`). Baut die URL kontextabhaengig: `CONTEXT_SYSTEM` → `contextid`-Param, `CONTEXT_MODULE` → `cmid`-Param (sonst keine URL). **Rueckgabe:** Liste `['name', 'url']`. **Bewertung:** A — sauber parametrisiertes Roh-SQL, defensives Casting der Kontextwerte.

### `public static function get_all_taggedoptions_conditions()` — public static
- **Zweck:** Liefert Map `condid => name` aller Bedingungen, deren `logicjson.conditionname === 'taggedoptions'`. **Seiteneffekte:** `$DB->get_records('booking_cert_cond', ...)`; pro Record `json_decode($record->logicjson)`. **Rueckgabe:** Array. **Bewertung:** B — `$conditionjson->conditionname` wird ohne Null-/Property-Guard gelesen; bei syntaktisch defektem `logicjson` liefert `json_decode` `null` und der Property-Zugriff erzeugt eine PHP-Warning (der vorgelagerte `empty($record->logicjson)`-Guard faengt nur leere, nicht ungueltige JSON). P3-Robustheit.

### `public static function get_tagged_condition_ids_for_option(int $optionid): array` — public static
- **Zweck:** Liefert die IDs der taggedoptions-Bedingungen, die die Option referenzieren. **Seiteneffekte:** `get_records_sql` mit `c.logicjson LIKE '%"conditionname":"taggedoptions"%'`. **Rueckgabe:** Liste von Bedingungs-ids (`array_keys`). **Bewertung:** B — der `LIKE`-Match auf serialisiertes JSON ist fragil gegenueber Whitespace-Varianten im logicjson; fuer den vom Framework erzeugten kanonischen JSON aber zuverlaessig.

### `public static function save_tagged_conditions_from_option_form(array $formdata): void` — public static
- **Zweck:** Persistiert die im Formular gewaehlten taggedconditions fuer die Option. **Seiteneffekte:** baut ein `$data`-Objekt (optionid + `conditions`) und delegiert an `(new taggedoptions())->save_items(0, $data)`. **Bewertung:** B — bricht ohne `optionid` still ab (frueher Return); die eigentliche Persistenz liegt vollstaendig in `taggedoptions::save_items` (Delegations-Adapter).

## Bewertungs-Resümee
Schlanke Anzeige-/Tagging-Bruecke mit gut parametrisierten Roh-SQL-Lookups und sauberem Output-Escaping. Schwaechen sind das nicht lokalisierte „Choose", der LIKE-auf-JSON-Match und der fehlende Decode-Guard in `get_all_taggedoptions_conditions`. Alles funktional unkritisch. Klassen-Score **B / P3**.
