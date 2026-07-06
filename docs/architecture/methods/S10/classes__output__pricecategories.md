# pricecategories — Methoden-Doku
**Datei:** `classes/output/pricecategories.php` · **LOC:** 70 · **Subsystem:** S10 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`pricecategories` ist ein schlankes renderable/templatable DTO, das ein bereits vorgerendertes Preiskategorien-Formular umhuellt und zusaetzlich die existierenden Preiskategorien als base64-kodiertes JSON fuer das clientseitige AMD-Modul (`dynamicpricecategoriesform`) bereitstellt. Persistenz: liest `booking_pricecategories` (read-only). Kollaborateure: `$DB`, die Admin-Seite `pricecategories.php`, das `pricecategories_form` (dessen Render hier als String hereingereicht wird), das JS-Modul (konsumiert das encodierte Snapshot).

## Methoden

### `public function __construct(string $renderedpricecategoriesform)` — public
- **Zweck:** Speichert das uebergebene Formular-HTML und laedt alle Preiskategorien (`id ASC`), serialisiert sie als `base64_encode(json_encode(...))` in `existingpricecategories`. **Seiteneffekte:** `$DB->get_records('booking_pricecategories', null, 'id ASC')`. **Bewertung:** A — ein einzelner Query, klar. Die base64/json-Kodierung dient dem sicheren Transport in ein data-Attribut (vermeidet HTML-Escaping-Probleme); fuer den Admin-only-Kontext angemessen.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt das Render-Array mit dem vorgerenderten Formular zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `['renderedpricecategoriesform' => ...]`. **Bewertung:** B — `existingpricecategories` wird im Konstruktor befuellt, aber hier *nicht* ans Template durchgereicht; der Wert muss also anderweitig (Property-Zugriff/JS) konsumiert werden. Minimaler Inkonsistenz-Geruch, funktional aber unkritisch.

## Bewertungs-Resümee
Kompaktes, sauberes Wrapper-DTO fuer die Preiskategorien-Adminseite mit genau einem Query. Einzige Randnotiz: das encodierte `existingpricecategories`-Snapshot wird nicht ueber `export_for_template` weitergegeben. Klassen-Score **A / P3**.
