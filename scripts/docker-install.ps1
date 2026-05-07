param(
    [int]$Port = 8084,
    [switch]$Fresh
)

$ErrorActionPreference = 'Stop'

function Invoke-Compose {
    docker compose @args
}

function Invoke-Calixy {
    docker compose exec -T calixy @args
}

$root = Resolve-Path (Join-Path $PSScriptRoot '..')
Set-Location $root

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw 'Docker was not found. Install Docker Desktop, start it, then run this script again.'
}

docker info *> $null
if ($LASTEXITCODE -ne 0) {
    throw 'Docker is not running. Start Docker Desktop, then run this script again.'
}

if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
}

if ($Fresh) {
    Invoke-Compose down -v
}

$env:CALIXY_DOCKER_PORT = $Port

Write-Host "Building and starting Calixy on http://localhost:$Port ..."
Invoke-Compose up -d --build

Write-Host 'Installing PHP dependencies ...'
Invoke-Calixy composer install

if (Test-Path 'package.json') {
    Write-Host 'Installing JavaScript dependencies and building Vite assets ...'
    Invoke-Calixy npm install --ignore-scripts
    Invoke-Calixy npm run build
}

if (Test-Path 'artisan') {
    Write-Host 'Preparing Laravel ...'
    Invoke-Calixy sh -lc 'test -f .env || cp .env.example .env'
    Invoke-Calixy php artisan key:generate --force
    Invoke-Calixy php artisan migrate --seed --force
    Invoke-Calixy php artisan storage:link
    Invoke-Calixy php artisan config:clear
    Invoke-Calixy php artisan route:clear
    Invoke-Calixy php artisan view:clear
} else {
    Write-Host 'No artisan file found; skipped Laravel application preparation.'
}

Write-Host ''
Write-Host "Calixy is ready: http://localhost:$Port"
Write-Host 'Useful commands:'
Write-Host '  docker compose logs -f calixy'
Write-Host '  docker compose logs -f mysql'
Write-Host '  docker compose down'
Write-Host '  .\scripts\docker-install.ps1 -Fresh'
