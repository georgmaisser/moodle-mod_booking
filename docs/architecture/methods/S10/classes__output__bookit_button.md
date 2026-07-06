# bookit_button — Methoden-Doku
**Datei:** `classes/output/bookit_button.php` · **LOC:** 107 · **Subsystem:** S10 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`bookit_button` ist ein renderable/templatable-DTO, das die Daten fuer den „Jetzt buchen"-Button aufbereitet. Der Konstruktor normalisiert ein hereingegebenes `$data`-Array, setzt Defaults (Label, CSS-Klasse, foruser-String, area, userid, componentname, pricecategoryidentifier) und merkt sich, ob `local_shopping_cart` installiert ist. Einzige Property `public $data`. Kollaborateure: `bo_info::get_for_user_button_string()`, `singleton_service` (User + Preiskategorie), `local_shopping_cart\shopping_cart` (nur Existenzpruefung), `$USER`.

## Methoden

### `public function __construct(array $data = [])` — public
- **Zweck:** Reichert das `$data`-Array mit Default-Werten an und legt es in `$this->data` ab. Erkennt Shopping-Cart-Verfuegbarkeit, fuellt Label/Klasse/area/userid/componentname/pricecategoryidentifier und den nutzerspezifischen Button-String. **Seiteneffekte:** `class_exists('local_shopping_cart\\shopping_cart')`, `get_string('booknow', 'mod_booking')`, `bo_info::get_for_user_button_string($data['userid'])`, `singleton_service::get_instance_of_user()`, `singleton_service::get_pricecategory_for_user()`, Lesen von `$USER`. **Bewertung:** C — Reihenfolge-Defekt: `$data['userid']` wird in Z.72 (`get_for_user_button_string`) gelesen, der Default `$USER->id` aber erst in Z.79–81 gesetzt; bei fehlendem `userid` entsteht ein Undefined-Index-Notice und der foruser-String wird mit `null` berechnet. Ebenso wird `pricecategoryidentifier` schon in Z.88 aus dem (ggf. ungesetzten) `userid` ermittelt — funktioniert nur, weil der Konstruktor in der Praxis stets mit gesetztem `userid` aufgerufen wird.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Gibt das vollstaendig normalisierte `$this->data`-Array unveraendert zurueck. **Seiteneffekte:** keine. **Rueckgabe:** `array`. **Bewertung:** A — reiner Pass-through.

## Bewertungs-Resümee
Konzeptionell ein einfacher Default-Anreicherer, jedoch mit einer fragilen Auswertungsreihenfolge: `userid` wird vor seiner Default-Belegung konsumiert. Praktisch unkritisch, weil Aufrufer `userid` mitliefern, aber latent fehleranfaellig. Klassen-Score **B / P3**.
