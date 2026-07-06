# set_checked_booking_instance — Methoden-Doku
**Datei:** `classes/external/set_checked_booking_instance.php` · **LOC:** 114 · **Subsystem:** S11 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`set_checked_booking_instance` ist eine `external_api`-Webservice-Klasse (mit `declare(strict_types=1)`), die als Helfer fuer das separate Plugin `local_urise` dient: Sie markiert eine Booking-Instanz als „konfiguriert". Ohne installiertes `local_urise` ist der Service ein No-op. Kein eigener Zustand. Kollaborateure: `mod_booking\singleton_service`, `mod_booking\coursecategories`, `context_module`, Moodle-Capability-API. Standard-Dreiklang.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert den Parameter `id` (PARAM_INT). **Seiteneffekte:** keine. **Rueckgabe:** Parameter-Schema. **Bewertung:** B — der Doc-Kommentar (`'Context Id'` / „parameters for unenrol user") ist copy-paste-falsch; tatsaechlich ist `id` eine bookingid (siehe `execute`).

### `public static function execute(int $id): array` — public static
- **Zweck:** Validiert `id`; wenn `local_urise\permissions` nicht existiert, sofortiges No-op-Ergebnis. Sonst laedt es die Booking-Settings ueber `singleton_service::get_instance_of_booking_by_bookingid($id)`, prueft `local/urise:viewdashboard` auf dem Modul-Kontext und ruft bei Erfolg `coursecategories::set_configured_booking_instances($id)` auf. **Seiteneffekte:** `class_exists`-Probe, Singleton-Load der Booking-Settings, `context_module::instance($bookingsettings->cmid)`, `has_capability`, mutierender Aufruf `coursecategories::set_configured_booking_instances` (schreibt Konfigurations-Status). **Rueckgabe:** `['successs' => 0|$status]`. **Bewertung:** C — mehrere Auffaelligkeiten: (1) Result-Key `successs` ist durchgaengig falsch geschrieben (Tippfehler, auch im Schema). (2) Kein `validate_context($context)` vor der Mutation — es wird nur `has_capability` auf dem aus der bookingid abgeleiteten Kontext geprueft (Capability deckt die Berechtigung ab, der formale `validate_context`-Schritt fehlt jedoch). (3) `successs` ist als PARAM_TEXT deklariert, obwohl die Rueckgabe von `set_configured_booking_instances` numerisch/boolesch ist. Funktional fuer den eng umrissenen urise-Helper-Zweck tragbar.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe `['successs' => PARAM_TEXT]`. **Seiteneffekte:** keine. **Rueckgabe:** Return-Schema. **Bewertung:** B — uebernimmt den Tippfehler `successs`; jeder JS-Konsument muss exakt diesen Key lesen.

## Bewertungs-Resümee
Klar abgegrenzter Cross-Plugin-Helfer mit korrekter Defensive (No-op ohne urise, Capability-Gate). Schwaechen sind kosmetisch/struktureller Natur: durchgaengiger `successs`-Tippfehler, falsche Doc-Kommentare und fehlender expliziter `validate_context`-Aufruf. Klassen-Score **C / P3**.
