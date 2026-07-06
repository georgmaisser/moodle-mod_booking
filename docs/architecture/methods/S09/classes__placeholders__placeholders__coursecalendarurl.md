# coursecalendarurl — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/coursecalendarurl.php` · **LOC:** 105 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`coursecalendarurl` ist eine Platzhalter-Klasse (extends `\mod_booking\placeholders\placeholder_base`) im Messaging-/Placeholder-Subsystem. Sie loest den `{coursecalendarurl}`-Platzhalter zu einem HTML-Link auf das ICS-Kalender-Abonnement aller Kurse des Nutzers. Keine eigene Persistenz; statisch. Kollaborateure: `booking_utils::booking_generate_calendar_subscription_link()`, `singleton_service::get_instance_of_user()`, der Prozess-Singleton-Cache `placeholders_info::$placeholders`, `get_string`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Erzeugt bei vorhandenem `$optionid` einen `<a>`-Link auf das Kalender-Abo (`'courses'`-Variante) des Nutzers. Bei fehlendem optionid lokalisierter Fehlerstring.
- **Seiteneffekte:** Memoisiert ueber `placeholders_info::$placeholders[$cachekey]` mit `$cachekey = "$classname-$optionid-$userid"` (userspezifisch, da der Subscription-Link nutzerabhaengig ist). Instanziiert `new booking_utils()`, laedt den User via `singleton_service::get_instance_of_user($userid)` und ruft `booking_generate_calendar_subscription_link()`.
- **Rueckgabe:** HTML-Link-String oder Fehlerstring.
- **Bewertung:** B — korrekt userspezifischer Cachekey (anders als die rein options-bezogenen Kurs-Platzhalter). Der Guard prueft `$optionid`, obwohl der Wert selbst gar nicht in die Link-Erzeugung einfliesst (nur User zaehlt) — der Link haengt faktisch nur am User, optionid dient nur als Anwesenheits-Gate.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert generelle Aktivierung des Platzhalters.
- **Seiteneffekte:** keine.
- **Rueckgabe:** immer `true`.
- **Bewertung:** A — konstanter Gate.

## Bewertungs-Resümee
Schmaler User-Kalender-Link-Platzhalter mit sauberem, userspezifischem Memo-Cache. Kleiner Geruch: `$optionid` ist reines Anwesenheits-Gate und fliesst nicht in den Link. Funktional unkritisch. Klassen-Score **B / P3**.
