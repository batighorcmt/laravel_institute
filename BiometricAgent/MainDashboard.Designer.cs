using System;
using System.Drawing;
using System.Windows.Forms;
using MaterialSkin.Controls;

namespace BiometricAgent
{
    // Designer-generated controls defined manually (avoids .Designer.cs dependency)
    public partial class MainDashboard
    {
        private System.ComponentModel.IContainer components = null!;

        // Header labels
        private Label lblTitle = null!;
        private Label lblVersion = null!;
        private Label lblSchool = null!;
        private Label lblServerStatus = null!;

        // Stats
        private Label lblOnlineLabel = null!, lblOfflineLabel = null!;
        private Label lblOnline = null!, lblOffline = null!, lblPending = null!;
        private Label lblLastSync = null!;

        // Device panel
        private FlowLayoutPanel panelDevices = null!;

        // Log display
        private RichTextBox rtfLog = null!;

        // Buttons
        private MaterialButton btnSyncNow;
        private MaterialButton btnRefreshDevices;
        private MaterialButton btnSettings;
        private MaterialButton btnDevices;
        private MaterialButton btnBackupRestore;
        private MaterialButton btnSyncManager;
        private MaterialButton btnUsers;

        private void InitializeComponent()
        {
            this.Text            = "🔏 Biometric Sync Agent — Batighor EIMS";
            this.Size            = new Size(1080, 640);
            this.MinimumSize     = new Size(1080, 640);
            this.StartPosition   = FormStartPosition.CenterScreen;
            this.Font            = new Font("Segoe UI", 9f);
            this.BackColor       = AppTheme.Background;
            this.Load           += MainDashboard_Load;

            // ── Header ───────────────────────────────────────────────────────
            var pHeader = new Panel
            {
                Dock      = DockStyle.Top,
                Height    = 72,
                BackColor = AppTheme.Surface
            };

            lblTitle = new Label
            {
                Text      = "Biometric Sync Agent",
                Location  = new Point(20, 8),
                Font      = new Font("Segoe UI", 15, FontStyle.Bold),
                ForeColor = AppTheme.TextPrimary,
                AutoSize  = true
            };

            var lblCompany = new Label
            {
                Text      = CompanyName,
                Location  = new Point(20, 36),
                Font      = new Font("Segoe UI", 7.5f, FontStyle.Bold),
                ForeColor = AppTheme.TextSecondary,
                AutoSize  = true
            };

            lblVersion = new Label
            {
                Text      = $"Version {AppVersion}",
                Location  = new Point(20, 50),
                Font      = new Font("Segoe UI", 7.5f),
                ForeColor = AppTheme.TextSecondary,
                AutoSize  = true
            };

            lblSchool = new Label
            {
                Text      = "School: —",
                Location  = new Point(300, 20),
                Font      = new Font("Segoe UI", 11),
                ForeColor = AppTheme.TextPrimary,
                AutoSize  = true
            };

            lblServerStatus = new Label
            {
                Text      = "🟡 Server: Connecting…",
                Location  = new Point(300, 44),
                Font      = new Font("Segoe UI", 9, FontStyle.Bold),
                ForeColor = AppTheme.Warning,
                AutoSize  = true
            };

            pHeader.Controls.AddRange(new Control[] { lblTitle, lblCompany, lblVersion, lblSchool, lblServerStatus });
            this.Controls.Add(pHeader);

            // ── Stats strip ──────────────────────────────────────────────────
            var pStats = new Panel
            {
                Dock      = DockStyle.Top,
                Height    = 52,
                BackColor = AppTheme.SurfaceAlt
            };

            int sx = 20;
            lblOnlineLabel  = MakeStatLabel("Online:", sx,         pStats);
            lblOnline       = MakeStatValue("0", sx + 55,          pStats, AppTheme.Success);
            lblOfflineLabel = MakeStatLabel("Offline:", sx + 120,  pStats);
            lblOffline      = MakeStatValue("0", sx + 185,         pStats, AppTheme.Danger);
            lblPending      = MakeStatValue("Offline Queue: 0", sx + 260, pStats, AppTheme.Success);
            lblLastSync     = MakeStatValue("Last sync: —", sx + 460, pStats, AppTheme.TextSecondary);

            this.Controls.Add(pStats);

            // ── Buttons strip ────────────────────────────────────────────────
            var pButtons = new Panel
            {
                Dock      = DockStyle.Top,
                Height    = 44,
                BackColor = AppTheme.Background
            };

            int bx = 12, bgap = 8;
            btnSyncNow = MakeButton("Sync Now", bx, pButtons);
            btnSyncNow.Click += btnSyncNow_Click;
            bx += btnSyncNow.Width + bgap;

            btnRefreshDevices = MakeButton("Refresh Devices", bx, pButtons);
            btnRefreshDevices.Click += btnRefreshDevices_Click;
            bx += btnRefreshDevices.Width + bgap;

            btnSettings = MakeButton("Settings", bx, pButtons);
            btnSettings.Click += btnSettings_Click;
            bx += btnSettings.Width + bgap;

            btnDevices = MakeButton("Devices", bx, pButtons);
            btnDevices.Click += btnDevices_Click;
            bx += btnDevices.Width + bgap;

            btnBackupRestore = MakeButton("Backup and Restore", bx, pButtons);
            btnBackupRestore.Click += btnBackupRestore_Click;
            bx += btnBackupRestore.Width + bgap;

            btnSyncManager = MakeButton("Sync Manager", bx, pButtons);
            btnSyncManager.Click += btnSyncManager_Click;
            bx += btnSyncManager.Width + bgap;

            btnUsers = MakeButton("ব্যবহারকারী", bx, pButtons);
            btnUsers.Click += btnUsers_Click;

            this.Controls.Add(pButtons);

            // ── Device panel ─────────────────────────────────────────────────
            var lblDevices = new Label
            {
                Text      = "  Connected Devices",
                Dock      = DockStyle.Top,
                Height    = 28,
                BackColor = AppTheme.Surface,
                ForeColor = AppTheme.TextPrimary,
                Font      = new Font("Segoe UI", 9, FontStyle.Bold),
                TextAlign = ContentAlignment.MiddleLeft
            };
            this.Controls.Add(lblDevices);

            panelDevices = new FlowLayoutPanel
            {
                Dock      = DockStyle.Top,
                Height    = 110,
                BackColor = AppTheme.Background,
                AutoScroll = true,
                Padding   = new Padding(4)
            };
            this.Controls.Add(panelDevices);

            // ── Log list ─────────────────────────────────────────────────────
            var lblLogTitle = new Label
            {
                Text      = "  Activity Log",
                Dock      = DockStyle.Top,
                Height    = 28,
                BackColor = AppTheme.Surface,
                ForeColor = AppTheme.TextPrimary,
                Font      = new Font("Segoe UI", 9, FontStyle.Bold),
                TextAlign = ContentAlignment.MiddleLeft
            };
            this.Controls.Add(lblLogTitle);

            rtfLog = new RichTextBox
            {
                Dock        = DockStyle.Fill,
                BackColor   = AppTheme.Background,
                ForeColor   = AppTheme.TextSecondary,
                Font        = new Font("Consolas", 8.5f),
                BorderStyle = BorderStyle.None,
                ReadOnly    = true,
                ScrollBars  = RichTextBoxScrollBars.Vertical,
                WordWrap    = false,
            };
            this.Controls.Add(rtfLog);

            // ── Fix Z-Order for correct layout ──
            this.Controls.SetChildIndex(pHeader, 6);
            this.Controls.SetChildIndex(pStats, 5);
            this.Controls.SetChildIndex(pButtons, 4);
            this.Controls.SetChildIndex(lblDevices, 3);
            this.Controls.SetChildIndex(panelDevices, 2);
            this.Controls.SetChildIndex(lblLogTitle, 1);
            this.Controls.SetChildIndex(rtfLog, 0);
        }

