# Demarre MySQL, le backend PHP et le frontend Vite avec les versions correctes.
#
# Pourquoi ce script existe : le PATH systeme pointe vers PHP 7.4.33
# (incompatible, le projet exige >= 8.2) et le service Windows
# wampmysqld64 n'est pas toujours demarre. Ce script fixe les binaires
# exacts a utiliser pour eviter de relancer les mauvaises versions.
#
# Ce projet partage l'instance MySQL du projet PHP jumeau (apprentissage-POO-PHP)
# -- une seule base de donnees MySQL tourne sur la machine, apprentissage_js vit
# dedans a cote de apprentissage_poo. Le port backend (8010) et le port frontend
# (5200) sont volontairement distincts de ceux du projet PHP (8000 / 51xx) pour
# pouvoir lancer les deux projets en meme temps sans collision.

$ErrorActionPreference = "Stop"

$RepoRoot = Split-Path -Parent $PSScriptRoot
$PhpBin   = "C:\wamp64\bin\php\php8.2.26\php.exe"
$MysqldExe = "C:\wamp64\bin\mysql\mysql9.1.0\bin\mysqld.exe"
$MysqldIni = "C:\wamp64\bin\mysql\mysql9.1.0\my.ini"

function Test-PortListening($port) {
    return [bool](Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue)
}

# 1. MySQL (port 3306) - partagee avec apprentissage-POO-PHP, apprentissage_js
#    vit dans la meme instance mysql9.1.0
if (Test-PortListening 3306) {
    Write-Output "MySQL deja actif sur le port 3306."
} else {
    $svc = Get-Service wampmysqld64 -ErrorAction SilentlyContinue
    $started = $false
    if ($svc) {
        try {
            Start-Service wampmysqld64 -ErrorAction Stop
            $started = $true
            Write-Output "Service wampmysqld64 demarre."
        } catch {
            Write-Warning "Impossible de demarrer le service wampmysqld64 (droits admin requis). Lancement manuel de mysqld..."
        }
    }
    if (-not $started) {
        Start-Process -FilePath $MysqldExe -ArgumentList "--defaults-file=`"$MysqldIni`"", "--console" `
            -RedirectStandardOutput "$RepoRoot\backend\mysql-manual.log" `
            -RedirectStandardError "$RepoRoot\backend\mysql-manual-err.log" -WindowStyle Hidden
        Write-Output "mysqld (mysql9.1.0) lance manuellement sur le port 3306."
    }
}

# 2. Backend PHP (port 8010) - doit etre PHP 8.2+, jamais le 7.4 du PATH systeme
if (Test-PortListening 8010) {
    Write-Output "Backend deja actif sur le port 8010."
} else {
    Start-Process -FilePath $PhpBin -ArgumentList "-S", "localhost:8010", "-t", "public/" `
        -WorkingDirectory "$RepoRoot\backend" `
        -RedirectStandardOutput "$RepoRoot\backend\php-server.log" `
        -RedirectStandardError "$RepoRoot\backend\php-server-err.log" -WindowStyle Hidden
    Write-Output "Backend PHP 8.2.26 lance sur http://localhost:8010"
}

# 3. Frontend Vite (port fixe 5200, voir vite.config.js)
if (Test-PortListening 5200) {
    Write-Output "Frontend deja actif sur le port 5200."
} else {
    Start-Process -FilePath "npm.cmd" -ArgumentList "run", "dev" `
        -WorkingDirectory "$RepoRoot\frontend" `
        -RedirectStandardOutput "$RepoRoot\frontend\vite.log" `
        -RedirectStandardError "$RepoRoot\frontend\vite-err.log" -WindowStyle Hidden
    Write-Output "Frontend lance sur http://localhost:5200 (voir frontend/vite.log en cas de souci)."
}
