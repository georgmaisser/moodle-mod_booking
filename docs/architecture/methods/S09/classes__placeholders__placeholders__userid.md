# userid — Methoden-Doku
**Datei:** `classes/placeholders/placeholders/userid.php` · **LOC:** 87 · **Subsystem:** S09 · **Klassen-Score:** B / P3
> [Subsystem-Doc](../../subsystems/S09_messaging_placeholders.md)

## Klassenueberblick
`userid` ist eine Platzhalter-Klasse (`{userid}`) im Messaging-/Placeholder-Subsystem, abgeleitet von `\mod_booking\placeholders\placeholder_base`. Sie liefert beim Rendern eines Texts (Mail, Beschreibung, Pollurl) die ID des Nutzers, fuer den der Text gerendert wird. Die Klasse ist rein statisch und zustandslos: kein Konstruktor, keine Properties, keine Persistenz. Kollaborateure: keine — der Wert stammt ausschliesslich aus dem uebergebenen `$userid`-Parameter. `require_once lib.php` (Z.29) wird nur fuer die Konstante `MOD_BOOKING_DESCRIPTION_WEBSITE` im Default-Parameter benoetigt.

## Methoden

### `public static function return_value(int $cmid = 0, int $optionid = 0, int $userid = 0, int $installmentnr = 0, int $duedate = 0, float $price = 0, string &$text = '', array &$params = [], int $descriptionparam = MOD_BOOKING_DESCRIPTION_WEBSITE)` — public static
- **Zweck:** Liefert die User-ID als String. **Seiteneffekte:** keine — liest nur `$userid`; `$text`/`$params` werden trotz `&`-Referenz nicht mutiert. **Rueckgabe:** `(string)$userid` falls `$userid > 0`, sonst Leerstring. **Bewertung:** A — minimal, korrekt, keine DB-/Cache-Zugriffe. Die breite, vom `placeholder_base`-Kontrakt geerbte Signatur ist hier groesstenteils ungenutzt (nur `$userid` relevant), aber durch das einheitliche Placeholder-Interface vorgegeben.

### `public static function is_applicable(): bool` — public static
- **Zweck:** Signalisiert, dass dieser Platzhalter immer ausgewertet werden soll. **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

### `public static function for_pollurl(): bool` — public static
- **Zweck:** Markiert den Platzhalter als pollurl-tauglich (darf in Umfrage-Links eingesetzt werden). **Seiteneffekte:** keine. **Rueckgabe:** konstant `true`. **Bewertung:** A.

## Bewertungs-Resümee
Trivialer, korrekter Werte-Platzhalter ohne Zustand, DB- oder Cache-Zugriff. Einziger Schoenheitspunkt ist die vom Basis-Kontrakt erzwungene breite Signatur, von der nur `$userid` genutzt wird. Funktional unkritisch. Klassen-Score **B / P3**.
