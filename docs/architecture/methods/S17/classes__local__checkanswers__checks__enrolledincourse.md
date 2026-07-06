# enrolledincourse — Methoden-Doku
**Datei:** `classes/local/checkanswers/checks/enrolledincourse.php` · **LOC:** 71 · **Subsystem:** S17 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S17_reporting.md)

## Klassenueberblick
`enrolledincourse` ist eine der Check-Subklassen des `checkanswers`-Mechanismus, der prueft, ob bestehende Buchungs-Answers noch gueltig sind. Diese konkrete Pruefung beantwortet die Frage „ist der gebuchte User noch im zugehoerigen Kurs eingeschrieben?". Die Klasse ist zustandslos (rein statische API), ihre Identitaet ergibt sich aus der Konstante `checkanswers::CHECK_COURSE_ENROLLMENT`. Persistenz: keine eigene; liest indirekt ueber `singleton_service`-Settings und Moodles `is_enrolled()`. Kollaborateure: `singleton_service` (Option- und Booking-Settings), `context_course`, Moodle-Core `is_enrolled()`.

## Methoden

### `public static function get_id()` — public static
- **Zweck:** Liefert die Check-ID dieser Klasse (`self::$id`, gesetzt auf `checkanswers::CHECK_COURSE_ENROLLMENT`), damit der Dispatcher die Pruefung zuordnen kann. **Seiteneffekte:** keine. **Rueckgabe:** int (Check-Konstante). **Bewertung:** A — trivialer Getter.

### `public static function check_answer(stdClass $answer)` — public static
- **Zweck:** Entscheidet, ob die uebergebene Answer noch valide ist, indem der zugehoerige User auf aktive Kurseinschreibung geprueft wird. Aufloesungskette: `optionid` -> `booking_option_settings` -> `cmid` -> `booking_settings` -> `course`; darauf `is_enrolled(context_course::instance($course), $userid)`. **Seiteneffekte:** lesend; zwei `singleton_service`-Lookups (gecacht), `context_course::instance()`, Core-`is_enrolled()`-Query. **Rueckgabe:** bool — true, wenn der User noch eingeschrieben ist. **Bewertung:** A — kurz, klar, nutzt die gecachten Settings-Singletons; keine N+1-Gefahr pro Aufruf, sofern der aufrufende Loop die Singletons wiederverwendet.

### Triviale Properties
`public static int $id` (Z.44) als Check-Identitaet, initialisiert aus `checkanswers::CHECK_COURSE_ENROLLMENT`.

## Bewertungs-Resümee
Minimaler, gut fokussierter Check ohne eigene Persistenz; delegiert sauber an Core-`is_enrolled()` und die Settings-Singletons. Keine funktionalen Schwaechen. Klassen-Score **A / P3**.
