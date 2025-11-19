# WebP Conversion Script for Shenieva Teacher Project
# Converts PNG, JPG, JPEG, and static GIF files to WebP format

param(
    [int]$Quality = 85,              # WebP quality (0-100)
    [switch]$DryRun = $false,        # Preview without converting
    [switch]$KeepOriginals = $true,  # Keep original files as .original
    [switch]$Verbose = $true         # Show detailed output
)

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  WebP Conversion Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Check if cwebp is available
try {
    $cwebpVersion = & cwebp -version 2>&1
    Write-Host "✓ cwebp found: $($cwebpVersion[0])" -ForegroundColor Green
} catch {
    Write-Host "✗ Error: cwebp not found!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install libwebp first:" -ForegroundColor Yellow
    Write-Host "  Option 1 (Scoop):" -ForegroundColor White
    Write-Host "    scoop install main/libwebp" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  Option 2 (Manual):" -ForegroundColor White
    Write-Host "    Download from: https://developers.google.com/speed/webp/download" -ForegroundColor Gray
    Write-Host ""
    exit 1
}

Write-Host ""

# Settings
$staticDir = ".\static"
$extensions = @("*.png", "*.jpg", "*.jpeg")
$stats = @{
    Total = 0
    Converted = 0
    Skipped = 0
    Failed = 0
    OriginalSize = 0
    ConvertedSize = 0
}

Write-Host "Configuration:" -ForegroundColor Yellow
Write-Host "  Directory: $staticDir" -ForegroundColor White
Write-Host "  Quality: $Quality%" -ForegroundColor White
Write-Host "  Keep Originals: $KeepOriginals" -ForegroundColor White
Write-Host "  Dry Run: $DryRun" -ForegroundColor White
Write-Host ""

if ($DryRun) {
    Write-Host "⚠️  DRY RUN MODE - No files will be modified" -ForegroundColor Yellow
    Write-Host ""
}

# Get all image files
$imageFiles = @()
foreach ($ext in $extensions) {
    $imageFiles += Get-ChildItem -Path $staticDir -Recurse -Filter $ext
}

$stats.Total = $imageFiles.Count
Write-Host "Found $($stats.Total) images to convert" -ForegroundColor Cyan
Write-Host ""

if ($imageFiles.Count -eq 0) {
    Write-Host "No images found. Exiting." -ForegroundColor Yellow
    exit 0
}

# Confirmation prompt
if (-not $DryRun) {
    Write-Host "Press any key to start conversion, or Ctrl+C to cancel..." -ForegroundColor Yellow
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    Write-Host ""
}

# Progress counter
$current = 0

foreach ($file in $imageFiles) {
    $current++
    $relativePath = $file.FullName.Replace((Get-Location).Path, "").TrimStart('\')
    
    # Create output filename
    $outputPath = [System.IO.Path]::ChangeExtension($file.FullName, ".webp")
    
    # Skip if WebP already exists (unless we're replacing)
    if ((Test-Path $outputPath) -and $KeepOriginals) {
        Write-Host "[$current/$($stats.Total)] ⊘ SKIP: $relativePath (WebP exists)" -ForegroundColor Gray
        $stats.Skipped++
        continue
    }
    
    $originalSize = (Get-Item $file.FullName).Length
    $stats.OriginalSize += $originalSize
    
    if ($DryRun) {
        Write-Host "[$current/$($stats.Total)] ○ PREVIEW: $relativePath → $(Split-Path -Leaf $outputPath)" -ForegroundColor Cyan
        $stats.Converted++
        continue
    }
    
    try {
        # Convert to WebP
        $result = & cwebp -q $Quality $file.FullName -o $outputPath 2>&1
        
        if (Test-Path $outputPath) {
            $convertedSize = (Get-Item $outputPath).Length
            $stats.ConvertedSize += $convertedSize
            $savings = [math]::Round((1 - ($convertedSize / $originalSize)) * 100, 1)
            
            Write-Host "[$current/$($stats.Total)] ✓ CONVERTED: $relativePath" -ForegroundColor Green
            if ($Verbose) {
                Write-Host "    Size: $([math]::Round($originalSize/1KB, 1))KB → $([math]::Round($convertedSize/1KB, 1))KB (${savings}% savings)" -ForegroundColor DarkGray
            }
            
            # Rename original to .original if keeping
            if ($KeepOriginals) {
                $backupPath = "$($file.FullName).original"
                Move-Item -Path $file.FullName -Destination $backupPath -Force
                if ($Verbose) {
                    Write-Host "    Backup: $backupPath" -ForegroundColor DarkGray
                }
            } else {
                # Delete original
                Remove-Item -Path $file.FullName -Force
            }
            
            $stats.Converted++
        } else {
            throw "Output file not created"
        }
        
    } catch {
        Write-Host "[$current/$($stats.Total)] ✗ FAILED: $relativePath" -ForegroundColor Red
        Write-Host "    Error: $($_.Exception.Message)" -ForegroundColor DarkRed
        $stats.Failed++
    }
}

# Summary Report
Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Conversion Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Total Files:      $($stats.Total)" -ForegroundColor White
Write-Host "Converted:        $($stats.Converted)" -ForegroundColor Green
Write-Host "Skipped:          $($stats.Skipped)" -ForegroundColor Yellow
Write-Host "Failed:           $($stats.Failed)" -ForegroundColor Red
Write-Host ""

if (-not $DryRun -and $stats.Converted -gt 0) {
    $originalMB = [math]::Round($stats.OriginalSize / 1MB, 2)
    $convertedMB = [math]::Round($stats.ConvertedSize / 1MB, 2)
    $savedMB = [math]::Round(($stats.OriginalSize - $stats.ConvertedSize) / 1MB, 2)
    $savedPercent = [math]::Round((1 - ($stats.ConvertedSize / $stats.OriginalSize)) * 100, 1)
    
    Write-Host "Size Comparison:" -ForegroundColor Yellow
    Write-Host "  Original:    ${originalMB} MB" -ForegroundColor White
    Write-Host "  Converted:   ${convertedMB} MB" -ForegroundColor White
    Write-Host "  Saved:       ${savedMB} MB (${savedPercent}%)" -ForegroundColor Green
    Write-Host ""
}

if ($DryRun) {
    Write-Host "This was a dry run. Run again without -DryRun to convert." -ForegroundColor Yellow
} elseif ($stats.Converted -gt 0) {
    Write-Host "✓ Conversion complete!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "  1. Run: .\update-image-references.ps1" -ForegroundColor White
    Write-Host "  2. Test locally: npm run dev" -ForegroundColor White
    Write-Host "  3. Build: npm run build" -ForegroundColor White
    Write-Host "  4. Preview: npm run preview" -ForegroundColor White
}

Write-Host ""
