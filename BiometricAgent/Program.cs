using System;
using System.Windows.Forms;

namespace BiometricAgent
{
    internal static class Program
    {
        [STAThread]
        static void Main(string[] args)
        {
            ApplicationConfiguration.Initialize();

            // Global exception handlers to catch unexpected crashes and log them.
            Application.ThreadException += (s, e) => HandleUiException(e.Exception);
            AppDomain.CurrentDomain.UnhandledException += (s, e) =>
            {
                if (e.ExceptionObject is Exception ex) HandleDomainException(ex);
                else HandleDomainException(new Exception("Unknown unmanaged exception"));
            };
            System.Threading.Tasks.TaskScheduler.UnobservedTaskException += (s, e) =>
            {
                try { LogException(e.Exception); } catch { }
                e.SetObserved();
            };

            try
            {
                // If started with uploader arguments, run uploader mode and exit.
                if (args != null && args.Length > 0 && args[0] == "--upload-templates")
                {
                    int argc = args.Length;
                    if (argc < 5)
                    {
                        Console.Error.WriteLine("Usage: --upload-templates <ip> <port> <machineNumber> <templatesJsonPath>");
                        Environment.Exit(2);
                    }
                    var ip = args[1];
                    var port = int.Parse(args[2]);
                    var machineNumber = int.Parse(args[3]);
                    var jsonPath = args[4];
                    returnCode:;
                    int code = RunUploader(ip, port, machineNumber, jsonPath);
                    Environment.Exit(code);
                }

                Application.Run(new MainDashboard());
            }
            catch (Exception ex)
            {
                HandleDomainException(ex);
            }
        }

        private static int RunUploader(string ip, int port, int machineNumber, string jsonPath)
        {
            try
            {
                if (!System.IO.File.Exists(jsonPath))
                {
                    Console.Error.WriteLine("Templates file not found: " + jsonPath);
                    return 3;
                }

                var json = System.IO.File.ReadAllText(jsonPath);
                var templates = Newtonsoft.Json.JsonConvert.DeserializeObject<System.Collections.Generic.List<BiometricTemplate>>(json);
                if (templates == null || templates.Count == 0)
                {
                    Console.WriteLine("No templates to upload.");
                    return 0;
                }

                var adapter = BiometricAdapterFactory.Create("zkteco");
                // This is a short-lived write-only session (push templates then exit) so
                // we don't register for real-time events - see Connect()'s registerEvents doc.
                if (!adapter.Connect(ip, port, machineNumber, registerEvents: false))
                {
                    Console.Error.WriteLine("Failed to connect to device at " + ip);
                    return 4;
                }

                // Give the device a moment to settle right after the TCP handshake before
                // hitting it with writes - some ZK firmware rejects/ignores commands sent
                // immediately after Connect_Net.
                System.Threading.Thread.Sleep(500);

                int success = 0;
                foreach (var t in templates)
                {
                    try
                    {
                        // Normalize biometric id like main app
                        var biometricId = t.BiometricId ?? string.Empty;
                        biometricId = System.Text.RegularExpressions.Regex.Replace(biometricId, "^\\D+", "");
                        t.BiometricId = biometricId;

                        // Log template diagnostic info to stdout and log file
                        var len = (t.TemplateData ?? string.Empty).Length;
                        var logDir = System.IO.Path.Combine(AppDomain.CurrentDomain.BaseDirectory ?? ".", "logs");
                        try { System.IO.Directory.CreateDirectory(logDir); } catch { }
                        var dlog = System.IO.Path.Combine(logDir, "uploader_debug.log");
                        var entry = $"[{DateTime.Now:yyyy-MM-dd HH:mm:ss}] Uploading biometricId={biometricId}, length={len}\n";
                        try { System.IO.File.AppendAllText(dlog, entry); } catch { }
                        Console.WriteLine(entry.Trim());

                        if (adapter.UploadTemplate(t))
                        {
                            success++;
                        }
                        else
                        {
                            var reason = adapter.LastError;
                            var failEntry = $"[{DateTime.Now:yyyy-MM-dd HH:mm:ss}] FAILED biometricId={biometricId}: {reason}\n";
                            try { System.IO.File.AppendAllText(dlog, failEntry); } catch { }
                            Console.Error.WriteLine(failEntry.Trim());
                        }
                        System.Threading.Thread.Sleep(50);
                    }
                    catch (Exception ex)
                    {
                        Console.Error.WriteLine("Upload error: " + ex.Message);
                    }
                }

                Console.WriteLine($"Uploaded {success}/{templates.Count} templates to {ip}");
                adapter.Disconnect();
                return success == templates.Count ? 0 : 1;
            }
            catch (Exception ex)
            {
                Console.Error.WriteLine("Uploader failed: " + ex.Message);
                return 5;
            }
        }

        private static void HandleUiException(Exception ex)
        {
            try { LogException(ex); } catch { }
            try { MessageBox.Show($"An error occurred: {ex.Message}", "Agent Error", MessageBoxButtons.OK, MessageBoxIcon.Error); } catch { }
        }

        private static void HandleDomainException(Exception ex)
        {
            try { LogException(ex); } catch { }
            try { MessageBox.Show($"A fatal error occurred: {ex.Message}", "Agent Fatal Error", MessageBoxButtons.OK, MessageBoxIcon.Error); } catch { }
        }

        private static void LogException(Exception ex)
        {
            var logDir = System.IO.Path.Combine(Application.StartupPath ?? ".", "logs");
            try { System.IO.Directory.CreateDirectory(logDir); } catch { }
            var logFile = System.IO.Path.Combine(logDir, "agent_exceptions.log");
            var payload = $"[{DateTime.Now:yyyy-MM-dd HH:mm:ss}] {ex}\n";
            System.IO.File.AppendAllText(logFile, payload);
        }
    }
}
