# Simple WebP Path Updater
$files = Get-ChildItem -Path "src" -Recurse -Filter "*.svelte"
Write-Host "Found $($files.Count) files" -ForegroundColor Cyan

foreach ($f in $files) {
    $c = Get-Content $f.FullName -Raw
    $c = $c -replace '"/trash_collect_game/([^"]+)\.png"', '"/converted/trash_collect_game/$1.webp"'
    $c = $c -replace "'\''/trash_collect_game/([^'']+)\.png'\'", "'/converted/trash_collect_game/`$1.webp'"
    $c = $c -replace '`/trash_collect_game/([^`]+)\.png`', '`/converted/trash_collect_game/$1.webp`'
    $c = $c -replace '"/assets/([^"]+)\.(png|jpg|jpeg|gif)"', '"/converted/assets/$1.webp"'
    $c = $c -replace "'/assets/([^']+)\.(png|jpg|jpeg|gif)'", "'/converted/assets/`$1.webp'"
    Set-Content $f.FullName -Value $c -NoNewline
}
Write-Host "Done!" -ForegroundColor Green
