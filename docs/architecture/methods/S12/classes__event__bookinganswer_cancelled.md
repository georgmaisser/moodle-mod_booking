# bookinganswer_cancelled — Methoden-Doku
**Datei:** `classes/event/bookinganswer_cancelled.php` · **LOC:** 108 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswer_cancelled` ist ein Standard-Moodle-Logevent (`extends \core\event\base`) fuer die Stornierung einer Buchungsantwort (ein User wird aus einer Option ausgetragen). Anders als die Fehler-Events erzeugt es in `get_description()` eine voll lokalisierte, kontextabhaengige Beschreibung, die zwischen Selbst-Stornierung (`userid == relateduserid`) und Fremd-Stornierung unterscheidet und optional `extrainfo` anhaengt. Eigene Persistenz: keine (Logstore-Event). Kein `validate_data()` — `relateduserid` und `other.extrainfo` werden ungeprueft vorausgesetzt bzw. nur teils per `empty()` abgesichert. Kollaborateure: `singleton_service` (User-/Option-Settings-Auflösung), `get_string`, `moodle_url`, `stdClass`.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten. **Seiteneffekte:** `crud='u'`, `edulevel=LEVEL_PARTICIPATING`, `objecttable='booking_answers'`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Anzeigename. **Rueckgabe:** `get_string('bookinganswercancelled', 'mod_booking')`. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Baut eine lokalisierte Beschreibung: liest `userid` (Akteur), `relateduserid` (betroffener User) und `objectid` (Option), loest darueber Klar-/Anzeigenamen auf und waehlt je nach Selbst-/Fremd-Stornierung einen anderen String. **Seiteneffekte:** drei `singleton_service`-Lookups (`get_instance_of_user` x2, `get_instance_of_booking_option_settings`); haengt optional `other.extrainfo` an. **Rueckgabe:** `get_string('eventdesc:bookinganswercancelledself'|'eventdesc:bookinganswercancelled', ...)` plus `$extrainfo`. **Bewertung:** B — sauber lokalisiert und cache-gestuetzt (singleton_service), aber ohne `validate_data` greift die Methode direkt auf `$this->data['relateduserid']` zu; fehlt dieses Feld beim Triggern, faellt der `(int)`-Cast auf 0 und es wird ein Pseudo-User aufgeloest (siehe Findings).

### `public function get_url()` — public
- **Zweck:** Verlinkt auf die Teilnehmer-Verwaltung der Option. **Rueckgabe:** `moodle_url('/mod/booking/subscribeusers.php', ['id' => $this->contextinstanceid, 'optionid' => $this->objectid])`. **Bewertung:** A.

## Bewertungs-Resümee
Der inhaltlich reichhaltigste der dokumentierten Events: kontextsensitive, lokalisierte Beschreibung mit singleton-gestuetzter Namensaufloesung. Hauptschwaeche ist das Fehlen einer `validate_data()`-Methode, sodass `relateduserid` ungeprueft erwartet wird; bei fehlendem Feld entsteht keine Exception, sondern eine irrefuehrende Beschreibung mit User-ID 0. Funktional unkritisch (nur Logtext). Klassen-Score **B / P3**.
