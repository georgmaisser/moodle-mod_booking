# wb_payment — Methoden-Doku
**Datei:** `classes/utils/wb_payment.php` · **LOC:** 145 · **Subsystem:** S22 · **Klassen-Score:** B / P2
> [Subsystem-Doc](../../subsystems/S22_*.md)

## Klassenueberblick
`wb_payment` ist die PRO-Lizenzverifikation von mod_booking. Sie haelt einen eingebetteten RSA-Public-Key (`MOD_BOOKING_PUBLIC_KEY`) und entschluesselt damit den in den Plugin-Settings hinterlegten Lizenzschluessel zweistufig (RSA-Public-Decrypt + AES-128-CBC mit `$CFG->wwwroot` als Schluessel), um an Ablaufdatum und optionalen Produkt-Token zu kommen. `pro_version_is_activated()` ist der zentrale Gate-Punkt: viele PRO-Features im gesamten Plugin fragen diese statische Methode ab. Keine eigene Persistenz; liest ausschliesslich `get_config('booking')->licensekey`. Reine Static-Utility-Klasse ohne Instanzzustand. Kollaborateure: `get_config`, OpenSSL-Funktionen, `$CFG`, der externe `wb_license`-Generator (Format-Gegenstueck).

## Methoden

### `public static function decryptlicensekey(string $encryptedlicensekey): string` — public static
- **Zweck:** Entschluesselt den verschluesselten Lizenzschluessel zur Ablaufdatum-/Nutzlast-Gewinnung. Ablauf: base64-Decode → `openssl_public_decrypt` mit eingebettetem Public-Key → erneutes base64-Decode → `openssl_decrypt` (AES-128-CBC) mit `$CFG->wwwroot` als Key, IV aus den ersten 16 Bytes, SHA2-Header (32 Byte) uebersprungen.
- **Seiteneffekte:** `global $CFG` (liest `wwwroot`); keine DB/IO. Bei leerem RSA-Resultat oder IV-Laenge != 16 fruehe Rueckgabe `false`.
- **Rueckgabe:** entschluesselter Klartext (z.B. `"2025-12-31"` oder `"2025-12-31;bookingagent"`), bzw. `false` bei zu kurzem/ungueltigem Schluessel.
- **Bewertung:** C — der deklarierte Rueckgabetyp ist `: string`, die beiden Guard-Pfade geben aber `return false;` zurueck. Unter `declare(strict_types=1)` waere das ein `TypeError`; hier (keine strict_types-Deklaration) wird `false` still zu `""` gecastet — funktioniert, ist aber irrefuehrend und ein latentes Typrisiko. Zudem wird der HMAC/SHA2-Header (32 Byte) nur weggeschnitten, aber NICHT gegen Manipulation verifiziert (kein `hash_hmac`-Vergleich) — Integritaet haengt allein an der RSA-Stufe.

### `public static function parse_license_content($decryptedcontent): array` — public static
- **Zweck:** Zerlegt den entschluesselten Klartext in Ablaufdatum und Produkt-Token. Unterstuetzt Legacy-Format (`"Y-m-d"`, kein Token) und produktgebundenes Format (`"Y-m-d;product"`).
- **Seiteneffekte:** keine (reine String-Operation, `explode(';', ..., 2)`).
- **Rueckgabe:** `['expirationdate' => string, 'product' => string]`; `product` leer fuer Legacy-Keys.
- **Bewertung:** A — defensiver `(string)`-Cast und `?? ''`/`isset`-Guards fangen `false`/`null`-Input (z.B. aus `decryptlicensekey()`-Fehlerpfad) sauber ab.

### `public static function pro_version_is_activated()` — public static
- **Zweck:** Zentraler Gate: ist eine gueltige, nicht abgelaufene PRO-Lizenz hinterlegt? Liest `get_config('booking')->licensekey`, entschluesselt + parst ihn, akzeptiert nur Legacy-Keys (`product === ''`) oder kombinierte Booking+Agent-Keys (`PRODUCT_BOOKING_AGENT`) — Agent-only-Keys schalten Booking-PRO NICHT frei — und prueft `time() < strtotime(expirationdate)`.
- **Seiteneffekte:** `get_config('booking')` (DB-Read, ungecached pro Aufruf); keine Schreibvorgaenge. Liest `BEHAT_SITE_RUNNING`/`PHPUNIT_TEST`-Konstanten.
- **Rueckgabe:** `bool` — `true` bei gueltiger Lizenz ODER wenn Behat-/PHPUnit-Tests laufen (Test-Override am Ende), sonst `false`.
- **Bewertung:** B — Logik korrekt und gut kommentiert; Produkt-Whitelist sauber. Schwaechen: (1) kein fehlender Rueckgabetyp-Hint (`bool`), (2) `get_config('booking')` wird bei jedem Aufruf erneut geladen — als haeufig in Render-Pfaden aufgerufenes Gate ein potenzieller (wenn auch von Moodle teilgecachter) Redundanz-Punkt; eine request-lokale Memoisierung waere sinnvoll. `strtotime` auf leerem/falschem Datum liefert `false`, was durch `!== false` korrekt abgefangen wird.

### Triviale Konstanten
`PRODUCT_BOOKING_AGENT = 'bookingagent'` (Z.42) und `MOD_BOOKING_PUBLIC_KEY` (Z.49–57, eingebetteter RSA-Public-Key) als Klassenkonstanten.

## Bewertungs-Resümee
Kompakte, klar strukturierte Lizenz-Utility mit sinnvoller Trennung in Entschluesselung / Parsing / Gate. Funktional korrekt; die Produkt-Token-Whitelist und die Test-Overrides sind bewusst gesetzt. Hauptkritik: `decryptlicensekey` deklariert `: string`, gibt aber in Fehlerpfaden `false` zurueck (latentes Typrisiko, nur durch fehlende strict_types abgefedert), und die zweite Krypto-Stufe verifiziert den mitgefuehrten SHA2/HMAC-Header nicht. Beides nicht akut sicherheitskritisch (RSA-Stufe traegt die Authentizitaet), aber dokumentationswuerdig. Klassen-Score **B / P2**.
