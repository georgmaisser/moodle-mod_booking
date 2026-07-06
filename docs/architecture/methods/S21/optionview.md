# optionview — Methoden-Doku
**Datei:** `optionview.php` · **LOC:** 173 · **Subsystem:** S21 · **Klassen-Score:** D / P2
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point: die oeffentliche Detailseite *einer* Buchungsoption (`/mod/booking/optionview.php?cmid=&optionid=`). Bewusst **ohne** `require_login()` am Anfang — die Seite soll auch fuer nicht eingeloggte Besucher (Direkt-Link / Bestell-Landingpage) erreichbar sein. Der Script orchestriert: Parameter-Parsing, optionales Override eines User-Felds via Passwort (`override_user_field`), bedingte Login/Capability-Erzwingung fuer gebuchte Nutzer, Site-Policy-Acceptance-Redirect, und schliesslich das Rendern von `bookingoption_description` ueber den `mod_booking`-Renderer. Keine Klasse, keine Persistenz im engeren Sinne (liest nur). Kollaborateure: `singleton_service` (booking/option-settings/answers/user), `override_user_field`, `customform_prefill`, `bookingoption_description`, `\tool_policy\api`, `core_plugin_manager`, `$PAGE`/`$OUTPUT`.

## Ablauf (prozeduraler Request-Flow)

### Parameter & Kontext (Z.39–68)
- **Zweck:** Liest `cmid`, `optionid` (required), `userid`, Return-/Redirect-Parameter sowie `cvpwd`/`cvfield` (Override-Passwort + Zielfeld). Bildet `modcontext` (module) und `syscontext` (system). **Seiteneffekte:** Wenn `userid != $USER->id` und die `mod/booking:updatebooking`-Capability fehlt, wird `userid` auf den eigenen User zurueckgesetzt (verhindert Fremd-User-View ohne Recht). `$PAGE->set_context($syscontext)` + `set_url`. **Bewertung:** B — Kontext bewusst System (damit Seite ohne Kurs-Enrolment laeuft); Userid-Guard ist sinnvoll.

### Override-User-Field (Z.60–63)
- **Zweck:** `new override_user_field($cmid)`; wenn das uebergebene Passwort (`cvpwd`) gueltig ist, wird `cvfield` als Userpref fuer `userid` gesetzt. **Seiteneffekte:** schreibt User-Preference (Mutation auf GET-Request). **Bewertung:** C — Schreiboperation aus einem GET ohne sesskey; abgesichert nur durch das Feld-Passwort. Funktional gewollt (Kassen-/Override-Workflow), aber CSRF-untypisch fuer Moodle.

### Singletons & Early-Redirect (Z.70–79)
- **Zweck:** Laedt booking-Instanz, option-settings, booking-answers; `get_usersonlist()`. Wenn `redirecttocourse===1` und der aktuelle User auf der Teilnehmerliste steht, Redirect direkt in den Kurs. **Seiteneffekte:** `redirect()` (Exit). **Bewertung:** B.

### Sichtbarkeits-/Login-/Capability-Block (Z.81–133)
- **Zweck:** Nur wenn `$settings` existiert: Auswahl des anzuzeigenden `$user` (eigener vs. fremder), Customform-Prefill (nur eingeloggt), bedingte Login-/Capability-Erzwingung und Site-Policy-Acceptance. **Seiteneffekte:** ggf. `require_login()`, `require_capability('mod/booking:view')`, Redirect auf Policy-Seite. **Bewertung:** D — der Capability-Block (Z.100–115) erzwingt `require_login`/`require_capability` *nur* wenn der User bereits gebucht ist (`user_status > RESERVED`) **und** `showbookingdetailstoall` aus ist; die Verschachtelung mehrerer `isloggedin() && !isguestuser()`-Wiederholungen (Z.90–118) ist schwer zu lesen und der inline-Kommentar selbst nennt die Logik fixbeduerftig. Funktional fragil, daher P2 fuer die Datei.

### Render & Footer (Z.135–173)
- **Zweck:** Setzt Title/Pagelayout, gibt Header aus, baut `bookingoption_description` (Modus `OPTIONVIEW`) und rendert sie ueber `render_bookingoption_description_view`. Unsichtbare Optionen werden nur bei `mod/booking:canseeinvisibleoptions` gezeigt, sonst Hinweistext. Bindet bei installiertem `local_shopping_cart` das Cart-AMD-Init ein. Else-Zweig (kein gueltiges Setting): Redirect auf `view.php`. **Seiteneffekte:** Echo HTML, `js_call_amd`, Footer. **Bewertung:** D — siehe Findings: `$modalcounter` (Z.166) ist in diesem Script **nirgends definiert** und wird ungeprueft an `js_call_amd` uebergeben (PHP-Notice + null-Argument). Der TODO-Kommentar (Z.139–142) dokumentiert ausserdem einen bekannten Context-Switch-nach-Header-Smell.

## Bewertungs-Resümee
Sicherheitssensibler oeffentlicher Entry-Point mit absichtlich gelockerter Login-Pflicht. Die Sichtbarkeits-/Policy-Logik ist korrekt gemeint, aber stark verschachtelt und mehrfach wiederholt; ein undefiniertes `$modalcounter` beim Cart-Init und eine GET-getriggerte Userpref-Mutation ohne sesskey sind die konkreten Schwachstellen. Klassen-Score **D / P2**.
