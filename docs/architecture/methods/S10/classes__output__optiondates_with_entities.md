# optiondates_with_entities — Methoden-Doku
**Datei:** `classes/output/optiondates_with_entities.php` · **LOC:** 78 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`optiondates_with_entities` ist ein Renderable/Templatable-DTO, das die Termine einer Buchungsoption inklusive der zugeordneten `local_entities` (Ort/Equipment) fuer die Anzeige aufbereitet. Im Unterschied zu `optiondates_only` holt es die angereicherten Sessions ueber das `booking_option`-Objekt (Mail-Beschreibungs-Variante mit Entities). Persistenz: keine. Kollaborateure: `singleton_service` (Option-Instanz), `booking_option::return_array_of_sessions`, `booking_option_settings`; importiert (aber im Klassenkoerper ungenutzt) `entitiesrelation_handler`, `dates_handler`, `moodle_url`, `stdClass`.

## Methoden

### `public function __construct(booking_option_settings $settings)` — public
- **Zweck:** Holt ueber `singleton_service::get_instance_of_booking_option($settings->cmid, $settings->id)` die Option und befuellt `sessions` mit `return_array_of_sessions(null, MOD_BOOKING_DESCRIPTION_MAIL, true, true)` (Mail-Format inkl. Entities/Teacher). **Seiteneffekte:** indirekt ueber Singleton-Service (ggf. Settings-Lade-/Cache-Pfade). **Bewertung:** B — setzt im Gegensatz zu `optiondates_only` die Flags `showsessions`/`onesession` nicht aus der tatsaechlichen Session-Anzahl; sie behalten ihre Default-Werte (`showsessions=true`, `onesession=false`), unabhaengig davon ob 0 oder genau 1 Session existiert. Das Template erhaelt also bei leerer/einzelner Terminliste irrefuehrende Flags.

### `public function export_for_template(renderer_base $output): array` — public
- **Zweck:** Templatable-Vertrag; liefert `showsessions`, `onesession`, `dates`. **Rueckgabe:** Array fuer Mustache. **Bewertung:** A.

### Triviale Properties
Drei oeffentliche Properties (`showsessions`, `sessions`, `onesession`, Z.48–54) als Werte-Halter.

## Bewertungs-Resümee
Kleines Entities-anreicherndes Termin-DTO. Haupt-Schwachpunkt: die Sichtbarkeits-Flags `showsessions`/`onesession` werden — anders als im Schwester-DTO `optiondates_only` — nicht aus der Session-Anzahl abgeleitet und bleiben auf ihren Defaults, was bei 0 oder 1 Sessions falsche Template-Signale gibt. Sonst unauffaellig. Klassen-Score **A / P3**.
