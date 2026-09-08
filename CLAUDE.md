# Destille, vormals GleitenderMittelwert (RollingAverage) — Hinweise für die Arbeit an diesem Repository

**Umbenennung 09.09.2026:** Das Modul heißt jetzt **„Destille"** — Ergebnis eines
gemeinsamen, spielerischen Namensfindungsprozesses aller vier DG65-Toolkit-Mitglieder
(Dietmars Wunsch: zweideutig/anregend statt nur beschreibend). Umgesetzt nach dem
in diesem Dokument selbst dokumentierten sicheren Muster: NUR `library.json→name`
("DG65 Toolkit Destille") + `module.json→aliases` (+"Destille") geändert — Klassenname
`RollingAverage`, `module.json→name` und die Modul-ID bleiben unangetastet, keine
Bestandsinstanz ist betroffen. "Gleitender Mittelwert" bleibt als Alias auffindbar.
Die anderen drei Mitglieder: Katasteramt (vormals StrukturHub), Pförtner (vormals
Türöffner), Vormund (vormals MigrationsHub) — Namensfindung + Kollisionsprüfung lief
über die gemeinsame Datei `Nextcloud/Claude/DG65-Toolkit-Ideen.md`.

**Verbindliches Konventions-Manifest: `Nextcloud/Claude/TOOLKIT.md`** (Pendant zu SUITE.md
für NRG-Stack, seit 09.09.2026 von allen vier Mitgliedern gegengelesen und verbindlich).
Enthält NUR, was für DG65 Toolkit zusätzlich/anders gilt — für alles Symcon-Allgemeine
(Stolperfallen, Store-Review, Formular-Konvention, Lizenz, Sprachregel) bleibt SUITE.md
die Quelle. Wichtigster eigener Punkt: Anzeigename (`library.json→name`/`aliases`) und
technischer Name (PHP-Klasse/Präfix/Repo) sind bewusst getrennt und der technische Name
ist eingefroren, sobald (a) ein anderes Modul `function_exists('RAVG_...')` prüft ODER
(b) irgendeine reale Instanz läuft (Destilles eigener Fall — kein Cross-Modul-Konsument,
trotzdem eingefroren, siehe TOOLKIT.md-Abschnitt "Anzeigename vs. technischer Name" für
die volle Herleitung, dort als eigener Präzedenzfall neben Vormund/MigrationsHub
festgehalten).


## DG65 Toolkit statt NRG-Stack

