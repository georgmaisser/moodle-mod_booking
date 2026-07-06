# enrollink — Methoden-Doku
**Datei:** `enrollink.php` · **LOC:** 91 · **Subsystem:** S21 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point zum Einloesen eines Enrol-Links (Lizenz-Bundle-Mechanik: ein Kaeufer reserviert n Plaetze, generiert ein `erlid` und Dritte schreiben sich darueber selbst ein). Die Seite ist bewusst zweistufig: Bedingungen werden VOR dem Login geprueft (damit eine blockierende Meldung ohne Anmeldung sichtbar ist), erst danach Login + tatsaechliche Einschreibung. Die gesamte Domaenenlogik liegt in der Klasse `mod_booking\enrollink`; das Skript ist reiner Controller. Kollaborateure: `enrollink::get_instance`, `enrolment_blocking`, `get_readable_info`, `enrol_user`, `get_courselink_url`, `get_bookingdetailslink_url`, `get_bookingoptiontitle`, `get_condition_block_description`, Template `mod_booking/enrollink`.

## Ablauf (Request-/Permission-Flow)

### Parameter + Guard (Z.30-36)
- **Zweck:** Liest `erlid` (`optional_param`, `PARAM_TEXT`); bei leerem Wert Redirect auf Site-Root. Laedt die Domaenen-Instanz `enrollink::get_instance($erlid)`. **Seiteneffekte:** ggf. DB-Read in `get_instance`, Redirect. **Bewertung:** B — `PARAM_TEXT` fuer einen Token-artigen Identifier ist grosszuegig; Validierung der `erlid`-Existenz liegt in `get_instance`.

### Pre-Login-Bedingungspruefung (Z.38-52)
- **Zweck:** `enrolment_blocking()` prueft Hindernisse, bevor Login erzwungen wird. Bei Treffer: System-Context/URL setzen, Header, Render des `enrollink`-Templates mit `info` (lesbar via `get_readable_info`) und `error => 1`. **Seiteneffekte:** Echo HTML; KEIN Login. **Bewertung:** B — bewusste Reihenfolge (Blocking-Info ohne Anmeldung). Anmerkung: `$OUTPUT->header()` (Z.45) und der via `$PAGE->get_renderer` geholte `$output` (Z.44) werden gemischt genutzt — funktional aequivalent, stilistisch uneinheitlich.

### Login + Einschreibung (Z.53-89)
- **Zweck:** `require_login(0, true, null, true)` (Gast-Autologin erlaubt, `setwantsurltome`), Context/URL, Header. Ruft `enrol_user($USER->id)` und holt Folge-Links/Title. Bei `MOD_BOOKING_AUTOENROL_STATUS_BLOCKED_BY_CONDITION` -> Warnung (`enrollink:7` mit Bedingungsbeschreibung, `iswarning=1`); sonst lesbare Info, `error=1` nur bei `"enrolmentexception"`. Render des Templates mit `info/error/iswarning/courselink/bodetailslink/namebookingoption`. **Seiteneffekte:** **Einschreibung des Nutzers in den Zielkurs (DB-Writes ueber `enrol_user`)**, Echo HTML. **Bewertung:** B — die eigentliche Mutation (Self-Enrolment) ist in `enrollink::enrol_user` gekapselt; der Controller behandelt die Status-Codes sauber. Hinweis: Es gibt keine explizite sesskey-/POST-Pruefung — die Einschreibung erfolgt bei GET des Links. Das ist fuer einen Einmal-/Token-Einloeselink (Bundle-Mechanik) per Design so, der Schutz liegt in der `erlid`-Einmaligkeit/Plausibilitaet innerhalb der `enrollink`-Klasse (siehe deren Doku).

### Footer (Z.91)
- **Zweck:** Gemeinsamer `$OUTPUT->footer()` fuer beide Zweige. **Seiteneffekte:** Echo. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker, klar gegliederter Controller mit bewusster Pre-Login-Bedingungspruefung und vollstaendiger Delegation der Domaenenlogik an `mod_booking\enrollink`. Die zustandsaendernde Einschreibung erfolgt per GET ohne sesskey — fuer den Token-Einloese-Anwendungsfall vertretbar, die Sicherheit haengt jedoch vollstaendig an Einmaligkeit/Entropie der `erlid` und den Pruefungen in der `enrollink`-Klasse (P2-Hinweis, nicht im Skript fixbar). Stilistische Uneinheitlichkeit `$OUTPUT` vs. `$output`. Keine funktionalen Bugs im Skript selbst. Klassen-Score **B / P2**.
