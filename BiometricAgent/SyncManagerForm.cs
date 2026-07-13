using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Text.RegularExpressions;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace BiometricAgent
{
    public class SyncManagerForm : Form
    {
        private readonly AgentConfig _config;
        private readonly CloudSyncManager _cloud;
        private readonly Dictionary<string, IBiometricAdapter> _adapters;
        private readonly int _schoolId;
        private readonly OfflineQueue _offlineQueue;

        private ComboBox cmbDevices;
        private Button btnStep1;
        private Button btnStep2;
        private Button btnStep3;
        private ProgressBar prgProgress;
        private Label lblProgress;
        private RichTextBox rtfLog;
        private Button btnClose;

        public SyncManagerForm(AgentConfig config, CloudSyncManager cloud, Dictionary<string, IBiometricAdapter> adapters, int schoolId)
        {
            _config = config;
            _cloud = cloud;
            _adapters = adapters;
            _schoolId = schoolId;
            _offlineQueue = new OfflineQueue();

            InitializeUI();
            PopulateDevices();
        }

        private void InitializeUI()
        {
            this.Text = "Biometric Sync Manager";
            this.Size = new Size(600, 500);
            this.StartPosition = FormStartPosition.CenterParent;
            this.BackColor = Color.FromArgb(30, 30, 46);
            this.ForeColor = Color.White;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;

            Label lblTitle = new Label
            {
                Text = "Biometric Template Workflow",
                Font = new Font("Segoe UI", 14, FontStyle.Bold),
                Location = new Point(20, 20),
                AutoSize = true
            };
            Controls.Add(lblTitle);

            Label lblSelect = new Label
            {
                Text = "Device:",
                Location = new Point(20, 70),
                AutoSize = true
            };
            Controls.Add(lblSelect);

            cmbDevices = new ComboBox
            {
                Location = new Point(140, 67),
                Width = 200,
                DropDownStyle = ComboBoxStyle.DropDownList
            };
            Controls.Add(cmbDevices);

            btnStep1 = CreateButton("Step 1: Send Users to Device", 20, 110, Color.FromArgb(59, 130, 246));
            btnStep1.Click += async (s, e) => await Step1_SendUsersAsync();
            Controls.Add(btnStep1);

            btnStep2 = CreateButton("Step 2: Upload Templates to Web", 20, 160, Color.FromArgb(139, 92, 246));
            btnStep2.Click += async (s, e) => await Step2_UploadTemplatesAsync();
            Controls.Add(btnStep2);

            btnStep3 = CreateButton("Step 3: Distribute to Selected Device", 20, 210, Color.FromArgb(16, 185, 129));
            btnStep3.Click += async (s, e) => await Step3_DistributeTemplatesAsync();
            Controls.Add(btnStep3);

            prgProgress = new ProgressBar
            {
                Location = new Point(20, 258),
                Width = 540,
                Height = 18,
                Style = ProgressBarStyle.Blocks,
                MarqueeAnimationSpeed = 30
            };
            Controls.Add(prgProgress);

            lblProgress = new Label
            {
                Text = "",
                Location = new Point(20, 279),
                Width = 540,
                ForeColor = Color.LightGray,
                Font = new Font("Segoe UI", 8),
                AutoSize = false
            };
            Controls.Add(lblProgress);

            rtfLog = new RichTextBox
            {
                Location = new Point(20, 300),
                Width = 540,
                Height = 95,
                BackColor = Color.FromArgb(40, 42, 54),
                ForeColor = Color.LightGray,
                ReadOnly = true,
                BorderStyle = BorderStyle.None
            };
            Controls.Add(rtfLog);

            btnClose = CreateButton("Close", 460, 410, Color.FromArgb(107, 114, 128));
            btnClose.Width = 100;
            btnClose.Click += (s, e) => this.Close();
            Controls.Add(btnClose);
        }

        private Button CreateButton(string text, int x, int y, Color color)
        {
            return new Button
            {
                Text = text,
                Location = new Point(x, y),
                Width = 250,
                Height = 35,
                FlatStyle = FlatStyle.Flat,
                BackColor = color,
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 9, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
        }

        private void PopulateDevices()
        {
            foreach (var dev in _config.Devices)
            {
                if (!string.IsNullOrWhiteSpace(dev.IpAddress))
                {
                    cmbDevices.Items.Add(new DeviceItem
                    {
                        Name = dev.Name,
                        IpAddress = dev.IpAddress,
                        Serial = dev.SerialNumber,
                        Port = dev.Port,
                        MachineNumber = dev.MachineNumber
                    });
                }
            }
            if (cmbDevices.Items.Count > 0)
                cmbDevices.SelectedIndex = 0;
        }

        private void Log(string msg)
        {
            if (rtfLog.InvokeRequired)
            {
                rtfLog.Invoke(new Action(() => Log(msg)));
                return;
            }
            rtfLog.AppendText($"[{DateTime.Now:HH:mm:ss}] {msg}\n");
            rtfLog.SelectionStart = rtfLog.Text.Length;
            rtfLog.ScrollToCaret();
        }

        /// <summary>Updates the progress bar. max &lt;= 0 with non-empty text shows an
        /// indeterminate (marquee) bar for phases with no known total (e.g. reading a
        /// device where the user count isn't known up-front); max &gt; 0 shows a determinate
        /// percentage. Called with ("" , 0, 0) to reset to idle.</summary>
        private void SetProgress(int value, int max, string text)
        {
            if (prgProgress.InvokeRequired)
            {
                prgProgress.Invoke(new Action(() => SetProgress(value, max, text)));
                return;
            }

            if (max > 0)
            {
                prgProgress.Style = ProgressBarStyle.Blocks;
                prgProgress.Maximum = max;
                prgProgress.Value = Math.Max(0, Math.Min(value, max));
            }
            else if (!string.IsNullOrEmpty(text))
            {
                prgProgress.Style = ProgressBarStyle.Marquee;
            }
            else
            {
                prgProgress.Style = ProgressBarStyle.Blocks;
                prgProgress.Value = 0;
            }

            lblProgress.Text = text;
        }

        private IBiometricAdapter? GetAdapter(string ipAddress)
        {
            if (_adapters.TryGetValue(ipAddress, out var adapter) && adapter.IsConnected)
                return adapter;
            return null;
        }

        private async Task Step1_SendUsersAsync()
        {
            if (cmbDevices.SelectedItem is not DeviceItem item) return;
            var adapter = GetAdapter(item.IpAddress);
            if (adapter == null)
            {
                Log($"❌ {item.Name} is offline.");
                return;
            }

            btnStep1.Enabled = false;
            Log($"⬇️ Fetching users from web...");
            try
            {
                var users = await _cloud.GetUsersAsync(_schoolId);
                if (users.Count == 0)
                {
                    Log("⚠️ No users found on web (if this is unexpected, check the main dashboard log for a connection/server error).");
                    return;
                }

                users = users.Select(user =>
                {
                    var biometricId = user.BiometricId ?? string.Empty;
                    if (!string.IsNullOrWhiteSpace(_config.SchoolCode) &&
                        biometricId.StartsWith(_config.SchoolCode, StringComparison.OrdinalIgnoreCase))
                    {
                        biometricId = biometricId.Substring(_config.SchoolCode.Length);
                    }
                    biometricId = Regex.Replace(biometricId, "^\\D+", "");
                    return new UserRecord
                    {
                        BiometricId = biometricId,
                        Name = user.Name,
                        Role = user.Role
                    };
                }).ToList();

                var unsyncedUsers = _offlineQueue.FilterUnsyncedUsers(item.Serial, users);
                if (unsyncedUsers.Count == 0)
                {
                    Log($"✅ No new or changed users to push to {item.Name}.");
                    return;
                }

                Log($"📤 Pushing {unsyncedUsers.Count} users to {item.Name}...");
                int successCount = 0;
                int processed = 0;
                SetProgress(0, unsyncedUsers.Count, $"Pushing users to {item.Name}... 0/{unsyncedUsers.Count}");
                await Task.Run(() =>
                {
                    foreach (var user in unsyncedUsers)
                    {
                        var tmpl = new BiometricTemplate
                        {
                            BiometricId = user.BiometricId,
                            Name = user.Name,
                            Privilege = user.Role == "Teacher" ? 0 : 0,
                            FingerIndex = 0,
                            TemplateData = ""
                        };
                        if (adapter.UploadTemplate(tmpl)) successCount++;
                        processed++;
                        SetProgress(processed, unsyncedUsers.Count, $"Pushing users to {item.Name}... {processed}/{unsyncedUsers.Count}");
                        if (processed % 50 == 0)
                        {
                            Log($"⏳ Uploaded {processed}/{unsyncedUsers.Count} users to {item.Name}...");
                        }
                    }
                });

                // final progress log if not already reported
                if (processed % 50 != 0)
                {
                    Log($"⏳ Uploaded {processed}/{unsyncedUsers.Count} users to {item.Name}...");
                }

                if (successCount > 0)
                {
                    _offlineQueue.MarkUsersSynced(item.Serial, unsyncedUsers);
                }

                Log($"✅ Successfully sent {successCount}/{unsyncedUsers.Count} users to {item.Name}.");
                Log($"👉 Now physically enroll fingerprints on {item.Name}.");
            }
            catch (Exception ex)
            {
                Log($"❌ Error: {ex.Message}");
            }
            finally
            {
                btnStep1.Enabled = true;
                SetProgress(0, 0, "");
            }
        }

        private async Task Step2_UploadTemplatesAsync()
        {
            if (cmbDevices.SelectedItem is not DeviceItem item) return;
            var adapter = GetAdapter(item.IpAddress);
            if (adapter == null)
            {
                Log($"❌ {item.Name} is offline.");
                return;
            }

            btnStep2.Enabled = false;
            Log($"📥 Reading templates from {item.Name}...");
            SetProgress(0, 0, $"Reading templates from {item.Name}...");
            try
            {
                int lastLogged = 0;
                var templates = await Task.Run(() => adapter.ReadAllTemplates(processed =>
                {
                    SetProgress(0, 0, $"Reading {item.Name}... {processed} user record(s) read so far");
                    if (processed - lastLogged >= 25)
                    {
                        lastLogged = processed;
                        Log($"⏳ Read {processed} user record(s) so far from {item.Name}...");
                    }
                }));

                var deviceSerial = string.Empty;
                try { deviceSerial = adapter.GetSerialNumber(); } catch { }

                if (templates.Count == 0)
                {
                    if (!string.IsNullOrWhiteSpace(adapter.LastError))
                        Log($"❌ {adapter.LastError}");
                    Log($"⚠️ No templates found on device. Adapter: {adapter.Brand}, Serial: {deviceSerial}");
                    return;
                }

                Log($"📊 Read {templates.Count} template record(s) from {item.Name} (Serial: {deviceSerial}).");
                Log($"⬆️ Uploading {templates.Count} template(s) to web - existing users/templates on the web will be replaced...");

                const int chunkSize = 50;
                int uploaded = 0;
                bool allOk = true;
                SetProgress(0, templates.Count, $"Uploading to web... 0/{templates.Count}");
                for (int offset = 0; offset < templates.Count; offset += chunkSize)
                {
                    var chunk = templates.Skip(offset).Take(chunkSize).ToList();
                    bool ok = await _cloud.SyncTemplatesUpAsync(_schoolId, item.Serial, chunk);
                    if (!ok)
                    {
                        allOk = false;
                        Log($"❌ Failed to upload records {offset + 1}-{offset + chunk.Count} (check the main dashboard log for the server's error response).");
                        break;
                    }
                    uploaded += chunk.Count;
                    SetProgress(uploaded, templates.Count, $"Uploading to web... {uploaded}/{templates.Count}");
                }

                if (allOk)
                {
                    _offlineQueue.MarkTemplatesSynced(item.Serial, templates);
                    Log($"✅ Successfully uploaded {uploaded}/{templates.Count} template(s) to web DB (existing users/templates replaced).");
                }
            }
            catch (Exception ex)
            {
                Log($"❌ Error: {ex.Message}");
            }
            finally
            {
                btnStep2.Enabled = true;
                SetProgress(0, 0, "");
            }
        }

        private async Task Step3_DistributeTemplatesAsync()
        {
            if (cmbDevices.SelectedItem is not DeviceItem item) return;
            var adapter = GetAdapter(item.IpAddress);
            if (adapter == null)
            {
                Log($"❌ {item.Name} is offline.");
                return;
            }

            btnStep3.Enabled = false;
            Log($"⬇️ Downloading templates from web for {item.Name}...");
            SetProgress(0, 0, "Downloading templates from web...");
            try
            {
                var templates = await _cloud.SyncTemplatesDownAsync(_schoolId, item.Serial);

                if (templates.Count == 0)
                {
                    Log("⚠️ No templates found on web DB (if templates were uploaded earlier, check the main dashboard log for a connection/server error).");
                    return;
                }

                Log($"📤 Distributing {templates.Count} template(s) to {item.Name}...");

                // This device only tolerates ONE active command connection at a time - a
                // second simultaneous one doesn't get rejected, but every write on either
                // session then silently fails (confirmed via direct SDK testing: writes
                // return SDK error -2 while two sessions are open, succeed the instant only
                // one exists). The uploader subprocesses below open their own connection,
                // so release the main dashboard's connection first and restore it after.
                adapter.Disconnect();

                int succeeded = 0;
                int total = templates.Count;
                for (int i = 0; i < total; i++)
                {
                    var t = templates[i];
                    SetProgress(i, total, $"Distributing to {item.Name}... {i}/{total}");

                    if (string.IsNullOrWhiteSpace(t.TemplateData) || string.IsNullOrWhiteSpace(t.BiometricId))
                    {
                        Log($"⚠️ Skipping record {i + 1}/{total} due to empty data or biometric id.");
                        continue;
                    }

                    // Spawn a helper process (same exe, --upload-templates mode) instead of
                    // calling the SDK in-process, so a native COM fault on one bad template
                    // only kills that one child process rather than the whole agent.
                    // Run on a background thread - Process.WaitForExit/ReadToEnd below are
                    // synchronous and would otherwise freeze the UI for up to a minute.
                    const int maxAttempts = 3;
                    bool ok = false;
                    int lastExitCode = 0;
                    string lastErr = "";
                    for (int attempt = 1; attempt <= maxAttempts && !ok; attempt++)
                    {
                        var result = await Task.Run(() => RunUploaderProcess(item, t));
                        ok = result.success;
                        lastExitCode = result.exitCode;
                        lastErr = result.stdErr;

                        if (!string.IsNullOrWhiteSpace(lastErr))
                            Log($"Uploader errors for {item.Name} (#{i + 1}, attempt {attempt}): {lastErr}");

                        if (!ok && attempt < maxAttempts)
                        {
                            // A negative exit code means the child process crashed natively
                            // (e.g. 0xC0000005 access violation); a positive one means the
                            // SDK call itself returned false. Both have shown up as
                            // transient/reconnect-timing sensitive, so retry either way.
                            bool crashed = lastExitCode < 0;
                            Log($"⚠️ Record {i + 1} (BiometricId {t.BiometricId}) {(crashed ? "crashed" : "failed")} (exit {lastExitCode}), retrying (attempt {attempt + 1}/{maxAttempts})...");
                            await Task.Delay(crashed ? 1500 : 500);
                        }
                    }

                    if (ok)
                        succeeded++;
                    else
                        Log($"⚠️ Record {i + 1} (BiometricId {t.BiometricId}) failed after {maxAttempts} attempts, last exit code {lastExitCode}.");

                    // small pause between templates so the device isn't hammered with
                    // back-to-back connect/disconnect cycles
                    await Task.Delay(500);
                    SetProgress(i + 1, total, $"Distributing to {item.Name}... {i + 1}/{total}");
                }

                Log($"✅ {succeeded}/{total} distributed to {item.Name}.");
            }
            catch (Exception ex)
            {
                Log($"❌ Error: {ex.Message}");
            }
            finally
            {
                // Restore the main dashboard's own connection so attendance monitoring
                // resumes normally after handing the device to the uploader subprocesses.
                if (!adapter.IsConnected)
                    adapter.Connect(item.IpAddress, item.Port, item.MachineNumber);

                btnStep3.Enabled = true;
                SetProgress(0, 0, "");
            }
        }

        private (bool success, int exitCode, string stdErr) RunUploaderProcess(DeviceItem item, BiometricTemplate t)
        {
            var singleTemp = System.IO.Path.GetTempFileName();
            System.IO.File.WriteAllText(singleTemp, Newtonsoft.Json.JsonConvert.SerializeObject(new[] { t }));
            try
            {
                var psi = new System.Diagnostics.ProcessStartInfo
                {
                    FileName = Application.ExecutablePath,
                    Arguments = $"--upload-templates {item.IpAddress} {item.Port} {item.MachineNumber} \"{singleTemp}\"",
                    UseShellExecute = false,
                    RedirectStandardOutput = true,
                    RedirectStandardError = true,
                    CreateNoWindow = true
                };

                using var proc = System.Diagnostics.Process.Start(psi);
                if (proc == null)
                    return (false, -1, "Failed to start uploader process.");

                string serr = proc.StandardError.ReadToEnd();
                proc.StandardOutput.ReadToEnd();
                proc.WaitForExit(1000 * 60); // 1 minute timeout per template
                return (proc.ExitCode == 0, proc.ExitCode, serr.Trim());
            }
            finally
            {
                try { System.IO.File.Delete(singleTemp); } catch { }
            }
        }

        private class DeviceItem
        {
            public string Name { get; set; } = "";
            public string IpAddress { get; set; } = "";
            public string Serial { get; set; } = "";
            public int Port { get; set; } = 4370;
            public int MachineNumber { get; set; } = 1;
            public override string ToString() => $"{Name} ({IpAddress})";
        }
    }
}
