# booking_potential_user_selector — Methoden-Doku
**Datei:** `classes/booking_potential_user_selector.php` · **LOC:** 172 · **Subsystem:** S01 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S01_*.md)

## Klassenueberblick
`booking_potential_user_selector` (erbt `booking_user_selector_base`) ist das User-Selector-Widget fuer das **manuelle Hinzubuchen** von Teilnehmern zu einer Option: es liefert die Liste der noch buchbaren (potenziellen) User. Die gesamte Logik steckt in `find_users()`, einer grossen SQL-Bau-Methode, die Enrolment-, Gruppen-, Institutions-, „bookanyone"- und Bereits-gebucht-Filter kombiniert. Kollaborateure: `$DB`, `booking::booking_get_groupmembers_sql`, `booking_check_if_teacher`, Core `get_enrolled_sql`/`users_order_by_sql`, `context_module`/`context_course`. Persistenz (lesend): `user`, `booking_answers`, `booking_options`.

## Methoden

### `public function __construct($name, $options)` — public
- **Zweck:** Speichert das rohe `$options`-Array zusaetzlich als oeffentliches Property (fuer Zugriff in `find_users`) und delegiert an den Parent-Konstruktor. **Bewertung:** B — haelt `$options` doppelt (Parent zerlegt es bereits in Felder); redundanter State.

### `public function find_users($search)` — public
- **Zweck:** Baut und fuehrt die Suche nach buchbaren Usern aus; gruppiert das Ergebnis unter einem lokalisierten Gruppennamen. **Filter-Kaskade:** (1) `bookanyone`-User-Preference + Capability `mod/booking:bookanyone` → hebt den Enrolment-Filter auf (site-weite Suche); sonst nur in der accesscontext Eingeschriebene (`get_enrolled_sql`, `id > 1` schliesst Guest aus). (2) SEPARATEGROUPS ohne `accessallgroups` → Einschraenkung auf Gruppenmitglieder via `booking::booking_get_groupmembers_sql`. (3) Trainer ohne `readallinstitutionusers` → Einschraenkung auf die Institution der Option. (4) Ausschluss bereits gebuchter User (`booking_answers` mit `waitinglist <> DELETED`). (5) `suspended = 0 AND deleted = 0`. **Seiteneffekte:** mehrere `$DB`-Reads (`count_records_sql` Guard via `too_many_results`, dann `get_records_sql`). **Bewertung:** C — sehr lange Methode (~115 LOC) mit mehreren verschachtelten Bedingungspfaden und handgebautem SQL; **String-Interpolation von `$this->options['optionid']` direkt in die NOT-IN-Subquery** (Z.128) statt Bind-Parameter — hier zwar zuvor auf int aus Form-Kontext, aber Stilbruch und SQL-Injection-Antipattern, das man nicht kopieren sollte. Gemischte Verantwortung (Capability-Pruefung + SQL-Bau + Praesentations-Gruppennamen).

### Triviale Properties
`options` (public, dupliziert Parent-State).

## Bewertungs-Resümee
Funktional zentrale, aber schwergewichtige Methode mit hoher zyklomatischer Komplexitaet und mehreren Sicherheits-/Stil-Smells (interpolierte optionid in SQL, doppelter `$options`-State, Praesentation und Datenzugriff vermischt). Kandidat fuer Zerlegung (Filter-Builder extrahieren, alle Werte binden). Klassen-Score **C / P2**.
