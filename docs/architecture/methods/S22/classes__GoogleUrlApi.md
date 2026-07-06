# GoogleUrlApi — Methoden-Doku
**Datei:** `classes/GoogleUrlApi.php` · **LOC:** 96 · **Subsystem:** S22 · **Klassen-Score:** C / P3
> [Subsystem-Doc](../../subsystems/S22_db_layer.md)

## Klassenueberblick
`GoogleUrlApi` ist eine **vollstaendig stillgelegte** Util-Klasse (kein Namespace, globaler Klassenname) im mod_booking-Autoload-Pfad. Sie sollte frueher URLs ueber die mittlerweile von Google abgeschaltete URL-Shortener-API (`urlshortener/v1`) kuerzen/expandieren. Saemtliche Member — Property `$apiurl`, Konstruktor sowie die Methoden `shorten`, `expand`, `send` — sind auskommentiert; die Klasse hat de facto keinen ausfuehrbaren Koerper. Zwei Inline-Kommentare (Z.25–26) markieren sie explizit als nicht mehr verwendet und zur baldigen Entfernung vorgesehen. Persistenz: keine. Kollaborateure: historisch cURL + Google URL Shortener API; aktuell keine.

## Methoden
Die Klasse deklariert **keine aktiven Methoden**. Alle ehemaligen Member sind als auskommentierter Code erhalten (jeweils mit `phpcs:ignore Squiz.PHP.CommentedOutCode.Found`):

- `__construct($key, $apiurl = '.../urlshortener/v1/url')` — auskommentiert; haette `$this->apiurl` mit angehaengtem API-Key gesetzt.
- `shorten($url)` — auskommentiert; haette `send($url)` aufgerufen und die `id` (Kurz-URL) zurueckgegeben.
- `expand($url)` — auskommentiert; haette `send($url, false)` aufgerufen und `longUrl` zurueckgegeben.
- `send($url, $shorten = true)` — auskommentiert; haette per cURL gegen die Google-API gepostet/gelesen und JSON dekodiert.

**Bewertung:** C — toter Code. Die zugrundeliegende Google-URL-Shortener-API ist seit 2019 abgeschaltet; selbst reaktiviert waere die Klasse funktionslos. Kein Sicherheits- oder Laufzeitrisiko, da nichts ausgefuehrt wird, aber unnoetige Wartungslast. Empfehlung deckt sich mit dem Eigen-Kommentar: Datei entfernen.

## Bewertungs-Resümee
Leere Huelle einer obsoleten Integration; die Klasse enthaelt ausschliesslich auskommentierten Code und ist im Repo nur noch historischer Ballast. Funktional irrelevant, sollte geloescht werden. Klassen-Score **C / P3**.
