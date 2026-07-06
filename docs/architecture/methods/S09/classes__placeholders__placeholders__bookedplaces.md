# bookedplaces — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/bookedplaces.php` · **LOC:** 81 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`bookedplaces` ist eine Platzhalter-Klasse (`extends placeholder_base`) fuer einen Token, der die Anzahl der gebuchten Plaetze einer Buchungsoption liefert. Keine eigene Persistenz; liest ueber den `singleton_service` die `booking_option_settings` und das `booking_answers`-Objekt. Kollaborateure: `singleton_service::get_instance_of_booking_option_settings`, `singleton_service::get_instance_of_booking_answers`, `booking_answers::return_all_booking_information`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE): string` — public static
- **Zweck:** Gibt die Zahl der aktuell gebuchten Plaetze der Option als String zurueck. **Seiteneffekte:** keine DB-Schreibvorgaenge; laedt Settings + Answers ueber den (gecachten) Singleton-Service und ruft `return_all_booking_information(-1)` auf. Der Marker `-1` wird laut Inline-Kommentar bewusst genutzt, um „immer korrekt auf das Array zuzugreifen" (statt auf einen konkreten userid-Eintrag). **Rueckgabe:** `(string) $freetobook['notbooked']['booked']`. **Bewertung:** B — funktional korrekt; verlaesst sich ungeprueft auf das Vorhandensein der verschachtelten Keys `['notbooked']['booked']` (kein `??`-Guard) und ignoriert `optionid == 0`, was bei fehlerhaftem Aufruf in eine Settings-Aufloesung mit id 0 laeuft. In den vorgesehenen Render-Pfaden ist `optionid` gesetzt, daher kein harter Bug.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Markiert den Platzhalter generell als anwendbar. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Kompakter Read-Only-Platzhalter, der die Plaetze-Zaehlung sauber an `booking_answers` delegiert. Schwaechen sind defensiver Natur (keine Guards fuer fehlende Array-Keys / `optionid == 0`), in der Praxis unkritisch. Klassen-Score **B / P3**.
