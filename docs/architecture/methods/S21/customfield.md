# customfield — Methoden-Doku
**Datei:** `customfield.php` · **LOC:** 54 · **Subsystem:** S21 · **Klassen-Score:** A / -
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Admin-Verwaltungsseite fuer die Booking-Customfields. Sie haengt sich an die Core-Customfield-Infrastruktur (`core_customfield`) und rendert deren Standard-Management-UI ueber den booking-spezifischen Handler. Keine eigene Persistenz — saemtliches CRUD laeuft ueber `core_customfield`/`booking_handler`. Kollaborateure: `admin_externalpage_setup`, `mod_booking\customfield\booking_handler`, `core_customfield\output\management`, Core-Renderer `core_customfield`.

## Request-/Permission-Flow
1. `require_login(0, false)` (Z.32) — kein Gast-Autologin.
2. `admin_externalpage_setup('modbookingcustomfield')` (Z.34) — die eigentliche Zugriffskontrolle: nur Admins / berechtigte Verwalter erreichen registrierte External Admin Pages.
3. `$PAGE->set_url` + Title (Z.36-42).
4. Core-Customfield-Renderer holen (Z.44), `booking_handler::create()` (Z.45), `management`-Output-Objekt bauen (Z.46).
5. Ausgabe (Z.48-53): Header, Heading (`bookingcustomfield`), `check_for_forbidden_shortnames_and_return_warning()` (Warnung bei kollidierenden/verbotenen Shortnames), das gerenderte Management-Widget, Footer.

## Auffaelligkeiten
- Keine. Reine Delegation an Core-Customfield-Management; Zugriffsschutz korrekt ueber `admin_externalpage_setup`. Auskommentierter Alt-Code (Z.38-39) ist als `phpcs:ignore` markiert.

## Bewertungs-Resümee
Schlanke, korrekte Admin-Seite, die vollstaendig auf die Core-Customfield-API aufsetzt; einzige Booking-Spezifik ist der `booking_handler` und die Shortname-Warnung. Keine funktionalen Risiken. Klassen-Score **A / -**.
