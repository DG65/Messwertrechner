<?php

class RollingAverage extends IPSModule
{
    private const UNIT_SECONDS = [0 => 1, 1 => 60, 2 => 3600, 3 => 86400];

    // Formular-Konvention (SUITE.md "Einheitliche Formular-Optik"),
    // nachgezogen 08.09.2026 im Zuge von Dietmars Verbund-Manifest-Abgleich
    // — Muster 1:1 aus MeterHub übernommen (dortige Referenzimplementierung).
    private const NEWS_VERSION = '1.12.4';
    private const FORUM_THREAD_URL = 'https://community.symcon.de/t/modul-gleitender-mittelwert-mittelwertberechnungen/144104/3';
    private const LICENSE_URL = 'https://github.com/DG65/Messwertrechner/blob/main/LICENSE';
    private const PAYPAL_URL = 'https://paypal.me/DietmarGureth';

    // Presets für Methode 9 (Integration): je Wert [ResetIntervall,
    // Divisor]. ResetIntervall 0=nie automatisch/1=täglich/2=monatlich.
    // Divisor rechnet die aufsummierten "Wert×Sekunden" (z.B. Watt-
    // sekunden) in die gewünschte Ausgabegröße um.
    private const INTEGRATION_PRESETS = [
        0 => ['reset' => 1, 'divisor' => 3600],       // Täglich -> Wh
        1 => ['reset' => 1, 'divisor' => 3600000],    // Täglich -> kWh
        2 => ['reset' => 2, 'divisor' => 3600000],    // Monatlich -> kWh
        3 => ['reset' => 0, 'divisor' => 3600000],    // Nie automatisch -> kWh
        4 => ['reset' => 0, 'divisor' => 1],          // Nie automatisch -> Rohsumme (Wert×Sekunden)
    ];

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('TickInterval', 10);
        $this->RegisterPropertyString('Channels', '[]');

        // Zuordnung Kanal -> tatsächliche Variablen-IDs. Bewusst ein
        // Attribut (nicht Teil der Channels-Property!) — würde man das
        // stattdessen in die listengebundene Channels-Property zurück-
        // schreiben, sperrt das in IP-Symcon das Drag & Drop der Liste.
        $this->RegisterAttributeString('ChannelState', '[]');

        // Formular-Konvention: dismissible Panels (Zweck-Intro, News,
        // Forum-Hinweis), siehe GetConfigurationForm().
        $this->RegisterAttributeBoolean('PurposeIntroGone', false);
        $this->RegisterAttributeString('SeenNews', '');
        $this->RegisterAttributeBoolean('ForumHintGone', false);

