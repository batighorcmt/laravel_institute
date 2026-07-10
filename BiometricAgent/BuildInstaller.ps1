# Build and package BiometricAgent as a Windows installer.
# Requires Inno Setup compiler (iscc.exe) in PATH to generate the installer.

$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Push-Location $projectDir
try {
    Write-Host "Publishing BiometricAgent as self-contained x64 Windows application..."
    dotnet publish .\BiometricAgent.csproj -c Release -r win-x64 --self-contained true /p:PublishSingleFile=true /p:PublishTrimmed=false /p:IncludeAllContentForSelfExtract=true /p:EnableCompressionInSingleFile=true

    $publishDir = Join-Path $projectDir 'bin\Release\net8.0-windows\win-x64\publish'
    if (-Not (Test-Path $publishDir)) {
        throw "Publish directory not found: $publishDir"
    }

    Write-Host "Copying native SDK files into publish folder..."
    Copy-Item -Path .\Sdk\*.dll -Destination $publishDir -Force

    $installerScript = Join-Path $projectDir 'BiometricAgentInstaller.iss'
    $isccCommand = Get-Command iscc.exe -ErrorAction SilentlyContinue
    if ($isccCommand) {
        $iscc = $isccCommand.Source
    }

    if (-not $iscc) {
        $possiblePaths = @(
            "$env:ProgramFiles(x86)\Inno Setup 6\ISCC.exe",
            "$env:ProgramFiles\Inno Setup 6\ISCC.exe",
            "$env:ProgramFiles(x86)\Inno Setup 5\ISCC.exe",
            "$env:ProgramFiles\Inno Setup 5\ISCC.exe"
        )
        foreach ($path in $possiblePaths) {
            if (Test-Path $path) {
                $iscc = $path
                break
            }
        }
    }

    if ($iscc) {
        Write-Host "Compiling Inno Setup installer using: $iscc"
        & $iscc /q $installerScript
        Write-Host "Installer build complete. Check the output folder for BiometricAgentSetup.exe."
    }
    else {
        Write-Warning "Inno Setup compiler (iscc.exe) was not found. Trying IExpress fallback."
        $iexpress = "$env:windir\system32\iexpress.exe"
        if (Test-Path $iexpress) {
            Write-Host "Generating IExpress directive file..."
            $sedFile = Join-Path $projectDir 'BiometricAgentInstaller.sed'
            $outputFile = Join-Path $projectDir 'BiometricAgentSetup.exe'
            $publishFiles = Get-ChildItem -Path $publishDir -File | Select-Object -ExpandProperty Name

            $sedLines = @(
                '[Version]',
                'Class=IEXPRESS',
                'SEDVersion=3',
                '[Options]',
                'PackagePurpose=InstallApp',
                'ShowInstallProgramWindow=1',
                'HideExtractAnimation=0',
                'UseLongFileName=1',
                'InsideCompressed=0',
                'CABFixedSize=0',
                'RebootMode=I',
                'InstallPrompt=Do you want to install BiometricAgent?',
                'DisplayLicense=',
                'FinishedMessage=BiometricAgent installation is complete.',
                'TargetName=BiometricAgentSetup.exe',
                'FriendlyName=BiometricAgent Installer',
                'AppLaunched=BiometricAgent.exe',
                'PostInstallCmd=',
                'AdminQuietInstCmd=',
                'UserQuietInstCmd=',
                'RunHidden=0',
                'ExtractTitle=BiometricAgent Setup',
                'ExtractOkText=The package was successfully created.',
                "OutputPath=$projectDir",
                '',
                '[SourceFiles]',
                'SourceFiles0=Publish',
                "SourceFiles0Path=$publishDir"
            )

            foreach ($fileName in $publishFiles) {
                $sedLines += "SourceFiles0Files=$fileName"
            }

            $sedLines += @(
                '',
                '[DestinationDirs]',
                'DefaultDestDir=0',
                '',
                '[Strings]',
                'InstallPrompt=Do you want to install BiometricAgent?',
                'DisplayLicense=',
                'TargetName=BiometricAgentSetup.exe',
                'AppLaunched=BiometricAgent.exe',
                'PostInstallCmd=',
                'AdminQuietInstCmd=',
                'UserQuietInstCmd=',
                'OutputPath=.'
            )

            $sedLines | Set-Content -Path $sedFile -Encoding ASCII

            Write-Host "Using IExpress to create a self-extracting installer..."
            $proc = Start-Process -FilePath $iexpress -ArgumentList '/Q', $sedFile -NoNewWindow -Wait -PassThru -WorkingDirectory $projectDir
            if ($proc.ExitCode -eq 0) {
                if (Test-Path $outputFile) {
                    Write-Host "IExpress package created successfully: $outputFile"
                }
                else {
                    Write-Warning "IExpress completed but did not create $outputFile."
                    Write-Host "Search the publish directory or rerun the wizard manually."
                }
            }
            else {
                Write-Warning "IExpress failed with exit code $($proc.ExitCode).";
                Write-Host "Publish output is available at: $publishDir"
            }
        }
        else {
            Write-Warning "IExpress is not available on this machine. Install Inno Setup or use the publish folder directly."
            Write-Host "Publish output is available at: $publishDir"
        }
    }
}
finally {
    Pop-Location
}
