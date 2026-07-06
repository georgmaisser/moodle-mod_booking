# get_submission_mobile — Methoden-Doku
**Datei:** `classes/external/get_submission_mobile.php` · **LOC:** 227 · **Subsystem:** S11 · **Klassen-Score:** C / P2
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`get_submission_mobile` ist eine `external_api`-Webservice-Klasse, die Zwischenstaende eines Custom-Form (Booking-Condition `customform`) fuer die Mobile-App im MUC-Cache `customformuserdata` zwischenspeichert, mergt bzw. zuruecksetzt. Cachekey-Schema: `"<userid>_<itemid>_customform"`. Keine DB-Persistenz (trotz `global $DB`, der ungenutzt bleibt); Kollaborateure: `cache` (MUC), importiert aber ungenutzt: `dynamic_form`, `mobile`, `external_warnings`, `stdClass`.

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert `itemid` (PARAM_INT), `userid` (PARAM_INT), `sessionkey` (PARAM_RAW), `reset` (PARAM_BOOL) und `data` (Liste aus name/value-Paaren), alle mit Defaults. **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** B — `sessionkey` ist deklariert, wird aber nirgends ausgewertet (siehe `execute`).

### `public static function execute($itemid, $userid, $sessionkey, $reset, $data): array` — public static
- **Zweck:** Speichert/merged (`reset=false`) oder loescht (`reset=true`) die Custom-Form-Daten eines Users/einer Option im Cache. **Seiteneffekte:** `cache::make('mod_booking','customformuserdata')`; `$cache->delete()` bzw. `$cache->set()`; zwei try/catch-Bloecke, die Fehler als `submitted=0`-Array zurueckgeben statt Exceptions zu werfen. **Rueckgabe:** Array `submitted/message/template/json`. **Bewertung:** D — **kein `require_login`, keine Kontextvalidierung, keine Capability-Pruefung** und der `sessionkey`-Parameter wird nie geprueft (Kommentar in `execute_parameters` "for security verification" ist unerfuellt). Der Aufrufer steuert `userid` frei, sodass Cache-Eintraege fremder User geschrieben/zurueckgesetzt werden koennen (IDOR; Datenmanipulation an fremden Form-Zwischenstaenden). Zusaetzlich wird der Cachekey aus den **rohen** Funktionsargumenten `$userid`/`$itemid` gebildet (nicht aus dem validierten `$params`), waehrend `merge_data()` ebenfalls die Rohwerte erhaelt — die `validate_parameters`-Sanitisierung wird damit umgangen. Fehlerdetails (Exception-Message) gehen ungefiltert in `message` zurueck (Info-Leak).

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt die Rueckgabe (`submitted` PARAM_INT, `message`/`json` PARAM_RAW, `template` PARAM_TEXT). **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A.

### `public static function merge_data($cacheddata, $data, $itemid, $userid): array` — public static
- **Zweck:** Mergt neue `name/value`-Felder mit zuvor gecachten Werten zu einem flachen Array (`id`/`userid` plus Feldwerte); neue Werte gewinnen, alte bleiben erhalten, wenn nicht ueberschrieben. **Seiteneffekte:** keine (reine Transformation). **Rueckgabe:** Merged-Array. **Bewertung:** B — defensiv (akzeptiert Array und Objekt als `$cacheddata`, prueft `name`/`value`-Existenz), aber die Doppelbehandlung Array/Objekt ist Duplikat-Code; `id`/`userid` werden korrekt aus dem Merge ausgenommen.

### `public static function build_formdata_string($itemid, $userid, $sesskey, $data): string` — public static
- **Zweck:** Baut einen URL-encodeten Form-Submit-String fuer das `mod_booking_form_condition_customform_form` (Replay des Custom-Form-Submits). **Seiteneffekte:** keine. **Rueckgabe:** Query-String. **Bewertung:** C — die drei `if/else if/else`-Zweige (`shorttext`/`select`/Default) erzeugen bis auf die `str_replace(' ','+')`-Sonderbehandlung im `shorttext`-Fall identische Ausgaben (toter Verzweigungs-Aufwand); Werte werden nur per simplem Space-Replace statt `urlencode()` kodiert (kaputt bei `&`, `=`, Sonderzeichen). Innerhalb der Klasse wird die Methode nicht aufgerufen — Helfer fuer externe Konsumenten.

## Bewertungs-Resümee
Funktional erfuellt die Klasse den Cache-Roundtrip, aber sie hat ein echtes Autorisierungs-Loch: kein Login-/Capability-Gate und ein ignorierter `sessionkey`, kombiniert mit caller-gesteuerter `userid` (Cross-User-Schreib/Reset auf Form-Zwischenstaende). Dazu Umgehung der `validate_parameters`-Sanitisierung (Rohargumente fuer Cachekey/Merge), Info-Leak in Fehlermeldungen und die kaputte/redundante `build_formdata_string`-Kodierung. Klassen-Score **C / P2**.
