# booking_readiness_provider — Methoden-Doku
**Datei:** `classes/local/wizard/booking/booking_readiness_provider.php` · **LOC:** 73 · **Subsystem:** S15 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S15_wizard_ai.md)

## Klassenueberblick
`booking_readiness_provider` ist ein duck-typed Provider, der vom Agent-Engine-Readiness-Panel (`bookingextension_agent\local\wizard\aiready`) per Konvention entdeckt wird, ohne dass die Engine eine Compile-Time-Abhaengigkeit zu mod_booking-Internas haelt. Die Klasse hat keinen Zustand und keine Persistenz: sie besteht aus einer einzigen statischen Methode, die fuer eine Booking-Instanz Kennzahlen (Anzahl Optionen, Anzahl gebuchter Nutzer) aggregiert. Kollaborateure: `singleton_service` (Instanz-/Settings-/Answers-Caches), `booking_option_settings`, `booking_answers`.

## Methoden

### `public static function get_booking_statistics(int $cmid, int $bookingid): array` — public static
- **Zweck:** Liefert `['num_options' => int, 'num_booked' => int]` fuer die angegebene Booking-Instanz: Optionsanzahl via `get_all_options_count()`, Anzahl gebuchter Nutzer durch Aufsummieren von `count($answers->get_usersonlist())` ueber alle Optionen.
- **Seiteneffekte:** Lesend; `singleton_service::get_instance_of_booking_by_bookingid()`, pro Option zusaetzlich `get_instance_of_booking_option_settings()` und `get_instance_of_booking_answers()` (jeweils MUC-/Singleton-gecacht). Keine Schreibvorgaenge.
- **Rueckgabe:** `array{num_options:int,num_booked:int}`; bei nicht aufloesbarer Instanz oder jeder Exception `['num_options' => 0, 'num_booked' => 0]`.
- **Bewertung:** B — der `$cmid`-Parameter ist laut Doc nur „signature symmetry" und bleibt ungenutzt; die Schleife ueber `get_all_options(0, 0)` mit zwei Singleton-Lookups je Option ist ein potenzielles N+1-Muster bei Instanzen mit vielen Optionen (S15-1). Der breite `catch (\Exception)` schluckt jede Ursache und liefert stillschweigend Nullwerte (kein `debugging`), was eine echte Fehlkonfiguration als „leere Instanz" maskiert. Fuer einen reinen Readiness-Indikator pragmatisch und fail-soft korrekt.

## Bewertungs-Resümee
Schlanker, zustandsloser Statistik-Provider mit sauberer Fail-Soft-Semantik fuer das Readiness-Panel. Schwaechen: ungenutzter `$cmid`, N+1-artige Aggregation ueber alle Optionen und ein vollstaendig stiller Exception-Schlucker. Funktional unkritisch (read-only, Anzeigezweck). Klassen-Score **B / P3**.
