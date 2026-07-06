# addbookingoption — Methoden-Doku
**Datei:** `classes/external/addbookingoption.php` · **LOC:** 533 · **Subsystem:** S11 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S11_external.md)

## Klassenueberblick
Externer Webservice (`external_api`) zum Anlegen bzw. Aktualisieren einer Buchungsoption per Webservice-Call. Die Klasse selbst enthaelt nur die Moodle-WS-Standard-Trias (Parameterschema, Ausfuehrung, Rueckgabeschema). Die eigentliche Fachlogik (Aufloesung von Kurs/Instanz, Anlegen/Update der Option, Einschreibungen) ist an `mod_booking\utils\webservice_import` delegiert; Kontextaufloesung erfolgt ueber `singleton_service`. Damit ist die Klasse im Wesentlichen eine duenne, aber sehr breite Schemafassade.

## Methoden

### `execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert das vollstaendige Eingabeschema des WS (ca. 46 Felder: Name, Identifier, Ziel-/Einschreibekurs, Kapazitaeten, Zeiten, Texte, Bedingungen, mergeparam etc.).
- **Parameter / Rueckgabe:** keine Parameter; gibt `external_function_parameters` zurueck.
- **Seiteneffekte:** keine (reine Schema-Konstruktion).
- **Aufrufkette:** Vom Moodle-WS-Framework aufgerufen; intern in `execute()` an `validate_parameters()` uebergeben.
- **Bewertung:** B. Sehr lang (~280 LOC), aber rein deklarativ/repetitiv — fuer ein WS-Parameterschema idiomatisch und vertretbar. Kein verstecktes Verhalten. Smell nur: Laenge >80 LOC (`addbookingoption.php:57-338`), aber unkritisch da kein Kontrollfluss.

### `execute(string $name, string $identifier, ...46 Params): array` — public static
- **Zweck:** Einstiegspunkt des WS; prueft Berechtigung, validiert Parameter, bereinigt NULL-Werte und delegiert die Verarbeitung an `webservice_import::process_data()`.
- **Parameter:** 46 Einzelparameter (Pflicht: `$name`, `$identifier`; Rest optional/nullable). Spiegeln das Schema aus `execute_parameters()`.
- **Rueckgabe:** `array` (Ergebnis von `process_data()`; gem. `execute_returns` mit `status`-Bool).
- **Seiteneffekte:**
  - **Capability-Check:** `has_capability('mod/booking:updatebooking', ...)` gegen `context_module` (via Option- oder cmid) bzw. `context_system`; wirft `moodle_exception('nopermissions')`.
  - **Lesen:** `singleton_service::get_instance_of_booking_option_settings($bookingoptionid)` (Settings/cmid-Aufloesung, ggf. DB/Cache).
  - **Schreiben (indirekt):** ueber `new webservice_import()->process_data()` — DB-Writes an Buchungsoptionen, ggf. Einschreibungen, Events; nicht in dieser Datei sichtbar.
- **Aufrufkette:** Vom WS-Framework aufgerufen → ruft `validate_parameters`, `singleton_service`, `webservice_import::process_data`.
- **Bewertung:** C. Gemischte Verantwortung in einer Methode: dreistufige Capability-Auflosung + Param-Whitelist-Mapping + NULL-Filter + Delegation. Die Signatur mit 46 Positionsparametern ist schwer wartbar/fehleranfaellig (Reihenfolge-Kopplung an Schema). Param-Keys werden zweimal gepflegt (Schema + Mapping-Array) → Duplikat/Drift-Risiko. Doc-Bug: PHPDoc nennt `$notifcationtext`/`$userusername`, Code-Param `$userusername` wird auf Key `user_username` gemappt — leichte Inkonsistenz. Smells: lange Param-Liste + duplizierte Feldliste (`addbookingoption.php:393-521`), verschachtelte Capability-if/else (`:444-455`).

### `execute_returns(): external_single_structure` — public static
- **Zweck:** Deklariert Rueckgabeschema (`status`-Bool).
- **Seiteneffekte:** keine. **Aufrufkette:** WS-Framework. **Bewertung:** A (trivial, deklarativ).

### Triviale Akzessoren
Keine klassischen Getter/Setter. Anonyme Closure in `execute()` (`array_filter`, `:514-516`) ist trivialer NULL-Filter, nicht separat bewertet.
