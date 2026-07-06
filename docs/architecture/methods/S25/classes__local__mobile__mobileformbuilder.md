# mobileformbuilder — Methoden-Doku
**Datei:** `classes/local/mobile/mobileformbuilder.php` · **LOC:** 203 · **Subsystem:** S25 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S25_mobile.md)

## Klassenueberblick
`mobileformbuilder` ist ein rein statischer Renderer, der Custom-Form-Definitionen in Ionic-App-Markup (`<ion-*>`) fuer die Moodle-Mobile-App uebersetzt. Es kombiniert Mustache-Templates (`mod_booking/mobile/ionform/*`) mit handgebauten HTML/JS-Strings, die per `core-site-plugins-call-ws` auf die Webservice-Funktion `mod_booking_get_submission_mobile` zurueckbinden. Keine Persistenz, kein State; Kollaborateure: `$OUTPUT` (Template-Rendering), `get_string`, `sesskey`. Auch hier sind die Doc-Bloecke („Class cartstore", „Builds form for ionic mobile app" auf jeder Methode) Copy-Paste und teils irrefuehrend.

## Methoden

### `public static function submission_form_submitted(): string` — public static
- **Zweck:** Liefert statisches Erfolgs-Markup (ion-card mit Haken-Icon + Erfolgsmeldung) nach erfolgreicher Submission. **Seiteneffekte:** `get_string` (2x). **Rueckgabe:** HTML-String. **Bewertung:** B — Inline-Styles/Markup hartcodiert, aber unkritisch.

### `public static function reset_submission_form_btn($dataglobal): string` — public static
- **Zweck:** Baut den „Zuruecksetzen"-Button, der die WS mit `reset:true` und leeren Daten aufruft. **Seiteneffekte:** keine. **Rueckgabe:** HTML-String. **Bewertung:** C — `$dataglobal['id']`/`['userid']` werden mit `?? 0` direkt in das `[params]`-JS-Objekt konkateniert, **ohne `(int)`-Cast**; bei string-Werten wandern sie roh in das Ionic-JS-Template (siehe Resümee).

### `public static function build_submission_form($dataglobal, $ionichtml, $resetsubmissionform): string` — public static
- **Zweck:** Umrahmt das gesammelte Feld-Markup mit ion-card/ion-list und dem Submit-Button, der via `CoreUtilsProvider.objectToArrayOfObjects(CONTENT_OTHERDATA.data, 'name','value')` die eingegebenen Werte mit `reset:false` an die WS sendet; haengt den Reset-Button an. **Seiteneffekte:** `sesskey()` (eingebettet als `sessionkey`), `get_string`. **Rueckgabe:** HTML-String. **Bewertung:** C — wie oben ungecastete `id`/`userid`-Interpolation in den JS-Param-String.

### `public static function build_submission_entitites(object $formsarray, array $dataglobal)` — public static
- **Zweck:** Iteriert die Form-Definitionen, setzt Header-/Cancel-/Submit-Strings, mappt jeden `formtype` auf das passende Ionic-Template (advcheckbox/static/shorttext|mail|url/select) und sammelt das Markup; bei einem fehlerfrei abgeschlossenen Element wird stattdessen der Reset-Button gesetzt; umrahmt am Ende via `build_submission_form`. **Seiteneffekte:** `global $OUTPUT`, mehrfach `$OUTPUT->render_from_template(...)`, `get_string`. **Rueckgabe:** HTML-String (leer, wenn kein renderbares Element). **Bewertung:** C — die Gating-Logik `if (!isset($submission->error) || $submission->error)` (render bei Fehler oder fehlendem error-Flag) vs. `else if (isset && !error)` (Reset-Button) ist subtil und schwer lesbar; Typname-Tippfehler im Methodennamen (`entitites`). Nicht durch `default`-Zweig abgedeckte `formtype`-Werte werden still uebersprungen.

### `public static function get_select_options(array $myform)` — public static
- **Zweck:** Parst den `value`-String eines Select (`key => label` pro Zeile) in eine `values`-Liste fuer das Select-Template; faellt bei Zeilen ohne `=>` auf Zeilenindex als Key zurueck. **Seiteneffekte:** keine. **Rueckgabe:** das angereicherte `$myform`-Array (`['values' => [...]]`). **Bewertung:** B — robust gegen fehlende Trenner; leere letzte Zeile (trailing PHP_EOL) erzeugt ggf. eine Pseudo-Option.

## Bewertungs-Resümee
Funktionaler, aber rein string-basierter Mobile-Renderer. Die Mustache-Pfade sind sauber; die handgebauten JS-Param-Strings sind der Schwachpunkt: `$dataglobal['id']`/`['userid']` werden ohne Integer-Cast direkt in `[params]="{...}"`-Ausdruecke interpoliert (`reset_submission_form_btn`, `build_submission_form`). Solange diese Werte garantiert numerisch aus dem WS kommen, ist es harmlos; eine `(int)`-Absicherung waere dennoch angebracht (Defense-in-Depth gegen Markup/JS-Injection). Dazu Lesbarkeits-/Benennungsschwaechen und irrefuehrende Doc-Bloecke. Klassen-Score **C / P2**.
