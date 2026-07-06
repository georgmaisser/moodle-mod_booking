# starttime — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/starttime.php` · **LOC:** 100 · **Subsystem:** S09 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`starttime` ist der Mail-/Text-Platzhalter (`[starttime]`), der die Startzeit (Uhrzeit) einer Buchungsoption als lokalisierten Zeit-String liefert. Strukturell nahezu identisch zu `startdate`, nur dass `strftimetime` statt `strftimedate` als Zeitformat verwendet wird und ebenfalls auf `coursestarttime` aus `booking_option_settings` zurueckgreift. Sie erbt von `\mod_booking\placeholders\placeholder_base`. Kollaborateure: `singleton_service`, `placeholders_info`, `userdate()`/`get_string()`.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Gibt `coursestarttime` der Option als per `strftimetime` formatierte Uhrzeit zurueck. **Seiteneffekte:** Lesezugriff via `singleton_service::get_instance_of_booking_option_settings($optionid)`. Bei leerem `$optionid` Rueckgabe der Fehler-Sprachzeichenkette `sthwentwrongwithplaceholder`. **Rueckgabe:** formatierte Uhrzeit, leerer String (wenn `coursestarttime` falsy), oder Fehlertext. **Bewertung:** C — der Request-Memo ist defekt: `$cachekey` wird gebildet und am Methodenkopf via `isset(placeholders_info::$placeholders[$cachekey])` geprueft (Z.74-77), aber der berechnete `$value` wird **nie** in `placeholders_info::$placeholders` zurueckgeschrieben (anders als `startdate` Z.86). Der Cache-Treffer-Zweig kann damit nie greifen; bei mehrfacher Verwendung desselben Platzhalters in einem Request wird `get_instance_of_booking_option_settings` redundant aufgerufen (Singleton mildert die Kosten, der Memo bleibt aber wirkungslos).

### `public static function is_applicable(): bool` — public static
- **Zweck:** Vertrags-Hook, ob der Platzhalter verarbeitet wird. **Seiteneffekte:** keine. **Rueckgabe:** immer `true`. **Bewertung:** A.

## Bewertungs-Resümee
Funktional liefert der Platzhalter korrekte Werte, aber die eingebaute Memoisierung ist toter Code (Lookup ohne Store), wodurch der beabsichtigte Cache-Effekt entfaellt. Geringfuegige Performance-Schwaeche, keine Korrektheitsverletzung. Klassen-Score **C / P3**.
