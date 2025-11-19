# Update Image References Script
# Automatically updates .png, .jpg, .jpeg references to .webp in .svelte files

param(
    [switch]$DryRun = $false,  # Preview changes without modifying
    [switch]$Verbose = $true   # Show detailed output
)

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Update Image References Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$stats = @{
    FilesScanned = 0
    FilesModified = 0
    ReferencesUpdated = 0
}

# Pattern to match image references (with various quote styles)
$patterns = @(
    @{ Regex = '([''"`])([^''"`]*?)\.(png|jpg|jpeg)(\1)'; Description = "String literals" },
    @{ Regex = '(src\s*=\s*[''"`])([^''"`]*?)\.(png|jpg|jpeg)([''"`])'; Description = "src attributes" },
    @{ Regex = '(image\s*:\s*[''"`])([^''"`]*?)\.(png|jpg|jpeg)([''"`])'; Description = "image properties" },
    @{ Regex = '(path\s*:\s*[''"`])([^''"`]*?)\.(png|jpg|jpeg)([''"`])'; Description = "path properties" }
)

Write-Host "Configuration:" -ForegroundColor Yellow
Write-Host "  Dry Run: $DryRun" -ForegroundColor White
Write-Host "  Verbose: $Verbose" -ForegroundColor White
Write-Host ""

if ($DryRun) {
    Write-Host "⚠️  DRY RUN MODE - No files will be modified" -ForegroundColor Yellow
    Write-Host ""
}

# Get all .svelte files
$svelteFiles = Get-ChildItem -Path ".\src" -Recurse -Filter "*.svelte"
$stats.FilesScanned = $svelteFiles.Count

Write-Host "Found $($stats.FilesScanned) Svelte files to scan" -ForegroundColor Cyan
Write-Host ""

foreach ($file in $svelteFiles) {
    $relativePath = $file.FullName.Replace((Get-Location).Path, "").TrimStart('\')
    $content = Get-Content $file.FullName -Raw
    $originalContent = $content
    $fileModified = $false
    $fileReferences = 0
    
    # Apply each pattern
    foreach ($patternInfo in $patterns) {
        $regex = $patternInfo.Regex
        
        if ($content -match $regex) {
            $matches = [regex]::Matches($content, $regex)
            
            foreach ($match in $matches) {
                $oldExt = $match.Groups[3].Value
                $fullPath = $match.Groups[2].Value + "." + $oldExt
                $newPath = $match.Groups[2].Value + ".webp"
                
                # Check if corresponding .webp file exists
                $webpPath = Join-Path ".\static" $newPath.TrimStart('/')
                
                if (Test-Path $webpPath) {
                    # Replace the extension
                    $oldValue = $match.Value
                    $newValue = $oldValue -replace "\.$oldExt", ".webp"
                    $content = $content.Replace($oldValue, $newValue)
                    
                    $fileReferences++
                    $stats.ReferencesUpdated++
                    
                    if ($Verbose) {
                        Write-Host "  → $fullPath → $newPath" -ForegroundColor Gray
                    }
                    
                    $fileModified = $true
                }
            }
        }
    }
    
    # Write changes if file was modified
    if ($fileModified) {
        Write-Host "✓ $relativePath ($fileReferences updates)" -ForegroundColor Green
        
        if (-not $DryRun) {
            Set-Content -Path $file.FullName -Value $content -NoNewline
        }
        
        $stats.FilesModified++
    } elseif ($Verbose) {
        Write-Host "○ $relativePath (no changes)" -ForegroundColor DarkGray
    }
}

# Summary Report
Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Update Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Files Scanned:       $($stats.FilesScanned)" -ForegroundColor White
Write-Host "Files Modified:      $($stats.FilesModified)" -ForegroundColor Green
Write-Host "References Updated:  $($stats.ReferencesUpdated)" -ForegroundColor Green
Write-Host ""

if ($DryRun) {
    Write-Host "This was a dry run. Run again without -DryRun to apply changes." -ForegroundColor Yellow
} elseif ($stats.FilesModified -gt 0) {
    Write-Host "✓ References updated successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "  1. Test locally: npm run dev" -ForegroundColor White
    Write-Host "  2. Check games: http://localhost:5173/student/game/trash_3" -ForegroundColor White
    Write-Host "  3. Check levels: http://localhost:5173/student/play" -ForegroundColor White
    Write-Host "  4. If all good, commit changes: git add . && git commit -m 'Convert images to WebP'" -ForegroundColor White
} else {
    Write-Host "No changes needed!" -ForegroundColor Yellow
}

Write-Host ""

# Manual verification suggestions
if ($stats.FilesModified -gt 0 -and -not $DryRun) {
    Write-Host "⚠️  Recommended Manual Checks:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Check these files for dynamic image paths:" -ForegroundColor White
    Write-Host "  • src/routes/student/game/trash_*/+page.svelte" -ForegroundColor Gray
    Write-Host "  • src/routes/student/village/GiftShop.svelte" -ForegroundColor Gray
    Write-Host "  • src/routes/student/Levels/**/*.svelte" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Search for computed filenames like:" -ForegroundColor White
    Write-Host '  • Template literals: `${variable}.png`' -ForegroundColor Gray
    Write-Host '  • String concatenation: name + ".png"' -ForegroundColor Gray
    Write-Host ""
}
