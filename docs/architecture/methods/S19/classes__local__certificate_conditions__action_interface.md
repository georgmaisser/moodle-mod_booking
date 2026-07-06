# action_interface — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/action_interface.php` · **LOC:** 105 · **Subsystem:** S19 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`action_interface` ist der Vertrag fuer Action-Handler innerhalb des Zertifikatsbedingungs-Frameworks (Drei-Saeulen-Modell Condition/Filter/Action). Eine Action ist die ausfuehrende Seite: wenn eine Zertifikatsbedingung fuer einen User erfuellt ist, fuehrt die zugeordnete Action etwas aus (aktuell nur `createcertificate`). Das Interface buendelt drei Aufgabenfelder: (1) Formular-Integration (`add_action_to_mform`, `get_name_of_action`, `validate`), (2) Persistenz/Hydration der Konfiguration als JSON (`save_action`, `set_defaults`, `set_actiondata`, `set_actiondata_from_json`) und (3) Ausfuehrung (`execute`, `execute_action`). Es definiert keine Persistenz selbst; die einzige bekannte Implementierung ist `actions\createcertificate`. Der DocBlock der Datei ist ein Copy-Paste aus `option_conditions_info` ("Helper to display certificate condition references…") und beschreibt das Interface nicht.

## Methoden (Vertrag, ohne Implementierung)

### `public function add_action_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt action-spezifische Felder in das DynamicForm ein. **Seiteneffekte:** Implementierungsabhaengig (Form-Mutation per Referenz). **Bewertung:** A.

### `public function get_name_of_action(bool $localized = true): string` — public
- **Zweck:** Liefert den Anzeige-/Schluesselnamen der Action (lokalisiert oder roh). **Rueckgabe:** String. **Bewertung:** A.

### `public function save_action(stdClass &$data): void` — public
- **Zweck:** Serialisiert die abgeschickte Action-Konfiguration in JSON-Formdaten. **Seiteneffekte:** mutiert `$data` per Referenz. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Setzt Default-Formwerte aus einem bestehenden Condition-Record. **Seiteneffekte:** mutiert `$data`. **Rueckgabe:** laut DocBlock `mixed` (Implementierung `createcertificate` gibt nichts zurueck). **Bewertung:** B — Rueckgabetyp im Vertrag unspezifiziert (`mixed` ohne Return-Typehint), waehrend andere Setter `void` deklarieren.

### `public function set_actiondata(stdClass $record): void` — public
- **Zweck:** Setzt internen Action-Zustand aus einem Condition-Record. **Bewertung:** A (Vertrag) — Hinweis: die einzige Implementierung ist ein No-op.

### `public function set_actiondata_from_json(string $json): void` — public
- **Zweck:** Setzt internen Action-Zustand aus JSON-Payload. **Bewertung:** A.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Wendet Action-Logik auf einen SQL-Builder-Kontext an (per Referenz). **Bewertung:** B — semantisch unklar fuer eine "Action" (klingt nach Condition-/Filter-Vertrag); in `createcertificate` ein No-op, wirkt wie ein aus dem gemeinsamen Framework-Vertrag vererbter, hier unbenutzter Slot.

### `public function execute_action(stdClass $context, stdClass $condition): void` — public
- **Zweck:** Fuehrt die eigentliche Action im gegebenen Kontext (User/Option) aus. **Seiteneffekte:** implementierungsabhaengig (z. B. Zertifikatsausstellung). **Bewertung:** A — Kern-Methode des Vertrags.

### `public function validate(array $data): array` — public
- **Zweck:** Validiert action-spezifische Formdaten. **Rueckgabe:** Map `feldname => fehlermeldung`. **Bewertung:** A.

## Bewertungs-Resümee
Sauber dokumentierter, kohaerenter Interface-Vertrag fuer die Action-Saeule des Zertifikatsframeworks. Kleine Unstimmigkeiten: der Datei-Header-DocBlock ist ein irrefuehrendes Copy-Paste, `set_defaults` deklariert `mixed` statt `void` und `execute(stdClass &$sql, array &$params)` wirkt fuer eine Action konzeptionell deplatziert (vermutlich aus einem geteilten Condition/Filter-Vertrag uebernommen, in der einzigen Implementierung leer). Reines Interface, kein Laufzeitverhalten. Klassen-Score **A / P3**.
