using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Text.RegularExpressions;
using System.Drawing;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;
using Microsoft.Win32;
using MaterialSkin;

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
        private System.Windows.Forms.Timer _connectivityTimer = null!;

        // App version constant
        public const string AppVersion = "1.0.0";
        public new const string CompanyName = "BATIGHOR SOFTWARE SYSTEMS LTD";

        // Active device adapters keyed by serial number
        private readonly Dictionary<string, IBiometricAdapter> _adapters = new();
        private readonly Dictionary<string, string> _deviceStatus = new();
        private readonly Dictionary<string, Label> _deviceStatusLabels = new();
        private readonly HashSet<string> _offlineDeviceNames = new();
        private NotificationAlertForm? _masterAlert = null;
        
        // --- Added for tray & reconnect logic ---
        private NotifyIcon _notifyIcon = null!;
        private Icon _appIcon = null!;
        private Icon? _trayIconConnected;
        private Icon? _trayIconDisconnected;
        private bool _allowVisible = false;
        private Dictionary<string, DateTime> _lastReconnectAttempt = new();
        private int _isSyncing;
        private int _isCheckingConnectivity;

        // Server (SaaS API) connection state - distinct from per-device local TCP
        // connection state (_deviceStatus). Null = not yet determined.
        private bool? _serverConnected = null;
        private DateTime _lastServerReauthAttempt = DateTime.MinValue;

        public MainDashboard()
        {
            _config       = AgentConfig.Load();
            _offlineQueue = new OfflineQueue();
            _cloud        = new CloudSyncManager(_config, _offlineQueue, LogMessage);
            AppTheme.Apply();
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

            try
            {
                var (connected, disconnected) = TrayIconComposer.BuildBadgedIcons(_appIcon ?? SystemIcons.Application);
                _trayIconConnected = connected;
                _trayIconDisconnected = disconnected;
            }
            catch { }

            _notifyIcon = new NotifyIcon
            {
                Icon = _trayIconDisconnected ?? _appIcon ?? SystemIcons.Application,
                Text = "Batighor EIMS — Server: connecting…",
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

            // Start invisible: minimized + no taskbar button, rather than intercepting
            // SetVisibleCore. Application.Run's Show() then goes through the completely
            // normal WinForms path (still guarantees CreateControl -> Load fires), so
            // startup logic (API auth/connect) always runs immediately at process start
            // instead of only once the user opens the dashboard from the tray icon.
            this.WindowState = FormWindowState.Minimized;
            this.ShowInTaskbar = false;
        }

        private void ShowDashboard()
        {
            _allowVisible = true;
            this.ShowInTaskbar = true;
            this.Show();
            this.WindowState = FormWindowState.Normal;
            this.Activate();
        }

        protected override void OnShown(EventArgs e)
        {
            base.OnShown(e);
            // Belt-and-suspenders: Minimized+ShowInTaskbar=false already keeps this
            // invisible on startup, this just guards against ever rendering a frame.
            if (!_allowVisible) this.Hide();
        }

        protected override void OnFormClosing(FormClosingEventArgs e)
        {
            if (e.CloseReason == CloseReason.UserClosing)
            {
                e.Cancel = true;
                this.Hide();
                this.ShowInTaskbar = false;
                _allowVisible = false;
            }
            base.OnFormClosing(e);
        }

        // ── Bootstrap ───────────────────────────────────────────────────────
        private void MainDashboard_Load(object sender, EventArgs e)
        {
            ApplyAutoStart();

            if (string.IsNullOrWhiteSpace(_config.SchoolCode) || string.IsNullOrWhiteSpace(_config.AgentToken))
            {
                var settingsForm = new SettingsForm(_config);
                if (settingsForm.ShowDialog() == DialogResult.OK)
                {
                    _config = settingsForm.Config;
                    _config.Save();
                    _cloud = new CloudSyncManager(_config, _offlineQueue, LogMessage);
                    ApplyAutoStart();
                }
            }

            // Fix: show company only once in lblCompany, version only in lblVersion
            lblSchool.Text  = _config.SchoolCode.Length > 0 ? _config.SchoolCode : "Not configured";
            lblVersion.Text = $"Version {AppVersion}";

            // Run startup connection logic immediately
            _ = RunStartupLogicAsync();
        }

        private async Task RunStartupLogicAsync()
        {
            // Give UI a tiny bit of time to initialize fully before logging
            await Task.Delay(100);

            LogMessage("Agent starting…");
            LogMessage($"📡 Connecting to: {_config.SaasApiUrl}");

            bool auth = await _cloud.AuthenticateAsync();
            if (auth)
            {
                LogMessage("✅ Authenticated with SaaS server.");
                bool hb = await _cloud.SendAgentHeartbeatAsync();
                SetServerConnected(hb);
                if (hb)
                    LogMessage("✅ Connect request with server successful");
                else
                    LogMessage("⚠️ Heartbeat failed – check API route on server.");
            }
            else
            {
                SetServerConnected(false);
                LogMessage("❌ Authentication failed. Check URL, School Code & Token.");
                LogMessage($"   URL used: {_config.SaasApiUrl}");
            }

            await ConnectAllDevicesAsync();
            StartSyncTimer();
            StartConnectivityTimer();

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
        private async Task ConnectAllDevicesAsync()
        {
            _adapters.Clear();
            panelDevices.Controls.Clear();
            _deviceStatusLabels.Clear();

            var deviceResults = await Task.Run(() =>
            {
                var results = new List<(DeviceConfig dev, string status, bool ok, string serial)>();

                foreach (var dev in _config.Devices)
                {
                    LogMessage($"⏳ Trying {dev.Name} at {dev.IpAddress}:{dev.Port} with device #{dev.MachineNumber}");
                    var adapter = BiometricAdapterFactory.Create("zkteco");
                    bool ok = adapter.Connect(dev.IpAddress, dev.Port, dev.MachineNumber);
                    string status = ok ? "online" : "offline";
                    string serial = ok ? adapter.GetSerialNumber() : string.Empty;
                    string error = ok ? string.Empty : adapter.LastError;

                    results.Add((dev, status, ok, serial));
                    _adapters[dev.IpAddress] = adapter;
                    _deviceStatus[dev.IpAddress] = status;
                    
                    if (!string.IsNullOrWhiteSpace(error))
                        LogMessage($"❌ {dev.Name} ({dev.IpAddress}:{dev.Port}) error: {error}");
                }

                return results;
            });

            foreach (var item in deviceResults)
            {
                if (!string.IsNullOrWhiteSpace(item.serial) && string.IsNullOrWhiteSpace(item.dev.SerialNumber))
                {
                    item.dev.SerialNumber = item.serial;
                }

                AddDeviceCard(item.dev, item.status);
                LogMessage($"{(item.ok ? "🟢" : "🔴")} {item.dev.Name} ({item.dev.IpAddress}) – {item.status.ToUpper()}");
            }

            if (deviceResults.Any(x => !string.IsNullOrWhiteSpace(x.serial) && string.IsNullOrWhiteSpace(x.dev.SerialNumber)))
            {
                _config.Save();
            }

            lblOnline.Text  = _deviceStatus.Count(x => x.Value == "online").ToString();
            lblOffline.Text = _deviceStatus.Count(x => x.Value == "offline").ToString();
        }

        private void AddDeviceCard(DeviceConfig dev, string status)
        {
            Color statusColor = status == "online" ? AppTheme.Success : AppTheme.Danger;

            var card = new Panel
            {
                Width  = 220,
                Height = 84,
                Margin = new Padding(8),
                BackColor = AppTheme.Surface
            };
            card.Paint += (s, e) =>
            {
                using var pen = new Pen(statusColor, 2);
                e.Graphics.DrawLine(pen, 0, 0, 0, card.Height); // left accent stripe, elevation-lite
            };

            card.Controls.Add(new Label
            {
                Text     = dev.Name,
                Location = new Point(14, 10),
                ForeColor = AppTheme.TextPrimary,
                Font     = new Font("Segoe UI", 10, FontStyle.Bold),
                AutoSize = true
            });
            var lblStatus = new Label
            {
                Text      = status.ToUpper(),
                Location  = new Point(14, 36),
                ForeColor = statusColor,
                Font      = new Font("Segoe UI", 9, FontStyle.Bold),
                AutoSize  = true
            };
            card.Controls.Add(lblStatus);
            card.Controls.Add(new Label
            {
                Text      = $"{dev.IpAddress}:{dev.Port}",
                Location  = new Point(14, 58),
                ForeColor = AppTheme.TextSecondary,
                Font      = new Font("Segoe UI", 8),
                AutoSize  = true
            });

            panelDevices.Controls.Add(card);
            _deviceStatusLabels[dev.IpAddress] = lblStatus;
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

        // ── Connectivity auto-refresh (fast, separate from the heavier attendance sync) ──
        private void StartConnectivityTimer()
        {
            _connectivityTimer = new System.Windows.Forms.Timer { Interval = 7000 };
            _connectivityTimer.Tick += async (object? _, EventArgs __) => await FastConnectivityCheckAsync();
            _connectivityTimer.Start();
        }

        /// <summary>Sends a server heartbeat and, if it fails, retries re-authentication at
        /// most once a minute (same cadence/throttle pattern as device reconnect below) so
        /// the agent recovers on its own from any cause of server disconnection - an expired
        /// token, a dropped Bearer header after Settings reloads the HttpClient, a transient
        /// server hiccup - instead of staying disconnected until the process is restarted.</summary>
        private async Task<bool> SendHeartbeatWithReauthAsync()
        {
            if (await _cloud.SendAgentHeartbeatAsync())
                return true;

            bool shouldReauth = (DateTime.Now - _lastServerReauthAttempt).TotalMinutes >= 1;
            if (!shouldReauth)
                return false;

            _lastServerReauthAttempt = DateTime.Now;
            LogMessage("⏳ Server heartbeat failed - attempting to reconnect...");
            if (await _cloud.AuthenticateAsync())
            {
                bool ok = await _cloud.SendAgentHeartbeatAsync();
                if (ok) LogMessage("🟢 Reconnected to server.");
                return ok;
            }
            return false;
        }

        private async Task FastConnectivityCheckAsync()
        {
            if (Interlocked.CompareExchange(ref _isCheckingConnectivity, 1, 0) != 0)
                return; // previous check still running, skip this tick

            try
            {
                // Reuse this same fast tick to refresh server (SaaS API) connectivity too,
                // so the header badge / tray icon stay on a comparably snappy cadence to
                // device status instead of only updating once per (slower) sync cycle.
                var serverCheckTask = SendHeartbeatWithReauthAsync();

                var statuses = await CheckDeviceConnectivityAsync();
                bool changed = false;
                foreach (var kv in statuses)
                {
                    if (!_deviceStatus.TryGetValue(kv.Key, out var prev) || prev != kv.Value)
                        changed = true;

                    _deviceStatus[kv.Key] = kv.Value;
                    UpdateDeviceCardStatus(kv.Key, kv.Value);
                }

                if (changed)
                {
                    lblOnline.Text  = _deviceStatus.Count(x => x.Value == "online").ToString();
                    lblOffline.Text = _deviceStatus.Count(x => x.Value == "offline").ToString();
                }

                SetServerConnected(await serverCheckTask);
            }
            finally
            {
                Interlocked.Exchange(ref _isCheckingConnectivity, 0);
            }
        }

        /// <summary>Cheap per-device liveness probe shared by the fast connectivity timer
        /// and the heavier attendance-sync cycle, so both agree on device state and share
        /// the same reconnect-attempt throttle (avoids hammering a persistently offline
        /// device with connect attempts every few seconds).</summary>
        private async Task<Dictionary<string, string>> CheckDeviceConnectivityAsync()
        {
            return await Task.Run(() =>
            {
                var statuses = new Dictionary<string, string>();

                foreach (var dev in _config.Devices)
                {
                    if (!_adapters.TryGetValue(dev.IpAddress, out var adapter))
                        continue;

                    bool alive = adapter.CheckConnection();
                    string status = alive ? "online" : "offline";

                    if (!alive)
                    {
                        bool shouldReconnect = !_lastReconnectAttempt.TryGetValue(dev.IpAddress, out var lastTry) ||
                            (DateTime.Now - lastTry).TotalMinutes >= 2;

                        if (shouldReconnect)
                        {
                            _lastReconnectAttempt[dev.IpAddress] = DateTime.Now;
                            LogMessage($"Attempting to reconnect {dev.Name}...");
                            bool reconnected = adapter.Connect(dev.IpAddress, dev.Port, dev.MachineNumber);
                            status = reconnected ? "online" : "offline";
                            if (reconnected)
                            {
                                CloseOfflineAlert(dev);
                                LogMessage($"🟢 {dev.Name} reconnected.");
                            }
                            else
                            {
                                ShowOfflineAlert(dev);
                            }
                        }
                    }

                    statuses[dev.IpAddress] = status;
                }

                return statuses;
            });
        }

        private async Task DoSyncCycle()
        {
            if (Interlocked.CompareExchange(ref _isSyncing, 1, 0) != 0)
            {
                LogMessage("⚠️ Previous sync still running, skipping this interval.");
                return;
            }

            try
            {
                // 0. Send Agent Heartbeat (silently to keep logs clean), retrying
                // re-authentication automatically if it fails (see SendHeartbeatWithReauthAsync).
                bool hbSuccess = await SendHeartbeatWithReauthAsync();
                SetServerConnected(hbSuccess);
                if (!hbSuccess) {
                    LogMessage("⚠️ Connection with server failed or offline");
                }

                // 1. Retry offline queue
                await _cloud.RetryOfflineQueueAsync();

                // 2. Connectivity + reconnect (shared with the fast connectivity timer)
                var connectivity = await CheckDeviceConnectivityAsync();

                var syncResults = await Task.Run(() =>
                {
                    var results = new List<(DeviceConfig dev, string status, List<PunchRecord> punches, string serialToSend, string hbSerial)>();

                    foreach (var dev in _config.Devices)
                    {
                        if (!_adapters.TryGetValue(dev.IpAddress, out var adapter))
                            continue;

                        string status = connectivity.TryGetValue(dev.IpAddress, out var st) ? st : "offline";

                        string serialToSend = string.IsNullOrWhiteSpace(dev.SerialNumber)
                            ? $"UNKNOWN-{dev.IpAddress.Replace(".", "")}"
                            : dev.SerialNumber;

                        var punches = new List<PunchRecord>();
                        if (status == "online")
                        {
                            if (string.IsNullOrWhiteSpace(dev.SerialNumber))
                            {
                                string sn = adapter.GetSerialNumber();
                                if (!string.IsNullOrWhiteSpace(sn))
                                {
                                    dev.SerialNumber = sn;
                                }
                            }

                            if (!string.IsNullOrWhiteSpace(dev.SerialNumber)
                                && !string.Equals(serialToSend, dev.SerialNumber, StringComparison.OrdinalIgnoreCase))
                            {
                                _offlineQueue.MigrateDeviceSerial(serialToSend, dev.SerialNumber);
                                serialToSend = dev.SerialNumber;
                            }

                            var allPunches = adapter.ReadNewAttendanceLogs();
                            punches = _offlineQueue.FilterNewPunches(serialToSend, allPunches);
                        }

                        string hbSerial = string.IsNullOrWhiteSpace(dev.SerialNumber)
                            ? $"UNKNOWN-{dev.IpAddress.Replace(".", "")}" 
                            : dev.SerialNumber;

                        results.Add((dev, status, punches, serialToSend, hbSerial));
                    }

                    return results;
                });

                foreach (var item in syncResults)
                {
                    _deviceStatus[item.dev.IpAddress] = item.status;
                    UpdateDeviceCardStatus(item.dev.IpAddress, item.status);

                    if (item.punches.Count > 0)
                    {
                        var distinctIds = item.punches.Select(p => p.BiometricId).Distinct().ToList();
                        LogMessage($"📥 {item.dev.Name}: {item.punches.Count} punch(es) read ({distinctIds.Count} distinct biometric IDs).");
                        if (distinctIds.Count <= 10)
                            LogMessage($"   Distinct IDs: {string.Join(", ", distinctIds)}");
                        
                        bool syncOk = await _cloud.SyncAttendanceAsync(GetSchoolId(), item.serialToSend, item.punches);
                        if (!syncOk)
                            LogMessage($"⚠️ {item.dev.Name}: sync queued for retry.");
                        else
                        {
                            LogMessage($"✅ {item.dev.Name}: attendance pushed.");
                            _offlineQueue.MarkPunchesAsSynced(item.serialToSend, item.punches);
                        }
                    }

                    await _cloud.SendHeartbeatAsync(GetSchoolId(), item.hbSerial, item.status, item.dev.IpAddress, item.dev.Name, item.dev.Location);
                }

                lblLastSync.Text = $"Last sync: {DateTime.Now:HH:mm:ss}";
                lblOnline.Text  = _deviceStatus.Count(x => x.Value == "online").ToString();
                lblOffline.Text = _deviceStatus.Count(x => x.Value == "offline").ToString();
                UpdatePendingCount();
            }
            finally
            {
                Interlocked.Exchange(ref _isSyncing, 0);
            }
        }

        private void UpdateDeviceCardStatus(string ipAddress, string status)
        {
            if (!_deviceStatusLabels.TryGetValue(ipAddress, out var lbl)) return;
            lbl.Text = status.ToUpper();
            lbl.ForeColor = status == "online" ? AppTheme.Success : AppTheme.Danger;
        }

        /// <summary>Updates server (SaaS API) connection state - distinct from per-device
        /// local TCP status - and reflects it in the dashboard header badge and the tray
        /// icon overlay so it's visible at a glance without opening the window.</summary>
        private void SetServerConnected(bool connected)
        {
            if (this.InvokeRequired) { this.Invoke(new Action(() => SetServerConnected(connected))); return; }

            bool changed = _serverConnected != connected;
            _serverConnected = connected;

            if (lblServerStatus != null)
            {
                lblServerStatus.Text = connected ? "🟢 Server: Connected" : "🔴 Server: Disconnected";
                lblServerStatus.ForeColor = connected ? AppTheme.Success : AppTheme.Danger;
            }

            if (changed && _notifyIcon != null)
            {
                var icon = connected ? _trayIconConnected : _trayIconDisconnected;
                if (icon != null) _notifyIcon.Icon = icon;
                _notifyIcon.Text = connected ? "Batighor EIMS — Server: Connected" : "Batighor EIMS — Server: Disconnected";
            }
        }

        private void ShowOfflineAlert(DeviceConfig dev)
        {
            if (this.InvokeRequired) { this.Invoke(new Action(() => ShowOfflineAlert(dev))); return; }
            if (_offlineDeviceNames.Add(dev.Name))
            {
                LogMessage($"🔴 {dev.Name} is OFFLINE!");
                RefreshMasterAlert();
            }
        }

        private void CloseOfflineAlert(DeviceConfig dev)
        {
            if (this.InvokeRequired) { this.Invoke(new Action(() => CloseOfflineAlert(dev))); return; }
            if (_offlineDeviceNames.Remove(dev.Name))
                RefreshMasterAlert();
        }

        private void RefreshMasterAlert()
        {
            // Close old alert safely
            if (_masterAlert != null && !_masterAlert.IsDisposed)
            {
                _masterAlert.FormClosed -= OnMasterAlertClosed;
                _masterAlert.Close();
                _masterAlert = null;
            }

            if (_offlineDeviceNames.Count == 0) return;

            // Build consolidated message
            string lines = string.Join("\n", _offlineDeviceNames.Select(n => "• " + n));
            string message = $"⚠️ Offline Devices ({_offlineDeviceNames.Count}):\n{lines}";
            _masterAlert = new NotificationAlertForm(message);
            _masterAlert.FormClosed += OnMasterAlertClosed;
            _masterAlert.Show();
        }

        private void OnMasterAlertClosed(object? s, EventArgs e) => _masterAlert = null;

        private int GetSchoolId() => 1; // Loaded from SaaS after auth in production

        private void UpdatePendingCount()
        {
            int pending = _offlineQueue.GetPending().Count;
            lblPending.Text = $"Offline Queue: {pending}";
            lblPending.ForeColor = pending > 0 ? AppTheme.Warning : AppTheme.Success;
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

        public bool PushUserToDevice(string deviceKey, UserRecord user)
        {
            // _adapters is keyed by IP address, but callers may pass a serial number or
            // an IP - resolve via the device config either way instead of relying on a
            // dictionary lookup that only matches for one of the two key shapes.
            var dev = _config.Devices.FirstOrDefault(d =>
                string.Equals(d.SerialNumber, deviceKey, StringComparison.OrdinalIgnoreCase) ||
                string.Equals(d.IpAddress, deviceKey, StringComparison.OrdinalIgnoreCase));

            IBiometricAdapter? adapter = dev != null && _adapters.TryGetValue(dev.IpAddress, out var found) ? found : null;

            if (adapter == null || !adapter.IsConnected)
                return false;

            var biometricId = user.BiometricId ?? string.Empty;
            // If the server stores IDs with a school-code prefix (e.g. JSS26001), strip it
            if (!string.IsNullOrWhiteSpace(_config.SchoolCode) &&
                biometricId.StartsWith(_config.SchoolCode, StringComparison.OrdinalIgnoreCase))
            {
                biometricId = biometricId.Substring(_config.SchoolCode.Length);
            }
            // Remove any remaining leading non-digit characters so device gets numeric ID like 26001
            biometricId = Regex.Replace(biometricId, "^\\D+", "");

            var template = new BiometricTemplate
            {
                BiometricId = biometricId,
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
            if (rtfLog.InvokeRequired)
            {
                rtfLog.Invoke(new Action(() => LogMessage(msg)));
            }
            else
            {
                string timeStr = $"[{DateTime.Now:HH:mm:ss}] ";
                rtfLog.AppendText(timeStr + msg + Environment.NewLine);
                rtfLog.SelectionStart = rtfLog.Text.Length;
                rtfLog.ScrollToCaret();
            }
        }

        // ── Dark Theme ───────────────────────────────────────────────────────
        private void ApplyDarkTheme()
        {
            BackColor = AppTheme.Background;
            ForeColor = AppTheme.TextPrimary;
        }

        // ── UI button handlers ───────────────────────────────────────────────
        private async void btnSettings_Click(object sender, EventArgs e)
        {
            var settingsForm = new SettingsForm(_config);
            if (settingsForm.ShowDialog() == DialogResult.OK)
            {
                _config = settingsForm.Config;
                _config.Save();

                // Replacing _cloud creates a fresh HttpClient with no Bearer token - the
                // old token from startup's AuthenticateAsync() is lost with it. Re-auth
                // immediately so the agent doesn't silently stop being able to reach the
                // server until the next full restart (this was the root cause of "saved
                // Settings with no changes -> server heartbeat fails forever").
                _cloud = new CloudSyncManager(_config, _offlineQueue, LogMessage);
                bool auth = await _cloud.AuthenticateAsync();
                SetServerConnected(auth && await _cloud.SendAgentHeartbeatAsync());

                ApplyAutoStart();
                await ConnectAllDevicesAsync();
                lblSchool.Text  = _config.SchoolCode.Length > 0 ? _config.SchoolCode : "Not configured";
            }
        }

        private async void btnSyncNow_Click(object sender, EventArgs e)
        {
            btnSyncNow.Enabled = false;
            await DoSyncCycle();
            btnSyncNow.Enabled = true;
        }

        private async void btnRefreshDevices_Click(object sender, EventArgs e)
        {
            await ConnectAllDevicesAsync();
        }

        private async void btnDevices_Click(object? sender, EventArgs e)
        {
            using var frm = new DeviceManagementForm(_config);
            frm.ShowDialog(this);
            if (frm.DevicesChanged)
            {
                _config.Save();
                await ConnectAllDevicesAsync();
            }
        }

        private void btnSyncManager_Click(object? sender, EventArgs e)
        {
            using var frm = new SyncManagerForm(_config, _cloud, _adapters, GetSchoolId());
            frm.ShowDialog(this);
        }

        private void btnBackupRestore_Click(object? sender, EventArgs e)
        {
            using var frm = new BackupRestoreForm(_config, _cloud, _adapters, _offlineQueue, GetSchoolId());
            frm.ShowDialog(this);
        }

        private void btnUsers_Click(object sender, EventArgs e)
        {
            var usersForm = new UsersEnrollmentForm(this, _config);
            usersForm.Show();
        }
    }
}
