# pricecategory_changed — Methoden-Doku
**Datei:** `classes/event/pricecategory_changed.php` · **LOC:** 70 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`pricecategory_changed` ist ein Moodle-Logevent (`\core\event\base`), das gefeuert wird, wenn der Identifier einer Preiskategorie geaendert wurde. Es ist insbesondere ein Observer-Trigger: ein nachgelagerter Observer schreibt bestehende Preise in `booking_prices` auf den neuen Kategorie-Identifier um. Keine eigene Persistenz; fachliches `objecttable` ist `booking_prices`. Kollaborateure: Trigger im Pricecategories-Settings-Pfad, registrierter Observer (Preis-Umschreibung), `get_string()`, `moodle_url`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Deklariert die Event-Metadaten. **Seiteneffekte:** Setzt `data['crud']='u'` (update), `data['edulevel']=LEVEL_TEACHING`, `data['objecttable']='booking_prices'`. **Bewertung:** B — `LEVEL_TEACHING` ist fuer ein reines Preiskategorie-Admin-Event eine grosszuegige Klassifikation (eher Konfiguration als Lehre); funktional unkritisch.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename. **Seiteneffekte:** `get_string('pricecategorychanged','mod_booking')`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert eine statische Beschreibung „Price category has been changed". **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — bewusst generisch; enthaelt weder alten noch neuen Identifier, daher fuer das Log-Audit wenig aussagekraeftig (kein Bug, aber duenn).

### `public function get_url()` — public
- **Zweck:** Verweist auf die globale Preiskategorien-Verwaltungsseite. **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url('/mod/booking/pricecategories.php', [])`. **Bewertung:** A — keine Instanz-/Parameterabhaengigkeit, robust.

## Bewertungs-Resümee
Minimaler, robuster Event-Wrapper. Im Gegensatz zu den anderen Events greift `get_url()` auf keine `other`-Felder zu und ist damit fehlerunempfindlich. Schwaeche nur in der Aussagekraft der statischen Beschreibung. Klassen-Score **B / P3**.
