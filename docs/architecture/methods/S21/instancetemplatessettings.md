# instancetemplatessettings — Methoden-Doku
**Datei:** `instancetemplatessettings.php` · **LOC:** 78 · **Subsystem:** S21 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S21_*.md)

## Klassenueberblick
Prozeduraler Admin-Entry-Point (keine Klasse). Listet die in `booking_instancetemplate` gespeicherten Instanz-Templates ueber das `instancetemplatessettings_table` (local_wunderbyte_table) und erlaubt das Loeschen einzelner Templates per URL-Parameter. Die Seite haengt am Admin-Tree-Knoten `modbookinginstancetemplatessettings`. Kollaborateure: `admin_externalpage_setup`, `$OUTPUT`, `$DB`, `instancetemplatessettings_table`, Capabilities `mod/booking:updatebooking` / `mod/booking:addeditownoption`.

## Request-/Permission-Flow
1. **Bootstrap (Z.26-39):** laedt `config.php` + `adminlib.php`, `require_login(0, false)` (kein Guest-Autologin), dann `admin_externalpage_setup(...)` als Admin-Seiten-Rahmen.
2. **Parameter (Z.41):** `delete` (PARAM_INT) — id des zu loeschenden Templates.
3. **Context + Permission-Gate (Z.43-52):** Systemkontext; Zugriff nur wenn `mod/booking:updatebooking` ODER `mod/booking:addeditownoption` im Systemkontext. Andernfalls Header/Heading „accessdenied" + `nopermissiontoaccesspage` und `die()`.
4. **Loesch-Aktion (Z.56-59):** bei `delete > 0` direkt `$DB->delete_records('booking_instancetemplate', ['id' => $instancetodelete])` und `redirect(...)` mit Erfolgsmeldung.
5. **Tabelle (Z.61-75):** `instancetemplatessettings_table` mit `set_sql("id, name, template", "{booking_instancetemplate}", "1=1")`, baseurl, Page-Title/Navbar, dann `$table->out(25, true)` (paginiert, 25/Seite).
6. **Render (Z.72-77):** Header, Heading, Tabelle, Footer.

- **Seiteneffekte:** DB-Loeschung in `booking_instancetemplate`, HTTP-Redirect, Seiten-Output.
- **Bewertung:** B — **CSRF/Data-Loss (P2):** Die Loesch-Aktion (Z.56-58) wird allein durch den GET-Parameter `delete` ausgeloest, **ohne `require_sesskey()`**. Ein praepariierter Link (`?delete=N`) loescht im Kontext eines berechtigten Admins ein Template ohne Bestaetigung. Funktional ansonsten kompakt und korrekt.

## Bewertungs-Resümee
Schlanke Admin-Listen-/Loesch-Seite mit sauberem Permission-Gate. Einziger relevanter Mangel ist die fehlende sesskey-Pruefung vor der destruktiven `delete_records`-Operation (CSRF). Klassen-Score **B / P2**.
