[CmdletBinding()]
param([ValidateRange(65536, 8388608)][int]$BytesPerStream = 1048576)

$ErrorActionPreference = 'Stop'
$info = [Diagnostics.ProcessStartInfo]::new()
$info.FileName = 'node.exe'
$info.Arguments = "-e " + ('"const n={0}; process.stdout.write(''o''.repeat(n)); process.stderr.write(''e''.repeat(n));"' -f $BytesPerStream)
$info.UseShellExecute = $false; $info.CreateNoWindow = $true
$info.RedirectStandardOutput = $true; $info.RedirectStandardError = $true
$process = [Diagnostics.Process]::new(); $process.StartInfo = $info
if (-not $process.Start()) { throw 'Unable to start synthetic Node process.' }
$stdoutTask = $process.StandardOutput.ReadToEndAsync()
$stderrTask = $process.StandardError.ReadToEndAsync()
$process.WaitForExit()
$stdout = $stdoutTask.GetAwaiter().GetResult()
$stderr = $stderrTask.GetAwaiter().GetResult()
if ($process.ExitCode -ne 0 -or $stdout.Length -ne $BytesPerStream -or $stderr.Length -ne $BytesPerStream) {
    throw "Synthetic drain failed: exit=$($process.ExitCode), stdout=$($stdout.Length), stderr=$($stderr.Length)."
}
"Synthetic async drain passed: stdout=$($stdout.Length), stderr=$($stderr.Length)."
