# emailrelated — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/emailrelated.php` · **LOC:** 113 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`emailrelated` ist eine Platzhalter-Klasse (`extends placeholder_base`), die den Platzhalter `{emailrelated}` durch die E-Mail-Adresse des *related users* eines Events ersetzt (z.B. der von einer Aktion betroffene Nutzer, nicht der Mail-Empfaenger). Anders als `email` zieht sie die `userid` aus den deserialisierten Event-Daten in `$rulejson`. Stateless; reine statische API. Persistenz: keine eigene; liest `$user->email` via `singleton_service`. Request-scoped Memo via `placeholders_info::$placeholders`. Kollaborateure: Core-Event-Restore (`$class::restore`), `singleton_service`, `placeholders_info`, `json_decode`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE, string $rulejson = '')` — public static
- **Zweck:** Restauriert aus dem `$rulejson` das ausloesende Event, liest dessen `relateduserid` und gibt die E-Mail dieses Users zurueck. Erweitert die Basis-Signatur um den `$rulejson`-Parameter.
- **Seiteneffekte:** `json_decode($rulejson)`; dynamischer Event-Restore `$class::restore((array)$rulejson->datafromevent, [])` mit `$class = $rulejson->datafromevent->eventname`; `singleton_service::get_instance_of_user($userid)`; liest/schreibt `placeholders_info::$placeholders["$classname-$userid"]`.
- **Rueckgabe:** string — E-Mail des related users; bei leerem/fehlendem `rulejson` `get_string('sthwentwrongwithplaceholder', ...)`; bei vorhandenem rulejson aber **ohne** `relateduserid` der initialisierte Leerstring `''`.
- **Bewertung:** B — Funktional korrekt fuer den Event-getriebenen Pfad. Zwei Schwachpunkte: (1) `$class = $rulejson->datafromevent->eventname` mit anschliessendem `$class::restore(...)` ruft eine aus JSON stammende Klassennamen-Zeichenkette als statische Methode auf — ein Restore via persistierter Rule-Definition. Da `$rulejson` aus gespeicherten, vom Plugin selbst geschriebenen Booking-Rules stammt (nicht aus Request-Input), ist das im Normalbetrieb vertrauenswuerdig; der Vollstaendigkeit halber als latentes Dynamic-Dispatch-Muster vermerkt (P3). (2) Fehlt `relateduserid`, faellt die Funktion still durch und liefert `''` ohne Fehlerstring.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gibt an, ob der Platzhalter aufgerufen werden soll. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Event-getriebene Variante von `email`, die die Empfaenger-Mail aus restaurierten Event-Daten zieht. Korrekt im Plugin-Kontext; die dynamische `$class::restore`-Aufruf-Kette ueber einen aus JSON gelesenen Klassennamen ist ein vertretbares, aber bemerkenswertes Muster (Vertrauen auf integre, plugin-geschriebene Rule-Daten). Klassen-Score **B / P3**.
