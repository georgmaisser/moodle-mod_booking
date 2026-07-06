# add_price_category_skill — Methoden-Doku
**Datei:** `classes/local/wizard/options/skills/add_price_category_skill.php` · **LOC:** 283 · **Subsystem:** S15 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S15_*.md)

## Klassenueberblick
`add_price_category_skill` ist der Agent-Skill `mod_booking.add_price_category`: legt eine neue Preiskategorie (z.B. student/member/external) fuer Buchungsoptions-Pricing an. Erbt von `booking_skill_base` und implementiert `skill_trigger_provider_interface` (aus `bookingextension_agent`). Risikoklasse R2, Pflicht-Capability `moodle/site:config` (Site-Level). Persistenz delegiert vollstaendig an `pricecategories_handler` (`upsert_pricecategory` / `get_pricecategories_indexed_by_identifier`); kein eigener DB-Zugriff. Lebenszyklus folgt dem Skill-Vertrag: Selbstbeschreibung (`get_schema`/Trigger/Guidance), `check_structure` (oberflaechlich), `run_preflight` (tief, Capability + Duplikat-Bestaetigung), `execute` (Upsert + lokalisierte Nutzermeldung). Kollaborateure: `context_system`, `pricecategories_handler`, geerbte Helfer (`localized_string`, `get_output_language`, `invalid`/`confirmable`/`pass`, `build_task_debug_message`, `resolve_cmid_from_context_or_cmid`).

## Methoden

### `public function __construct()` — public
- **Zweck:** Initialisiert die Skill-Basis mit `readonly=false`, Risikoklasse `R2` und benoetigter Capability `['moodle/site:config']`. **Seiteneffekte:** `parent::__construct(...)`. **Bewertung:** A.

### `public function get_name(): string` — public
- **Zweck:** Liefert den Task-Namen (`self::TASK_NAME = 'mod_booking.add_price_category'`). **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_contextual_prompt_packs(): array` — public
- **Zweck:** Liefert ein Guidance-Pack `mod_booking.pricing` mit Triggern (price/preise/cost/...) und Hinweisen zur Preis-Objekt-Struktur und zum Override-Token `duplicate_identifier`. **Seiteneffekte:** keine. **Rueckgabe:** `array<int,array<string,mixed>>`. **Bewertung:** A — statische Prompt-Anreicherung, fuettert die Selection/Construction-Phase.

### `public function get_schema(): array` — public
- **Zweck:** Beschreibt das Task-Schema (version 1, Description, `readonly` aus `is_read_only()`, Properties identifier[req]/name/defaultvalue/pricecatsortorder/override). **Seiteneffekte:** keine. **Rueckgabe:** array. **Bewertung:** A.

### `public function get_message_triggers(): array` — public
- **Zweck:** Definiert den Message-Trigger `mod_booking.confirm_duplicate_price_category` (User bestaetigt Duplikat). **Seiteneffekte:** keine. **Rueckgabe:** `array<int,array<string,mixed>>`. **Bewertung:** A.

### `public function check_structure(array $input): array` — public
- **Zweck:** Oberflaechliche Eingabevalidierung: `identifier` Pflicht und Muster `^[a-z0-9_-]+$/i`; `defaultvalue` (falls gesetzt) numerisch und nicht-negativ. Fehlertexte lokalisiert ueber Output-Sprache. **Seiteneffekte:** keine (nur `localized_string`-Lookups). **Rueckgabe:** `array{valid:bool,errors:array<int,string>}`. **Bewertung:** B — solide, aber die `defaultvalue`-Pruefung ist als `if !is_numeric ... else if <0` verschachtelt: ein nicht-numerischer Wert ueberspringt korrekterweise die `<0`-Pruefung; funktional richtig, nur leicht umstaendlich.

### `protected function run_preflight(array $input, int $cmid, int $userid): array` — protected
- **Zweck:** Tiefe Preflight-Pruefung: resolved cmid; ruft `check_structure`; prueft `moodle/site:config` fuer `$userid` im System-Kontext; ermittelt Override `duplicate_identifier`; laedt vorhandene Kategorien (`pricecategories_handler::get_pricecategories_indexed_by_identifier`) und verlangt bei aktivem Namensduplikat (disabled===0) ohne Override eine Bestaetigung; sonst normalisiert es Input (identifier/name getrimmt, defaultvalue float, pricecatsortorder int) und gibt `pass`. **Seiteneffekte:** `has_capability(...)`, `pricecategories_handler`-Instanziierung + DB-Read der Kategorien; keine Mutation. **Rueckgabe:** `invalid`/`confirmable`/`pass`-Struktur. **Bewertung:** B — korrekte Gate-Logik; Capability-Check hier nutzt explizit `$userid` (gut). Duplikat-Lookup nur per `strtolower(identifier)`, konsistent mit der Index-Map.

### `private function build_preflight_issues(array $messages): array` — private
- **Zweck:** Wandelt rohe Fehlermeldungen in Preflight-Issues (`code=PRICE_CATEGORY_PREFLIGHT_BLOCKED`, `severity=needs_clarification`), leere Strings uebersprungen. **Seiteneffekte:** keine. **Rueckgabe:** `array<int,array<string,string>>`. **Bewertung:** A.

### `public function execute(array $input, int $cmid, int $userid): array` — public
- **Zweck:** Fuehrt das Anlegen aus: resolved cmid, Re-Check der Capability, Default-Name aus identifier ableiten falls leer (`ucfirst` + `_`/`-`→Space), `pricecategories_handler::upsert_pricecategory(identifier, name, defaultvalue, sortorder?)`; reichert das Result um lokalisierte `usermessage`, `outputlang` und `debugmessage` an. **Seiteneffekte:** DB-Mutation via Handler (`upsert_pricecategory`), `has_capability`, `get_string`. **Rueckgabe:** array mit status/detail/resultid bzw. Handler-Result. **Bewertung:** B — funktional korrekt, aber zwei Punkte: (1) der Capability-Re-Check verwendet `has_capability('moodle/site:config', context_system::instance())` OHNE `$userid` (also gegen `$USER`), waehrend der Preflight gegen `$userid` prueft — inkonsistent, falls Engine den Task unter anderer Nutzeridentitaet ausfuehrt; (2) Anreicherung nur `if (is_array($result))` — bei nicht-array-Result (Handler-Vertragsbruch) faellt die Nutzermeldung still aus.

## Bewertungs-Resümee
Sauber strukturierter Mutations-Skill mit klarer Drei-Phasen-Trennung (structure/preflight/execute), korrektem R2-/Capability-Gate und Duplikat-Bestaetigung. Schwaechen sind kosmetisch bis gering: inkonsistenter Subject des Capability-Checks zwischen preflight ($userid) und execute ($USER), sowie still ausfallende Anreicherung bei nicht-array-Result. Persistenz vollstaendig an `pricecategories_handler` delegiert. Klassen-Score **B / P3**.
