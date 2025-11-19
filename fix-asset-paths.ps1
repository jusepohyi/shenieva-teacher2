# PowerShell script to fix asset paths for Netlify deployment
# This script replaces incorrect asset paths in all Svelte files

Write-Host "🔧 Fixing asset paths for Netlify deployment..." -ForegroundColor Cyan

# Fix trash_collect_game paths (change /assets/trash_collect_game to /trash_collect_game)
$files = Get-ChildItem -Path "src" -Include "*.svelte","*.ts","*.js" -Recurse

$count = 0
foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $original = $content
    
    # Fix runtime paths (NOT import.meta.glob)
    # Only replace /assets/trash_collect_game when NOT inside import.meta.glob
    $lines = Get-Content $file.FullName
    $modified = $false
    $newLines = @()
    
    foreach ($line in $lines) {
        $newLine = $line
        
        # Skip lines with import.meta.glob
        if ($line -notmatch "import\.meta\.glob") {
            # Replace /assets/trash_collect_game with /trash_collect_game
            if ($line -match "/assets/trash_collect_game") {
                $newLine = $line -replace "/assets/trash_collect_game", "/trash_collect_game"
                $modified = $true
            }
        } else {
            # For import.meta.glob lines, fix /static/assets/ to /static/
            if ($line -match "/static/assets/trash_collect_game") {
                $newLine = $line -replace "/static/assets/trash_collect_game", "/static/trash_collect_game"
                $modified = $true
            }
        }
        
        $newLines += $newLine
    }
    
    if ($modified) {
        $newLines | Set-Content $file.FullName
        $count++
        Write-Host "  ✅ Fixed: $($file.Name)" -ForegroundColor Green
    }
}

Write-Host "`n✨ Fixed $count files!" -ForegroundColor Green
Write-Host "`n📝 Next steps:" -ForegroundColor Yellow
Write-Host "  1. Review the changes with: git diff" -ForegroundColor White
Write-Host "  2. Test locally: npm run dev" -ForegroundColor White
Write-Host "  3. Build: npm run build" -ForegroundColor White
Write-Host "  4. Test the build: npm run preview" -ForegroundColor White
Write-Host "  5. Deploy to Netlify" -ForegroundColor White
