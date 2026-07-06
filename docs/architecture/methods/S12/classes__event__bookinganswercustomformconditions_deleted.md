# bookinganswercustomformconditions_deleted — Methoden-Doku
**Datei:** `classes/event/bookinganswercustomformconditions_deleted.php` · **LOC:** 99 · **Subsystem:** S12 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S12_events.md)

## Klassenueberblick
`bookinganswercustomformconditions_deleted` ist ein Moodle-Logevent (`\core\event\base`), das das Loeschen von Custom-Form-Bedingungsdaten einer Buchungsantwort protokolliert. Keine eigene Persistenz; Bezug ueber `objecttable = 'booking_answers'`/`objectid`. Auffaellig: die Beschreibung loest Akteur und betroffenen User ueber `singleton_service::get_instance_of_user(...)` in Klarnamen auf — der einzige Slot-/Answer-Event, der dafuer DB-/Cache-Lookups macht. Kollaborateure: `singleton_service`, `get_string`, `subscribeusers.php` (Log-URL).

## Methoden

### `protected function init()` — protected
- **Zweck:** Setzt `crud = 'u'`, `edulevel = LEVEL_PARTICIPATING`, `objecttable = 'booking_answers'`. **Seiteneffekte:** Mutiert `$this->data`. **Bewertung:** A.

### `public static function get_name()` — public static
- **Zweck:** Name ueber `get_string('bookinganswerupdated', 'mod_booking')`. **Seiteneffekte:** keine. **Rueckgabe:** string. **Bewertung:** B — der generische Name „bookinganswerupdated" beschreibt das spezifische „custom form conditions deleted"-Ereignis nur unscharf (im Log schwer von echten Updates zu unterscheiden).

### `public function get_description()` — public
- **Zweck:** Liest `userid` (Akteur), `objectid` (booking-answer-id), `data['relateduser']` (betroffener User) und `data['other']['column']`; loest beide User-IDs in `Vorname Nachname (ID: n)` auf und gibt `get_string('eventdesc:bookinganswercustomformconditionsdeleted', 'mod_booking', $a)` zurueck. **Seiteneffekte:** zwei `singleton_service::get_instance_of_user()`-Aufrufe (DB/Cache-Userlookup). **Rueckgabe:** string. **Bewertung:** C — siehe Findings: (a) liest `$this->data['relateduser']` statt des Standard-Felds `relateduserid` — ist dieser Key nicht gesetzt, entsteht ein PHP-Warning/Undefined-Array-Key und ein fehlerhafter Lookup `get_instance_of_user(0)`; (b) `$col` aus `other['column']` wird berechnet, aber nie in `$a` verwendet (toter Code / unvollstaendige Beschreibung); (c) kein `validate_data()`, daher keine Garantie, dass die referenzierten Felder existieren.

### `public function get_url()` — public
- **Zweck:** Log-URL `/mod/booking/subscribeusers.php?id=<contextinstanceid>&optionid=<objectid>`. **Seiteneffekte:** keine. **Rueckgabe:** `moodle_url`. **Bewertung:** B — verwendet `objectid` (die booking-answer-id) als `optionid`-Parameter; sofern `objectid` hier tatsaechlich die optionid traegt, ok, andernfalls zeigt der Link auf die falsche Option.

### Triviale Properties
Keine; die Klasse besitzt nur die vier Event-Methoden. Es fehlt eine `validate_data()`-Ueberschreibung (anders als bei den slot*-Events).

## Bewertungs-Resümee
Funktional ein einfaches update-Event, aber mit mehreren Schwaechen in `get_description`: nicht-kanonischer `data['relateduser']`-Zugriff (Undefined-Key-Risiko + falscher User-Lookup), ungenutzter `column`-Wert und fehlende `validate_data()`-Absicherung. Dazu der generische Name „bookinganswerupdated". Keine Datenverlust-Folgen, aber spuerbare Korrektheits-/Robustheits-Maengel. Klassen-Score **B / P3** (mit C-bewerteter `get_description`).
