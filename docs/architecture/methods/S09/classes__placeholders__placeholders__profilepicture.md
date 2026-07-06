# profilepicture — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/profilepicture.php` · **LOC:** 119 · **Subsystem:** S09 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`profilepicture` ist ein Platzhalter-Field der Messaging-Engine (`extends \mod_booking\placeholders\placeholder_base`) und liefert das Profilbild des adressierten Nutzers als inline base64-`<img>` fuer E-Mails. Keine eigene Persistenz; liest Dateien ueber `get_file_storage()` aus dem User-Kontext-Dateibereich (`user/icon`). Kollaborateure: `context_user`, `get_file_storage()`, statischer Request-Memo `placeholders_info::$placeholders`. Rein statisch (kein State), wird vom Placeholder-Resolver (`placeholders_info`) je Mail-Render aufgerufen.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Ermittelt das Profilbild (`f1`-Datei) des Nutzers, base64-kodiert es und gibt einen inline-`<img>`-Tag zurueck; bei fehlendem `userid` einen lokalisierten Fehlerstring. **Seiteneffekte:** `context_user::instance($userid, IGNORE_MISSING)`, `get_file_storage()->get_area_files(...)`, `$file->get_content()` (Dateisystem-/DB-Lesezugriff). Liest den Memo `placeholders_info::$placeholders["$classname-$optionid"]`, **schreibt ihn aber nie zurueck**. **Rueckgabe:** HTML-`<img>`-String, leerer String (kein Bild gefunden) oder Fehlerstring. **Bewertung:** C — mehrere Defekte: (1) Wenn `userid` gesetzt ist, aber `context_user::instance(...)` mit `IGNORE_MISSING` `false` liefert, bleibt `$value` **undefiniert** und die `return`-Zeile loest eine PHP-Warning aus / gibt null statt eines Strings zurueck. (2) Der Cache-Read bei Z.76 hat keinen korrespondierenden Write — der Memo ist toter Code, das Bild wird bei jeder Anrede neu aus dem Filestore gelesen und base64-kodiert (Ineffizienz im Mail-Versand-Loop). (3) Der `cachekey` basiert auf `$optionid`, obwohl das Bild pro `$userid` variiert — waere der Write vorhanden, kollidierten verschiedene Nutzer derselben Option. (4) MIME-Typ `data:image/image;base64` ist ungueltig (sollte z.B. `image/png` sein), was die Bilddarstellung in strikten Mail-Clients verhindern kann.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Gate, ob der Platzhalter ueberhaupt aufgeloest werden soll. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A — triviales konstantes Gate (Vertragspflicht von `placeholder_base`).

## Bewertungs-Resümee
Funktional liefert die Klasse das Inline-Profilbild, aber `return_value` haeuft mehrere echte Maengel: undefinierte `$value`-Variable bei fehlendem User-Kontext, ein nie befuellter (und falsch geschluesselter) Cache und ein ungueltiger MIME-Typ. Keine Datenverlust-/Sicherheitsfolgen, aber der base64-Re-Encode pro Empfaenger ist im Massen-Mail-Pfad spuerbar. Klassen-Score **C / P2**.
