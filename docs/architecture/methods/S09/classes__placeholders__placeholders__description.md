# description — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/description.php` · **LOC:** 132 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`description` ist eine Platzhalter-Klasse (`extends placeholder_base`) im Placeholder-Subsystem: Sie ersetzt den Platzhalter `{description}` in Instanz-/Options-/Mail-Texten durch die formatierte Beschreibung der Buchungsoption. Stateless; reine statische API (`return_value`, `is_applicable`, `for_pollurl`). Persistenz: keine eigene; liest `booking_option_settings->description` ueber `singleton_service`. Request-scoped Memo via statisches Array `placeholders_info::$placeholders` (Singleton-Cache + Loop-Praevention). Kollaborateure: `singleton_service`, `placeholders_info`, `format_text`, `get_string`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert die per `format_text` gerenderte Optionsbeschreibung als Platzhalterwert. Greift den Klassennamen via `get_called_class()` ab und nutzt ihn als Cache-Praefix.
- **Seiteneffekte:** `singleton_service::get_instance_of_booking_option_settings($optionid)` (Settings-Load, ggf. cmid-Ableitung); schreibt/liest `placeholders_info::$placeholders[$cachekey]` (request-scoped). Cachekey ist `"$classname-$optionid-$userid"`.
- **Rueckgabe:** string — formatierte Beschreibung; bei erkannter Rekursion `get_string('loopprevention', ...)`; bei fehlender `userid` `get_string('sthwentwrongwithplaceholder', ...)`.
- **Bewertung:** B — Die Loop-Praevention ist ein durchdachter, aber subtiler Mechanismus: Sentinel `1` markiert „in Bearbeitung", wird via `++` auf `2` gesetzt, der fertige Wert ersetzt den Sentinel. Der `is_numeric`-Guard in Z.84 verhindert, dass ein noch-numerischer Sentinel als fertiger Wert zurueckgegeben wird. Korrekt, aber schwer lesbar; eine Beschreibung, die zufaellig numerisch ist (`format_text('5')` → `'5'`), wuerde beim naechsten Aufruf nicht aus dem Cache geliefert (`is_numeric`-Miss) und neu gerendert — funktional unkritisch, nur kein Cache-Hit.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter ueberhaupt aufgerufen werden soll. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter in Pollurls (Umfrage-Links) sinnvoll ist. **Seiteneffekte:** keine. **Rueckgabe:** `false` (volle Beschreibung in einer Pollurl unsinnig). **Bewertung:** A.

## Bewertungs-Resümee
Stateless Platzhalter mit korrektem Render-Pfad (`format_text` erhaelt HTML). Einziger nennenswerter Punkt ist die schwer durchschaubare Loop-Praevention/Memo-Logik in `return_value`; sie funktioniert, ist aber fragil gegenueber rein numerischen Beschreibungswerten (verfehlter Cache-Hit, kein Datenfehler). Klassen-Score **B / P3**.
