[Setup]
AppName=BiometricAgent
AppVersion=1.0.0
AppPublisher=Batighor Software Systems Ltd
AppPublisherURL=http://www.batighorbd.com
DefaultDirName={pf}\BiometricAgent
DefaultGroupName=BiometricAgent
OutputBaseFilename=BiometricAgentSetup
Compression=lzma2
SolidCompression=yes
PrivilegesRequired=admin
ArchitecturesInstallIn64BitMode=x64
WizardStyle=modern
DisableStartupPrompt=yes
UninstallDisplayIcon={app}\BiometricAgent.exe

[Files]
Source: "bin\Release\net8.0-windows\win-x64\publish\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\BiometricAgent"; Filename: "{app}\BiometricAgent.exe"
Name: "{commondesktop}\BiometricAgent"; Filename: "{app}\BiometricAgent.exe"; Tasks: desktopicon

[Tasks]
Name: desktopicon; Description: "Create a desktop icon"; GroupDescription: "Additional icons:"; Flags: unchecked

[Run]
Filename: "{sys}\regsvr32.exe"; Parameters: "/s ""{app}\\zkemkeeper.dll"""; Flags: runhidden waituntilterminated; StatusMsg: "Registering Biometric SDK..."

[UninstallRun]
Filename: "{sys}\regsvr32.exe"; Parameters: "/s /u ""{app}\\zkemkeeper.dll"""; Flags: runhidden waituntilterminated; StatusMsg: "Unregistering Biometric SDK..."
