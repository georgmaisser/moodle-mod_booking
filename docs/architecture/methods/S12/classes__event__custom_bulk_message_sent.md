# custom_bulk_message_sent — Methoden-Doku
**Datei:** `classes/event/custom_bulk_message_sent.php` · **LOC:** 74 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`custom_bulk_message_sent` ist ein Moodle-Event (`extends \core\event\base`), das ausgeloest wird, wenn eine benutzerdefinierte Massennachricht an (laut Doc-Block) mehr als 75% der gebuchten Nutzer einer Buchungsoption versendet wurde. Keine eigene Persistenz; das Event wird ueber den Standard-Logstore-Mechanismus geschrieben. `objecttable` ist `booking_options`. Kollaborateure: `\core\event\base`, `get_string()`, sowie der Action-Konsument `booking_rules\actions\send_copy_of_mail`. Datei requiret defensiv `mod/booking/lib.php`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Pflicht-Init des Events; setzt `crud='r'` (read, da reine Versand-Benachrichtigung), `edulevel=LEVEL_TEACHING`, `objecttable='booking_options'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A — kanonischer Event-Init.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Anzeigename via `get_string('custombulkmessagesent', 'mod_booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Liefert eine englische Klartext-Beschreibung mit Betreff und Options-id. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** C — greift `$this->other['subject']`/`$this->other['optionid']` direkt zu, OHNE die `json_decode`-/`is_string`-Absicherung, die `custom_message_sent`/`message_sent` verwenden; wird `other` aus dem Logstore als JSON-String restauriert, liefert der Array-Zugriff PHP-Warnungen/leere Werte. Zudem werden Betreff und id unescaped in den Rueckgabestring konkateniert (kein `s()`), was bei spaeterer HTML-Ausgabe der Beschreibung eine Injection-Flaeche bietet (hier nur Klartext, daher geringeres Risiko als bei den HTML-Description-Events).

## Bewertungs-Resümee
Schlankes Versand-Event. Funktional unkritisch im Normalpfad, aber `get_description()` ist gegen den restaurierten (JSON-String-)`other`-Zustand nicht abgesichert und escaped Eingaben nicht — beides im Gegensatz zu den Schwester-Events dieses Subsystems. Klassen-Score **B / P3**.
