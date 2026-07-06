# confirmation — Methoden-Doku
**Datei:** `classes/local/confirmationworkflow/confirmation.php` · **LOC:** 101 · **Subsystem:** S04 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S04_booking_process_bookit.md)

## Klassenueberblick
`confirmation` ist eine statische Bruecke vom Kern-Buchungsprozess zur Bestaetigungs-Capability der `bookingextension`-Subplugins. Sie iteriert ueber alle installierten `bookingextension`-Plugins, sucht deren `\bookingextension_<name>\local\confirmbooking`-Klasse und befragt sie, ob ein Approver eine Buchung bestaetigen darf bzw. wie viele Bestaetigungen ein Workflow benoetigt. Zustandslos, keine Persistenz. Kollaborateure: `\core_plugin_manager`, `get_config`, die `confirmbooking`-Klassen der Subplugins (vgl. `confirmbooking_interface`).

## Methoden

### `public static function check_confirm_capability(int $optionid, int $approverid, int $userid): array` — public static
- **Zweck:** Aggregiert ueber alle aktivierten `bookingextension`-Subplugins, ob mindestens eines dem Approver erlaubt, die Buchung von `$userid` zu bestaetigen. Pro Plugin: Existenzpruefung der Klasse, Skip wenn Subplugin via `get_config('bookingextension_<name>', '<name>enabled')` deaktiviert, sonst Aufruf `confirmbooking::has_capability_to_confirm_booking($optionid,$approverid,$userid)`. **Seiteneffekte:** `\core_plugin_manager::instance()->get_plugins_of_type('bookingextension')`, `get_config(...)` je Plugin; lesend. **Rueckgabe:** `[bool $allowed, string $message, bool $reload]` — Short-Circuit `[true,'',false]` beim ersten positiven Treffer; sonst Default `[false, get_string('notallowedtoconfirm'), $reload]` mit Message/Reload des zuletzt geprueften Plugins. **Bewertung:** C — ruft `has_capability_to_confirm_booking()` **ohne** `method_exists`-Guard auf (anders als die Schwestermethode), und diese Methode ist im `confirmbooking_interface` gar nicht deklariert; ein Subplugin, dessen `confirmbooking`-Klasse die Methode nicht implementiert, fuehrt zu einem Fatal Error. `global $USER` wird deklariert, aber nie verwendet (toter Code).

### `public static function get_required_confirmation_count(int $optionid): int` — public static
- **Zweck:** Ermittelt die maximal von allen aktivierten Subplugins geforderte Anzahl an Bestaetigungen fuer eine Option (Maximum, damit bei mehreren aktiven Plugins die strengste Anforderung gilt). **Seiteneffekte:** `\core_plugin_manager`-Iteration, `get_config(...)` je Plugin; lesend. Defensiver `method_exists($classname,'get_required_confirmation_count')`-Guard vor dem Aufruf. **Rueckgabe:** int — 0, wenn kein Plugin aktiv/zustaendig; sonst das Maximum der Plugin-Werte. **Bewertung:** B — korrekt und defensiver als die Schwestermethode; Schleifenkosten bei vielen Subplugins gering.

## Bewertungs-Resümee
Saubere Aggregations-Bruecke mit sinnvollem Short-Circuit (Capability) bzw. Max-Aggregation (Count). Wermutstropfen: `check_confirm_capability` ruft eine nicht im Interface deklarierte Methode ohne `method_exists`-Schutz auf (Fatal-Error-Risiko bei nicht konformen Subplugins) und schleppt ein ungenutztes `global $USER`. Klassen-Score **B / P3**.
