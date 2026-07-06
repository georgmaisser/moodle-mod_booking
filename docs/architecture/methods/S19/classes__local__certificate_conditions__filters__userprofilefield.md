# userprofilefield — Methoden-Doku
**Datei:** `classes/local/certificate_conditions/filters/userprofilefield.php` · **LOC:** 224 · **Subsystem:** S19 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S19_certificates.md)

## Klassenueberblick
`userprofilefield` ist der einzige konkrete `filter_interface`-Handler des Zertifikatsbedingungs-Frameworks. Ein Filter verfeinert eine Zertifikatsbedingung um eine User-Profilfeld-Pruefung: vergleicht ein (Custom-)Profilfeld mit einem konfigurierten Wert (`=` oder „enthaelt"). Die Konfiguration wird als JSON (`filterjson`) auf dem Bedingungs-Record persistiert. Drei oeffentliche Properties (`field`, `operator`, `value`) halten den Laufzeitzustand. Kollaborateure: `MoodleQuickForm` (Formular), `$DB` (`user_info_field`-Lookup), `singleton_service` (User-Laden inkl. Profilfelder), Konsumenten in der Condition-Evaluierung.

## Methoden

### `public function add_filter_to_mform(MoodleQuickForm &$mform, ?array &$ajaxformdata = null)` — public
- **Zweck:** Haengt zwei Formularelemente an: ein `select` mit den verfuegbaren Profilfeldern und ein `text`-Feld fuer den Vergleichswert. **Seiteneffekte:** ruft `self::get_available_profile_fields()` (DB-Query); `addElement`/`setType` auf `$mform`. **Bewertung:** B — es gibt kein Formularelement fuer den `operator`, obwohl die Property und `evaluate()` einen `~`-Operator (contains) unterstuetzen; der `~`-Pfad ist damit ueber das UI nicht erreichbar.

### `private static function get_available_profile_fields(): array` — private static
- **Zweck:** Liefert Map `shortname => "name (shortname)"` der Custom-Profilfelder fuer das Select, mit fuehrendem `'' => choose...`. **Seiteneffekte:** `$DB->get_records('user_info_field', null, 'name ASC, shortname ASC', 'shortname, name')`. **Rueckgabe:** Options-Array. **Bewertung:** A — schlanker, korrekt parametrisierter Lookup.

### `public function get_name_of_filter(bool $localized = true): string` — public
- **Zweck:** Anzeigename des Filters, lokalisiert oder als technischer Key. **Seiteneffekte:** `get_string`. **Rueckgabe:** string. **Bewertung:** A.

### `public function save_filter(stdClass &$data): void` — public
- **Zweck:** Serialisiert die Filterkonfiguration nach `$data->filterjson`. **Seiteneffekte:** mutiert `$data->filterjson` (json_encode aus `filtername`, `field`, `value`). **Bewertung:** B — der `operator` wird NICHT mitgespeichert (nur `field`/`value`), sodass die `~`-Konfiguration aus `set_filterdata_from_json` nie persistiert werden kann; in der Praxis bleibt der Operator immer beim Default `=`.

### `public function set_defaults(stdClass &$data, stdClass $record)` — public
- **Zweck:** Befuellt Formular-Defaults aus einem bestehenden Bedingungs-Record. **Seiteneffekte:** ruft `set_filterdata_from_json($record->filterjson)` (setzt Instanz-Properties) UND dekodiert `filterjson` ein zweites Mal, um `$data->filter_userprofilefield_field`/`_value` zu setzen. **Bewertung:** B — doppeltes `json_decode` desselben Strings (Redundanz); funktional korrekt.

### `public function set_filterdata(stdClass $record): void` — public
- **Zweck:** Interface-Pflichtmethode; aktuell leerer Stub („Nothing necessary for now."). **Seiteneffekte:** keine. **Bewertung:** A — bewusster No-op.

### `public function set_filterdata_from_json(string $json): void` — public
- **Zweck:** Hydriert `field`/`operator`/`value` aus dem JSON-Payload. **Seiteneffekte:** mutiert Instanz-Properties; guarded mit `if ($jsonobject)`. **Bewertung:** A.

### `public function execute(stdClass &$sql, array &$params): void` — public
- **Zweck:** Interface-Pflichtmethode fuer SQL-Builder-Integration; bewusster Stub („not used for conditions execution"). **Seiteneffekte:** keine. **Bewertung:** A — dokumentierter No-op.

### `public function evaluate(stdClass $context): bool` — public
- **Zweck:** Kern-Auswertung: prueft, ob das Profilfeld des betroffenen Users den konfigurierten Wert erfuellt. Erwartet `$context->event`; bestimmt `userid` bevorzugt aus `relateduserid`, sonst `userid`. **Seiteneffekte:** `singleton_service::get_instance_of_user($userid, true)` (laedt Profilfelder). **Rueckgabe:** bool — `true` wenn kein Feld gesetzt (keine Einschraenkung); `false` bei fehlendem Event/User; sonst Vergleich `~` (strpos/contains) bzw. `=` (lockerer `==`-Vergleich). **Bewertung:** B — loser `==`-Vergleich (Typ-Juggling) und `strpos`-Contains sind fuer Profilstrings akzeptabel; greift bei nicht existentem `$user->profile[$this->field]` defensiv auf `''` zurueck.

### `public function validate(array $data): array` — public
- **Zweck:** Formularvalidierung; verlangt Pflichteingabe von Feld und Wert. **Seiteneffekte:** `get_string`. **Rueckgabe:** Fehler-Map (leer = valide). **Bewertung:** A.

### Triviale Properties
Drei oeffentliche Properties `field` (''), `operator` ('='), `value` ('') als Wertehalter (Z.37–49).

## Bewertungs-Resümee
Sauberer, kleiner Filter-Handler mit klarer Trennung von Form/Persistenz/Evaluierung. Hauptschwaeche ist die Operator-Inkonsistenz: `evaluate()` unterstuetzt `~` (contains), aber weder das Formular (`add_filter_to_mform`) noch `save_filter()` koennen den Operator setzen/persistieren — der `~`-Zweig ist toter, nicht erreichbarer Code. Dazu kleinere Redundanz (Doppel-Decode in `set_defaults`). Funktional unkritisch. Klassen-Score **B / P3**.
