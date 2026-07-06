# scheduledmails — Methoden-Doku
**Datei:** `scheduledmails.php` · **LOC:** 107 · **Subsystem:** S21 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Debug-only Uebersichtsseite fuer geplante Adhoc-Mails (PRO-gated). Trotz Header-Kommentar „Rule edit form" zeigt die Seite eine Liste geplanter Mails: sie ermittelt einen Kontext (cmid → Modul-Kontext, sonst System-Kontext), prueft `mod/booking:viewscheduledmails`, baut die Admin-/PAGE-Umgebung und rendert per `output\scheduledmails`-DTO ueber `renderer::render_scheduledmails_list`. Kollaborateure: `wb_payment` (PRO-Gate), `output\scheduledmails`, `table\scheduledmails_table`, `standardfilter`.

## Methoden
Keine Methoden — Top-Level-Request-Flow:

### Debug-Gate (Z.34–36) — top-level
- **Zweck:** Wenn der Developer-Debug nicht an ist, eine Warn-Div ausgeben. **Seiteneffekte:** `echo` einer `alert`-Div — aber **kein Abbruch**; die Seite rendert in jedem Fall weiter. Sieht hier eher als Hinweis gemeint, nicht als Schutz. **Bewertung:** C — die Seite ist faktisch nicht debug-gegated, nur visuell markiert.

### Kontext-/Login-Aufloesung (Z.38–67) — top-level
- **Zweck:** Bestimmt `$contextid` aus `cmid`/`contextid`. `require_login(0,false)` (kein Gast-Autologin). Bei `cmid` → `require_course_login` + Modul-Kontext; sonst Fallback auf System-Kontext. **Seiteneffekte:** Login-Erzwingung, `context::instance_by_id`, `require_capability('mod/booking:viewscheduledmails', $context)`. **Bewertung:** C — **logischer Defekt Z.48–49:** der Zweig `if (empty($cmid) && !empty($contextid))` ueberschreibt den uebergebenen `$contextid` sofort mit `context_system::instance()->id`, statt ihn zu verwenden. Ein explizit per `contextid` adressierter (Nicht-System-)Kontext wird dadurch ignoriert und auf System gezwungen. Der nachfolgende Z.58-Block (`empty($urlparams)`) faengt den parameterlosen Fall ohnehin ab, sodass der Z.48-Zweig redundant/widerspruechlich ist. (Prio P3, nur Debug-Seite.)

### Seiten-Setup + Render (Z.69–107) — top-level
- **Zweck:** Setzt Kontext/URL (zeigt interessanterweise auf `edit_rules.php`), deaktiviert den activityheader, ruft fuer den System-Kontext (`contextid==1`) bei Site-Admins `admin_externalpage_setup('modbookingeditrules')`. PRO-Gate: nur mit aktiver PRO-Version wird `output\scheduledmails` gerendert; sonst (mit cmid) eine PRO-noetig-Warnung. **Seiteneffekte:** Header/Heading/Footer-Ausgabe, Renderer-Aufruf. **Bewertung:** B — Kontext/Capability korrekt; URL-/Pagetype-Wahl (`edit_rules.php`) ist verwirrend gegenueber dem Dateinamen.

## Bewertungs-Resümee
Funktional unkritische Debug-/PRO-Seite mit korrektem Capability-Gate. Schwaechen: das „Debug-Gate" bricht nicht ab (nur Warnung), und der `contextid`-Aufloesungszweig Z.48–49 ueberschreibt den uebergebenen Wert auf System (widerspruechlich/redundant). Geringe Tragweite. Klassen-Score **C / P3**.
