# mybookings — Methoden-Doku
**Datei:** `mybookings.php` · **LOC:** 92 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Rendert die persoenliche Buchungsuebersicht eines Users. Die eigentliche Listenausgabe wird an `shortcodes::mycourselist` delegiert; optional wird zusaetzlich die ShoppingCart-Kaufhistorie via `local_shopping_cart\shortcodes::shoppingcarthistory` angehaengt. Kollaborateure: `singleton_service` (User-Lookup), `shortcodes`, Core `$PAGE`/`$OUTPUT`/`$USER`, optional `local_shopping_cart`. Keine eigene Persistenz.

## Request-/Permission-Flow
1. `require_once config.php` + `locallib.php`; `require_login(0, false)` — angemeldeter User noetig, kein Gast-Autologin, keine Kursbindung.
2. Liest `userid`, `completed`, `search`, `filter`, `typefilter` als `optional_param` (alle `PARAM_INT`).
3. Capability-Ermittlung fuer Fremd-Ansicht (`$userid != $USER->id`): zunaechst `mod/booking:bookforothers` (System-Kontext); existiert das Plugin `local_shopping_cart`, wird `$hascapability` mit `local/shopping_cart:cashier` **ueberschrieben**.
4. Nur bei nicht-leerer `userid` UND `$hascapability` wird der Fremd-User per `singleton_service::get_instance_of_user($userid)` geladen, sonst Fallback auf `$USER`.
5. Setzt User-Kontext (`context_user::instance`), `extend_for_user`, URL, Pagelayout `base`; baut Heading (`bookings` fuer Fremd-, `mybookingoptions` fuer Eigen-Ansicht).
6. Gibt Header/Heading aus, ruft `shortcodes::mycourselist('', $arguments, ...)` mit fixem Sort (`coursestarttime` desc) und den Filter-Argumenten; haengt optional die ShoppingCart-History an (gated auf `class_exists` + Config `displayshoppingcarthistory`).

## Bewertung der Logik
- **Bewertung:** B — sauberer Delegations-Stil; die Capability-Logik ist jedoch inkonsistent (siehe Findings): das Vorhandensein von `local_shopping_cart` ersetzt die `bookforothers`-Pruefung komplett durch `cashier`, sodass ein Manager mit `bookforothers` aber ohne `cashier` die Fremd-Ansicht verliert.
- Filter-Parameter werden als `PARAM_INT` deklariert, obwohl `search` semantisch eher ein Freitext-Suchstring waere; downstream-Verwendung in `mycourselist` bestimmt, ob das ein echtes Problem ist (hier nur weitergereicht).

## Findings
- `mybookings.php:48-50` — Bei installiertem `local_shopping_cart` ueberschreibt der `cashier`-Check den zuvor gesetzten `bookforothers`-Wert; ein User mit `bookforothers` aber ohne `cashier` kann fremde Buchungen nicht mehr ansehen (Berechtigungs-Regression, P3).

## Bewertungs-Resümee
Kompakter, gut lesbarer Entry-Point, der die Arbeit an Shortcodes delegiert. Einzige funktionale Schwaeche ist die ueberschreibende Capability-Logik. Klassen-Score **B / P3**.
