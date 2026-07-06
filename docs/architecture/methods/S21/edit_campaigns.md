# edit_campaigns — Methoden-Doku
**Datei:** `edit_campaigns.php` · **LOC:** 80 · **Subsystem:** S21 · **Klassen-Score:** A / P3
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Admin-Seiten-Entry-Point (keine Klasse). Rendert die Verwaltungsseite fuer Booking-Kampagnen: Header/Heading + dismissible Info-Alert, danach (PRO-gated) die gerenderte Liste gespeicherter Kampagnen, und initialisiert das AMD-Modul `dynamiccampaignsform` fuer das DynamicForm-basierte Anlegen/Bearbeiten. Kollaborateure: `campaigns_info`, `wb_payment`, `adminlib.php`, `locallib.php`, mod_booking-Renderer, globale `$PAGE`/`$SITE`/`$DB`.

## Methoden
Kein Klassen-/Funktions-Body — reiner Request-Flow auf Top-Level.

### Request-/Permission-Flow
- **Z.31–33:** Bootstrap `config.php`, `locallib.php`, `adminlib.php`.
- **Z.38:** `require_login(0, false)` — kein Guest-Autologin.
- **Z.40:** `admin_externalpage_setup('modbookingeditrules')` — Autorisierung ueber die externe Admin-Seite (Site-Admin); teilt sich den Seiten-Identifier mit der Rules-Seite.
- **Z.42–54:** URL, `activityheader->disable()`, Pagelayout `admin`, `limitedwidth`-Body-Klasse, Pagetype, Titel.
- **Z.56–59:** Renderer holen; Header + Heading (`bookingcampaignswithbadge`).
- **Z.60–65:** Statisch zusammengebautes Bootstrap-Alert mit `bookingcampaignssubtitle` (dismissible).
- **Z.67–72:** PRO-Gate: bei aktiver PRO-Lizenz (`wb_payment::pro_version_is_activated()`) → `campaigns_info::return_rendered_list_of_saved_campaigns()`, sonst Warn-Alert `infotext:prolicensenecessary`.
- **Z.74–78:** `js_call_amd('mod_booking/dynamiccampaignsform', 'init', ['.booking-campaigns-container'])`.
- **Z.80:** Footer.
- **Seiteneffekte:** HTML-Output; JS-Modul-Init; keine direkten DB-Schreibvorgaenge (CRUD laeuft ueber das DynamicForm/AMD).
- **Bewertung:** A — sauberer Admin-Entry-Point. Importe `campaigns`, `booking_rules`, `global $DB` werden im Script nicht direkt verwendet (toter Import/Use) — kosmetisch. Das Alert-Markup ist inline statt aus einem Template (kleiner Stilbruch).

## Bewertungs-Resümee
Idiomatische, PRO-gegatete Admin-Seite mit AMD-getriebenem Kampagnen-Form. Autorisierung korrekt ueber `admin_externalpage_setup`. Nur kosmetische Punkte (ungenutzte Use/Global, Inline-Alert). Klassen-Score **A / P3**.
