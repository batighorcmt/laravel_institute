# BiometricAgent – SDK Setup

Before building, copy **all DLLs** from the ZKTeco SDK folder into the `Sdk\` subfolder:

```
BiometricAgent\
  Sdk\
    zkemkeeper.dll
    zkemsdk.dll
    commpro.dll
    comms.dll
    rscagent.dll
    rscomm.dll
    tcpcomm.dll
    usbcomm.dll
```

Source path:
```
C:\Users\You\Downloads\844401440sdk_64_bit\Communication Protocol SDK(64Bit Ver6.2.4.0)\sdk\
```

Then **register the COM DLL** once (as Administrator):

```cmd
regsvr32 "C:\Users\You\Downloads\844401440sdk_64_bit\Communication Protocol SDK(64Bit Ver6.2.4.0)\sdk\zkemkeeper.dll"
```

Or use the provided `Auto-install_sdk.bat` script in the SDK root folder.

## Build

```cmd
dotnet build BiometricAgent.csproj -c Release
```

## Publish Single EXE

```cmd
cd BiometricAgent
powershell .\BuildInstaller.ps1
```

- This will publish the app as a self-contained single `BiometricAgent.exe`.
- If Inno Setup is installed (`iscc.exe` available), it also builds `BiometricAgentSetup.exe`.
- If Inno Setup is not installed, use the published folder directly.

## Installer

1. Install Inno Setup from https://jrsoftware.org/isinfo.php
2. Run `powershell .\BuildInstaller.ps1`
3. The generated installer will be `BiometricAgentSetup.exe`

## First Run

1. Launch `BiometricAgent.exe`
2. Click **⚙ Settings**
3. Fill in SaaS API URL, School Code, Agent Token
4. Add devices (Name, Serial, IP, Port)
5. Click **💾 Save & Close**
6. Click **⟳ Sync Now**
