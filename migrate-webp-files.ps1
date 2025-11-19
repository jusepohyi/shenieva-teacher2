# WebP Migration Script
# Moves converted WebP files from static/converted/ to their correct locations

param(
    [switch]$DryRun = $false,
    [switch]$CreateBackup = $true
)

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  WebP Migration Script" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$stats = @{
    FilesMoved = 0
    FilesSkipped = 0
    Errors = 0
}

# Check if converted folder exists
if (-not (Test-Path "static\converted")) {
    Write-Host "✗ Error: static\converted folder not found!" -ForegroundColor Red
    Write-Host "Please make sure your converted WebP files are in static\converted\" -ForegroundColor Yellow
    exit 1
}

Write-Host "Configuration:" -ForegroundColor Yellow
Write-Host "  Source: static\converted\" -ForegroundColor White
Write-Host "  Destination: static\" -ForegroundColor White
Write-Host "  Create Backup: $CreateBackup" -ForegroundColor White
Write-Host "  Dry Run: $DryRun" -ForegroundColor White
Write-Host ""

if ($DryRun) {
    Write-Host "⚠️  DRY RUN MODE - No files will be modified" -ForegroundColor Yellow
    Write-Host ""
}

# Phase 1: Create Backup
if ($CreateBackup -and -not $DryRun) {
    Write-Host "Phase 1: Creating Backup..." -ForegroundColor Cyan
    Write-Host ""
    
    $backupDir = "static\_original_images_backup"
    
    if (Test-Path $backupDir) {
        Write-Host "  Backup folder already exists, skipping..." -ForegroundColor Yellow
    } else {
        try {
            New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
            Write-Host "  ✓ Created backup folder: $backupDir" -ForegroundColor Green
            
            # Backup existing files
            if (Test-Path "static\assets") {
                Copy-Item -Path "static\assets" -Destination "$backupDir\assets" -Recurse -Force
                Write-Host "  ✓ Backed up static\assets\" -ForegroundColor Green
            }
            
            if (Test-Path "static\trash_collect_game") {
                Copy-Item -Path "static\trash_collect_game" -Destination "$backupDir\trash_collect_game" -Recurse -Force
                Write-Host "  ✓ Backed up static\trash_collect_game\" -ForegroundColor Green
            }
            
            if (Test-Path "static\favicon.png") {
                Copy-Item -Path "static\favicon.png" -Destination "$backupDir\" -Force
                Write-Host "  ✓ Backed up favicon.png" -ForegroundColor Green
            }
            
            if (Test-Path "static\avatar.jpg") {
                Copy-Item -Path "static\avatar.jpg" -Destination "$backupDir\" -Force
                Write-Host "  ✓ Backed up avatar.jpg" -ForegroundColor Green
            }
            
            Write-Host ""
            Write-Host "  ✓ Backup complete!" -ForegroundColor Green
        } catch {
            Write-Host "  ✗ Backup failed: $($_.Exception.Message)" -ForegroundColor Red
            exit 1
        }
    }
    Write-Host ""
}

# Phase 2: Move WebP Files
Write-Host "Phase 2: Moving WebP Files..." -ForegroundColor Cyan
Write-Host ""

# Function to move files
function Move-WebPFiles {
    param(
        [string]$Source,
        [string]$Destination,
        [string]$Description
    )
    
    if (Test-Path $Source) {
        if ($DryRun) {
            Write-Host "  ○ Would move: $Source → $Destination" -ForegroundColor Cyan
            $stats.FilesMoved++
        } else {
            try {
                # Create destination directory if it doesn't exist
                $destDir = Split-Path -Parent $Destination
                if ($destDir -and -not (Test-Path $destDir)) {
                    New-Item -ItemType Directory -Path $destDir -Force | Out-Null
                }
                
                # Move the item
                if (Test-Path $Destination) {
                    Remove-Item -Path $Destination -Recurse -Force
                }
                Move-Item -Path $Source -Destination $Destination -Force
                Write-Host "  ✓ Moved: $Description" -ForegroundColor Green
                $stats.FilesMoved++
            } catch {
                Write-Host "  ✗ Failed: $Description - $($_.Exception.Message)" -ForegroundColor Red
                $stats.Errors++
            }
        }
    } else {
        Write-Host "  ⊘ Skipped: $Description (not found)" -ForegroundColor Gray
        $stats.FilesSkipped++
    }
}

# Move top-level files
Write-Host "Moving top-level files..." -ForegroundColor Yellow
Move-WebPFiles "static\converted\favicon.webp" "static\favicon.webp" "favicon.webp"
Move-WebPFiles "static\converted\avatar.webp" "static\avatar.webp" "avatar.webp"
Write-Host ""

