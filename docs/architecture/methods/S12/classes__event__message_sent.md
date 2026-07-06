# message_sent — Methoden-Doku
**Datei:** `classes/event/message_sent.php` · **LOC:** 157 · **Subsystem:** S12 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`message_sent` ist ein Moodle-Event (`extends \core\event\base`) ueber den Versand einer System-Mail an einen Nutzer. `get_description()` baut — wie `custom_message_sent` — eine aufklappbare Bootstrap-Collapse-HTML-Beschreibung, loest zusaetzlich aber Sender- und Empfaenger-Namen via `singleton_service::get_instance_of_user()` auf. `crud='r'`, `objecttable='booking_options'`. Keine eigene Persistenz (Standard-Logstore). Kollaborateure: `\core\event\base`, `singleton_service`, `get_string()`, die `MOD_BOOKING_MSGPARAM_*`-Konstanten (via `mod/booking/lib.php`).

## Methoden

### `protected function init()` — protected
- **Zweck:** Event-Init; `crud='r'`, `edulevel=LEVEL_TEACHING`, `objecttable='booking_options'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Lokalisierter Name via `get_string('messagesent', 'booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — Komponenten-Kurzform `'booking'` statt `'mod_booking'` (aequivalent, aber inkonsistent).

### `public function get_description()` — public
- **Zweck:** Baut eine HTML-Collapse-Beschreibung (uniqueid via `uniqid()`) mit Nachrichtentyp, Betreff, aufgeloesten Sender-/Empfaenger-Namen und aufklappbarem Nachrichtenkoerper. Robust gegen beide `other`-Formen (`is_string` → `json_decode`, sonst Array-Cast). **Seiteneffekte:** zwei `singleton_service::get_instance_of_user()`-Aufrufe (gecachte User-Lookups). **Rueckgabe:** HTML-string. **Bewertung:** C — mehrere Maengel: (1) `$subject` und `$message` werden UNESCAPED in das zurueckgegebene HTML konkateniert (Stored-XSS-Flaeche bei HTML-Rendering der Log-Beschreibung). (2) Z.97 Fallback-Bug: `$relatedusername = empty($relateduser) ? $userid : ...` faellt auf `$userid` (Sender) statt `$relateduserid` (Empfaenger) zurueck — bei nicht aufloesbarem Empfaenger wird die Sender-id als Empfaengername angezeigt. (3) Z.90 `$messageparam = $other->messageparam ?? '';` defaultet auf Leerstring, der an `transform_msgparam(int $msgparam)` uebergeben wird; ohne `strict_types` zu `0` gecastet, ansonsten konsistent mit dem Konstanten-Mapping. (4) nahezu vollstaendiges Duplikat von `custom_message_sent::get_description()`.

### `private function transform_msgparam(int $msgparam): string` — private
- **Zweck:** Mappt `MOD_BOOKING_MSGPARAM_*` auf englisches Klartext-Label; `default => 'Unknown message type'`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — funktional korrekt, aber wortgleiches Duplikat von `custom_message_sent::transform_msgparam()` und nicht lokalisiert.

### Triviale Properties
Keine eigenen Properties; Zustand liegt im geerbten `$this->data`.

## Bewertungs-Resümee
Funktional weitgehend korrekt, aber mit drei substanziellen Schwaechen in `get_description()`: unescapte nutzergesteuerte Inhalte (XSS-Flaeche, P2), falscher Fallback `$userid` statt `$relateduserid` fuer den Empfaengernamen (Anzeige-Bug), sowie starke Code-Duplikation mit `custom_message_sent`. Klassen-Score **C / P2**.
