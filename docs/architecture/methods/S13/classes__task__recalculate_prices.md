# recalculate_prices — Methoden-Doku
**Datei:** `classes/task/recalculate_prices.php` · **LOC:** 105 · **Subsystem:** S13 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S13_*.md)

## Klassenueberblick
`recalculate_prices` ist ein `\core\task\adhoc_task`, der die Preise aller Buchungsoptionen einer Booking-Instanz anhand der globalen Preisformel (`defaultpriceformula`) neu berechnet. Er wird typischerweise vom Entry-Script `recalculateprices.php` (S21) nach Validierung in die Adhoc-Queue gestellt. Custom-Data: `{cmid}`. Persistenz: schreibt indirekt in `booking_prices` (ueber `price::add_price`). Kollaborateure: `price`, `singleton_service`, `\mod_booking\booking::get_all_optionids`, Konfiguration `booking/globalcurrency` + `booking/defaultpriceformula`. Trotz des Klassen-Kommentars ("clean caches at campaign start/end" — copy/paste-Artefakt) hat die Klasse nichts mit Campaign-Caches zu tun.

## Methoden

### `public function get_name()` — public
- **Zweck:** Liefert den lokalisierten Task-Namen (`taskrecalculateprices`). **Seiteneffekte:** `get_string`. **Rueckgabe:** `lang_string|string`. **Bewertung:** A.

### `public function execute()` — public
- **Zweck:** Iteriert ueber alle Optionen der per `cmid` aufgeloesten Booking-Instanz und berechnet je Preiskategorie den neuen Formelpreis, der dann persistiert wird. **Seiteneffekte:** `get_config` (Waehrung + Formel), `singleton_service::get_instance_of_booking_settings_by_cmid`, `booking::get_all_optionids`, pro Option `singleton_service::get_instance_of_booking_option_settings`, pro Kategorie `price::calculate_price_with_bookingoptionsettings` + `price::add_price` (DB-Write) + `mtrace`. Optionen mit `priceformulaoff == 1` werden uebersprungen. **Bewertung:** B — funktional korrekt; verschachtelte Doppelschleife (Optionen x Preiskategorien) mit je einem DB-Write pro Kategorie ist O(n*m) und bei grossen Instanzen ein moderater Write-Aufwand (P3, durch Adhoc-Ausfuehrung im Cron entschaerft). `$price = new price('option')` wird nur zum Lesen von `pricecategories` instanziiert, danach werden ausschliesslich statische `price::`-Aufrufe genutzt — leichte Inkonsistenz.

## Bewertungs-Resümee
Schlanker, gut nachvollziehbarer Recalc-Task. Schwaechen sind kosmetisch (irrefuehrender Campaign-Cache-Kommentar, halb-statische Nutzung von `price`) bzw. perf-mild (Write pro Option/Kategorie). Keine funktionalen Defekte. Klassen-Score **B / P3**.
