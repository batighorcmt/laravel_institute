using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Drawing;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;
using Microsoft.Win32;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Main Agent Dashboard (WinForms)
    // ─────────────────────────────────────────────────────────────────────────
    public partial class MainDashboard : Form
    {
        private AgentConfig _config;
        private OfflineQueue _offlineQueue;
        private CloudSyncManager _cloud;
        private System.Windows.Forms.Timer _syncTimer = null!;

        // App version constant
        public const string AppVersion = "1.0.0";
        public new const string CompanyName = "BATIGHOR SOFTWARE SYSTEMS LTD";

        // Active device adapters keyed by serial number
        private readonly Dictionary<string, IBiometricAdapter> _adapters = new();
        private readonly Dictionary<string, string> _deviceStatus = new();
        private readonly Dictionary<string, NotificationAlertForm> _activeAlerts = new();
        
        // --- Added for tray & reconnect logic ---
        private NotifyIcon _notifyIcon = null!;
        private Icon _appIcon = null!;
        private bool _allowVisible = false;
        private Dictionary<string, DateTime> _lastReconnectAttempt = new();

        public MainDashboard()
        {
            _config       = AgentConfig.Load();
            _offlineQueue = new OfflineQueue();
            _cloud        = new CloudSyncManager(_config, _offlineQueue);
            InitializeComponent();
            ApplyDarkTheme();

            // Load Icon and Setup Tray
            try
            {
                string iconPath = System.IO.Path.Combine(Application.StartupPath, "app_icon.ico");
                if (System.IO.File.Exists(iconPath))
                {
                    _appIcon = new Icon(iconPath);
                    this.Icon = _appIcon;
                }
            }
            catch { }

            _notifyIcon = new NotifyIcon
            {
                Icon = _appIcon ?? SystemIcons.Application,
                Text = "Batighor EIMS",
                Visible = true
            };
            
            _notifyIcon.DoubleClick += (s, e) => ShowDashboard();

            var ctxMenu = new ContextMenuStrip();
            ctxMenu.Items.Add("Show Dashboard", null, (s, e) => ShowDashboard());
            ctxMenu.Items.Add("Exit", null, (s, e) => {
                _notifyIcon.Visible = false;
                Environment.Exit(0);
            });
            _notifyIcon.ContextMenuStrip = ctxMenu;
        }

        private void ShowDashboard()
        {
            _allowVisible = true;
            this.Show();
            this.WindowState = FormWindowState.Normal;
        }

        protected override void SetVisibleCore(bool value)
        {
            if (!_allowVisible)
            {
                value = false;
                if (!IsHandleCreated) CreateHandle();
            }
            base.SetVisibleCore(value);
        }

        protected override void OnFormClosing(FormClosingEventArgs e)
        {
            if (e.CloseReason == CloseReason.UserClosing)
            {
                e.Cancel = true;
                this.Hide();
                _allowVisible = false;
            }
            base.OnFormClosing(e);
        }

        // ── Bootstrap ───────────────────────────────────────────────────────
        private async void MainDashboard_Load(object sender, EventArgs e)
        {
            ApplyAutoStart();

            if (string.IsNullOrWhiteSpace(_config.SchoolCode) || string.IsNullOrWhiteSpace(_config.AgentToken))
            {
                LogMessage("Configuration missing. Please configure first.");
                var settingsForm = new SettingsForm(_config);
                if (settingsForm.ShowDialog() == DialogResult.OK)
                {
                    _config = settingsForm.Config;
                    _config.Save();
                    _cloud = new CloudSyncManager(_config, _offlineQueue);
                    ApplyAutoStart();
                }
            }

            lblSchool.Text  = _config.SchoolCode.Length > 0 ? _config.SchoolCode : "Not configured";
            lblVersion.Text = $"{CompanyName}  |  Version {AppVersion}";

            LogMessage("Agent starting…");
            LogMessage($"📡 Connecting to: {_config.SaasApiUrl}");

            bool auth = await _cloud.AuthenticateAsync();
            if (auth)
            {
                LogMessage("✅ Authenticated with SaaS server.");
                // Immediately send heartbeat so web dashboard shows online right away
                bool hb = await _cloud.SendAgentHeartbeatAsync();
                if (hb)
                    LogMessage("Connect request with server successful");
                else
                    LogMessage("⚠️ Heartbeat failed – check API route on server.");
            }
            else
            {
                LogMessage("⚠️  Authentication failed. Check URL, School Code & Token.");
                LogMessage($"   URL used: {_config.SaasApiUrl}");
            }

            ConnectAllDevices();
            StartSyncTimer();

            // Check for updates in background (non-blocking)
            _ = CheckForUpdateAsync();
        }

        private void ApplyAutoStart()
        {
            try
            {
                using RegistryKey? rk = Registry.CurrentUser.OpenSubKey(
                    "SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Run", true);
                if (rk == null) return;
                if (_config.AutoStart)
                    rk.SetValue("BiometricAgent", Application.ExecutablePath);
                else
                    rk.DeleteValue("BiometricAgent", false);
            }
            catch (Exception ex)
            {
                LogMessage("Auto-start setup failed: " + ex.Message);
            }
        }

        // ── Device management ───────────────────────────────────────────────
        private void ConnectAllDevices()
        {
            _adapters.Clear();
            panelDevices.Controls.Clear();

            foreach (var dev in _config.Devices)
            {
                var adapter = BiometricAdapterFactory.Create("zkteco");
                bool ok     = adapter.Connect(dev.IpAddress, dev.Port);
                string status = ok ? "online" : "offline";

                _adapters[dev.SerialNumber] = adapter;
                _deviceStatus[dev.SerialNumber] = status;

                AddDeviceCard(dev, status);
                LogMessage($"{(ok ? "🟢" : "🔴")} {dev.Name} ({dev.IpAddress}) – {status.ToUpper()}");
            }

            lblOnline.Text  = _deviceStatus.Count(x => x.Value == "online").ToString();
            lblOffline.Text = _deviceStatus.Count(x => x.Value == "offline").ToString();
        }

        private void AddDeviceCard(DeviceConfig dev, string status)
        {
            Color statusColor = status == "online" ? Color.FromArgb(52, 211, 153) : Color.FromArgb(248, 113, 113);

            var card = new Panel
            {
                Width  = 220,
                Height = 80,
                Margin = new Padding(8),
                BackColor = Color.FromArgb(30, 30, 46)
            };

            card.Controls.Add(new Label
            {
                Text     = dev.Name,
                Location = new Point(10, 10),
                ForeColor = Color.White,
                Font     = new Font("Segoe UI", 10, FontStyle.Bold),
                AutoSize = true
            });
            card.Controls.Add(new Label
            {
                Text      = status.ToUpper(),
                Location  = new Point(10, 34),
                ForeColor = statusColor,
                Font      = new Font("Segoe UI", 9),
                AutoSize  = true
            });
            card.Controls.Add(new Label
            {
                Text      = $"{dev.IpAddress}:{dev.Port}",
                Location  = new Point(10, 56),
                ForeColor = Color.Gray,
                Font      = new Font("Segoe UI", 8),
                AutoSize  = true
            });

            panelDevices.Controls.Add(card);
        }

        // ── Sync timer ───────────────────────────────────────────────────────
        private void StartSyncTimer()
        {
            _syncTimer = new System.Windows.Forms.Timer
            {
                Interval = _config.SyncIntervalSeconds * 1000
            };
            _syncTimer.Tick += async (object? _, EventArgs __) => await DoSyncCycle();
            _syncTimer.Start();
            LogMessage($"⏱  Sync every {_config.SyncIntervalSeconds}s started.");
        }

        private async Task DoSyncCycle()
        {
            // 0. Send Agent Heartbeat
            bool hbSuccess = await _cloud.SendAgentHeartbeatAsync();
            if (hbSuccess) {
                LogMessage("Connect request with server successful");
            } else {
                LogMessage("⚠️ Connection with server failed or offline");
            }

            // 1. Retry offline queue
            await _cloud.RetryOfflineQueueAsync();

            // 2. Poll each connected device for new punches
            foreach (var dev in _config.Devices)
            {
                if (!_adapters.TryGetValue(dev.SerialNumber, out var adapter))
                    continue;

                string status = "online";
                if (!adapter.IsConnected)
                {
                    status = "offline";
                    bool shouldReconnect = false;
                    
                    if (!_lastReconnectAttempt.TryGetValue(dev.SerialNumber, out var lastTry) ||
                        (DateTime.Now - lastTry).TotalMinutes >= 2)
                    {
                        shouldReconnect = true;
                    }

                    if (shouldReconnect)
                    {
                        _lastReconnectAttempt[dev.SerialNumber] = DateTime.Now;
                        LogMessage($"Attempting to reconnect {dev.Name}...");
                        bool reconnected = adapter.Connect(dev.IpAddress, dev.Port);
                        
                        if (!reconnected)
                        {
                            ShowOfflineAlert(dev);
                        }
                        else
                        {
                            status = "online";
                            CloseOfflineAlert(dev);
                            LogMessage($"🟢 {dev.Name} reconnected.");
                        }
                    }
                }

                if (status == "online")
                {
                    var punches = adapter.ReadNewAttendanceLogs();
                    if (punches.Count > 0)
                    {
                        LogMessage($"📥 {dev.Name}: {punches.Count} punch(es) read.");
                        await _cloud.SyncAttendanceAsync(GetSchoolId(), dev.SerialNumber, punches);
                    }
                }

                // Send heartbeat
                await _cloud.SendHeartbeatAsync(GetSchoolId(), dev.SerialNumber, status, dev.IpAddress);
            }

            lblLastSync.Text = $"Last sync: {DateTime.Now:HH:mm:ss}";
            UpdatePendingCount();
        }

        private void ShowOfflineAlert(DeviceConfig dev)
        {
            if (_activeAlerts.ContainsKey(dev.SerialNumber)) return;

            if (this.InvokeRequired)
            {
                this.Invoke(new Action(() => ShowOfflineAlert(dev)));
                return;
            }

            var alert = new NotificationAlertForm($"Alert: Device '{dev.Name}' ({dev.IpAddress}) is offline!");
            alert.FormClosed += (s, e) => _activeAlerts.Remove(dev.SerialNumber);
            alert.Show();
            _activeAlerts[dev.SerialNumber] = alert;
            LogMessage($"🔴 {dev.Name} is OFFLINE!");
        }

        private void CloseOfflineAlert(DeviceConfig dev)
        {
            if (_activeAlerts.TryGetValue(dev.SerialNumber, out var alert))
            {
                if (this.InvokeRequired)
                {
                    this.Invoke(new Action(() => CloseOfflineAlert(dev)));
                    return;
                }
                alert.Close();
                _activeAlerts.Remove(dev.SerialNumber);
            }
        }

        private int GetSchoolId() => 1; // Loaded from SaaS after auth in production

        private void UpdatePendingCount()
        {
            int pending = _offlineQueue.GetPending().Count;
            lblPending.Text = $"Offline Queue: {pending}";
            lblPending.ForeColor = pending > 0 ? Color.FromArgb(251, 191, 36) : Color.FromArgb(52, 211, 153);
        }

        // ── Auto-Update Checker ─────────────────────────────────────────────
        private async Task CheckForUpdateAsync()
        {
            var updateInfo = await _cloud.CheckForUpdateAsync();
            if (updateInfo == null) return;

            var current = new Version(AppVersion);
            Version latest;
            if (!Version.TryParse(updateInfo.LatestVersion, out latest!)) return;

            if (latest > current)
            {
                string msg = $"নতুন আপডেট পাওয়া গেছে!\n\n" +
                             $"নতুন ভার্সন: {updateInfo.LatestVersion}\n" +
                             $"বর্তমান ভার্সন: {AppVersion}\n\n" +
                             $"{updateInfo.ReleaseNotes}\n\n" +
                             $"এখনই ডাউনলোড করবেন?";

                if (InvokeRequired)
                {
                    Invoke(() => ShowUpdateDialog(updateInfo, msg));
                }
                else
                {
                    ShowUpdateDialog(updateInfo, msg);
                }
            }
        }

        private void ShowUpdateDialog(UpdateInfo updateInfo, string msg)
        {
            var result = MessageBox.Show(msg, "আপডেট পাওয়া গেছে!",
                MessageBoxButtons.YesNo, MessageBoxIcon.Information);

            if (result == DialogResult.Yes)
            {
                try { Process.Start(new ProcessStartInfo(updateInfo.DownloadUrl) { UseShellExecute = true }); }
                catch (Exception ex) { MessageBox.Show("ডাউনলোড খুলতে সমস্যা: " + ex.Message); }
            }
        }

        // ── Users & Enrollment ──────────────────────────────────────────────
        public async Task<List<UserRecord>> LoadUsersFromCloudAsync()
        {
            return await _cloud.GetUsersAsync(GetSchoolId());
        }

        public bool PushUserToDevice(string serialNumber, UserRecord user)
        {
            if (!_adapters.TryGetValue(serialNumber, out var adapter) || !adapter.IsConnected)
                return false;

            var template = new BiometricTemplate
            {
                BiometricId = user.BiometricId,
                Name = user.Name,
                FingerIndex = 0,
                TemplateData = "",   // Empty - user created, fingerprint to be enrolled locally
                Privilege = 0
            };
            return adapter.UploadTemplate(template);
        }

        // ── Logging ─────────────────────────────────────────────────────────
        private void LogMessage(string msg)
        {
            if (lstLog.InvokeRequired)
                lstLog.Invoke(() => LogMessage(msg));
            else
                lstLog.Items.Insert(0, $"[{DateTime.Now:HH:mm:ss}] {msg}");
        }

        // ── Dark Theme ───────────────────────────────────────────────────────
        private void ApplyDarkTheme()
        {
            BackColor = Color.FromArgb(17, 17, 27);
            ForeColor = Color.White;
        }

        // ── UI button handlers ───────────────────────────────────────────────
        private void btnSettings_Click(object sender, EventArgs e)
        {
            var settingsForm = new SettingsForm(_config);
            if (settingsForm.ShowDialog() == DialogResult.OK)
            {
                _config = settingsForm.Config;
                _config.Save();
                _cloud = new CloudSyncManager(_config, _offlineQueue);
                ApplyAutoStart();
                ConnectAllDevices();
                lblSchool.Text  = _config.SchoolCode.Length > 0 ? _config.SchoolCode : "Not configured";
            }
        }

        private async void btnSyncNow_Click(object sender, EventArgs e)
        {
            btnSyncNow.Enabled = false;
            await DoSyncCycle();
            btnSyncNow.Enabled = true;
        }

        private void btnRefreshDevices_Click(object sender, EventArgs e)
        {
            ConnectAllDevices();
        }

        private async void btnUploadTemplates_Click(object sender, EventArgs e)
        {
            btnUploadTemplates.Enabled = false;
            LogMessage("↑ Starting Template Upload...");

            foreach (var dev in _config.Devices)
            {
                if (_adapters.TryGetValue(dev.SerialNumber, out var adapter) && adapter.IsConnected)
                {
                    LogMessage($"Reading templates from {dev.Name}...");
                    var templates = adapter.ReadAllTemplates();
                    if (templates.Count > 0)
                    {
                        bool success = await _cloud.SyncTemplatesUpAsync(GetSchoolId(), dev.SerialNumber, templates);
                        LogMessage(success ? $"✅ {dev.Name}: Uploaded {templates.Count} templates." : $"❌ {dev.Name}: Failed to upload templates.");
                    }
                    else
                    {
                        LogMessage($"ℹ️ {dev.Name}: No templates found.");
                    }
                }
            }

            LogMessage("↑ Template Upload Finished.");
            btnUploadTemplates.Enabled = true;
        }

        private async void btnDownloadTemplates_Click(object sender, EventArgs e)
        {
            btnDownloadTemplates.Enabled = false;
            LogMessage("↓ Starting Template Download...");

            foreach (var dev in _config.Devices)
            {
                if (_adapters.TryGetValue(dev.SerialNumber, out var adapter) && adapter.IsConnected)
                {
                    LogMessage($"Fetching templates for {dev.Name}...");
                    var templates = await _cloud.SyncTemplatesDownAsync(GetSchoolId(), dev.SerialNumber);
                    if (templates.Count > 0)
                    {
                        int successCount = 0;
                        foreach (var tpl in templates)
                        {
                            if (adapter.UploadTemplate(tpl)) successCount++;
                        }
                        LogMessage($"✅ {dev.Name}: Downloaded & Applied {successCount}/{templates.Count} templates.");
                    }
                    else
                    {
                        LogMessage($"ℹ️ {dev.Name}: No new templates to download.");
                    }
                }
            }

            LogMessage("↓ Template Download Finished.");
            btnDownloadTemplates.Enabled = true;
        }

        private void btnUsers_Click(object sender, EventArgs e)
        {
            var usersForm = new UsersEnrollmentForm(this, _config);
            usersForm.Show();
        }
    }
}
