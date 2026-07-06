# bookingredirect — Methoden-Doku
**Datei:** `bookingredirect.php` · **LOC:** 48 · **Subsystem:** S21 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S21_entry_scripts.md)

## Klassenueberblick
Prozeduraler Entry-Point (keine Klasse). Sehr kleiner Redirect-Shim: nimmt eine base64-codierte URL als Param und leitet auf die dekodierte URL weiter. Existiert als Workaround, weil der Moodle-Kalender-Exporter `&` zu `&amp;` escaped und dadurch Links in Outlook-Events brach. Kollaborateure: Core `require_login`, `base64_decode`, `filter_var`, roher `header('Location: ...')`.

## Request-/Permissions-Flow
1. `require_once config.php`.
2. `require_login(0, false)` — kein Guest-Autologin; erzwingt eingeloggten Nutzer (Site-Context).
3. `$encodedurl = required_param('encodedurl', PARAM_TEXT)`, dann `$link = base64_decode($encodedurl)`.
4. `filter_var($link, FILTER_VALIDATE_URL)`: bei valider URL `header("Location: $link"); exit();`.
5. Sonst Klartext-Ausgabe „The URL does not seem to be valid. Please contact a developer." (kein `$OUTPUT`-Header/Footer).

## Bewertung der Einzelschritte
- **Redirect (Z.42–46):** `filter_var(..., FILTER_VALIDATE_URL)` akzeptiert **jede** syntaktisch gueltige (auch externe) URL → **Open Redirect**. Da nur `require_login` davor steht, kann der Shim als authentifizierter Open-Redirect missbraucht werden (Phishing-Weiterleitung). Kein `clean_param(..., PARAM_URL)` / Host-Whitelist / `new moodle_url`-Validierung. Zudem wird `header("Location: $link")` roh gesetzt statt `redirect()`; bei `\r\n` im dekodierten String waere theoretisch Header-Injection denkbar, praktisch durch FILTER_VALIDATE_URL meist verhindert. **Bewertung:** C / P3 (Open Redirect; geringes Schadenpotenzial, da nur Redirect).
- **Fehlerausgabe (Z.48):** roher `echo` ohne Page-Layout — kosmetisch, aber funktional unkritisch. **Bewertung:** B.

## Bewertungs-Resümee
Bewusst minimaler Workaround-Shim. Hauptkritik ist der ungefilterte Redirect auf beliebige URLs (Open Redirect) statt einer PARAM_URL-Validierung oder Host-Whitelist; ansonsten harmlos. Klassen-Score **B / P3**.