        private Label MakeStatLabel(string text, int x, Control parent)
        {
            var lbl = new Label
            {
                Text      = text,
                Location  = new Point(x, 16),
                ForeColor = AppTheme.TextSecondary,
                AutoSize  = true
            };
            parent.Controls.Add(lbl);
            return lbl;
        }

        private Label MakeStatValue(string text, int x, Control parent, Color color)
        {
            var lbl = new Label
            {
                Text      = text,
                Location  = new Point(x, 16),
                ForeColor = color,
                Font      = new Font("Segoe UI", 9, FontStyle.Bold),
                AutoSize  = true
            };
            parent.Controls.Add(lbl);
            return lbl;
        }

        private MaterialButton MakeButton(string text, int x, Control parent)
        {
            var btn = new MaterialButton
            {
                Text = text,
                Location = new Point(x, 6),
                AutoSize = true,
                Height = 32,
                Type = MaterialButton.MaterialButtonType.Contained,
                UseAccentColor = false,
                HighEmphasis = true,
                Density = MaterialButton.MaterialButtonDensity.Default
            };
            parent.Controls.Add(btn);
            return btn;
        }

        protected override void Dispose(bool disposing)
        {
            if (disposing && components != null) components.Dispose();
            if (disposing)
            {
                _notifyIcon?.Dispose();
                _trayIconConnected?.Dispose();
                _trayIconDisconnected?.Dispose();
                _appIcon?.Dispose();
            }
            base.Dispose(disposing);
        }
    }
}
