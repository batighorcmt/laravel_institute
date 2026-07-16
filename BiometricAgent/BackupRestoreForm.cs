using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;
using MaterialSkin.Controls;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Backup & Restore — ZKTeco Time Attendance 5.0-style workflow: download all
    // data (fingers + card + face) from a device onto the computer so nothing is
    // lost if the device or PC fails, restore it to any device, and optionally
    // push it to the cloud.
    // ─────────────────────────────────────────────────────────────────────────
    public class BackupRestoreForm : Form
    {
        private readonly AgentConfig _config;
        private readonly CloudSyncManager _cloud;
        private readonly Dictionary<string, IBiometricAdapter> _adapters;
        private readonly OfflineQueue _store;
        private readonly int _schoolId;
        private readonly MainDashboard _dashboard;

        private ComboBox cmbSourceDevice = null!;
        private ComboBox cmbTargetDevice = null!;
        private MaterialButton btnDownload = null!;
        private MaterialButton btnUploadToCloud = null!;
        private MaterialButton btnDownloadFromCloud = null!;
        private MaterialButton btnRestore = null!;
        private MaterialButton btnRefreshBackups = null!;
        private DataGridView gridBackups = null!;
        private ProgressBar prgProgress = null!;
        private Label lblProgress = null!;
        private RichTextBox rtfLog = null!;

        public BackupRestoreForm(AgentConfig config, CloudSyncManager cloud, Dictionary<string, IBiometricAdapter> adapters, OfflineQueue store, int schoolId, MainDashboard dashboard)
        {
            _config = config;
            _cloud = cloud;
            _adapters = adapters;
            _store = store;
            _schoolId = schoolId;
            _dashboard = dashboard;

            AppTheme.Apply();
            BuildUI();
            PopulateDevices();
            RefreshBackupsGrid();
        }

        private void BuildUI()
        {
            Text = "Backup & Restore";
            Size = new Size(820, 640);
            StartPosition = FormStartPosition.CenterParent;
            BackColor = AppTheme.Background;
            ForeColor = AppTheme.TextPrimary;
            Font = new Font("Segoe UI", 9f);
            MinimumSize = new Size(700, 500);

            var lblTitle = new Label
            {
                Text = "🗄 Backup and Restore",
                Location = new Point(16, 14),
                Font = new Font("Segoe UI", 13, FontStyle.Bold),
                ForeColor = AppTheme.TextPrimary,
                AutoSize = true
            };
            Controls.Add(lblTitle);

            var lblHint = new Label
            {
                Text = "Download all fingerprints, card numbers and face data from a device so nothing is lost, and restore it to any device.",
                Location = new Point(16, 44),
                ForeColor = AppTheme.TextSecondary,
                AutoSize = true
            };
            Controls.Add(lblHint);

            var lblSource = new Label { Text = "Source device:", Location = new Point(16, 78), ForeColor = AppTheme.TextPrimary, AutoSize = true };
            Controls.Add(lblSource);
            cmbSourceDevice = new ComboBox { Location = new Point(120, 74), Width = 220, DropDownStyle = ComboBoxStyle.DropDownList };
            Controls.Add(cmbSourceDevice);

            btnDownload = CreateButton("Download All Data From Device", 360, 72);
            btnDownload.Click += async (s, e) => await DownloadFromDeviceAsync();
            Controls.Add(btnDownload);

            int rx = 16;
            btnUploadToCloud = CreateButton("Upload Selected To Cloud", rx, 114);
            btnUploadToCloud.Click += async (s, e) => await UploadSelectedToCloudAsync();
            Controls.Add(btnUploadToCloud);
            rx += btnUploadToCloud.Width + 8;

            btnDownloadFromCloud = CreateButton("Download From Cloud", rx, 114);
            btnDownloadFromCloud.Click += async (s, e) => await DownloadFromCloudAsync();
            Controls.Add(btnDownloadFromCloud);
            rx += btnDownloadFromCloud.Width + 8;

            btnRefreshBackups = CreateButton("Refresh List", rx, 114);
            btnRefreshBackups.Click += (s, e) => RefreshBackupsGrid();
            Controls.Add(btnRefreshBackups);

            var lblBackups = new Label
            {
                Text = "Local Backups (most recent first):",
                Location = new Point(16, 152),
                ForeColor = AppTheme.TextPrimary,
                AutoSize = true
            };
            Controls.Add(lblBackups);

            gridBackups = new DataGridView
            {
                Location = new Point(16, 174),
                Size = new Size(770, 218),
                Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right,
                BackgroundColor = AppTheme.Surface,
                ForeColor = AppTheme.TextPrimary,
                GridColor = AppTheme.SurfaceAlt,
                DefaultCellStyle = { BackColor = AppTheme.Surface, ForeColor = AppTheme.TextPrimary, SelectionBackColor = AppTheme.Primary },
                ColumnHeadersDefaultCellStyle = { BackColor = AppTheme.Primary, ForeColor = Color.White, Font = new Font("Segoe UI", 9, FontStyle.Bold) },
                BorderStyle = BorderStyle.None,
                AllowUserToAddRows = false,
                AllowUserToDeleteRows = false,
                RowHeadersVisible = false,
                SelectionMode = DataGridViewSelectionMode.FullRowSelect,
                MultiSelect = false,
                ReadOnly = true,
                AutoSizeColumnsMode = DataGridViewAutoSizeColumnsMode.Fill
            };
            gridBackups.Columns.Add(new DataGridViewTextBoxColumn { Name = "Id", HeaderText = "Batch #", FillWeight = 15 });
            gridBackups.Columns.Add(new DataGridViewTextBoxColumn { Name = "Device", HeaderText = "Device", FillWeight = 30 });
            gridBackups.Columns.Add(new DataGridViewTextBoxColumn { Name = "Users", HeaderText = "Users", FillWeight = 15 });
            gridBackups.Columns.Add(new DataGridViewTextBoxColumn { Name = "CapturedAt", HeaderText = "Captured At", FillWeight = 40 });
            Controls.Add(gridBackups);

            var lblTarget = new Label { Text = "Restore to device:", Location = new Point(16, 406), ForeColor = AppTheme.TextPrimary, AutoSize = true };
            Controls.Add(lblTarget);
            cmbTargetDevice = new ComboBox { Location = new Point(140, 402), Width = 220, DropDownStyle = ComboBoxStyle.DropDownList };
            Controls.Add(cmbTargetDevice);

            btnRestore = CreateButton("Restore Selected Backup To Device", 380, 398);
            btnRestore.Click += async (s, e) => await RestoreSelectedBatchAsync();
            Controls.Add(btnRestore);

            prgProgress = new ProgressBar
            {
                Location = new Point(16, 442),
                Width = 770,
                Height = 18,
                Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right,
                Style = ProgressBarStyle.Blocks,
                MarqueeAnimationSpeed = 30
            };
            Controls.Add(prgProgress);

            lblProgress = new Label
            {
                Text = "",
                Location = new Point(16, 463),
                Width = 770,
                Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right,
                ForeColor = AppTheme.TextSecondary,
                Font = new Font("Segoe UI", 8)
            };
            Controls.Add(lblProgress);

            rtfLog = new RichTextBox
            {
                Location = new Point(16, 486),
                Size = new Size(770, 110),
                Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right | AnchorStyles.Bottom,
                BackColor = AppTheme.Surface,
                ForeColor = AppTheme.TextSecondary,
                ReadOnly = true,
                BorderStyle = BorderStyle.None
            };
            Controls.Add(rtfLog);
        }

        private MaterialButton CreateButton(string text, int x, int y)
        {
            return new MaterialButton
            {
                Text = text,
                Location = new Point(x, y - 2),
                AutoSize = true,
                Height = 32,
                Type = MaterialButton.MaterialButtonType.Contained,
                UseAccentColor = false,
                HighEmphasis = true,
                Cursor = Cursors.Hand
            };
        }

        private void PopulateDevices()
        {
            cmbSourceDevice.Items.Clear();
            cmbTargetDevice.Items.Clear();
            foreach (var dev in _config.Devices)
            {
                if (string.IsNullOrWhiteSpace(dev.IpAddress)) continue;
                cmbSourceDevice.Items.Add(dev);
                cmbTargetDevice.Items.Add(dev);
            }
            cmbSourceDevice.DisplayMember = "Name";
            cmbTargetDevice.DisplayMember = "Name";
            if (cmbSourceDevice.Items.Count > 0) cmbSourceDevice.SelectedIndex = 0;
            if (cmbTargetDevice.Items.Count > 0) cmbTargetDevice.SelectedIndex = 0;
        }

        private void Log(string msg)
        {
            if (rtfLog.InvokeRequired) { rtfLog.Invoke(new Action(() => Log(msg))); return; }
            rtfLog.AppendText($"[{DateTime.Now:HH:mm:ss}] {msg}\n");
            rtfLog.SelectionStart = rtfLog.Text.Length;
            rtfLog.ScrollToCaret();
        }

        private void SetProgress(int value, int max, string text)
        {
            if (prgProgress.InvokeRequired) { prgProgress.Invoke(new Action(() => SetProgress(value, max, text))); return; }
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

        private void RefreshBackupsGrid()
        {
            gridBackups.Rows.Clear();
            foreach (var batch in _store.ListBackupBatches())
            {
                int idx = gridBackups.Rows.Add(batch.Id, $"{batch.DeviceName} ({batch.DeviceSerial})", batch.UserCount, batch.CapturedAt);
                gridBackups.Rows[idx].Tag = batch;
            }
        }

        private BackupBatchSummary? GetSelectedBatch()
        {
            var row = gridBackups.CurrentRow;
            return row?.Tag as BackupBatchSummary;
        }

        // ── Download All Data From Device ───────────────────────────────────
        private async Task DownloadFromDeviceAsync()
        {
            if (cmbSourceDevice.SelectedItem is not DeviceConfig dev)
            {
                Log("⚠️ Select a source device first.");
                return;
            }
            if (!_adapters.TryGetValue(dev.IpAddress, out var adapter) || !adapter.IsConnected)
            {
                Log($"❌ {dev.Name} is offline. Reconnect it first from the main dashboard.");
                return;
            }

            btnDownload.Enabled = false;
            Log($"⏳ Reading all data from {dev.Name}...");
            SetProgress(0, 0, $"Reading {dev.Name}...");

            // Keep the main dashboard's background connectivity timer from probing/reconnecting
            // this device while the read is in progress - same rationale as the restore path below.
            _dashboard.BeginExclusiveDeviceAccess(dev.IpAddress);
            try
            {
                int lastLogged = 0;
                var records = await Task.Run(() => adapter.ReadFullBackup(n =>
                {
                    SetProgress(0, 0, $"Reading {dev.Name}... {n} user(s) so far");
                    if (n - lastLogged >= 50)
                    {
                        lastLogged = n;
                        Log($"⏳ Read {n} user(s) so far from {dev.Name}...");
                    }
                }));

                if (records.Count == 0)
                {
                    Log($"⚠️ No users found on {dev.Name}." + (string.IsNullOrWhiteSpace(adapter.LastError) ? "" : $" {adapter.LastError}"));
                    return;
                }

                int withFingers = records.Count(r => r.Fingers.Count > 0);
                int withCard = records.Count(r => !string.IsNullOrWhiteSpace(r.CardNumber));
                int withFace = records.Count(r => !string.IsNullOrWhiteSpace(r.FaceData));
                Log($"📊 Read {records.Count} user(s): {withFingers} with fingerprint(s), {withCard} with card, {withFace} with face.");

                string serial = string.IsNullOrWhiteSpace(dev.SerialNumber) ? adapter.GetSerialNumber() : dev.SerialNumber;
                long batchId = await Task.Run(() => _store.SaveBackupBatch(serial, dev.Name, records));
                Log($"💾 Saved locally as backup batch #{batchId} (survives even if the device or this PC is later lost).");

                string jsonPath = await Task.Run(() => OfflineQueue.ExportBackupJson(serial, records));
                Log($"📄 Exported JSON copy to {jsonPath}");

                RefreshBackupsGrid();
            }
            catch (Exception ex)
            {
                Log($"❌ Error: {ex.Message}");
            }
            finally
            {
                _dashboard.EndExclusiveDeviceAccess(dev.IpAddress);
                btnDownload.Enabled = true;
                SetProgress(0, 0, "");
            }
        }

        // ── Cloud upload/download ────────────────────────────────────────────
        private async Task UploadSelectedToCloudAsync()
        {
            var batch = GetSelectedBatch();
            if (batch == null) { Log("⚠️ Select a backup batch first."); return; }

            btnUploadToCloud.Enabled = false;
            Log($"☁ Uploading backup batch #{batch.Id} ({batch.UserCount} user(s)) to the cloud...");
            SetProgress(0, 0, "Uploading to cloud...");
            try
            {
                var records = await Task.Run(() => _store.GetBackupBatchRecords(batch.Id));
                bool ok = await _cloud.UploadBackupAsync(_schoolId, batch.DeviceSerial, records);
                Log(ok ? "✅ Backup uploaded to cloud." : "❌ Cloud upload failed (see main dashboard log for details).");
            }
            catch (Exception ex)
            {
                Log($"❌ Error: {ex.Message}");
            }
            finally
            {
                btnUploadToCloud.Enabled = true;
                SetProgress(0, 0, "");
            }
        }

        private async Task DownloadFromCloudAsync()
        {
            if (cmbSourceDevice.SelectedItem is not DeviceConfig dev)
            {
                Log("⚠️ Select a device first (used to tag the downloaded batch).");
                return;
            }

            btnDownloadFromCloud.Enabled = false;
            Log($"☁ Downloading backup for {dev.Name} from the cloud...");
            SetProgress(0, 0, "Downloading from cloud...");
            try
            {
                var records = await _cloud.DownloadBackupAsync(_schoolId, dev.SerialNumber);
                if (records.Count == 0)
                {
                    Log("⚠️ No backup found on the cloud for this device's school (see main dashboard log for a connection/server error, if any).");
                    return;
                }

                long batchId = await Task.Run(() => _store.SaveBackupBatch(dev.SerialNumber, dev.Name, records));
                Log($"✅ Downloaded {records.Count} user(s) from cloud, saved locally as batch #{batchId}.");
                RefreshBackupsGrid();
            }
            catch (Exception ex)
            {
                Log($"❌ Error: {ex.Message}");
            }
            finally
            {
                btnDownloadFromCloud.Enabled = true;
                SetProgress(0, 0, "");
            }
        }

        // ── Restore to device ────────────────────────────────────────────────
        private async Task RestoreSelectedBatchAsync()
        {
            var batch = GetSelectedBatch();
            if (batch == null) { Log("⚠️ Select a backup batch first."); return; }
            if (cmbTargetDevice.SelectedItem is not DeviceConfig target)
            {
                Log("⚠️ Select a target device first.");
                return;
            }

            var confirm = MessageBox.Show(
                $"Restore {batch.UserCount} user(s) from batch #{batch.Id} to {target.Name} ({target.IpAddress})?",
                "Confirm Restore", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (confirm != DialogResult.Yes) return;

            btnRestore.Enabled = false;
            var records = await Task.Run(() => _store.GetBackupBatchRecords(batch.Id));
            Log($"📤 Restoring {records.Count} user(s) to {target.Name}...");

            // Release the main dashboard's own connection first, same rationale as
            // SyncManagerForm's distribute step - this device only tolerates one active
            // command connection, and the restore subprocesses below open their own.
            // Also tell the dashboard's background connectivity timer to leave this device
            // alone for now - otherwise it sees the disconnect as "went offline" and
            // reconnects it a few seconds later, creating a second competing session that
            // makes every write on both sessions fail with SDK error -2.
            _dashboard.BeginExclusiveDeviceAccess(target.IpAddress);
            IBiometricAdapter? liveAdapter = _adapters.TryGetValue(target.IpAddress, out var a) ? a : null;
            liveAdapter?.Disconnect();

            int succeeded = 0;
            try
            {
                for (int i = 0; i < records.Count; i++)
                {
                    var record = records[i];
                    SetProgress(i, records.Count, $"Restoring to {target.Name}... {i}/{records.Count}");

                    const int maxAttempts = 3;
                    bool ok = false;
                    string lastErr = "";
                    for (int attempt = 1; attempt <= maxAttempts && !ok; attempt++)
                    {
                        var result = await Task.Run(() => RunRestoreProcess(target, record));
                        ok = result.success;
                        lastErr = result.stdErr;
                        if (!ok && attempt < maxAttempts)
                        {
                            Log($"⚠️ {record.BiometricId} failed (attempt {attempt}), retrying...");
                            await Task.Delay(500);
                        }
                    }

                    if (ok) succeeded++;
                    else Log($"⚠️ {record.BiometricId} failed after {maxAttempts} attempts: {lastErr}");

                    await Task.Delay(300);
                    SetProgress(i + 1, records.Count, $"Restoring to {target.Name}... {i + 1}/{records.Count}");
                }

                Log($"✅ {succeeded}/{records.Count} restored to {target.Name}.");
            }
            finally
            {
                if (liveAdapter != null && !liveAdapter.IsConnected)
                    liveAdapter.Connect(target.IpAddress, target.Port, target.MachineNumber);

                // Only now hand the device back to the background connectivity timer -
                // releasing this before our own reconnect above would let the timer race us.
                _dashboard.EndExclusiveDeviceAccess(target.IpAddress);

                btnRestore.Enabled = true;
                SetProgress(0, 0, "");
            }
        }

        private (bool success, string stdErr) RunRestoreProcess(DeviceConfig target, UserBackupRecord record)
        {
            var tempPath = System.IO.Path.GetTempFileName();
            System.IO.File.WriteAllText(tempPath, Newtonsoft.Json.JsonConvert.SerializeObject(record));
            try
            {
                var psi = new System.Diagnostics.ProcessStartInfo
                {
                    FileName = Application.ExecutablePath,
                    Arguments = $"--restore-backup {target.IpAddress} {target.Port} {target.MachineNumber} \"{tempPath}\"",
                    UseShellExecute = false,
                    RedirectStandardOutput = true,
                    RedirectStandardError = true,
                    CreateNoWindow = true
                };

                using var proc = System.Diagnostics.Process.Start(psi);
                if (proc == null) return (false, "Failed to start restore process.");

                string serr = proc.StandardError.ReadToEnd();
                proc.StandardOutput.ReadToEnd();
                proc.WaitForExit(1000 * 60);
                return (proc.ExitCode == 0, serr.Trim());
            }
            finally
            {
                try { System.IO.File.Delete(tempPath); } catch { }
            }
        }
    }
}
