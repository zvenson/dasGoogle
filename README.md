# SvenDasGoogle – Google Business Bewertungen für Shopware 6

Shopware 6 Plugin, das **Google Business Bewertungen** als schwebendes Floating-Widget
und als CMS-Element in Erlebniswelten anzeigt. Live aus der Google Places API,
mit Cache und ohne Cookie-Banner-Probleme.

Entwickelt und gepflegt von **[Designburg.net](https://designburg.net)** – Shopware-
und Web-Agentur aus Hamburg. Brauchst du individuelle Anpassungen, weitere Plugins
oder Shopware-Hosting? Schreib uns: [shopware@designburg.net](mailto:shopware@designburg.net).

---

## Warum dieses Plugin?

Wir haben für unsere eigenen Shops nach einer günstigen Möglichkeit gesucht,
**echte Kundenbewertungen** im Storefront anzuzeigen. Etablierte Bewertungs­
plattformen wie Trusted Shops sind hervorragende Produkte, für unsere Shopgröße
aber wirtschaftlich nicht darstellbar gewesen – die laufenden Kosten standen in
keinem Verhältnis zum Nutzen.

Da viele unserer Kunden ohnehin eine **Google-Business-Bewertung** hinterlassen,
lag es nahe, genau diese direkt im Shop einzubinden. Das Plugin nutzt die
offizielle Google Places API, ist mit dem $200 Gratis-Guthaben pro Monat in den
meisten Fällen praktisch kostenlos und zeigt die Sterne plus die letzten
Rezensionen direkt im Shop an.

> **Hinweis:** Google-Bewertungen sind kein vollständiger Ersatz für ein
> zertifiziertes Gütesiegel inklusive Käuferschutz. Wer ein solches Siegel
> braucht, ist mit Anbietern wie Trusted Shops, eKomi oder Trustpilot
> weiterhin gut bedient. Dieses Plugin ist die schlankere Lösung für alle, die
> einfach nur ihre vorhandenen Google-Bewertungen sichtbar machen wollen.

---

## Features

- **Floating Widget** auf jeder Storefront-Seite – Position und Farbe konfigurierbar
- **CMS-Element** für Erlebniswelten (Karussell mit den letzten Bewertungen)
- **Caching** der API-Antworten (Standard 6 h) – minimiert Google-API-Kosten
- Kompatibel mit Shopware ab **6.6**

Eine ausführliche Anleitung findest du in der [HANDBUCH.md](HANDBUCH.md).

---

## Installation

### Variante A: Per ZIP (empfohlen für Shop-Betreiber)

1. Im [Releases-Bereich](https://github.com/zvenson/dasGoogle/releases) die aktuelle
   `SvenDasGoogle-x.y.z.zip` herunterladen.
2. Im Shopware-Admin → **Erweiterungen** → **Meine Erweiterungen** → **Erweiterung hochladen**
   die ZIP auswählen.
3. Plugin **installieren** und **aktivieren**.
4. Konfigurieren – siehe [HANDBUCH.md](HANDBUCH.md).

### Variante B: Per Composer / Git (für Entwickler)

```bash
cd custom/plugins/
git clone https://github.com/zvenson/dasGoogle.git SvenDasGoogle
cd ../..
bin/console plugin:refresh
bin/console plugin:install --activate SvenDasGoogle
bin/console cache:clear
```

### Variante C: ZIP selbst bauen

```bash
git clone https://github.com/zvenson/dasGoogle.git SvenDasGoogle
zip -r SvenDasGoogle.zip SvenDasGoogle \
    -x "SvenDasGoogle/.git/*" "SvenDasGoogle/.github/*" "SvenDasGoogle/*.code-workspace"
```

Die entstandene ZIP kann direkt im Shopware-Admin hochgeladen werden.

---

## Konfiguration

Du brauchst einen **Google API Key** (Places API aktiviert) und eine **Place ID**
deines Google-Business-Eintrags. Die Schritt-für-Schritt-Anleitung steht im
[HANDBUCH.md](HANDBUCH.md).

---

## Support & individuelle Anpassungen

Dieses Plugin wird von **[Designburg.net](https://designburg.net)** entwickelt.

- Webseite: <https://designburg.net>
- Kontakt: [shopware@designburg.net](mailto:shopware@designburg.net)
- Weitere Shopware-Plugins: <https://designburg.net/shopware>

Für Bug-Reports und Feature-Requests bitte ein
[GitHub-Issue](https://github.com/zvenson/dasGoogle/issues) eröffnen.

---

## Lizenz

Proprietär – © [Designburg.net](https://designburg.net). Nutzung im eigenen
Shop ist gestattet, Weiterverkauf nicht.
