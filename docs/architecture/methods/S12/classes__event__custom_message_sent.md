# custom_message_sent — Methoden-Doku
**Datei:** `classes/event/custom_message_sent.php` · **LOC:** 147 · **Subsystem:** S12 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`custom_message_sent` ist ein Moodle-Event (`extends \core\event\base`) ueber den Versand einer einzelnen benutzerdefinierten Nachricht. Im Gegensatz zu den schlanken Versand-Events baut es in `get_description()` ein aufklappbares Bootstrap-Collapse-HTML mit Betreff, Empfaenger/Sender-id und vollem Nachrichtentext. `crud='r'`, `objecttable='booking_options'`. Keine eigene Persistenz (Standard-Logstore). Kollaborateure: `\core\event\base`, `get_string()`, die `MOD_BOOKING_MSGPARAM_*`-Konstanten (via `mod/booking/lib.php`). Nahezu identisch zu `message_sent` (Code-Duplikat), nur ohne die Username-Aufloesung.

## Methoden

### `protected function init()` — protected
- **Zweck:** Event-Init; `crud='r'`, `edulevel=LEVEL_TEACHING`, `objecttable='booking_options'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Name via `get_string('custommessagesent', 'mod_booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Baut eine HTML-Collapse-Beschreibung (uniqueid via `uniqid()`), die Nachrichtentyp, Betreff, Empfaenger-id und Sender-id zeigt und den Nachrichtenkoerper aufklappbar einbettet. Robust gegen beide `other`-Formen: bei `is_string($data['other'])` wird `json_decode` angewandt (restaurierter Logstore-Zustand), sonst direkter Array-Cast. **Seiteneffekte:** keine (liest `$this->get_data()`). **Rueckgabe:** HTML-string. **Bewertung:** C — die `other`-Werte `$subject` und insbesondere `$message` werden UNESCAPED (kein `s()`/`format_text()`) in das zurueckgegebene HTML konkateniert. Da Beschreibungen von Log-Events als HTML gerendert werden und Betreff/Body frei vom Nutzer stammen, ist das eine Stored-XSS-Flaeche. Zudem fast vollstaendig dupliziert mit `message_sent::get_description()`.

### `private function transform_msgparam(int $msgparam): string` — private
- **Zweck:** Mappt die `MOD_BOOKING_MSGPARAM_*`-Konstante auf ein englisches Klartext-Label; `default => 'Unknown message type'`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — funktional korrekt, aber wortgleich zu `message_sent::transform_msgparam()` (Duplikat) und nicht lokalisiert. Hinweis: `messageparam` wird in Z.85 mit `?? 0` defaultet, sodass der int-Parameter sauber bedient wird.

### Triviale Properties
Keine eigenen Properties; Zustand liegt im geerbten `$this->data`.

## Bewertungs-Resümee
Funktional korrekt im Normalfall mit guter `other`-Format-Absicherung, aber zwei substanzielle Schwaechen: (1) unescapter HTML-Aufbau von nutzergesteuertem Betreff/Body in `get_description()` (XSS-Flaeche, P2), (2) nahezu vollstaendige Code-Duplikation mit `message_sent`. Klassen-Score **C / P2**.
