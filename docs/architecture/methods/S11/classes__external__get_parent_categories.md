# get_parent_categories — Methoden-Doku
**Datei:** `classes/external/get_parent_categories.php` · **LOC:** 142 · **Subsystem:** S11 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`get_parent_categories` ist eine `external_api`-Webservice-Klasse, die Coursecat-Knoten fuer den Dashboard-/Cart-Kontext (M:USI/Shopping-Cart) liefern soll. Im aktuellen Zustand ist sie ein **Stub**: Sie liest zwar Kategorien via `coursecategories::return_course_categories()` und sortiert sie, gibt aber faktisch nur einen statischen Summary-Knoten (bei leerer `coursecategoryid`) oder ein leeres Array zurueck. Keine eigene Persistenz; Kollaborateure: `$DB` (global importiert, ungenutzt), `coursecategories`, `context_coursecat` (importiert, ungenutzt).

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert den einzigen Parameter `coursecategoryid` (PARAM_INT, Default 0). **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A.

### `public static function execute(int $coursecategoryid): array` — public static
- **Zweck:** Soll die Kindkategorien-/Dashboard-Knoten fuer die uebergebene Kategorie zurueckgeben. **Seiteneffekte:** `require_login()`; `self::validate_parameters(...)`; `coursecategories::return_course_categories($params['coursecategoryid'])` (DB-Lesezugriff im Helfer); `usort()` mit case-insensitivem Namensvergleich. **Rueckgabe:** Bei leerer `coursecategoryid` ein Array mit genau einem statischen Summary-Knoten (`dashboardsummary`, contextid hartkodiert `1`, alle Zaehler 0); sonst ein leeres Array. **Bewertung:** D — die mit `return_course_categories()` geladenen und sortierten `$records` werden **nie** in `$returnarray` verwendet (tote Berechnung); die fuenf lokalen Zaehlervariablen (`$coursecount` etc.) bleiben konstant 0; keinerlei Capability-Pruefung jenseits `require_login`, obwohl die Returns-Struktur Buchungs-/Reservierungszahlen verspricht. Funktional unvollstaendig (Stub).

### `public static function execute_returns(): external_multiple_structure` — public static
- **Zweck:** Beschreibt die (reichhaltige) Rueckgabestruktur je Knoten: id/name/contextid/coursecount/bookingoptionscount/booked-/waitinglist-/reservedcount/description/path, optionale `courses`-Liste und `json`. **Seiteneffekte:** keine. **Rueckgabe:** `external_multiple_structure`. **Bewertung:** B — die deklarierte Struktur ist deutlich umfangreicher als das, was `execute()` tatsaechlich liefert (Vertragsdivergenz); Zaehlerfelder als PARAM_TEXT statt PARAM_INT typisiert.

## Bewertungs-Resümee
Eine erkennbar unfertige Webservice-Klasse: Parameter-/Returns-Deklaration ist sauber, aber `execute()` ignoriert die geladenen Kategorien vollstaendig und gibt nur einen statischen Summary-Knoten bzw. ein leeres Array zurueck. Kein Datenverlust, aber tote Berechnung + Vertragsdivergenz zwischen Returns-Struktur und Implementierung. Klassen-Score **C / P3**.
