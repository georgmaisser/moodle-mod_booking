# taggedoptions — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/conditions/taggedoptions.php` · **LOC:** 270 · **Subsystem:** S19 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`taggedoptions` ist ein Condition-Handler (`certificate_conditions_interface`) fuer die „Self-Tagging"-Variante: Eine Buchungsoption haengt sich selbst per Option-Formular an bestehende Zertifikatsbedingungen an. Die Konfiguration umfasst einen Required-Count (wie viele der zugeordneten Optionen abgeschlossen sein muessen). Persistenz verteilt sich auf zwei Stellen: der Required-Count liegt im `logicjson` des `booking_cert_cond`-Records, die zugeordneten Option-IDs in der Item-Tabelle `booking_cert_cond_item` (component=`mod_booking`, area=`bookingoption`). Evaluiert wird gegen ein `bookingoption_completed`-Event via Abschluss-Pruefung der Buchungsantworten. Kollaborateure: `$DB`, `singleton_service` (Option-Settings + Booking-Answers), `bookingoption_completed`-Event.

## Methoden

### `public function add_logic_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt das Required-Count-Textfeld (`condition_taggedoptions_requiredcount`, PARAM_INT, Default 1) ein. **Seiteneffekte:** mutiert `$mform`; ermittelt vorab `$cmid` aus `ajaxformdata['cmid']` bzw. via `$DB->get_record('context', ...)` aus `contextid` — **dieser ermittelte `$cmid` wird jedoch nirgends verwendet** (Dead Code / unnoetiger DB-Read im Form-Render). **Bewertung:** C — ueberfluessiger Context-Lookup ohne Wirkung; Feldname divergiert vom in `save_condition`/`set_defaults` gelesenen Namen (siehe Resümee).

### `public function get_name_of_logic(bool $localized = true): string` — public
- **Zweck:** Liefert Label `condition_taggedoptions` (lokalisiert oder roh). **Rueckgabe:** string. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Serialisiert `{conditionname:'taggedoptions', requiredcount}` nach `$data->conditionjson`. **Seiteneffekte:** mutiert `$data`; liest `$data->condition_bookingoption_requiredcount` (Zeile 97). **Bewertung:** D — **Feldnamen-Mismatch:** das Formular liefert `condition_taggedoptions_requiredcount`, gelesen wird `condition_bookingoption_requiredcount`. Der eingegebene Wert wird nie persistiert, `requiredcount` faellt immer auf 1 zurueck (siehe Findings). `global $DB;` deklariert, aber ungenutzt; Einrueckung Zeile 103 verrutscht.

### `public function save_items(int $conditionid, stdClass $data): void` — public
- **Zweck:** Verknuepft die aktuelle Option (`$data->optionid`) mit jeder in `$data->conditions` gewaehlten Bedingung in `booking_cert_cond_item`. **Seiteneffekte:** pro Ziel-Condition erst `delete_records` (alte Verknuepfung dieser optionid), dann `insert_record` mit area/component/leerem configjson. **Bewertung:** B — delete-then-insert haelt die Verknuepfung idempotent; ungenutztes `$conditionid`-Argument (es wird ueber `$data->conditions` iteriert, nicht ueber den uebergebenen Parameter) ist gewollt fuer diese Self-Tagging-Variante, aber leicht verwirrend.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Liest `requiredcount` aus `$record->logicjson` und schreibt ihn in `$data->condition_bookingoption_requiredcount` (Zeile 152). **Seiteneffekte:** mutiert `$data`; ungenutztes `global $DB`. **Bewertung:** D — schreibt erneut auf den **falschen Feldnamen** (`condition_bookingoption_requiredcount` statt `condition_taggedoptions_requiredcount`); das Formularfeld zeigt den gespeicherten Wert daher nie an (Default bleibt 1).

### `public function set_logicdata(stdClass $record): void` — public
- **Zweck:** Laedt die zugeordneten Option-IDs aus `booking_cert_cond_item` in `$this->optionids`, setzt `$this->optionid` auf das erste, und liest `requiredcount` aus `$record->logicjson`. **Seiteneffekte:** `$DB->get_records('booking_cert_cond_item', ...)`. **Bewertung:** B — sauberes Mapping via `array_column`/`array_map('intval', ...)`; ungenutztes `global $DB`-Pattern (hier tatsaechlich genutzt).

### `public function set_conditiondata_from_json(string $json): void` — public
- **Zweck:** Setzt `$this->requiredcount` aus dem JSON-Payload (min. 1). **Seiteneffekte:** mutiert Instanz; laedt **keine** Option-IDs (anders als `set_logicdata`). **Bewertung:** B — fuer den reinen JSON-Pfad fehlen die `optionids`, was die spaetere `evaluate`-Auswertung leerlaufen liesse, falls dieser Pfad ohne vorheriges `set_logicdata` genutzt wird.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** SQL-Constraint-Hook. **Seiteneffekte:** keine — leerer Rumpf. **Bewertung:** B — bewusstes No-op (diese Condition wird eventbasiert, nicht per Query ausgewertet).

### `public function evaluate(stdClass $context): bool` — public
- **Zweck:** `true`, wenn der User genug der zugeordneten Optionen abgeschlossen hat. **Seiteneffekte:** pro Kandidaten-Option `singleton_service::get_instance_of_booking_option_settings()` + `get_instance_of_booking_answers()` + `is_activity_completed($userid)`. **Rueckgabe:** bool. **Bewertung:** B — feuert nur fuer `bookingoption_completed`-Events; `requiredcount` wird auf `count(candidateoptionids)` gedeckelt; userid-Auflösung mit mehrstufigem Fallback; early-return sobald Schwelle erreicht (begrenzt die N+1-Schleife). Singleton-Service ist gecacht, daher keine echte DB-N+1.

### `public function validate(array $data): array` — public
- **Zweck:** Validierung. **Rueckgabe:** immer leeres Array. **Bewertung:** B — bewusst keine Regeln (Required-Count ist PARAM_INT mit Default).

### Triviale Properties
Drei oeffentliche Properties (`optionid`, `optionids`, `requiredcount`, Z.38-50) als Konfig-Halter.

## Bewertungs-Resümee
Funktional traegt die Klasse die Self-Tagging-Logik korrekt, aber zwei zusammengehoerige Feldnamen-Mismatches (Form-Feld `condition_taggedoptions_requiredcount` vs. gelesenes/geschriebenes `condition_bookingoption_requiredcount` in `save_condition`/`set_defaults`) brechen die Konfigurierbarkeit des Required-Counts in beide Richtungen: der eingegebene Wert wird nie gespeichert und ein gespeicherter Wert nie ins Formular zurueckgeladen — effektiv ist `requiredcount` auf 1 festgenagelt. Dazu kommen ein wirkungsloser Context/cmid-Lookup im Form-Render und mehrere ungenutzte `global $DB`. Klassen-Score **C / P2**.