# Move trash_collect_game (from root level - CORRECT location)
Write-Host "Moving trash_collect_game folder..." -ForegroundColor Yellow
if (Test-Path "static\converted\trash_collect_game") {
    if ($DryRun) {
        Write-Host "  ○ Would replace: static\trash_collect_game\" -ForegroundColor Cyan
        $stats.FilesMoved++
    } else {
        try {
            Remove-Item -Path "static\trash_collect_game" -Recurse -Force -ErrorAction SilentlyContinue
            Move-Item -Path "static\converted\trash_collect_game" -Destination "static\trash_collect_game" -Force
            Write-Host "  ✓ Moved trash_collect_game folder" -ForegroundColor Green
            $stats.FilesMoved++
        } catch {
            Write-Host "  ✗ Failed to move trash_collect_game: $($_.Exception.Message)" -ForegroundColor Red
            $stats.Errors++
        }
    }
}
Write-Host ""

# Move assets folder contents
Write-Host "Moving assets folder..." -ForegroundColor Yellow
if (Test-Path "static\converted\assets") {
    if ($DryRun) {
        Write-Host "  ○ Would copy: static\converted\assets\* → static\assets\" -ForegroundColor Cyan
        $stats.FilesMoved++
    } else {
        try {
            # Get all subdirectories and files
            $assetItems = Get-ChildItem -Path "static\converted\assets" -Recurse
            
            foreach ($item in $assetItems) {
                $relativePath = $item.FullName.Replace((Resolve-Path "static\converted\assets").Path, "").TrimStart('\')
                $destPath = Join-Path "static\assets" $relativePath
                
                if ($item.PSIsContainer) {
                    # Create directory
                    if (-not (Test-Path $destPath)) {
                        New-Item -ItemType Directory -Path $destPath -Force | Out-Null
                    }
                } else {
                    # Copy file
                    $destDir = Split-Path -Parent $destPath
                    if (-not (Test-Path $destDir)) {
                        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
                    }
                    Copy-Item -Path $item.FullName -Destination $destPath -Force
                }
            }
            
            Write-Host "  ✓ Copied assets folder contents" -ForegroundColor Green
            $stats.FilesMoved++
        } catch {
            Write-Host "  ✗ Failed to copy assets: $($_.Exception.Message)" -ForegroundColor Red
            $stats.Errors++
        }
    }
}
Write-Host ""

# Phase 3: Clean up duplicates
Write-Host "Phase 3: Cleaning Up..." -ForegroundColor Cyan
Write-Host ""

# Remove duplicate trash_collect_game from assets (incorrect location)
if (Test-Path "static\assets\trash_collect_game") {
    if ($DryRun) {
        Write-Host "  ○ Would delete: static\assets\trash_collect_game\ (duplicate)" -ForegroundColor Cyan
    } else {
        try {
            Remove-Item -Path "static\assets\trash_collect_game" -Recurse -Force
            Write-Host "  ✓ Removed duplicate: static\assets\trash_collect_game\" -ForegroundColor Green
        } catch {
            Write-Host "  ✗ Failed to remove duplicate: $($_.Exception.Message)" -ForegroundColor Red
        }
    }
}

# Remove converted folder
if (Test-Path "static\converted") {
    if ($DryRun) {
        Write-Host "  ○ Would delete: static\converted\" -ForegroundColor Cyan
    } else {
        try {
            Remove-Item -Path "static\converted" -Recurse -Force
            Write-Host "  ✓ Removed: static\converted\" -ForegroundColor Green
        } catch {
            Write-Host "  ⚠️  Could not remove static\converted\: $($_.Exception.Message)" -ForegroundColor Yellow
        }
    }
}

Write-Host ""

# Summary Report
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Migration Summary" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Files Moved:    $($stats.FilesMoved)" -ForegroundColor Green
Write-Host "Files Skipped:  $($stats.FilesSkipped)" -ForegroundColor Yellow
Write-Host "Errors:         $($stats.Errors)" -ForegroundColor $(if ($stats.Errors -gt 0) { "Red" } else { "White" })
Write-Host ""

if ($DryRun) {
    Write-Host "This was a dry run. Run again without -DryRun to migrate." -ForegroundColor Yellow
} elseif ($stats.Errors -eq 0) {
    Write-Host "✓ Migration complete!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "  1. Run: .\update-to-webp-references.ps1" -ForegroundColor White
    Write-Host "  2. Verify structure: ls static\" -ForegroundColor White
    Write-Host "  3. Test locally: npm run dev" -ForegroundColor White
} else {
    Write-Host "⚠️  Migration completed with errors. Please review." -ForegroundColor Yellow
}

Write-Host ""
