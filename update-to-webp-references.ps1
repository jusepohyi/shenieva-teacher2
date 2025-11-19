# Update Image References to WebP
# Changes .png, .jpg, .jpeg, .gif references to .webp in all .svelte files

param(
    [switch]$DryRun = $false,
    [switch]$Verbose = $true
)

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Update to WebP References" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$stats = @{
    FilesScanned = 0
    FilesModified = 0
    ReferencesUpdated = 0
    Breakdown = @{
        png = 0
        jpg = 0
        jpeg = 0
        gif = 0
    }
}

Write-Host "Configuration:" -ForegroundColor Yellow
Write-Host "  Dry Run: $DryRun" -ForegroundColor White
Write-Host "  Verbose: $Verbose" -ForegroundColor White
Write-Host ""

if ($DryRun) {
    Write-Host "⚠️  DRY RUN MODE - No files will be modified" -ForegroundColor Yellow
    Write-Host ""
}

# Get all .svelte files
$svelteFiles = Get-ChildItem -Path "src" -Recurse -Filter "*.svelte" -ErrorAction SilentlyContinue
$stats.FilesScanned = $svelteFiles.Count

Write-Host "Found $($stats.FilesScanned) Svelte files to scan" -ForegroundColor Cyan
Write-Host ""

# Extensions to replace
$extensions = @('png', 'jpg', 'jpeg', 'gif')

foreach ($file in $svelteFiles) {
    $relativePath = $file.FullName.Replace((Get-Location).Path, "").TrimStart('\')
    $content = Get-Content $file.FullName -Raw -ErrorAction SilentlyContinue
    
    if (-not $content) {
        continue
    }
    
    $originalContent = $content
    $fileModified = $false
    $fileChanges = @()
    
    # Replace each extension
    foreach ($ext in $extensions) {
        # Pattern 1: Standard paths in quotes (single, double, backtick)
        $pattern1 = "\.${ext}([`"'`)])"
        if ($content -match $pattern1) {
            $beforeCount = ([regex]::Matches($content, $pattern1)).Count
            $content = $content -replace $pattern1, ".webp`$1"
            $stats.Breakdown[$ext] += $beforeCount
            $stats.ReferencesUpdated += $beforeCount
            $fileChanges += "${ext}→webp ($beforeCount)"
            $fileModified = $true
        }
        
        # Pattern 2: File extensions in template literals
        $pattern2 = "\.${ext}``"
        if ($content -match $pattern2) {
            $beforeCount = ([regex]::Matches($content, $pattern2)).Count
            $content = $content -replace $pattern2, ".webp``"
            $stats.Breakdown[$ext] += $beforeCount
            $stats.ReferencesUpdated += $beforeCount
            if (-not $fileChanges.Contains("${ext}→webp")) {
                $fileChanges += "${ext}→webp ($beforeCount)"
            }
            $fileModified = $true
        }
        
        # Pattern 3: URLs in parentheses (CSS)
        $pattern3 = "\.${ext}\)"
        if ($content -match $pattern3) {
            $beforeCount = ([regex]::Matches($content, $pattern3)).Count
            $content = $content -replace $pattern3, ".webp)"
            $stats.Breakdown[$ext] += $beforeCount
            $stats.ReferencesUpdated += $beforeCount
            if (-not $fileChanges.Contains("${ext}→webp")) {
                $fileChanges += "${ext}→webp ($beforeCount)"
            }
            $fileModified = $true
        }
    }
    
    # Write changes if file was modified
    if ($fileModified) {
        $changesSummary = $fileChanges -join ", "
        Write-Host "✓ $relativePath" -ForegroundColor Green
        if ($Verbose) {
            Write-Host "  → $changesSummary" -ForegroundColor Gray
        }
        
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
Write-Host "Breakdown:" -ForegroundColor Yellow
Write-Host "  .png  → .webp:  $($stats.Breakdown.png)" -ForegroundColor White
Write-Host "  .jpg  → .webp:  $($stats.Breakdown.jpg)" -ForegroundColor White
Write-Host "  .jpeg → .webp:  $($stats.Breakdown.jpeg)" -ForegroundColor White
Write-Host "  .gif  → .webp:  $($stats.Breakdown.gif)" -ForegroundColor White
Write-Host ""

if ($DryRun) {
    Write-Host "This was a dry run. Run again without -DryRun to apply changes." -ForegroundColor Yellow
} elseif ($stats.FilesModified -gt 0) {
    Write-Host "✓ References updated successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "⚠️  Manual Verification Needed:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Check these files for template literals with variables:" -ForegroundColor White
    Write-Host '  • Search for: ${...}.png` or similar patterns' -ForegroundColor Gray
    Write-Host "  • Files: src/routes/student/game/trash_*/+page.svelte" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "  1. Verify changes: git diff src/" -ForegroundColor White
    Write-Host "  2. Test locally: npm run dev" -ForegroundColor White
    Write-Host "  3. Check games: http://localhost:5173/student/game/trash_3" -ForegroundColor White
    Write-Host "  4. Check levels: http://localhost:5173/student/play" -ForegroundColor White
    Write-Host "  5. If good: git add . && git commit -m 'Update image references to WebP'" -ForegroundColor White
} else {
    Write-Host "No changes needed!" -ForegroundColor Yellow
}

Write-Host ""
