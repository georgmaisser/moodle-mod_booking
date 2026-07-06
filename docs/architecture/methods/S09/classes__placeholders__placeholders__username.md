# username — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/username.php` · **LOC:** 97 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`username` ist eine Platzhalter-Klasse (`{username}`) im Messaging-/Placeholder-Subsystem, abgeleitet von `\mod_booking\placeholders\placeholder_base`. Sie ersetzt den Platzhalter durch den Moodle-`username` des Nutzers, fuer den der Text gerendert wird. Rein statisch, zustandslos (kein Konstruktor/Property). Kollaborateure: `singleton_service::get_instance_of_user()` (User-Load mit eigenem Caching) und `placeholders_info::$placeholders` (prozessweiter Request-Cache der bereits aufgeloesten Platzhalterwerte). Persistenz: keine eigene.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert den `username` des Users `$userid`, mit Request-Cache-Lookup vor dem User-Load. **Seiteneffekte:** liest/schreibt indirekt den geteilten Request-Cache `placeholders_info::$placeholders` (Lese-Pfad: Cache-Hit unter Key `"<classname>-<userid>"`); laedt bei Cache-Miss den User via `singleton_service::get_instance_of_user($userid)`. `$text`/`$params` werden nicht mutiert. **Rueckgabe:** den gecachten Wert (Cache-Hit) bzw. `$user->username`; bei leerem `$userid` den Fehlertext `get_string('sthwentwrongwithplaceholder', 'mod_booking', $classname)`. **Bewertung:** B — funktional korrekt. Klassennamens-Ableitung via `substr(strrchr(get_called_class(), '\\'), 1)` ergibt `"username"`. Auffaelligkeit: die Methode liest aus `placeholders_info::$placeholders[$cachekey]`, schreibt den frisch geladenen Wert dort aber selbst **nicht** zurueck — das Befuellen des Caches obliegt dem aufrufenden `placeholders_info` (siehe Findings); damit ist der Cache-Read-Pfad innerhalb dieser Methode rein lesend und wird nur durch externes Befuellen wirksam.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert, dass dieser Platzhalter immer ausgewertet werden soll. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

### Triviale Properties
Keine — Klasse haelt keinen Zustand.

## Bewertungs-Resümee
Schlanker User-Attribut-Platzhalter mit Request-Cache-Lookup und sauberem Fehler-Fallback bei fehlendem User. Anders als `userid` fehlt hier `for_pollurl()` — der Platzhalter wird also (geerbtes Default-Verhalten von `placeholder_base` vorausgesetzt) nicht zwingend pollurl-tauglich, was zum CLASS_INDEX-Eintrag (kein pollurl-Vermerk) passt. Funktional unkritisch. Klassen-Score **B / P3**.
