# certificate_conditions_interface — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/certificate_conditions_interface.php` · **LOC:** 116 · **Subsystem:** S19 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`certificate_conditions_interface` ist der Vertrag, den jeder Condition-Handler des Drei-Saeulen-Zertifikats-Frameworks (Conditions / Filters / Actions) erfuellen muss. Eine Condition kapselt die Logik „wann gilt eine Zertifikatsbedingung als erfuellt": sie traegt eigene Felder in das dynamische Booking-Option-Formular ein, serialisiert ihre Konfiguration nach JSON, rehydriert sich aus einem `booking_cert_cond`-Record bzw. JSON, evaluiert sich gegen einen Laufzeit-Kontext (typischerweise ein Event) und kann zugehoerige Items persistieren. Keine eigene Persistenz (reines Interface). Kollaborateure: `MoodleQuickForm`, `stdClass`, die konkrete Implementierung `conditions\taggedoptions` / `conditions\bookingoption`, sowie die Discovery-/Orchestrierungsschicht (`conditions_info`, `certificate_conditions`).

## Methoden

### `public function add_logic_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt die logikspezifischen Formularfelder (z. B. Required-Count, Option-Auswahl) in das uebergebene Quickform ein. **Seiteneffekte:** mutiert `$mform` (per Referenz) und optional `$ajaxformdata`. **Bewertung:** A — klarer Form-Builder-Hook.

### `public function get_name_of_logic(bool $localized = true): string` — public
- **Zweck:** Liefert den Anzeige-/Identifikator-Namen des Handlers; lokalisiert fuer die UI, unlokalisiert als stabiler Key. **Rueckgabe:** string. **Bewertung:** A.

### `public function save_condition(stdClass &$data): void` — public
- **Zweck:** Serialisiert die eingereichte Logik-Konfiguration in das JSON-Feld der `$data` (per Referenz). **Seiteneffekte:** mutiert `$data`. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt die Formular-Defaultwerte aus einem bestehenden Condition-Record vor. **Seiteneffekte:** mutiert `$data`. **Rueckgabe:** untypisiert (`mixed`) — der Vertrag laesst den Rueckgabewert offen, Implementierungen geben i. d. R. void zurueck. **Bewertung:** A — uneinheitlicher Rueckgabetyp ggü. den anderen `void`-Methoden, aber dokumentiert.

### `public function set_logicdata(stdClass $record): void` — public
- **Zweck:** Befuellt die internen Instanz-Properties des Handlers aus einem Condition-Record (inkl. ggf. zugehoeriger Items). **Seiteneffekte:** mutiert die Instanz. **Bewertung:** A.

### `public function set_conditiondata_from_json(string $json): void` — public
- **Zweck:** Befuellt die internen Properties allein aus dem JSON-Payload (ohne DB-Record). **Seiteneffekte:** mutiert die Instanz. **Bewertung:** A — leichtgewichtiger Rehydrierungspfad fuer die Evaluierung.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Traegt die Condition-Constraints in einen SQL-Builder-Kontext (`$sql`, `$params`) ein. **Seiteneffekte:** mutiert `$sql`/`$params`. **Bewertung:** A — generischer Query-Erweiterungs-Hook (kann je Handler ein No-op sein).

### `public function evaluate(stdClass $context): bool` — public
- **Zweck:** Wertet die Bedingung gegen einen Laufzeit-Kontext aus; `true` => Bedingung feuert. **Rueckgabe:** bool. **Bewertung:** A — Kern der Evaluierungs-Pipeline.

### `public function validate(array $data): array` — public
- **Zweck:** Validiert die logikspezifischen Formulardaten. **Rueckgabe:** Map `feldname => fehlermeldung` (leer = valide). **Bewertung:** A.

### `public function save_items(int $conditionid, stdClass $data): void` — public
- **Zweck:** Persistiert die zur Condition gehoerenden Items (z. B. Option-IDs) nach Vergabe der `$conditionid`. **Seiteneffekte:** DB-Schreibzugriff in der Implementierung. **Bewertung:** A.

## Bewertungs-Resümee
Sauberer, vollstaendiger Handler-Vertrag mit klarer Trennung von Form-Aufbau, (De-)Serialisierung, Persistenz und Evaluierung. Einziger Wermutstropfen: `set_defaults` ist als einzige Methode untypisiert (`mixed`) statt `void`, was die ansonsten konsistente Signatur-Linie bricht. Funktional unkritisch. Klassen-Score **A / -**.