**Entscheidungsverlauf (Dietmar, 08.09.2026):** GleitenderMittelwert ist ein
generisches Rechenwerkzeug (Mittelwert/Median/Trend/Integration/Hysterese/
Formel über beliebige Variablen) ohne Energie-/PV-Bezug. Erst wurde deshalb
entschieden, **gar keine** Verbund-Marke zu tragen (kurzzeitig stand
versuchsweise "NRG-Stack Gleitender Mittelwert" in `library.json`, per
Analogieschluss aus MeterHub übernommen — von Dietmar noch am selben Tag
live widersprochen: "so viel hat dieses Modul überhaupt nicht mit
NRG-Stack zu tun"). Danach hat Dietmar aber selbst die grundlegendere Frage
aufgeworfen, ob GleitenderMittelwert mit anderen NICHT-energiebezogenen
Modulen einen eigenen, zweiten Verbund bilden sollte ("DG65 Toolkit").

**Cross-Session-Umfrage (08.09.2026):** Auf Dietmars Wunsch direkt bei
WarnHub, CometWiFi und StrukturHub nachgefragt, wo sie sich zugehörig
fühlen (siehe [[ref-cross-session-messaging]] fürs Vorgehen). Ergebnis:
alle drei bestätigten unabhängig voneinander, NICHT zu NRG-Stack zu
gehören — aber nur **StrukturHub** passt fachlich zu einem engen
"Toolkit"-Verständnis (domänenfreier, wiederverwendbarer
Plattform-Baustein, genau wie GleitenderMittelwert). CometWiFi
(herstellerspezifische Geräte-Integration) und WarnHub (vollständiges,
fachlich tiefes Endnutzer-Produkt, eher vergleichbar mit EMS/InverterHub)
rieten beide explizit davon ab, "Toolkit" zu einer losen Sammelmarke für
alles Nicht-NRG-Stack zu verwässern.

**Dietmars Entscheidung (08.09.2026): "DG65 Toolkit" eng gefasst,
Gründungspaar GleitenderMittelwert + StrukturHub.** CometWiFi/WarnHub
bleiben vorerst eigenständig ohne Verbund-Marke — eine eigene Kategorie für
sie lohnt sich erst mit einem zweiten Modul ähnlichen Zuschnitts. Umgesetzt:
`library.json→name` = "DG65 Toolkit Gleitender Mittelwert" (1.12.1), README
um "Teil des DG65 Toolkit"-Absatz ergänzt. **Lehre für zukünftige
Verbund-Konventions-Abgleiche:** NICHT automatisch annehmen, dass ein im
selben GitHub-Konto liegendes Modul automatisch eine bestehende Marke
tragen soll (siehe [[feedback-nrg-stack-branding-nicht-automatisch]]) —
aber genauso wenig automatisch annehmen, dass "keine Marke" für immer
gilt, wenn eine passendere, enger geschnittene Kategorie erst noch gebaut
werden muss.

**Anders als NRG-Stack (SUITE.md, echte Datenverträge zwischen Modulen)
teilt DG65 Toolkit nach StrukturHubs eigenem Vorschlag vor allem
Entwicklungskonventionen, keinen Laufzeit-Vertrag** — jedes Toolkit-Modul
funktioniert für sich allein, setzt kein anderes voraus. Ein eigenes
Konventions-Manifest (Pendant zu SUITE.md) existiert noch nicht, ist bei
zwei Mitgliedern aber vermutlich bald sinnvoll — betrifft in erster Linie
die generischen, ohnehin modulübergreifend gültigen SUITE.md-Teile
(IP-Symcon-Stolpersteine, Store-Review-Checkliste, Formular-Optik), nicht
die NRG-spezifischen Vertragskapitel.

**Drittes Mitglied bereits geklärt: Türöffner** (Formular-Passwortschutz,
neu entstehendes Modul) — laut StrukturHub in dessen eigenem Kickoff-Dokument
(`Nextcloud/Claude/Tueroeffner-Kickoff.md`, Abschnitt "Markenzugehörigkeit —
entschieden") bereits als DG65-Toolkit-Mitglied festgelegt, nicht NRG-Stack,
obwohl OCPPHub (NRG-Stack) der erste wartende Konsument ist — dasselbe
Muster wie bei StrukturHub selbst (Konsument ≠ fachliche Zugehörigkeit).
StrukturHub hat sein eigenes `library.json→name` ebenfalls auf "DG65
Toolkit StrukturHub" umgestellt (09.09.2026).

**Weitere Geschwister-Recherche (09.09.2026):** Dietmars Auftrag, dass
GleitenderMittelwert und StrukturHub eigenständig in Foren nach weiteren
generischen Modul-Ideen suchen sollen. Vorgehen mit StrukturHub abgestimmt:
KEINE Vorab-Aufteilung nach Themenfeldern (Landschaft noch nicht gut genug
bekannt, falsche Aufteilung kostet mehr als sie spart) — stattdessen jede
Sitzung sucht unabhängig aus der eigenen fachlichen Perspektive (hier:
Richtung Berechnung/Datenverarbeitung/Formeln), Ergebnisse werden bei
Dietmar zusammengeführt und dort auf Überschneidung/Ergänzung geprüft.

**Offen/nicht entschieden:** Der GitHub-Repo-Name (`NRGGleitenderMittelwert`)
trägt weiterhin das `NRG`-Präfix aus der Zeit vor dieser Klärung — eine
Repo-Umbenennung wurde bewusst NICHT eigenmächtig vorgenommen (bricht
Store-Listing-URL und bestehende Klon-Links), das ist eine Entscheidung,
die Dietmar explizit treffen muss.

**Hilfsmodul, kein Verbund-Vertrag.** Berechnet gleitende Mittelwerte
beliebiger Variablen ohne Archiv-Abhängigkeit (Live-Sampling in einen
versteckten JSON-Ringpuffer). Stabil im Store (main) — Änderungen hier
betreffen echte Store-Nutzer, entsprechend konservativ vorgehen. Die
**Formular-Konvention** (Zweck-Intro/News/Doku/Forum/Lizenz-Panels, siehe
SUITE.md "Einheitliche Formular-Optik") gilt unabhängig von der
Toolkit/NRG-Stack-Frage — das ist eine UX-Konsistenz-Frage über alle
eigenen Module hinweg.

## Referenz-Status im Verbund

Trotz fehlender NRG-Stack-Marke ist dieses Modul zweifache Muster-Referenz
für andere NRG-Stack-Module (siehe SUITE.md) — das betrifft nur
Implementierungs-Muster, nicht die Modul-Identität selbst:

1. **`NRG.*`-Profil-Idempotenz**: `IPS_VariableProfileExists()` prüfen, nur
   bei Fehlen anlegen — wer zuerst startet, erzeugt es; kein Eigentümer-Modul.
2. **Klassische Variablenprofile** (Gegenstück zum Presentation-System):
   `NRG.*`-Scope-Klärung nennt dieses Modul ausdrücklich als Referenz.

## Technische Eckpunkte

- Ein Abtastintervall pro Instanz, Kanäle als Liste; je Kanal optional zweite
  Quelle (+/−-Verknüpfung, Invertieren) — erst verrechnen, dann mitteln.
- Methoden: arithmetisch, zeitgewichtet (robust gegen verpasste Ticks),
  Median, Min/Max, Standardabweichung, EMA, Summe, Änderungsrate (Mode 8),
  Integration (Mode 9), Schwellwert mit Hysterese (Mode 10), Formel
  (Mode 11) — der komplette "Rechentool"-Ausbau (Phasen 1-4, alle
  08.09.2026 in einer Session fertiggestellt, siehe project_rollingaverage-
  Gedächtnis für die volle Roadmap-Herleitung). Die ersten neun (0-8)
  arbeiten auf demselben Ringpuffer `[[t,v],...]` im Fenster, optional mit
  Ausreißer-Filter (Phase 4, siehe unten); Integration/Hysterese/Formel
  (9-11) brechen bewusst aus diesem Modell aus (siehe jeweils unten).
- Bewusst KEIN `AC_GetLoggedValues` — Unabhängigkeit vom Archiv ist ein
  Kernfeature, nicht als „Vereinfachung" wegrefactoren.

### Integration (Mode 9) — kein Ringpuffer, eigener Zustand im selben Puffer-Feld

Anders als die anderen neun Methoden braucht Integration KEIN Zeitfenster,
sondern läuft unbegrenzt bis zum nächsten Reset (Trapezregel: jeder Tick
addiert `(letzterWert+aktuellerWert)/2 * Δt` zu einem Akkumulator). Statt
dafür eine dritte Variable pro Kanal anzulegen, wird das ohnehin vorhandene
versteckte Puffer-Feld (`BufID`) zweckentfremdet: bei Mode 9 enthält es kein
JSON-Array `[[t,v],...]` mehr, sondern ein JSON-**Objekt**
`{_integrator:true, Accum, LastSampleTs, LastSampleValue, PeriodKey}`.

**Wichtiger Stolperstein, den `Tick()` aktiv abfängt:** Ein Kanal kann
jederzeit zwischen Mode 9 und einem der anderen neun wechseln, OHNE dass
sich `ChannelKey()` (Bezeichnung+Quelle) ändert — dieselbe `BufID`-Variable
bleibt also über einen Moduswechsel hinweg bestehen. `json_decode(...,
true)` liefert für ein JSON-Objekt genauso ein PHP-Array wie für eine
JSON-Liste — ein reines `is_array()`-Check hätte das Integrator-Objekt beim
Umschalten auf eine andere Methode fälschlich als gültigen Ringpuffer
akzeptiert (Zugriff auf `$e[0]` auf skalaren Werten wie `Accum`). Fix: die
Ringpuffer-Seite verwirft den Puffer zusätzlich bei
`isset($buffer['_integrator'])`, die Integrations-Seite verwirft ihn bei
FEHLENDEM `_integrator`-Schlüssel — beide Richtungen starten nach einem
Moduswechsel sauber neu, kein Crash, kein Datenmüll. Verifiziert in einem
lokalen PHP-Testtreiber (Reflection auf die privaten Methoden, kein
Symcon-Live-Zugriff nötig) — Skript nicht committet, siehe Scratchpad-
Historie dieser Session bei Bedarf zum Nachbauen.

**Reset-Erkennung über einen Perioden-Schlüssel, nicht über feste
Zeitstempel:** `PeriodKeyFor()` liefert `date('Y-m-d'|'Y-m'|'Y', $now)` je
nach Reset-Rhythmus. Weicht der gespeicherte vom aktuell berechneten
Schlüssel ab, wird VOR der Verarbeitung des aktuellen Ticks zurückgesetzt.
Das ist selbstheilend auch nach einem längeren Symcon-Ausfall über eine
Periodengrenze hinweg (der nächste Tick erkennt den Wechsel unabhängig
davon, wie lange die Instanz zuvor nicht lief) — anders als ein Ansatz über
`RegisterTimer`-gesteuerte feste Reset-Zeitpunkte, der einen verpassten
Reset stillschweigend verschleppt hätte.

**`RAVG_ResetChannel($id, $Bezeichnung)`** — manueller Reset per
Skript/Ereignis, adressiert bewusst über die Bezeichnung (Caption), nicht
über die interne Puffer-/Variablen-ID (Implementierungsdetail). Folgt der
"keine PHP-Standardwerte auf öffentlichen Vertragsmethoden"-Regel
([[ips-module-pitfalls]] Punkt 23) — ein Pflichtparameter, kein Default.

### Hysterese (Mode 10) — bewusst Float 0.0/1.0, keine echte Boolean-Variable

Symcon-Variablen können ihren Typ nach der Erstanlage nicht wechseln. Da
derselbe Kanal (gleicher `ChannelKey` = Bezeichnung+Quelle) jederzeit
zwischen Hysterese und jeder anderen Methode umgeschaltet werden kann, OHNE
dass die zugrundeliegende `AvgID`-Variable neu angelegt wird, hätte ein
`RegisterVariableBoolean()` für Mode 10 einen Typkonflikt riskiert, sobald
ein Nutzer zurück auf z.B. Arithmetisch wechselt (derselbe Ident, anderer
erwarteter Typ). Lösung: Hysterese bleibt wie alle anderen Methoden
Float-typisiert, Ausgabe einfach `0.0`/`1.0` statt `false`/`true` — dadurch
ist `RegisterVariableFloat()` weiterhin für JEDEN Modus einheitlich richtig,
kein bedingter Register-Aufruf nötig. Der bisherige Aktiv-Zustand wird direkt
aus `GetValue($vid)` gelesen (kein eigenes Zustands-Attribut) — der
Ausgabewert IST der Zustand.

**Richtung nicht als eigenes Formularfeld, sondern aus dem Vorzeichen der
Schwellendifferenz abgeleitet** (`HysteresisOn >= HysteresisOff` = steigend,
sonst fallend) — spart ein drittes Feld und macht Fehlkonfigurationen
(Richtung passt nicht zu den Schwellen) strukturell unmöglich, weil es keine
zusätzliche, potenziell widersprüchliche Einstellung gibt.

### Formel (Mode 11) — eigener Parser statt eval(), Skalierungsgrenzen bewusst gesetzt

Kein `eval()`/`create_function()`, obwohl die Formel nur vom Instanzbesitzer
selbst im eigenen Formular editierbar ist — sichere Grundhaltung statt "ist
ja nur lokal vertrauenswürdig" (siehe Systemvorgabe zu OWASP/Injection).
Eigener Tokenizer + rekursiver Parser mit Operator-Präzedenz
(Vergleich→Addition→Multiplikation→unär→Potenz→Primär) + Baum-Evaluator,
alle als private Methoden auf `RollingAverage` (kein separates globales
Klassen-Präfix nötig, da keine eigene globale Klasse entsteht). Nur zwei
Variablen (`x1`/`x2`, aus Quelle/Quelle 2) statt beliebig vieler Eingänge —
bewusste MVP-Grenze: mehr Eingänge bräuchten eine strukturell andere Liste
(mehr feste Spalten oder ein Nested-Editor), das war schon vor dieser Phase
als Sorge notiert (siehe „Wachsende Listenbreite" unten). Formel bekommt
KEINEN separaten Formel-Kanal-Chain-Mechanismus über x3/x4 — wer mehr als
zwei Eingänge braucht, verkettet mehrere Formel-Kanäle (Ausgabe von Kanal A
als Quelle von Kanal B).

Fehlerverhalten bewusst minimal gehalten: ungültige Formel → Wert bleibt
stehen, kein Crash, aber auch keine dedizierte Fehler-UI je Kanal (die Liste
hat dafür keine Spalte, ein Symptom-basiertes "Wert aktualisiert sich nicht"
ist der einzige Hinweis). Eine Vollvalidierung beim Speichern
(`GetConfigurationForm`-Vorschau o.ä.) wurde bewusst NICHT gebaut — würde
den Umfang für eine MVP-Phase sprengen, kann bei echtem Bedarf nachgezogen
werden.

### Ausreißer-Filter (Phase 4) — Vorverarbeitung, kein eigener Modus

Anders als Phase 1-3 (je ein neuer `Mode`-Wert) ist der Ausreißer-Filter
KEIN eigener Modus, sondern eine optionale Vorverarbeitungsstufe für die
neun Fenster-Methoden (0-8): `OutlierFilter`(Bool)/`OutlierFactor`(Float,
Vielfaches der Standardabweichung) greifen direkt im bestehenden
Ringpuffer-Zweig von `Tick()`, VOR dem Anhängen des neuen Samples — ein
Ausreißer wird einfach nicht angehängt, das Fenster wird aber trotzdem wie
gewohnt getrimmt (alte Werte altern normal weiter aus). Braucht mindestens
3 vorhandene Werte im Fenster (Stddev aus 0-2 Werten ist bedeutungslos).
Bei Integration/Hysterese/Formel (9-11) wirkungslos, da diese den
Ringpuffer gar nicht nutzen — keine Sonderbehandlung nötig, der Code-Pfad
wird für diese drei Modi schlicht nie erreicht.

### Wachsende Listenbreite — offener Punkt, noch nicht gelöst

Mit Phase 1-4 sind sechs neue, methodenspezifische Spalten dazugekommen
(Trend-Basis, Integrations-Einstellung, Schwelle Ein/Aus, Formel,
Ausreißer filtern/Schwelle (σ)) — die Kanal-Liste ist dadurch deutlich über
die früher als "gerade noch bildschirmfüllend" befundene ~1160px-Breite
hinausgewachsen (siehe [[ips-module-pitfalls]] Punkt 16), obwohl jede
Spalte je nach gewählter Methode meist irrelevant/leer bleibt. Symcon-
Listen erlauben keine pro-Zeile bedingte Spalten-Sichtbarkeit (bekannte
SDK-Grenze). Noch nicht entschieden, nur benannt: bei weiterem Ausbau lohnt
sich ein grundsätzlicherer UI-Umbau (z.B. ein zweites, methodenspezifisches
Detail-Panel statt immer mehr Listenspalten) — gehört zur "ganz am
Ende"-Besprechung mit Dietmar (siehe Umbenennungs-Frage oben), zusammen mit
der von Dietmar am 08.09.2026 angekündigten Grundsatzfrage, ob dieses
Modul mit anderen Modulen in einen Modulverbund gehen soll.

**Cross-Vendor-Validierung vor dem Bau:** Ableitung/Integration/periodischer
Reset wurden nicht aus dem Nichts entworfen, sondern nach Recherche in
Home Assistant (native "Derivative"/"Integration (Riemann sum)"-Sensoren,
"Utility Meter" mit 15,6% Nutzungsanteil laut deren eigener Statistik),
openHAB (explizite Nutzeranfrage nach Trend-Berechnung) und der KNX-Welt
(MDT/Zennio-Logikmodule mit dedizierten Hysterese-Bausteinen, für eine
spätere Phase) übernommen — Details siehe project_rollingaverage-Gedächtnis.

## Branch-Modell

Store-Modul mit `main` als Auslieferungsstand; `ems-integration` existiert
für Verbund-Anpassungen. Nutzersichtbares deutsch.

## Verbund-Manifest SUITE.md — Bezugsquelle (geändert 31.08.2026)

SUITE.md liegt seit 31.08.2026 NICHT mehr in einem GitHub-Repo (die
Modul-Repos sind öffentlich, SUITE.md enthält das komplette Architektur-/
Debugging-Know-how des Verbunds — Dietmars Entscheidung). Primärquelle ist
ausschließlich die lokale Datei `/Users/dietmar/Nextcloud/Claude/SUITE.md`
auf Dietmars Maschine, versioniert in einem eigenen lokalen Git-Repo ohne
Remote. Frühere Kopien dieses Dokuments wurden zusätzlich aus der Historie
aller Modul-Repos entfernt (`git filter-repo` + Force-Push). Kein
Fallback-Link mehr — ohne lokalen Zugriff auf Dietmars Maschine ist SUITE.md
nicht einsehbar.
