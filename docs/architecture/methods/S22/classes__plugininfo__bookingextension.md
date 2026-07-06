# bookingextension — Methoden-Doku
**Datei:** `classes/plugininfo/bookingextension.php` · **LOC:** 100 · **Subsystem:** S22 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S22_*.md)

## Klassenueberblick
`bookingextension` ist die Subplugin-Info-Basisklasse fuer den mod_booking-Subplugin-Typ `bookingextension`. Sie erweitert die Moodle-Core-Klasse `core\plugininfo\base` und liefert die von Core erwarteten Plugin-Lifecycle-Antworten (Aktiviert-Status, Deinstallierbarkeit, Pre-Uninstall-Hook). Zusaetzlich stellt sie No-op-Default-Implementierungen einiger Extension-Hooks bereit (`add_options_to_col_actions`, `get_allowedruleeventkeys`, `get_booking_history_description`), sodass konkrete Extensions nur die fuer sie relevanten Hooks ueberschreiben muessen. Bemerkenswert: die Klasse implementiert NICHT formal `bookingextension_interface` (kein `implements`), bietet aber dessen statische Default-Methoden teilweise an — der Vertrag wird also nicht durch Typ-Bindung, sondern per Konvention erfuellt. Keine Persistenz, keine Felder. Kollaborateure: Core-Plugin-Manager (ruft `is_enabled`/`is_uninstall_allowed`/`uninstall_cleanup`), Booking-Kern (ruft die statischen Hook-Defaults).

## Methoden

### `public function is_enabled()` — public
- **Zweck:** Meldet die Extension stets als aktiviert. **Seiteneffekte:** keine. **Rueckgabe:** `true` (hart). **Bewertung:** B — keine konfigurierbare Verfuegbarkeit; alle bookingextension-Subplugins gelten damit pauschal als enabled (bewusst, da Extensions eigene Lizenz-/Gate-Logik tragen, siehe Memory).

### `public function is_uninstall_allowed()` — public
- **Zweck:** Erlaubt Deinstallation ueber die Admin-UI. **Seiteneffekte:** keine. **Rueckgabe:** `true`. **Bewertung:** A — bewusste Abweichung vom Core-Default (der Deinstallation verbietet).

### `public function uninstall_cleanup()` — public
- **Zweck:** Pre-Uninstall-Hook; delegiert an die Core-Basis-Implementierung. **Seiteneffekte:** `parent::uninstall_cleanup()`. **Rueckgabe:** void. **Bewertung:** C — deklariert `global $CFG;`, verwendet `$CFG` aber nirgendwo (toter Import); der Override fuegt gegenueber `parent` nichts hinzu und ist damit ueberfluessig. P3, harmlos.

### `public static function add_options_to_col_actions(object $settings, $context): string` — public static
- **Zweck:** Default fuer den col_action-Hook; konkrete Extension ueberschreibt bei Bedarf. **Seiteneffekte:** keine. **Rueckgabe:** leerer String. **Bewertung:** A — sauberer No-op-Default. (Signatur weicht minimal vom Interface ab: `$context` ohne `mixed`-Typehint vs. `mixed $context` im Interface — fuer untypisierten Parameter kompatibel.)

### `public static function get_allowedruleeventkeys(): array` — public static
- **Zweck:** Default fuer erlaubte Rule-Event-Keys; leer = keine Extension-spezifischen Events. **Seiteneffekte:** keine. **Rueckgabe:** leeres Array. **Bewertung:** A.

### `public static function get_booking_history_description(stdClass $values, array $info): string` — public static
- **Zweck:** Default fuer History-Beschreibung; leer = Extension steuert keine Beschreibung bei. **Seiteneffekte:** keine. **Rueckgabe:** leerer String. **Bewertung:** A.

## Bewertungs-Resümee
Solide, schlanke Subplugin-Info-Basisklasse: Core-Lifecycle korrekt beantwortet, drei sinnvolle No-op-Hook-Defaults fuer abgeleitete Extensions. Schwaechen sind kosmetisch: `uninstall_cleanup()` ist ein redundanter Override mit ungenutztem `global $CFG`, und der `bookingextension_interface`-Vertrag wird nur per Konvention (kein `implements`) erfuellt — eine echte Typ-Bindung wuerde Drift zwischen Interface und Basisklasse fruehzeitig fangen. Funktional unkritisch. Klassen-Score **B / P3**.
