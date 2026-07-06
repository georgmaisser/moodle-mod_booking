# filter_interface — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/filter_interface.php` · **LOC:** 106 · **Subsystem:** S19 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`filter_interface` ist der Vertrag fuer die Filter-Saeule des Zertifikats-Frameworks. Ein Filter schraenkt ein, fuer welche Kontexte/Personen eine ansonsten erfuellte Bedingung tatsaechlich greift (z. B. „nur Nutzer mit Profilfeld X = Y"). Wie die Condition-Handler traegt ein Filter eigene Formularfelder ein, serialisiert/rehydriert seine Konfiguration ueber JSON bzw. Record, kann SQL-Constraints einbringen und evaluiert sich gegen einen Laufzeit-Kontext. Keine eigene Persistenz (reines Interface). Kollaborateure: `MoodleQuickForm`, `stdClass`, die konkrete Implementierung `filters\userprofilefield`, sowie die Discovery-Schicht `filters_info`.

> Hinweis: Der Klassen-Doc-Block ist (offenbar per Copy-Paste) mit „Helper to display certificate condition references on booking option form." beschriftet — das beschreibt nicht das Interface, sondern stammt aus `filters_info`/`conditions_info`. Rein kosmetisch.

## Methoden

### `public function add_filter_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Fuegt die filterspezifischen Formularfelder ein. **Seiteneffekte:** mutiert `$mform`/`$ajaxformdata`. **Bewertung:** A.

### `public function get_name_of_filter(bool $localized = true): string` — public
- **Zweck:** Anzeige-/Identifikatorname des Filters. **Rueckgabe:** string. **Bewertung:** A.

### `public function save_filter(stdClass &$data): void` — public
- **Zweck:** Serialisiert die Filterkonfiguration in das JSON-Feld der `$data`. **Seiteneffekte:** mutiert `$data`. **Bewertung:** A.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Belegt Formular-Defaults aus einem bestehenden Record. **Seiteneffekte:** mutiert `$data`. **Rueckgabe:** untypisiert (`mixed`). **Bewertung:** A — wie beim Condition-Interface ist dies die einzige nicht-`void`-Methode.

### `public function set_filterdata(stdClass $record): void` — public
- **Zweck:** Befuellt die internen Properties aus einem Condition-Record. **Seiteneffekte:** mutiert Instanz. **Bewertung:** A.

### `public function set_filterdata_from_json(string $json): void` — public
- **Zweck:** Befuellt die internen Properties aus dem JSON-Payload. **Seiteneffekte:** mutiert Instanz. **Bewertung:** A.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Traegt Filter-Constraints in den SQL-Builder-Kontext ein. **Seiteneffekte:** mutiert `$sql`/`$params`. **Bewertung:** A.

### `public function evaluate(stdClass $context): bool` — public
- **Zweck:** `true`, wenn der Filter den Kontext akzeptiert (meist Event-Daten). **Rueckgabe:** bool. **Bewertung:** A.

### `public function validate(array $data): array` — public
- **Zweck:** Validiert filterspezifische Formulardaten. **Rueckgabe:** Map `feldname => fehlermeldung`. **Bewertung:** A.

## Bewertungs-Resümee
Konsistenter, vollstaendiger Filter-Vertrag, weitgehend parallel zum Condition-Interface (ohne `save_items`, dafuer `*_filter*`-Benennung). Einzige Notizen: `set_defaults` ist als einzige Methode untypisiert, und der Klassen-Doc-Block traegt einen falschen Copy-Paste-Kommentar. Beides rein kosmetisch. Klassen-Score **A / -**.
