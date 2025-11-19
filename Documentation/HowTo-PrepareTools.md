# Introduction

Cette documentation d'installation et préparation des outils est issue de Chat GPT et sera corrigée au cours de sa première utilisation selon les incohérences relevées ou les besoins

## 🧰 Étape A — Vérifier et mettre à jour ton environnement macOS

### 1️⃣ Installer / Mettre à jour Homebrew

Homebrew est le gestionnaire de paquets sur Mac : indispensable pour PHP, Composer, Node, etc.

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
brew update
brew upgrade
```

Cette étape peut être assez longue et rencontrer des erreurs:

### En cas de dépendence manquante ou cassée

```bash
Error: The following directories are not writable by your user: /usr/local/share/man/man8
```

#### Gobject-introspection

pour vérfier qu'on est pas root

```bash
whoami
```

pour corriger:

```bash
sudo mkdir -p /usr/local/share/man/man8
sudo chown -R $(whoami) /usr/local/share/man
```

puis relancer l'installation de Brew

### Installation Homebrew terminée

```bash
brew --version
```

## 🧩 Étape B — Installer PHP et Composer

### 2️⃣ Installer PHP 8.3

```bash
brew install php
```

Teste :

```bash
php -v
```

→ Réponse attendue (ou similaire):

```bash
PHP 8.3.x (cli) (built: ...)
```

### 3️⃣ Installer Composer

```bash
brew install composer
```

Teste :

```bash
composer --version
```

→ Composer version 2.x.x attendu.

💡 Si Homebrew t’indique que Composer est déjà à jour, parfait.
