# edit_certificateconditions — Methoden-Doku
**Datei:** `edit_certificateconditions.php` · **LOC:** 109 · **Subsystem:** S21 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (kein Klassen-/Funktions-Body), der die Uebersichtsseite der Zertifikatsbedingungen rendert. Die Seite arbeitet dual-kontextuell: ohne Parameter im System-Context, mit `cmid` im Modul-Context (`context_module`) bzw. mit explizitem `contextid`. Sie ist PRO-gated: die eigentliche Liste der gespeicherten Bedingungen kommt von `certificate_conditions::get_rendered_list_of_saved_conditions($contextid)`, die DynamicForm-Interaktion uebernimmt das AMD-Modul `mod_booking/dynamiccertificateconditionsform`. Kollaborateure: `require_login`, `require_course_login`, `context`-API, `wb_payment::pro_version_is_activated()`, `admin_externalpage_setup`, Renderer `mod_booking`.

## Ablauf (Request-/Permission-Flow)

### Parameter-Aufnahme (Z.32-35)
- **Zweck:** Liest `cmid` und `contextid` (beide `optional_param`, `PARAM_INT`). **Seiteneffekte:** keine.

### Authentifizierung + Context-Aufloesung (Z.38-56)
- **Zweck:** `require_login(0, false)` (kein Gast-Autologin). Anschliessend Context-Kaskade: ohne `cmid`/`contextid` -> System-Context; mit `cmid` -> `get_course_and_cm_from_cmid($cmid, 'booking')` + `require_course_login` + `context_module::instance($cmid)`; sonst der uebergebene `contextid`. **Seiteneffekte:** DB-Reads ueber `get_course_and_cm_from_cmid`, Session-/Login-Gate. **Bewertung:** C — bei reiner `contextid`-Uebergabe (ohne `cmid`) bleibt `$urlparams` leer, sodass Z.52-53 es hart auf `['contextid' => 1]` setzt; das verwirft den tatsaechlich uebergebenen `contextid` aus der URL (Z.56 nutzt aber weiter die Variable `$contextid`, sodass die Capability-Pruefung gegen den richtigen Context laeuft — nur der `$PAGE`-URL-Parameter ist dann falsch `1`). Zudem ist die Annahme „System-Context-id == 1" (Z.68 `if ($contextid == 1)`) nur in Standard-Installationen wahr.

### Capability-Gate (Z.58)
- **Zweck:** `require_capability('mod/booking:editcertificateconditions', $context)`. **Seiteneffekte:** wirft `required_capability_exception` bei fehlender Berechtigung. **Bewertung:** A — korrekt gegen den aufgeloesten Context geprueft.

### Page-Setup (Z.60-84)
- **Zweck:** Setzt Context/URL, deaktiviert den Activity-Header, ruft bei System-Context fuer Siteadmins `admin_externalpage_setup('modbookingeditcertificateconditions')`, setzt Pagelayout `standard`, Body-Class `limitedwidth`, Pagetype, Title und holt den Renderer. **Seiteneffekte:** mutiert globales `$PAGE`. **Bewertung:** B — beide `if/else`-Zweige (Z.72/74) setzen identisch `set_pagelayout('standard')`, der Branch ist redundant.

### Rendering + PRO-Gate (Z.86-109)
- **Zweck:** Header + Heading + Info-Alert (mit Settings-Link), dann PRO-Check: bei aktiver PRO-Lizenz Ausgabe der gerenderten Bedingungsliste, sonst Warn-Alert „prolicensenecessary". Laedt AMD-Init fuer den DynamicForm-Container und gibt den Footer aus. **Seiteneffekte:** Echo von HTML, `js_call_amd`. **Bewertung:** B — geradliniges Render; der Selektor `.booking-rules-container` (statt eines certificateconditions-spezifischen) wird wiederverwendet.

## Bewertungs-Resümee
Solider, lesbarer Entry-Point mit korrektem Capability-Gate und PRO-Gating. Schwaechen: die `['contextid' => 1]`-Notbelegung (Z.52-53) ueberschreibt einen real uebergebenen `contextid` im PAGE-URL, der redundante Pagelayout-Branch und die harte Annahme System-Context == id 1. Funktional keine Datenverlust-/Sicherheitsluecke (Capability laeuft gegen den korrekten Context). Klassen-Score **B / P2**.
