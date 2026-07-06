# createcertificate — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/actions/createcertificate.php` · **LOC:** 231 · **Subsystem:** S19 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`createcertificate` ist die einzige konkrete Action des Zertifikatsbedingungs-Frameworks und implementiert `action_interface`. Sie stellt — wenn eine Zertifikatsbedingung fuer einen User erfuellt ist — ueber `mod_booking\local\certificateclass::issue_certificate` ein `tool_certificate`-Zertifikat aus. Konfiguration (Template-id `certid` + Ablaufdatum-Felder) wird als JSON in der Condition gespeichert und ueber die Setter wieder in die Instanz-Properties hydriert. Persistenz: indirekt ueber `certificateclass::issue_certificate` (Tabelle `tool_certificate_issues`); Konfiguration als JSON in der Condition. Kollaborateure: `certificateclass`, `tool_certificate\certificate` (Form-Helfer + Existenzpruefung), `actions_info::certificate_already_issued` (Idempotenz), `$DB` (Template-Liste). Idempotenz wird ueber die globale Config `booking/issuemultiplecertificates` gesteuert.

## Properties
`$certid` (int, Default 0), `$expirydatetype` (int), `$expirydateabsolute` (int), `$expirydaterelative` (int) — Konfigurationswerte, die aus dem Action-JSON hydriert werden.

## Methoden

### `public function add_action_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt das Template-Auswahlfeld (`action_createcertificate_certid`, PARAM_INT) und — falls `tool_certificate` installiert ist — die Ablaufdatum-Felder via `tool_certificate\certificate::add_expirydate_to_form` ein. **Seiteneffekte:** `$mform`-Mutation; ruft `get_available_certificate_templates` (DB-Read). **Bewertung:** A — defensives `class_exists`-Gate fuer die optionale Abhaengigkeit.

### `private static function get_available_certificate_templates(): array` — private static
- **Zweck:** Liefert die Template-Auswahl (`id => name`), fuehrend mit einem `choose...`-Platzhalter. Wenn `tool_certificate` fehlt, nur der Platzhalter. **Seiteneffekte:** `$DB->get_records('tool_certificate_templates', …, 'id, name')`. **Rueckgabe:** Array. **Bewertung:** A — laedt nur `id,name`, sortiert nach Name; saubere Degradation ohne Plugin.

### `public function get_name_of_action(bool $localized = true): string` — public
- **Zweck:** Liefert `action_createcertificate` als lokalisierten String oder rohen Schluessel. **Bewertung:** A.

### `public function save_action(stdClass &$data): void` — public
- **Zweck:** Serialisiert Formwerte (actionname, certid, expirydate*-Felder) als `actionjson` in `$data`. **Seiteneffekte:** mutiert `$data->actionjson`. **Bewertung:** A — defensives Int-Cast + Null-Coalescing.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Befuellt Formdefaults aus `record->actionjson` (certid + expirydate-Felder). **Seiteneffekte:** mutiert `$data`. **Bewertung:** B — `$data->action_createcertificate_certid` wird (anders als die uebrigen Felder) ohne `(int)`-Cast gesetzt; uneinheitlich, aber das Formfeld ist PARAM_INT.

### `public function set_actiondata(stdClass $record): void` — public
- **Zweck:** Vertraglich: internen Zustand aus Condition-Record setzen. **Seiteneffekte:** keine. **Bewertung:** B — leerer No-op-Body; die Hydration laeuft ausschliesslich ueber `set_actiondata_from_json`. Der leere Rumpf ist nicht zwingend ein Bug (die Quelle ruft den JSON-Setter), aber eine stille Vertragsluecke.

### `public function set_actiondata_from_json(string $json): void` — public
- **Zweck:** Hydriert die Instanz-Properties (certid, expirydate*) aus dem JSON-Payload. **Seiteneffekte:** mutiert `$this`. **Bewertung:** A — `json_decode`-Ergebnis wird vor Zugriff geprueft, Int-Cast + Null-Coalescing.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Vertraglicher SQL-Builder-Hook. **Seiteneffekte:** keine (leerer Body). **Bewertung:** B — No-op; fuer eine ausstellende Action ohne SQL-Logik konsequent, dokumentiert die unbenutzte Interface-Methode.

### `public function execute_action(stdClass $context, stdClass $condition): void` — public
- **Zweck:** Kernlogik: stellt das Zertifikat aus. Bricht ab, wenn `userid`/`optionid` aus dem Kontext oder `certid` fehlen. Ist die Config `booking/issuemultiplecertificates` nicht gesetzt, prueft `actions_info::certificate_already_issued` und bricht bei vorhandener Ausstellung ab (Idempotenz/Schutz vor Doppelausstellung). Andernfalls Aufruf `certificateclass::issue_certificate(optionid, userid, time(), certid, expirydatetype, expirydateabsolute, expirydaterelative, condition)`. **Seiteneffekte:** stellt ein Zertifikat aus (DB-Schreibvorgang in `tool_certificate`); liest globale Config. **Bewertung:** B — funktional korrekt mit explizitem Idempotenz-Gate; Hinweis: `get_config('booking', …)` nutzt die Komponente `booking` statt `mod_booking` (in mod_booking ueblich, hier konsistent mit der Schreibseite). Keine eigene Capability-Pruefung — wird vom aufrufenden Bedingungs-Evaluator vorausgesetzt.

### `public function validate(array $data): array` — public
- **Zweck:** Pflichtfeld-Validierung: Fehler, wenn kein Template (`action_createcertificate_certid`) gewaehlt wurde. **Rueckgabe:** Fehler-Map. **Bewertung:** A.

## Bewertungs-Resümee
Saubere, defensiv programmierte einzige Action des Frameworks: optionale `tool_certificate`-Abhaengigkeit ist durchgehend per `class_exists` abgesichert, die Idempotenzpruefung gegen Doppelausstellung ist vorhanden und Config-gesteuert. Schwaechen sind kosmetisch/strukturell: der leere `set_actiondata`-Body (Hydration nur ueber JSON), der No-op-`execute`-SQL-Hook, ein fehlender Int-Cast in `set_defaults` und der Copy-Paste-Datei-DocBlock. Keine Datenverlust-/Sicherheitsprobleme gefunden. Klassen-Score **B / P3**.