        $this->RegisterTimer('Tick', 0, 'RAVG_Tick($_IPS[\'TARGET\']);');
    }

    public function Destroy()
    {
        parent::Destroy();
    }

    /** Ganz vorn im Formular, noch vor dem News-Panel — einmalig dismissible. */
    private function PurposeIntro(): ?array
    {
        if ($this->ReadAttributeBoolean('PurposeIntroGone')) {
            return null;
        }
        return [
            'type' => 'ExpansionPanel', 'name' => 'PurposeIntroPanel', 'expanded' => true,
            'caption' => '👋  Wozu dieses Modul?',
            'items' => [
                ['type' => 'Label', 'caption' => 'Messwertrechner berechnet aus beliebigen IP-Symcon-Variablen laufend einen Mittelwert (oder Median, Minimum, Maximum, Summe, Standardabweichung, EMA …) über ein frei wählbares Zeitfenster — archivfrei, per Live-Sampling in einem versteckten Ringpuffer.'],
                ['type' => 'Label', 'caption' => 'Der Nutzen: geglättete, weniger schwankende Werte für Anzeigen, Diagramme oder Steuerungsentscheidungen (z. B. PV-Überschuss über 5 Minuten statt jeder einzelnen Messspitze) — ganz gleich, aus welchem Modul die Quellwerte stammen.'],
                ['type' => 'Button', 'caption' => 'Verstanden – nicht mehr anzeigen', 'onClick' => 'RAVG_AckPurposeIntro($id);'],
            ],
        ];
    }

    public function AckPurposeIntro()
    {
        $this->WriteAttributeBoolean('PurposeIntroGone', true);
        $this->UpdateFormField('PurposeIntroPanel', 'visible', false);
    }

    /** Aufgeklappt, pro Version einmalig bestätigbar. */
    private function NewsBanner(): ?array
    {
        if ($this->ReadAttributeString('SeenNews') === self::NEWS_VERSION) {
            return null;
        }
        return [
            'type' => 'ExpansionPanel', 'name' => 'NewsPanel', 'expanded' => true,
            'caption' => '🆕  Neu in Version ' . self::NEWS_VERSION,
            'items' => [
                ['type' => 'Label', 'caption' => '• 🆕 Dieses Modul heißt jetzt „Messwertrechner" (vorher „Gleitender Mittelwert"/„Destille") — reine Umbenennung im Zuge der DG65-Toolkit-Namensvereinheitlichung, sonst bleibt alles gleich: Instanzen, Variablen, Konfiguration und Funktionsweise sind unverändert.'],
                ['type' => 'Label', 'caption' => '• „Ausreißer filtern" (für die Fenster-Methoden 0-8): verwirft einzelne Sensor-Ausrutscher, die zu weit vom bisherigen Fenster-Durchschnitt abweichen (einstellbare Schwelle in Vielfachen der Standardabweichung), bevor sie den Mittelwert verzerren.'],
                ['type' => 'Button', 'caption' => 'Verstanden – nicht mehr anzeigen', 'onClick' => 'RAVG_AckNews($id);'],
            ],
        ];
    }

    public function AckNews()
    {
        $this->WriteAttributeString('SeenNews', self::NEWS_VERSION);
        $this->UpdateFormField('NewsPanel', 'visible', false);
    }

    /** Symcon-Forum-Hinweis — einmalig dismissible, kein Versionsbezug. */
    private function ForumHint(): ?array
    {
        if ($this->ReadAttributeBoolean('ForumHintGone')) {
            return null;
        }
        return [
            'type' => 'ExpansionPanel', 'name' => 'ForumHintPanel', 'expanded' => true,
            'caption' => '💬  Feedback im Symcon-Forum',
            'items' => [
                ['type' => 'Label', 'caption' => 'Fragen, Wünsche oder Fehler zu diesem Modul? Rückmeldungen sind im Community-Thread willkommen.'],
                ['type' => 'Button', 'caption' => 'Zum Forums-Thread', 'onClick' => "echo '" . self::FORUM_THREAD_URL . "';", 'link' => true],
                ['type' => 'Button', 'caption' => 'Verstanden – nicht mehr anzeigen', 'onClick' => 'RAVG_AckForumHint($id);'],
            ],
        ];
    }

    public function AckForumHint()
    {
        $this->WriteAttributeBoolean('ForumHintGone', true);
        $this->UpdateFormField('ForumHintPanel', 'visible', false);
    }

    /**
     * Lizenz-/Unterstützungs-Hinweis — Wortlaut verbundweit identisch
     * (SUITE.md "Einheitliche Formular-Optik", Variante A). Bewusst NICHT
     * dismissible, ganz unten nach dem Forum-Hinweis.
     */
    private function LicenseHint(): array
    {
        return [
            'type' => 'ExpansionPanel', 'expanded' => false,
            'caption' => '🧡  Über dieses Modul',
            'items' => [
                ['type' => 'Label', 'caption' => 'Entstanden aus echter Begeisterung für die eigene Anlage — und ein paar durchgetippten Abenden. Trotzdem: Software-Hobby hin oder her, das hier ist geistiges Eigentum und echte Arbeit steckt drin.'],
                ['type' => 'Label', 'caption' => 'Lizenz: PolyForm Noncommercial 1.0.0 — privat und nicht-kommerziell frei nutzbar, für den gewerblichen Einsatz braucht es eine gesonderte Lizenz vom Rechteinhaber.'],
                ['type' => 'Button', 'caption' => 'Lizenztext ansehen', 'onClick' => "echo '" . self::LICENSE_URL . "';", 'link' => true],
                ['type' => 'Label', 'caption' => 'Gewerbliche Nutzung oder Fragen zur Lizenz? Einfach melden: dietmar@gureth.eu'],
                ['type' => 'Label', 'caption' => 'Gefällt dir das Modul und du möchtest trotzdem etwas dalassen? Über eine kleine Spende freue ich mich — völlig freiwillig, keine Gegenleistung nötig.'],
                ['type' => 'Button', 'caption' => '☕  Spenden via PayPal', 'onClick' => "echo '" . self::PAYPAL_URL . "';", 'link' => true],
            ],
        ];
    }

    /**
     * Baut das Formular dynamisch: form.json bleibt die Quelle für die
     * Fachpanels (Abtastintervall, Formel-Doku samt eingebetteter
     * Diagramm-Bilder, Kanalliste) — bewusst NICHT wie bei MeterHub
     * vollständig nach PHP verlagert, da die Formel-Bilder als Base64 dort
     * nur unnötiges Transkriptionsrisiko wären. Davor/danach hängen die
     * verbundweiten, dismissible/nicht-dismissible Rahmen-Panels ein.
     */
    public function GetConfigurationForm()
    {
        $base = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        $docPanel = [
            'type' => 'ExpansionPanel', 'expanded' => false,
            'caption' => '📖  Dokumentation & Hilfe',
            'items' => [
                ['type' => 'Label', 'caption' => 'Messwertrechner ' . self::NEWS_VERSION . ' — Stand dieser Anleitung.'],
                ['type' => 'Label', 'caption' => 'Kanal anlegen: Bezeichnung vergeben, Quelle wählen, Fenster (Wert + Einheit) und Berechnungsmethode festlegen. Details zu den 12 Methoden stehen im Panel „Doku: Berechnungsmethoden" weiter unten.'],
                ['type' => 'Label', 'caption' => '„Ausreißer filtern" (nur bei den Fenster-Methoden 0-8 wirksam) verwirft einzelne Sensor-Ausrutscher automatisch, bevor sie den Mittelwert verzerren — Schwelle als Vielfaches der Standardabweichung, siehe Doku-Panel weiter unten.'],
                ['type' => 'Label', 'caption' => 'Pro Kanal entstehen zwei Variablen: der berechnete Wert (Anzeige) und ein versteckter Ringpuffer bzw. Integrations-Speicher (interner Zwischenspeicher, kein Symcon-Archiv nötig).'],
                ['type' => 'Label', 'caption' => 'Bei „Integration": „Fenster"/„Einheit" werden ignoriert (die Summe läuft unbegrenzt bis zum nächsten Reset) — stattdessen zählt die Spalte „Integrations-Einstellung". Ein manueller Reset per Skript/Ereignis ist über `RAVG_ResetChannel($id, $Bezeichnung)` möglich.'],
                ['type' => 'Label', 'caption' => 'Bei „Schwellwert mit Hysterese" und „Formel" werden „Fenster"/„Einheit" ebenfalls ignoriert — beide reagieren direkt auf den Momentanwert. Glättung + Hysterese/Formel kombinieren: zwei Kanäle verketten (Mittelwert-Kanal als Quelle eines zweiten Kanals).'],
            ],
        ];

        $form = [
            'elements' => array_values(array_filter(array_merge(
                [$this->PurposeIntro(), $this->NewsBanner(), $docPanel],
                $base['elements'],
                [$this->ForumHint(), $this->LicenseHint()]
            ))),
            'actions' => $base['actions'] ?? [],
            'status' => $base['status'],
        ];

        return json_encode($form);
    }

    // Stabiler Schlüssel aus Bezeichnung + Quelle. Ändert der Nutzer eine
    // von beiden bewusst, gilt das als neuer Kanal (alte Variable wird
    // aufgeräumt) — reines Umsortieren der Zeilen ändert den Schlüssel
    // dagegen nicht.
    private function ChannelKey(array $ch): string
    {
        return md5(($ch['Caption'] ?? '') . '|' . ($ch['SourceID'] ?? 0));
    }

    private function WindowSeconds(array $ch): int
    {
        $unit = (int)($ch['WindowUnit'] ?? 1);
        $mult = self::UNIT_SECONDS[$unit] ?? 60;
        // WindowMinutes = Kompatibilität mit Kanälen aus einer älteren Version
        $value = (float)($ch['WindowValue'] ?? ($ch['WindowMinutes'] ?? 10));
        return max(1, (int)round($value * $mult));
    }

    // Ermittelt das Standardprofil für eine NEU anzulegende Mittelwert-
    // Variable. Wird nur bei der Erstanlage aufgerufen (siehe ApplyChanges).
    private function ResolveProfile(array $ch): string
    {
        $digits = (int)($ch['Digits'] ?? -1);
        if ($digits >= 0) {
            // Feste Nachkommastellen angefordert — eigenes, reines
            // Nachkommastellen-Profil (keine Einheit), greift auch wenn
            // die Quelle gar kein Profil hat.
            $digitsProfile = 'RAVG.Digits' . $digits;
            if (!IPS_VariableProfileExists($digitsProfile)) {
                IPS_CreateVariableProfile($digitsProfile, VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileValues($digitsProfile, -1000000000, 1000000000, 0);
            }
            IPS_SetVariableProfileDigits($digitsProfile, $digits);
            return $digitsProfile;
        }

        // Automatisch: Mittelwert bekommt dasselbe Profil (Einheit/Format)
        // wie die Quelle — aber nur, wenn das Profil selbst auch für Float
        // gilt (die Mittelwert-Variable ist immer Float; ein Profil vom
        // Typ Integer/Boolean/String führt sonst zu "Warning: Variablentyp
        // und Profiltyp stimmen nicht überein"). Hat die Quelle kein
        // Profil, bleibt die Mittelwert-Variable ebenfalls ohne Profil.
        $srcID = (int)($ch['SourceID'] ?? 0);
        if ($srcID && IPS_VariableExists($srcID)) {
            $srcVar = IPS_GetVariable($srcID);
            $profile = $srcVar['VariableCustomProfile'] !== '' ? $srcVar['VariableCustomProfile'] : $srcVar['VariableProfile'];
            if ($profile !== '' && IPS_VariableProfileExists($profile)) {
                $profileInfo = IPS_GetVariableProfile($profile);
                if ($profileInfo['ProfileType'] === VARIABLETYPE_FLOAT) {
                    return $profile;
                }
            }
        }

        return '';
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $channels = json_decode($this->ReadPropertyString('Channels'), true);
        if (!is_array($channels)) {
            $channels = [];
        }

        $state = json_decode($this->ReadAttributeString('ChannelState'), true);
        if (!is_array($state)) {
            $state = [];
        }

        $newState = [];
        $seq = 0;
        foreach ($channels as $i => $ch) {
            $key = $this->ChannelKey($ch);
            $caption = ($ch['Caption'] ?? '') !== '' ? $ch['Caption'] : ('Mittelwert ' . ($i + 1));

            $entry = $state[$key] ?? [];
            $vid = (int)($entry['AvgID'] ?? 0);
            $bid = (int)($entry['BufID'] ?? 0);

            // Profil nur bei der Erstanlage setzen (als Standardprofil über
            // RegisterVariableFloat) — ein bestehendes Custom-Profil ist die
            // Hoheit des Nutzers und wird bei weiteren ApplyChanges-Läufen
            // nicht mehr angetastet (Symcon-Review-Feedback, Juli 2026).
            if (!$vid || !IPS_VariableExists($vid)) {
                $profile = $this->ResolveProfile($ch);
                $vid = $this->RegisterVariableFloat('avg_' . $seq, $caption, $profile, $i * 2);
            }
            IPS_SetName($vid, $caption);

            if (!$bid || !IPS_VariableExists($bid)) {
                $bid = $this->RegisterVariableString('buf_' . $seq, $caption . ' (Puffer)', '', $i * 2 + 1);
                SetValueString($bid, '[]');
            }
            IPS_SetHidden($bid, true);

            $newState[$key] = ['AvgID' => $vid, 'BufID' => $bid];
            $seq++;
        }

        // Kanäle, deren Zeile gelöscht oder deren Bezeichnung/Quelle
        // geändert wurde, aufräumen — unabhängig davon, wohin die
        // zugehörigen Variablen im Baum verschoben wurden.
        foreach ($state as $key => $entry) {
            if (!isset($newState[$key])) {
                if (!empty($entry['AvgID']) && IPS_VariableExists($entry['AvgID'])) {
                    IPS_DeleteVariable($entry['AvgID']);
                }
                if (!empty($entry['BufID']) && IPS_VariableExists($entry['BufID'])) {
                    IPS_DeleteVariable($entry['BufID']);
                }
            }
        }
        $this->WriteAttributeString('ChannelState', json_encode($newState));

        if (count($channels) === 0) {
            $this->SetTimerInterval('Tick', 0);
            $this->SetStatus(104);
            return;
        }

        $this->SetTimerInterval('Tick', $this->ReadPropertyInteger('TickInterval') * 1000);
        $this->SetStatus(102);
    }

    public function Tick()
    {
        $channels = json_decode($this->ReadPropertyString('Channels'), true);
        if (!is_array($channels)) {
            return;
        }
        $state = json_decode($this->ReadAttributeString('ChannelState'), true);
        if (!is_array($state)) {
            return;
        }

        $now = time();
        foreach ($channels as $ch) {
            $key = $this->ChannelKey($ch);
            $entry = $state[$key] ?? null;
            if (!$entry) {
                continue;
            }

            $srcID = (int)($ch['SourceID'] ?? 0);
            $vid = (int)$entry['AvgID'];
            $bid = (int)$entry['BufID'];

            if (!$srcID || !IPS_VariableExists($srcID)) {
                continue;
            }
            if (!$vid || !IPS_VariableExists($vid) || !$bid || !IPS_VariableExists($bid)) {
                continue;
            }

            $value = $this->InstantValue($ch, $srcID);
            $mode = (int)($ch['Mode'] ?? 0);

            // Integration (Mode 9) läuft unbegrenzt bis zum nächsten Reset
            // statt in einem festen Fenster — eigener Puffer-Zustand, kein
            // Ringpuffer. Bewusst VOR dem übrigen Ringpuffer-Umgang
            // abgezweigt, damit dessen Trimm-Logik nie versehentlich auf
            // den andersartigen Integrations-Zustand losgelassen wird.
            if ($mode === 9) {
                $this->TickIntegration($ch, $bid, $vid, $value, $now);
                continue;
            }

            // Schwellwert mit Hysterese (Mode 10) arbeitet direkt auf dem
            // aktuellen Momentanwert, nicht auf einem Zeitfenster — braucht
            // deshalb weder Ringpuffer noch Integrations-Zustand. Wer
            // Glättung UND Hysterese kombinieren möchte, verkettet zwei
            // Kanäle (ein Mittelwert-Kanal als Quelle eines Hysterese-
            // Kanals) statt beides in einer Methode zu vermischen.
            if ($mode === 10) {
                $this->TickHysteresis($ch, $vid, $value);
                continue;
            }

            // Formel (Mode 11) arbeitet wie Hysterese direkt auf den
            // Momentanwerten, nicht auf dem Fenster. Anders als die übrigen
            // Methoden liest sie Quelle/Quelle 2 bewusst ROH (ohne
            // Verknüpfung/Invertieren aus InstantValue) — die Formel selbst
            // übernimmt jede gewünschte Verrechnung von x1/x2.
            if ($mode === 11) {
                $this->TickFormula($ch, $vid, $srcID);
                continue;
            }

            $windowSec = $this->WindowSeconds($ch);

            $buffer = json_decode(GetValueString($bid), true);
            // isset(...['_integrator']) fängt den Fall ab, dass dieser
            // Kanal zuvor auf Integration stand und der Puffer noch deren
            // Zustandsobjekt statt einer Zeitreihen-Liste enthält.
            if (!is_array($buffer) || isset($buffer['_integrator'])) {
                $buffer = [];
            }

            // Ausreißer-Filter (optional, Modes 0-8): verwirft den NEUEN
            // Wert, wenn er zu weit vom Durchschnitt der BISHERIGEN
            // Fensterwerte abweicht (Vielfaches der Standardabweichung) —
            // ein einzelner Sensor-Ausrutscher verzerrt dadurch nicht den
            // Mittelwert. Erst ab 3 vorhandenen Werten aktiv (Stddev von
            // 0-2 Werten ist statistisch nicht aussagekräftig). Verworfene
            // Werte werden NICHT aufgenommen, das Fenster wird aber
            // trotzdem getrimmt — alte Werte altern normal weiter aus,
            // auch während gerade ein Ausreißer verworfen wird.
            $isOutlier = false;
            if (!empty($ch['OutlierFilter']) && count($buffer) >= 3) {
                $existingValues = array_column($buffer, 1);
                $mean = array_sum($existingValues) / count($existingValues);
                $variance = array_sum(array_map(function ($v) use ($mean) {
                    return ($v - $mean) ** 2;
                }, $existingValues)) / count($existingValues);
                $stddev = sqrt($variance);
                $factor = (float)($ch['OutlierFactor'] ?? 3.0);
                $isOutlier = $stddev > 0 && abs($value - $mean) > $factor * $stddev;
            }

            if (!$isOutlier) {
                $buffer[] = [$now, $value];
            }
            $buffer = array_values(array_filter($buffer, function ($e) use ($now, $windowSec) {
                return ($now - $e[0]) <= $windowSec;
            }));

            SetValueString($bid, json_encode($buffer));

            $result = $this->ComputeAverage($ch, $buffer, $mode, $now, $windowSec);
            if ($result !== null) {
                SetValueFloat($vid, $result);
            }
        }
    }

    /**
     * Integration über Zeit (Trapezregel): jeder Schritt trägt den
     * Durchschnitt aus letztem und aktuellem Wert, multipliziert mit der
     * verstrichenen Zeit, zum laufenden Akkumulator bei — z.B. Watt über
     * die Zeit aufsummiert ergibt Wattsekunden (Joule), per Divisor aus
     * INTEGRATION_PRESETS auf Wh/kWh/Rohsumme umgerechnet. Der Zustand
     * (Akkumulator, letzter Messpunkt, aktuelle Periode) liegt im selben
     * versteckten Puffer, den die anderen Methoden für den Ringpuffer
     * nutzen — hier aber als Objekt statt als Zeitreihen-Liste.
     */
    private function TickIntegration(array $ch, int $bid, int $vid, float $value, int $now): void
    {
        $state = json_decode(GetValueString($bid), true);
        if (!is_array($state) || !isset($state['_integrator'])) {
            $state = ['_integrator' => true, 'Accum' => 0.0, 'LastSampleTs' => null, 'LastSampleValue' => null, 'PeriodKey' => null];
        }

        $preset = self::INTEGRATION_PRESETS[(int)($ch['IntegrationPreset'] ?? 1)] ?? self::INTEGRATION_PRESETS[1];
        $periodKey = $this->PeriodKeyFor((int)$preset['reset'], $now);

        if ((int)$preset['reset'] !== 0 && $state['PeriodKey'] !== null && $state['PeriodKey'] !== $periodKey) {
            // Perioden-Grenze überschritten (z.B. lokale Mitternacht) ->
            // automatisch zurücksetzen. Der überschreitende Tick selbst
            // trägt bewusst noch nichts bei (der letzte Messpunkt läge in
            // der ALTEN Periode) — die neue Periode beginnt sauber mit
            // diesem Wert als erstem Referenzpunkt.
            $state['Accum'] = 0.0;
            $state['LastSampleTs'] = null;
            $state['LastSampleValue'] = null;
        }
        $state['PeriodKey'] = $periodKey;

        if ($state['LastSampleTs'] !== null) {
            $dt = max(0, $now - (int)$state['LastSampleTs']);
            $avgOverStep = ((float)$state['LastSampleValue'] + $value) / 2;
            $state['Accum'] = (float)$state['Accum'] + $avgOverStep * $dt;
        }
        $state['LastSampleTs'] = $now;
        $state['LastSampleValue'] = $value;

        SetValueString($bid, json_encode($state));
        SetValueFloat($vid, $state['Accum'] / max(1, (int)$preset['divisor']));
    }

    // 0 = nie automatisch, 1 = täglich (lokale Mitternacht), 2 = monatlich
    // (1. des Monats), 3 = jährlich (1.1.) — Vergleich über einen
    // Perioden-Schlüssel statt fester Zeitstempel, dadurch selbstheilend
    // auch nach einem längeren Symcon-Ausfall über eine Periodengrenze
    // hinweg (nächster Tick erkennt den Wechsel unabhängig davon, wie
    // lange die Instanz zuvor nicht gelaufen ist).
    private function PeriodKeyFor(int $resetInterval, int $now): string
    {
        switch ($resetInterval) {
            case 1:
                return date('Y-m-d', $now);
            case 2:
                return date('Y-m', $now);
            case 3:
                return date('Y', $now);
            default:
                return '';
        }
    }

    /**
     * Schwellwert mit Hysterese: bleibt aktiv/inaktiv, bis der jeweils
     * ANDERE Schwellwert erreicht wird — verhindert das klassische Flattern
     * bei einem einzelnen Schwellwert nahe der Grenze. Der Ausgabewert
     * bleibt bewusst ein Float (0.0/1.0) statt einer echten Boolean-
     * Variable, damit ein Moduswechsel auf/von Hysterese nie den
     * Variablentyp der bestehenden AvgID ändern muss (Symcon-Variablen
     * können ihren Typ nicht nachträglich wechseln). Die Richtung
     * (aktiv bei Überschreiten vs. Unterschreiten) wird nicht separat
     * abgefragt, sondern automatisch aus dem Verhältnis der beiden
     * Schwellen abgeleitet: Ein-Schwelle ≥ Aus-Schwelle = "steigend"
     * (z.B. PV-Überschuss: an ab 500 W, aus erst unter 200 W), Ein-Schwelle
     * < Aus-Schwelle = "fallend" (z.B. SOC-Warnung: an ab 20 %, aus erst
     * ab 30 %). Der bisherige Zustand wird direkt aus dem aktuellen
     * Variablenwert gelesen (kein eigenes Zustands-Attribut nötig).
     */
    private function TickHysteresis(array $ch, int $vid, float $value): void
    {
        $onThreshold = (float)($ch['HysteresisOn'] ?? 0);
        $offThreshold = (float)($ch['HysteresisOff'] ?? 0);
        $wasActive = (bool)GetValue($vid);

        if ($onThreshold >= $offThreshold) {
            $isActive = $wasActive ? ($value > $offThreshold) : ($value >= $onThreshold);
        } else {
            $isActive = $wasActive ? ($value < $offThreshold) : ($value <= $onThreshold);
        }

        SetValueFloat($vid, $isActive ? 1.0 : 0.0);
    }

    // Whitelist erlaubter Funktionen im Formel-Kanal — bewusst KEIN eval()
    // oder create_function(), auch wenn die Formel nur vom Instanz-
    // besitzer selbst im eigenen Formular eingegeben wird (sichere
    // Grundhaltung statt "ist ja nur lokal vertrauenswürdig").
    private const FORMULA_FUNCTIONS = ['abs', 'round', 'floor', 'ceil', 'min', 'max', 'sqrt', 'pow', 'log', 'log10', 'exp', 'sin', 'cos', 'tan', 'if'];

    private function TickFormula(array $ch, int $vid, int $srcID): void
    {
        $x1 = (float)GetValue($srcID);
        $src2 = (int)($ch['SourceID2'] ?? 0);
        $x2 = ($src2 && IPS_VariableExists($src2)) ? (float)GetValue($src2) : 0.0;

        $result = $this->EvaluateFormula((string)($ch['Formula'] ?? ''), $x1, $x2);
        if ($result !== null) {
            SetValueFloat($vid, $result);
        }
        // Bei ungültiger/nicht auswertbarer Formel (Syntaxfehler, Division
        // durch 0, unbekannte Variable/Funktion) bleibt der bisherige Wert
        // stehen — bewusst kein Crash, aber auch keine eigene Fehler-UI je
        // Kanal (die Liste hat dafür keine Spalte). Symptom für den Nutzer:
        // der Wert aktualisiert sich nach "Übernehmen" nicht mehr.
    }

    /**
     * Wertet einen Formel-Ausdruck sicher aus (Tokenizer + rekursiver
     * Parser + Evaluator, ohne eval()). Erlaubt: Zahlen, die Variablen x1/x2
     * (= Quelle/Quelle 2), +-*-/-%-^, Vergleichsoperatoren (liefern 1.0/0.0),
     * Klammern und die FORMULA_FUNCTIONS-Whitelist inkl. if(bedingung,dann,
     * sonst) für bedingte Ausdrücke. Gibt null bei jedem Syntax- oder
     * Auswertungsfehler zurück (z.B. Division durch 0, sqrt() von negativ).
     */
    private function EvaluateFormula(string $formula, float $x1, float $x2): ?float
    {
        $formula = trim($formula);
        if ($formula === '') {
            return null;
        }
        $tokens = $this->FormulaTokenize($formula);
        if ($tokens === null || count($tokens) === 0) {
            return null;
        }
        $ast = $this->FormulaParse($tokens);
        if ($ast === null) {
            return null;
        }
        $result = $this->FormulaEval($ast, $x1, $x2);
        if ($result === null || !is_finite($result)) {
            return null;
        }
        return (float)$result;
    }

    private function FormulaTokenize(string $expr): ?array
    {
        $tokens = [];
        $len = strlen($expr);
        $i = 0;
        while ($i < $len) {
            $c = $expr[$i];
            if (ctype_space($c)) {
                $i++;
                continue;
            }
            if (ctype_digit($c) || ($c === '.' && $i + 1 < $len && ctype_digit($expr[$i + 1]))) {
                $start = $i;
                while ($i < $len && (ctype_digit($expr[$i]) || $expr[$i] === '.')) {
                    $i++;
                }
                $tokens[] = ['type' => 'num', 'value' => (float)substr($expr, $start, $i - $start)];
                continue;
            }
            if (ctype_alpha($c) || $c === '_') {
                $start = $i;
                while ($i < $len && (ctype_alnum($expr[$i]) || $expr[$i] === '_')) {
                    $i++;
                }
                $tokens[] = ['type' => 'ident', 'value' => strtolower(substr($expr, $start, $i - $start))];
                continue;
            }
            if ($c === '<' || $c === '>' || $c === '=' || $c === '!') {
                if ($i + 1 < $len && $expr[$i + 1] === '=') {
                    $tokens[] = ['type' => 'op', 'value' => $c . '='];
                    $i += 2;
                    continue;
                }
                if ($c === '=' || $c === '!') {
                    return null; // einzelnes '=' bzw. '!' ohne '=' ist kein gültiger Operator
                }
                $tokens[] = ['type' => 'op', 'value' => $c];
                $i++;
                continue;
            }
            if (strpos('+-*/%^(),', $c) !== false) {
                $type = $c === '(' ? 'lparen' : ($c === ')' ? 'rparen' : ($c === ',' ? 'comma' : 'op'));
                $tokens[] = ['type' => $type, 'value' => $c];
                $i++;
                continue;
            }
            return null; // unbekanntes Zeichen
        }
        return $tokens;
    }

    private function FormulaParse(array $tokens)
    {
        $state = ['tokens' => $tokens, 'pos' => 0];
        $node = $this->FormulaParseComparison($state);
        if ($node === null || $state['pos'] !== count($state['tokens'])) {
            return null; // Syntaxfehler oder nicht verbrauchte Restzeichen
        }
        return $node;
    }

    private function FormulaPeek(array &$state): ?array
    {
        return $state['tokens'][$state['pos']] ?? null;
    }

    // Präzedenz von grob nach fein: Vergleich < Addition/Subtraktion <
    // Multiplikation/Division/Modulo < unäres Vorzeichen < Potenz < Klammer/
    // Zahl/Variable/Funktionsaufruf.
    private function FormulaParseComparison(array &$state)
    {
        $left = $this->FormulaParseAdditive($state);
        if ($left === null) {
            return null;
        }
        $tok = $this->FormulaPeek($state);
        if ($tok && $tok['type'] === 'op' && in_array($tok['value'], ['<', '<=', '>', '>=', '==', '!='], true)) {
            $state['pos']++;
            $right = $this->FormulaParseAdditive($state);
            if ($right === null) {
                return null;
            }
            return ['op' => $tok['value'], 'l' => $left, 'r' => $right];
        }
        return $left;
    }

    private function FormulaParseAdditive(array &$state)
    {
        $left = $this->FormulaParseTerm($state);
        if ($left === null) {
            return null;
        }
        while (($tok = $this->FormulaPeek($state)) && $tok['type'] === 'op' && in_array($tok['value'], ['+', '-'], true)) {
            $state['pos']++;
            $right = $this->FormulaParseTerm($state);
            if ($right === null) {
                return null;
            }
            $left = ['op' => $tok['value'], 'l' => $left, 'r' => $right];
        }
        return $left;
    }

    private function FormulaParseTerm(array &$state)
    {
        $left = $this->FormulaParseUnary($state);
        if ($left === null) {
            return null;
        }
        while (($tok = $this->FormulaPeek($state)) && $tok['type'] === 'op' && in_array($tok['value'], ['*', '/', '%'], true)) {
            $state['pos']++;
            $right = $this->FormulaParseUnary($state);
            if ($right === null) {
                return null;
            }
            $left = ['op' => $tok['value'], 'l' => $left, 'r' => $right];
        }
        return $left;
    }

    private function FormulaParseUnary(array &$state)
    {
        $tok = $this->FormulaPeek($state);
        if ($tok && $tok['type'] === 'op' && ($tok['value'] === '-' || $tok['value'] === '+')) {
            $state['pos']++;
            $operand = $this->FormulaParseUnary($state);
            if ($operand === null) {
                return null;
            }
            return ['op' => 'u' . $tok['value'], 'l' => $operand];
        }
        return $this->FormulaParsePower($state);
    }

    private function FormulaParsePower(array &$state)
    {
        $left = $this->FormulaParsePrimary($state);
        if ($left === null) {
            return null;
        }
        $tok = $this->FormulaPeek($state);
        if ($tok && $tok['type'] === 'op' && $tok['value'] === '^') {
            $state['pos']++;
            $right = $this->FormulaParseUnary($state); // rechtsassoziativ
            if ($right === null) {
                return null;
            }
            return ['op' => '^', 'l' => $left, 'r' => $right];
        }
        return $left;
    }

    private function FormulaParsePrimary(array &$state)
    {
        $tok = $this->FormulaPeek($state);
        if ($tok === null) {
            return null;
        }

        if ($tok['type'] === 'num') {
            $state['pos']++;
            return ['num' => $tok['value']];
        }

        if ($tok['type'] === 'lparen') {
            $state['pos']++;
            $inner = $this->FormulaParseComparison($state);
            if ($inner === null) {
                return null;
            }
            $close = $this->FormulaPeek($state);
            if (!$close || $close['type'] !== 'rparen') {
                return null;
            }
            $state['pos']++;
            return $inner;
        }

        if ($tok['type'] === 'ident') {
            $name = $tok['value'];
            $state['pos']++;
            $next = $this->FormulaPeek($state);
            if ($next && $next['type'] === 'lparen') {
                if (!in_array($name, self::FORMULA_FUNCTIONS, true)) {
                    return null;
                }
                $state['pos']++; // '('
                $args = [];
                $closeTok = $this->FormulaPeek($state);
                if ($closeTok && $closeTok['type'] === 'rparen') {
                    $state['pos']++;
                } else {
                    while (true) {
                        $arg = $this->FormulaParseComparison($state);
                        if ($arg === null) {
                            return null;
                        }
                        $args[] = $arg;
                        $sep = $this->FormulaPeek($state);
                        if ($sep && $sep['type'] === 'comma') {
                            $state['pos']++;
                            continue;
                        }
                        if ($sep && $sep['type'] === 'rparen') {
                            $state['pos']++;
                            break;
                        }
                        return null;
                    }
                }
                return ['fn' => $name, 'args' => $args];
            }
            if ($name !== 'x1' && $name !== 'x2') {
                return null; // nur bekannte Variablen erlaubt
            }
            return ['var' => $name];
        }

        return null;
    }

    private function FormulaEval($node, float $x1, float $x2): ?float
    {
        if (isset($node['num'])) {
            return $node['num'];
        }
        if (isset($node['var'])) {
            return $node['var'] === 'x1' ? $x1 : $x2;
        }
        if (isset($node['fn'])) {
            $args = [];
            foreach ($node['args'] as $a) {
                $v = $this->FormulaEval($a, $x1, $x2);
                if ($v === null) {
                    return null;
                }
                $args[] = $v;
            }
            return $this->FormulaCallFunction($node['fn'], $args);
        }
        if (isset($node['op'])) {
            $op = $node['op'];
            if ($op === 'u-') {
                $v = $this->FormulaEval($node['l'], $x1, $x2);
                return $v === null ? null : -$v;
            }
            if ($op === 'u+') {
                return $this->FormulaEval($node['l'], $x1, $x2);
            }
            $l = $this->FormulaEval($node['l'], $x1, $x2);
            $r = $this->FormulaEval($node['r'], $x1, $x2);
            if ($l === null || $r === null) {
                return null;
            }
            switch ($op) {
                case '+': return $l + $r;
                case '-': return $l - $r;
                case '*': return $l * $r;
                case '/': return $r == 0.0 ? null : $l / $r;
                case '%': return $r == 0.0 ? null : fmod($l, $r);
                case '^': return $l ** $r;
                case '<': return $l < $r ? 1.0 : 0.0;
                case '<=': return $l <= $r ? 1.0 : 0.0;
                case '>': return $l > $r ? 1.0 : 0.0;
                case '>=': return $l >= $r ? 1.0 : 0.0;
                case '==': return $l == $r ? 1.0 : 0.0;
                case '!=': return $l != $r ? 1.0 : 0.0;
            }
        }
        return null;
    }

    private function FormulaCallFunction(string $name, array $args): ?float
    {
        $n = count($args);
        switch ($name) {
            case 'abs': return $n === 1 ? abs($args[0]) : null;
            case 'round': return $n === 1 ? round($args[0]) : ($n === 2 ? round($args[0], (int)$args[1]) : null);
            case 'floor': return $n === 1 ? floor($args[0]) : null;
            case 'ceil': return $n === 1 ? ceil($args[0]) : null;
            case 'min': return $n >= 1 ? min($args) : null;
            case 'max': return $n >= 1 ? max($args) : null;
            case 'sqrt': return $n === 1 && $args[0] >= 0 ? sqrt($args[0]) : null;
            case 'pow': return $n === 2 ? $args[0] ** $args[1] : null;
            case 'log': return $n === 1 && $args[0] > 0 ? log($args[0]) : null;
            case 'log10': return $n === 1 && $args[0] > 0 ? log10($args[0]) : null;
            case 'exp': return $n === 1 ? exp($args[0]) : null;
            case 'sin': return $n === 1 ? sin($args[0]) : null;
            case 'cos': return $n === 1 ? cos($args[0]) : null;
            case 'tan': return $n === 1 ? tan($args[0]) : null;
            case 'if': return $n === 3 ? (($args[0] != 0.0) ? $args[1] : $args[2]) : null;
            default: return null;
        }
    }

    // Momentanwert VOR der Pufferung: optional mit einer zweiten Quelle
    // verknüpft (Addition/Subtraktion) und/oder invertiert. Damit lässt
    // sich z.B. "-(Quelle1 + Quelle2)" bilden und erst DANACH mitteln —
    // sauberer als zwei getrennte Mittelwerte hinterher zu kombinieren.
    private function InstantValue(array $ch, int $srcID): float
    {
        $value = (float)GetValue($srcID);

        $src2 = (int)($ch['SourceID2'] ?? 0);
        $combineMode = (int)($ch['CombineMode'] ?? 0);
        if ($src2 && $combineMode !== 0 && IPS_VariableExists($src2)) {
            $value2 = (float)GetValue($src2);
            $value = ($combineMode === 2) ? ($value - $value2) : ($value + $value2);
        }

        if (!empty($ch['Invert'])) {
            $value *= -1;
        }

        return $value;
    }

    // Mode 0 = Arithmetisch, 1 = Zeitgewichtet, 2 = Median, 3 = Minimum,
    // 4 = Maximum, 5 = Standardabweichung, 6 = Exponentiell (EMA),
    // 7 = Summe, 8 = Änderungsrate (Trend). Alle arbeiten auf demselben
    // Ringpuffer [[Zeitstempel, Wert], ...] innerhalb des konfigurierten
    // Fensters. Mode 9 (Integration) und Mode 10 (Hysterese) werden
    // bereits in Tick() gesondert behandelt (siehe TickIntegration/
    // TickHysteresis) und erreichen diese Methode nicht.
    private function ComputeAverage(array $ch, array $buffer, int $mode, int $now, int $windowSec): ?float
    {
        $count = count($buffer);
        if ($count === 0) {
            return null;
        }
        $values = array_column($buffer, 1);

        switch ($mode) {
            case 1:
                return $this->TimeWeightedAverage($buffer, $now);

            case 2: // Median
                sort($values);
                $mid = intdiv($count, 2);
                if ($count % 2 === 0) {
                    return ($values[$mid - 1] + $values[$mid]) / 2;
                }
                return $values[$mid];

            case 3: // Minimum
                return min($values);

            case 4: // Maximum
                return max($values);

            case 5: // Standardabweichung
                $mean = array_sum($values) / $count;
                $variance = array_sum(array_map(function ($v) use ($mean) {
                    return ($v - $mean) ** 2;
                }, $values)) / $count;
                return sqrt($variance);

            case 6: // Exponentiell gewichteter Mittelwert (EMA)
                return $this->ExponentialMovingAverage($buffer, $windowSec);

            case 7: // Summe
                return array_sum($values);

            case 8: // Änderungsrate (Trend)
                return $this->RateOfChange($buffer, (int)($ch['RateUnit'] ?? 2));

            case 9: // Integration — wird nicht über ComputeAverage berechnet, siehe TickIntegration
                return null;

            case 10: // Schwellwert mit Hysterese — wird nicht über ComputeAverage berechnet, siehe TickHysteresis
                return null;

            case 11: // Formel — wird nicht über ComputeAverage berechnet, siehe TickFormula
                return null;

            default: // Arithmetisch
                return array_sum($values) / $count;
        }
    }

    // Änderungsrate zwischen ältestem und neuestem Punkt im Fenster,
    // umgerechnet auf die gewählte Zeitbasis (Sekunde/Minute/Stunde/Tag).
    // Braucht mindestens zwei Punkte mit unterschiedlichem Zeitstempel,
    // sonst ist noch kein Trend messbar.
    private function RateOfChange(array $buffer, int $rateUnit): ?float
    {
        $count = count($buffer);
        if ($count < 2) {
            return null;
        }
        $first = $buffer[0];
        $last = $buffer[$count - 1];
        $dt = $last[0] - $first[0];
        if ($dt <= 0) {
            return null;
        }
        $mult = self::UNIT_SECONDS[$rateUnit] ?? 3600;
        return ($last[1] - $first[1]) / $dt * $mult;
    }

    // Jeder Messpunkt zählt proportional zu der Zeitspanne, in der sein
    // Wert galt — bis zum nächsten Sample bzw. bis jetzt beim letzten.
    // Robuster bei unregelmäßiger Taktung (verpasste Ticks, Neustart,
    // unterschiedliche Update-Intervalle der Quelle) als der einfache
    // arithmetische Mittelwert.
    private function TimeWeightedAverage(array $buffer, int $now): ?float
    {
        $count = count($buffer);
        $weightedSum = 0.0;
        $totalWeight = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $t = $buffer[$i][0];
            $v = $buffer[$i][1];
            $tNext = ($i + 1 < $count) ? $buffer[$i + 1][0] : $now;
            $weight = max(0, $tNext - $t);
            $weightedSum += $v * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight > 0) {
            return $weightedSum / $totalWeight;
        }
        // Nur ein Sample ohne Zeitspanne (z.B. gerade erst angelegt)
        return $buffer[$count - 1][1];
    }

    // Neuere Werte zählen stärker als ältere. Das Fenster dient als
    // Zeitkonstante (Tau): je größer das Fenster, desto träger reagiert
    // der EMA auf neue Werte.
    private function ExponentialMovingAverage(array $buffer, int $windowSec): ?float
    {
        $tau = max(1, $windowSec);
        $ema = null;
        $prevT = null;
        foreach ($buffer as $point) {
            [$t, $v] = $point;
            if ($ema === null) {
                $ema = $v;
            } else {
                $dt = max(0, $t - $prevT);
                $alpha = 1 - exp(-$dt / $tau);
                $ema = $ema + $alpha * ($v - $ema);
            }
            $prevT = $t;
        }
        return $ema;
    }

    /**
     * Setzt den Integrations-Akkumulator eines Kanals manuell zurück —
     * z.B. per Skript/Ereignis zum Start eines Ladezyklus oder einer
     * Abrechnungsperiode auslösbar, unabhängig vom automatischen
     * täglichen/monatlichen Reset. Adressiert per Bezeichnung (Caption),
     * da das der einzige für Skripte sinnvoll referenzierbare, stabile
     * Anker ist (die interne Puffer-/Variablen-ID ist Implementierungs-
     * detail). Gibt bewusst einen beschreibenden Text zurück statt nur
     * bool/void, damit ein aufrufendes Skript das Ergebnis ohne
     * Zusatzaufwand protokollieren/anzeigen kann.
     */
    public function ResetChannel(string $caption): string
    {
        $channels = json_decode($this->ReadPropertyString('Channels'), true);
        if (!is_array($channels)) {
            $channels = [];
        }
        $state = json_decode($this->ReadAttributeString('ChannelState'), true);
        if (!is_array($state)) {
            $state = [];
        }

        foreach ($channels as $ch) {
            if (($ch['Caption'] ?? '') !== $caption) {
                continue;
            }
            if ((int)($ch['Mode'] ?? 0) !== 9) {
                return 'Kanal „' . $caption . '“ ist kein Integrations-Kanal (Methode ≠ Integration) — nichts zurückgesetzt.';
            }
            $key = $this->ChannelKey($ch);
            $vid = (int)($state[$key]['AvgID'] ?? 0);
            $bid = (int)($state[$key]['BufID'] ?? 0);
            if (!$bid || !IPS_VariableExists($bid) || !$vid || !IPS_VariableExists($vid)) {
                return 'Kanal „' . $caption . '“ hat noch keinen internen Speicher — nichts zurückgesetzt.';
            }
            SetValueString($bid, json_encode(['_integrator' => true, 'Accum' => 0.0, 'LastSampleTs' => null, 'LastSampleValue' => null, 'PeriodKey' => null]));
            SetValueFloat($vid, 0.0);
            return 'Kanal „' . $caption . '“ zurückgesetzt.';
        }

        return 'Kein Kanal mit der Bezeichnung „' . $caption . '“ gefunden.';
    }
}
