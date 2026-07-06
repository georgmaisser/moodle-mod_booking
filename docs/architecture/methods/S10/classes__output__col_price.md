# col_price — Methoden-Doku
**Datei:** `classes/output/col_price.php` · **LOC:** 171 · **Subsystem:** S10 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S10_output_rendering.md)

## Klassenueberblick
`col_price` ist ein Renderable/Templatable-DTO fuer die Preis-Spalte einer Buchungsoption. Es ermittelt im Konstruktor je nach User-Status, ob fuer den (Kauf-)User ueberhaupt ein kaufbarer Preis anzuzeigen ist, und baut dann ein `cartitem` (aus `local_shopping_cart`) auf. Fuer Gaeste (nicht eingeloggt, mit Kontext) wird stattdessen die volle Preisliste aller Preiskategorien geladen. `export_for_template` formt diese beiden Faelle in das Mustache-Array um. Keine eigene Persistenz; Kollaborateure: `price` (Cache-/DB-Preise), `singleton_service` (booking_option_settings, booking_answers), `cartitem`, `pricecategory`-Cache, globaler `$USER`.

## Methoden

### `public function __construct(stdClass $values, booking_option_settings $settings, ?object $buyforuser = null, ?context $context = null)` — public
- **Zweck:** Ermittelt den anzuzeigenden Preis. Gast-Pfad (Kontext gesetzt und nicht eingeloggt): laedt alle `priceitems` via `price::get_prices_from_cache_or_db('option', $values->id)` und kehrt frueh zurueck. Sonst: faellt `$buyforuser` auf `$USER` zurueck, holt frisch `booking_option_settings` und `booking_answers` ueber `singleton_service` und prueft den `user_status`. Nur bei Status RESERVED/NOTBOOKED/DELETED/NOTIFYMELIST und vorhandenem Preis wird ein `cartitem` erzeugt und als Array in `$this->cartitem` gelegt.
- **Seiteneffekte:** Liest globalen `$USER`; Cache-/DB-Zugriffe ueber `price::*`; `singleton_service::get_instance_of_booking_option_settings` und `get_instance_of_booking_answers` (ueberschreibt den uebergebenen `$settings`-Parameter bewusst, Kommentar verweist auf Caching-Logik). Keine Schreibzugriffe.
- **Bewertung:** B — funktional korrekt, aber dichte Verantwortung (Gast vs. User, Status-Switch, Cartitem-Bau) in einem Konstruktor. Die Zuweisung `$this->priceitem = price::get_price(...)` in der `if`-Bedingung ist absichtlich, aber leseunfreundlich. Pro gerenderter Zeile ein `get_price`-Aufruf + Settings/Answers-Singleton-Lookup — in grossen Tabellen ein potentieller N+1-Hotspot (P2), gemildert durch die Cache-Schicht in `price`/`singleton_service`.

### `public function export_for_template(renderer_base $output): array` — public
- **Zweck:** Liefert das Template-Array. Gast-Pfad (`$this->context` gesetzt und `is_guest`): reichert jedes Preisitem um den Kategorienamen an, sortiert nach `pricecatsortorder` via `ksort` und entfernt danach die Keys via `array_values` (Mustache-vertraeglich). Sonst: bei fehlendem `cartitem` leeres Array, ansonsten ein flaches Array mit `itemid`/`itemname`/`price` (ueber `format_float` auf 2 Nachkommastellen)/`currency`/`componentname`/`area='option'`/`description`/`imageurl`/`priceitems`.
- **Seiteneffekte:** Gast-Pfad ruft pro Preisitem `price::get_active_pricecategory_from_cache_or_db(...)` (Cache-gestuetzt).
- **Rueckgabe:** assoziatives Array fuer das Mustache-Template (Form unterscheidet sich zwischen Gast- und User-Fall).
- **Bewertung:** B — saubere Sortier-/Key-Entfernungs-Logik. Risiko: `$this->cartitem['imageurl']` wird im Konstruktor nie gesetzt (cartitem-`as_array` liefert den Key, aber die Quelle setzt kein Bild) — bei abweichender cartitem-Struktur potenziell undefinierter Index; in der Praxis durch `cartitem::as_array` abgesichert.

## Bewertungs-Resümee
Zweckmaessiges Spalten-DTO mit zwei klar getrennten Pfaden (Gastpreisliste vs. kaufbarer Einzelpreis). Hauptschwaeche ist die Last pro gerenderter Tabellenzeile (Preis- und Singleton-Lookups), die nur durch die darunterliegenden Caches tragbar bleibt — bei Cache-Misses in grossen Tabellen ein N+1-Kandidat. Funktional unkritisch. Klassen-Score **B / P2**.
