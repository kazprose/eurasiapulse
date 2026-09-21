# Starts the local WordPress dev server (PHP built-in server + SQLite).
# Usage:  powershell -File dev/serve.ps1
$root = Split-Path -Parent $PSScriptRoot
& "$root\.local\php\php.exe" -S 127.0.0.1:8080 -t "$root\.local\wordpress" "$root\dev\router.php"
