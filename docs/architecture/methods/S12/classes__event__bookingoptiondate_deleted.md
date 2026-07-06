# bookingoptiondate_deleted — Methoden-Doku
**Datei:** `classes/event/bookingoptiondate_deleted.php` · **LOC:** 80 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookingoptiondate_deleted` ist ein Moodle-Standard-Event (`\core\event\base`-Subklasse), das ausgeloest wird, wenn ein Optionstermin/Session-Datum (`booking_optiondates`-Record) geloescht wurde. Die Klasse haelt keinen eigenen Zustand; sie parametrisiert das Core-Event-Framework und liefert Name/Beschreibung/URL furs Logging. Bis auf die `get_name()`/`get_description()`-Strings ist sie eine fast wortgleiche Kopie von `bookingoptiondate_created`. Persistenz: keine eigene; `objecttable` verweist auf `booking_optiondates`. Kollaborateure: `\core\event\base`, `get_string()`, `\moodle_url`, Trigger-Aufrufer im Optiondate-Loeschpfad.

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt die Event-Metadaten: `crud = 'c'`, `edulevel = LEVEL_TEACHING`, `objecttable = 'booking_optiondates'`. **Seiteneffekte:** mutiert `$this->data`. **Rueckgabe:** void. **Bewertung:** D — fehlerhafte CRUD-Klassifizierung: ein Delete-Event muss `crud = 'd'` setzen, hier steht (per Copy-Paste aus `_created`) `'c'`. Verfaelscht Log-Filter/-Reports, die nach CRUD-Typ selektieren.

### `public static function get_name()` — public static
- **Zweck:** Liefert den uebersetzten Anzeigenamen (`bookingoptiondatedeleted`). **Seiteneffekte:** `get_string(...)`. **Rueckgabe:** string. **Bewertung:** A.

### `public function get_description()` — public
- **Zweck:** Englischer Klartext-Satz: User mit `userid` hat Optiondate `objectid` geloescht. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — fest verdrahteter englischer String (nicht uebersetzbar), im Event-Korpus durchgaengig so.

### `public function get_url()` — public
- **Zweck:** Deep-Link zum Report (`report.php?id=<contextinstanceid>&optiondateid=<objectid>`). **Seiteneffekte:** keine. **Rueckgabe:** `\moodle_url`. **Bewertung:** B — verweist auf das soeben geloeschte Optiondate; der Link ist nach dem Delete ggf. ins Leere zeigend, fuer Audit-Nachvollzug aber vertretbar.

## Bewertungs-Resümee
Konventionelles Event, aber mit aus `_created` uebernommenem `crud = 'c'` statt `'d'` — eine echte, wenn auch niedrig-prioritaere Daten-/Reporting-Ungenauigkeit. Ansonsten unkritisch. Klassen-Score **B / P3**.
