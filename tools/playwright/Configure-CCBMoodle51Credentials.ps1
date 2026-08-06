[CmdletBinding()]
param(
    [string]$Path = (Join-Path $env:LOCALAPPDATA 'EasyEdu\credentials\ccb-moodle51.xml')
)

$ErrorActionPreference = 'Stop'
$directory = Split-Path -Parent $Path
New-Item -ItemType Directory -Path $directory -Force | Out-Null

$username = Read-Host 'Moodle 5.1 username [admin]'
if ([string]::IsNullOrWhiteSpace($username)) { $username = 'admin' }
$password = Read-Host 'Moodle 5.1 password' -AsSecureString
$credential = [PSCredential]::new($username, $password)

# Export-Clixml protects the SecureString with Windows DPAPI for this user and
# machine. The file is local-only and must never be copied into Git or Syncthing.
$credential | Export-Clixml -LiteralPath $Path -Force
Write-Output "Saved encrypted Moodle 51 credentials for $($credential.UserName)."
Write-Output "Path: $Path"
