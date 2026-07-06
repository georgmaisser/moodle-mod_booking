# bookit_price — Methoden-Doku
**Datei:** `classes/output/bookit_price.php` · **LOC:** 178 · **Subsystem:** S10 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`bookit_price` ist ein renderable/templatable-DTO fuer den Preis-Anteil des Buchen-Buttons. Im Konstruktor wird — abhaengig vom Buchungsstatus des Zielnutzers — ein `cartitem` (aus `local_shopping_cart`) erzeugt bzw. fuer Gaeste die volle Preisliste geladen. Properties: `public array $cartitem/$priceitems/$priceitem`, `private $context`, `private bool $nojs`. Kollaborateure: `singleton_service` (Settings/User/booking_answers), `price` (Preise + Preiskategorien), `booking_option::return_cancel_until_date()`, `cartitem`, `context_module`, `$USER`.

## Methoden

### `public function __construct(array $data)` — public
- **Zweck:** Ermittelt den Buchungsstatus des Zielnutzers und befuellt entweder `$this->priceitems` (Gast: alle Preise) oder `$this->cartitem` (nicht/noch buchbar: ein Cart-Item aus dem fuer den User geltenden Preis). **Seiteneffekte:** `context_module::instance()`, `singleton_service::get_instance_of_booking_option_settings()`, `get_instance_of_user()`, `get_instance_of_booking_answers()`, `price::get_prices_from_cache_or_db()`, `price::get_price()`, `booking_option::return_cancel_until_date()`, Lesen von `$USER`, `isloggedin()`. **Bewertung:** D — der Block, der `$context`/`$settings` setzt, haengt an `if ($data['area'] == 'option')` (Z.75); fuer jede andere `area` bleiben beide Variablen ununitialisiert und Z.88 (`if ($context && !isloggedin())`) sowie Z.96 (`if ($settings->id)`) loesen Undefined-Variable-Warnungen bzw. einen Fatal beim Property-Zugriff aus. Es gibt keinen `area`-Default und keine else-Behandlung. Zusaetzlich ist die Status-Logik korrekt nur fuer `area === 'option'` modelliert. Innerhalb des `switch` wird pro nicht-gebuchtem Status `price::get_price` aufgerufen — bei Listen-Rendering (viele Optionen) ist das ein Per-Item-Aufruf, der idealerweise gecacht sein sollte.

### `public function export_for_template(renderer_base $output)` — public
- **Zweck:** Baut das Template-Array: fuer Gaeste die Liste `priceitems` (je Eintrag mit aufgeloestem `pricecategoryname`), sonst — falls ein `cartitem` existiert — die einzelnen Felder inkl. `number_format`-Preis und optional `priceformatted` (`format_float`) sowie `nojs`. **Seiteneffekte:** `is_guest($this->context)`, `price::get_active_pricecategory_from_cache_or_db()` je Preiseintrag. **Rueckgabe:** `array` (leer wenn weder Gast-Preise noch Cart-Item). **Bewertung:** B — klar strukturiert; die `nojs`-Semantik ist invertiert benannt (Z.73: `$this->nojs` wird **true**, wenn `$data['nojs']` nicht/falsy gesetzt ist), was leicht zu Fehlinterpretation fuehrt; pro Gast-Preiseintrag ein Preiskategorie-Lookup (vertretbar, da gecacht).

### Triviale Properties
`$cartitem`, `$priceitems`, `$priceitem` (Werte-Halter), `$context`, `$nojs` (interne Flags, Z.47–60).

## Bewertungs-Resümee
Funktional fuer den Standardfall (`area === 'option'`, eingeloggter User/Gast) korrekt, aber mit einem realen Robustheitsdefekt: nicht-`option`-areas fuehren zu ununitialisierten `$context`/`$settings` und damit zu Warnungen/Fatal. Dazu die irrefuehrend invertierte `nojs`-Flag-Logik. Klassen-Score **C / P2**.
