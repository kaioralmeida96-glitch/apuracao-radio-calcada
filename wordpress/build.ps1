# Gera dist\apuracao-radio-calcada.zip com a versão atual do index.html
# Uso: powershell -ExecutionPolicy Bypass -File wordpress\build.ps1
$ErrorActionPreference = "Stop"
$root   = Split-Path $PSScriptRoot -Parent
$plugin = Join-Path $PSScriptRoot "apuracao-radio-calcada"
$dist   = Join-Path $root "dist"
$stage  = Join-Path $dist "apuracao-radio-calcada"

if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
New-Item -ItemType Directory -Force (Join-Path $stage "app") | Out-Null
Copy-Item (Join-Path $plugin "*.php") $stage
Copy-Item (Join-Path $plugin "readme.txt") $stage
Copy-Item (Join-Path $root "index.html") (Join-Path $stage "app\index.html")

$zip = Join-Path $dist "apuracao-radio-calcada.zip"
if (Test-Path $zip) { Remove-Item $zip -Force }
# o WordPress espera a pasta do plugin dentro do zip, com barras "/"
Add-Type -AssemblyName System.IO.Compression, System.IO.Compression.FileSystem
$z = [System.IO.Compression.ZipFile]::Open($zip, "Create")
Get-ChildItem $stage -Recurse -File | ForEach-Object {
  $rel = "apuracao-radio-calcada/" + $_.FullName.Substring($stage.Length + 1).Replace("\", "/")
  [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($z, $_.FullName, $rel) | Out-Null
}
$z.Dispose()
Remove-Item $stage -Recurse -Force
Write-Host "Gerado: $zip"
