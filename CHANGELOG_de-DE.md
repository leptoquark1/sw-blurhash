## 4.1.0

### Features

- Vorhandene Blurhashes über den Medienbrowser in der Administration entfernen
- Bestimmte Ordner direkt über den Mediabrowser in der Administration ausschließen
- CLI-Befehl `ec:blurhash:remove` zum Entfernen vorhandener Blurhashes
- Wenn das Plugin deinstalliert wird, werden alle vorhandenen Blurhash-Metadaten entfernt
- Admin-API-Ressource zum Entfernen vorhandener Blurhashes

### Fehlerbehebungen

- Rechtschreibfehler und Tippfehler in der deutschen Übersetzung (tinect)
- Kompatibilitätsprobleme mit PHP 8.0

## 4.0.1

Dieser Patch behebt einige wichtige Probleme in der emulierten Integration.

### Fehlerbehebungen

- Bereits dekodierte Elemente zeigen möglicherweise nicht das fertige Bild an (z. B. Off-Canvas-Warenkorb)
- Verzögertes Laden von Bildern mit gleichem Blurhash (z. B. Produkt-Detailseite)
- Vorladen von Bilder in der richtigen Auflösung (Responsive)
- Doppeltes laden des Lade-Spinner-Icons

# 4.0.0

### Features

Diese erste Version enthält alle grundlegenden konzeptionellen Features

- Generieren von Blurhashes direkt über den Medienbrowser
- Vorschau bei passenden Bildern, überall in der Administration
- Erweiterte Konfigurationsmöglichkeiten: Performance, Qualität, Einschränkungen (Blacklist) und Storefront-Integration
- Immer aktuell: Neue oder geänderte Medien werden automatisch abgearbeitet
- Steuerung auch über die Kommandozeile
- Emulierte Integration in jede Storefront-Theme
