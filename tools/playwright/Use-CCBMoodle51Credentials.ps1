[CmdletBinding()]
param(
    [string]$Path = (Join-Path $env:LOCALAPPDATA 'EasyEdu\credentials\ccb-moodle51.xml')
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $Path)) {
    throw "Saved Moodle 5.1 credentials are missing. Run Configure-CCBMoodle51Credentials.ps1 once."
}

$credential = Import-Clixml -LiteralPath $Path
if ($credential -isnot [PSCredential]) {
    throw 'Saved Moodle 5.1 credential file is invalid.'
}

$passwordPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($credential.Password)
try {
    # These values intentionally exist only in the current PowerShell process.
    # The source file is DPAPI-protected and is never part of the repository.
    $env:EASYEDU_MOODLE_URL = 'http://localhost'
    $env:EASYEDU_MOODLE_USERNAME = $credential.UserName
    $env:EASYEDU_MOODLE_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordPointer)
    # Compatibility aliases for the older accessibility smoke spec.
    $env:CCB_MOODLE_URL = $env:EASYEDU_MOODLE_URL
    $env:CCB_MOODLE_USERNAME = $env:EASYEDU_MOODLE_USERNAME
    $env:CCB_MOODLE_PASSWORD = $env:EASYEDU_MOODLE_PASSWORD
}
finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordPointer)
}

Write-Output "Loaded Moodle 5.1 credentials for process-local testing ($($credential.UserName))."
