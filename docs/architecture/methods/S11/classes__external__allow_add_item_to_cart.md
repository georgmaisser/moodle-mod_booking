# allow_add_item_to_cart — Methoden-Doku
**Datei:** `classes/external/allow_add_item_to_cart.php` · **LOC:** 108 · **Subsystem:** S11 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S11_external_api.md)

## Klassenueberblick
`allow_add_item_to_cart` ist eine Webservice-Funktion (`external_api`), die vor dem Warenkorb-Hinzufuegen prueft, ob eine Buchungsoption ueberhaupt fuer den Cart zulaessig ist. Sie ist ein duenner Vermittler zum optionalen Plugin `local_shopping_cart`: Bei preislosen Optionen oder fehlendem shopping_cart kurzschliesst sie mit Erfolg. Keine eigene Persistenz. Kollaborateure: `singleton_service` (Option-Settings), `local_shopping_cart\shopping_cart::allow_add_item_to_cart` (nur falls Plugin installiert).

## Methoden

### `public static function execute_parameters(): external_function_parameters` — public static
- **Zweck:** Deklariert Parameter `itemid` (PARAM_INT) und `userid` (PARAM_INT). **Seiteneffekte:** keine. **Rueckgabe:** `external_function_parameters`. **Bewertung:** A.

### `public static function execute(int $itemid, int $userid): array` — public static
- **Zweck:** Validiert Parameter, laedt Option-Settings; bei `empty($settings->useprice)` sofort Erfolg, sonst Delegation an shopping_cart (sofern Klasse existiert), andernfalls ebenfalls Erfolg. **Seiteneffekte:** `validate_parameters`, `singleton_service::get_instance_of_booking_option_settings`, ggf. `shopping_cart::allow_add_item_to_cart`. **Rueckgabe:** `['success' => int, 'itemname' => string]`. **Bewertung:** B — kein `require_login()`/`validate_context()`; der Hardcode-`1` fuer Erfolg ist bewusst (Kommentar: shopping_cart evtl. nicht installiert, Konstante darf nicht referenziert werden). Die beiden Erfolgs-Returns (preislos / kein Plugin) sind identisch dupliziert.

### `public static function execute_returns(): external_single_structure` — public static
- **Zweck:** Beschreibt das Ergebnis (`success` int, `itemname` text). **Seiteneffekte:** keine. **Rueckgabe:** `external_single_structure`. **Bewertung:** A.

## Bewertungs-Resümee
Schlanker, defensiv gegen fehlendes shopping_cart abgesicherter WS-Vermittler. Funktional korrekt; einzige Schwaechen sind die duplizierte Erfolgs-Return-Struktur und das Fehlen eines expliziten `require_login`/Kontext-Checks (die Berechtigung haengt allein an der WS-Service-Definition). Klassen-Score **B / P3**.
