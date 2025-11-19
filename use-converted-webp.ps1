# Update Paths to Use /converted/ WebP Images
param(
    [switch]$DryRun = $false,
    [switch]$Verbose = $true
)

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Update to /converted/ WebP Paths" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$stats = @{
    FilesScanned = 0
    FilesModified = 0
    ReferencesUpdated = 0
}

if ($DryRun) {
    Write-Host "⚠️  DRY RUN MODE - No files will be modified" -ForegroundColor Yellow
    Write-Host ""
}

# Get all .svelte files
$svelteFiles = Get-ChildItem -Path "src" -Recurse -Filter "*.svelte"
$stats.FilesScanned = $svelteFiles.Count

Write-Host "Found $($stats.FilesScanned) Svelte files" -ForegroundColor Cyan
Write-Host ""

foreach ($file in $svelteFiles) {
    $relativePath = $file.FullName.Replace((Get-Location).Path, "").TrimStart('\')
    $content = Get-Content $file.FullName -Raw
    
    if (-not $content) { continue }
    
    $fileModified = $false
    
    # Replace /trash_collect_game/ paths with /converted/trash_collect_game/
    $content = $content -replace '(["\x27`])/trash_collect_game/([^"\x27`]+)\.png(["\x27`])', '$1/converted/trash_collect_game/$2.webp$3'
    $content = $content -replace '(["\x27`])/trash_collect_game/([^"\x27`]+)\.jpe?g(["\x27`])', '$1/converted/trash_collect_game/$2.webp$3'
    
    # Replace /assets/ paths with /converted/assets/
    $content = $content -replace '(["\x27`])/assets/([^"\x27`]+)\.png(["\x27`])', '$1/converted/assets/$2.webp$3'
    $content = $content -replace '(["\x27`])/assets/([^"\x27`]+)\.jpe?g(["\x27`])', '$1/converted/assets/$2.webp$3'
    $content = $content -replace '(["\x27`])/assets/([^"\x27`]+)\.gif(["\x27`])', '$1/converted/assets/$2.webp$3'
    
    # Replace relative paths ../../assets/
    $content = $content -replace '(["\x27`])(\.\./)+assets/([^"\x27`]+)\.(png|jpe?g|gif)(["\x27`])', '$1$2converted/assets/$3.webp$5'
    
    if ($content -ne (Get-Content $file.FullName -Raw)) {
        Write-Host "✓ $relativePath" -ForegroundColor Green
        $fileModified = $true
        $stats.FilesModified++
        $stats.ReferencesUpdated++
        
        if (-not $DryRun) {
            Set-Content -Path $file.FullName -Value $content -NoNewline
        }
    } elseif ($Verbose) {
        Write-Host "○ $relativePath (no changes)" -ForegroundColor DarkGray
    }
}

# Summary
Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Files Scanned:  $($stats.FilesScanned)" -ForegroundColor White
Write-Host "Files Modified: $($stats.FilesModified)" -ForegroundColor Green
Write-Host ""

if ($DryRun) {
    Write-Host "This was a dry run. Run without -DryRun to apply changes." -ForegroundColor Yellow
} elseif ($stats.FilesModified -gt 0) {
    Write-Host "✓ Paths updated! Next steps:" -ForegroundColor Green
    Write-Host "  1. Test: npm run dev" -ForegroundColor White
    Write-Host "  2. Visit: http://localhost:5173/student/game/trash_3" -ForegroundColor White
    Write-Host "  3. Check console for errors" -ForegroundColor White
    Write-Host ""
    Write-Host "To rollback: git checkout -- src/" -ForegroundColor Yellow
}

Write-Host ""
