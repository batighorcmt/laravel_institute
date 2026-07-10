using System;
using System.Drawing;
using System.Windows.Forms;

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

        // Stats
        private Label lblOnlineLabel = null!, lblOfflineLabel = null!;
        private Label lblOnline = null!, lblOffline = null!, lblPending = null!;
        private Label lblLastSync = null!;

        // Device panel
        private FlowLayoutPanel panelDevices = null!;

        // Log list
        private ListBox lstLog = null!;

        // Buttons
        private Button btnSyncNow = null!, btnRefreshDevices = null!, btnSettings = null!;
        private Button btnUploadTemplates = null!, btnDownloadTemplates = null!, btnUsers = null!;

        private void InitializeComponent()
        {
            this.Text            = "🔏 Biometric Sync Agent";
            this.Size            = new Size(900, 620);
            this.MinimumSize     = new Size(900, 620);
            this.StartPosition   = FormStartPosition.CenterScreen;
            this.Font            = new Font("Segoe UI", 9f);
            this.Load           += MainDashboard_Load;

            // ── Header ───────────────────────────────────────────────────────
            var pHeader = new Panel
            {
                Dock      = DockStyle.Top,
                Height    = 70,
                BackColor = Color.FromArgb(30, 30, 46)
            };

            lblTitle = new Label
            {
                Text      = "Biometric Sync Agent",
                Location  = new Point(16, 6),
                Font      = new Font("Segoe UI", 14, FontStyle.Bold),
                ForeColor = Color.FromArgb(125, 211, 252),
                AutoSize  = true
            };

            var lblCompany = new Label
            {
                Text      = CompanyName,
                Location  = new Point(16, 34),
                Font      = new Font("Segoe UI", 7.5f, FontStyle.Bold),
                ForeColor = Color.FromArgb(100, 116, 139),
                AutoSize  = true
            };

            lblVersion = new Label
            {
                Text      = "v1.0",
                Location  = new Point(16, 44),
                Font      = new Font("Segoe UI", 8),
                ForeColor = Color.Gray,
                AutoSize  = true
            };

            lblSchool = new Label
            {
                Text      = "School: —",
                Location  = new Point(280, 26),
                Font      = new Font("Segoe UI", 11),
                ForeColor = Color.White,
                AutoSize  = true
            };

            pHeader.Controls.AddRange(new Control[] { lblTitle, lblCompany, lblVersion, lblSchool });
            this.Controls.Add(pHeader);

            // ── Stats strip ──────────────────────────────────────────────────
            var pStats = new Panel
            {
                Dock      = DockStyle.Top,
                Height    = 50,
                BackColor = Color.FromArgb(24, 24, 37)
            };

            int sx = 16;
            lblOnlineLabel  = MakeStatLabel("Online:", sx,         pStats);
            lblOnline       = MakeStatValue("0", sx + 55,          pStats, Color.FromArgb(52, 211, 153));
            lblOfflineLabel = MakeStatLabel("Offline:", sx + 120,  pStats);
            lblOffline      = MakeStatValue("0", sx + 185,         pStats, Color.FromArgb(248, 113, 113));
            lblPending      = MakeStatValue("Offline Queue: 0", sx + 260, pStats, Color.FromArgb(52, 211, 153));
            lblLastSync     = MakeStatValue("Last sync: —", sx + 460, pStats, Color.Gray);

            this.Controls.Add(pStats);

            // ── Buttons strip ────────────────────────────────────────────────
            var pButtons = new Panel
            {
                Dock      = DockStyle.Top,
                Height    = 40,
                BackColor = Color.FromArgb(17, 17, 27)
            };

            btnSyncNow = MakeButton("⟳ Sync Now", 10, pButtons, Color.FromArgb(99, 102, 241));
            btnSyncNow.Click += btnSyncNow_Click;

            btnRefreshDevices = MakeButton("⟳ Refresh Devices", 130, pButtons, Color.FromArgb(59, 130, 246));
            btnRefreshDevices.Click += btnRefreshDevices_Click;

            btnSettings = MakeButton("⚙ Settings", 250, pButtons, Color.FromArgb(75, 85, 99));
            btnSettings.Click += btnSettings_Click;

            btnUploadTemplates = MakeButton("↑ Upload Templates", 370, pButtons, Color.FromArgb(16, 185, 129));
            btnUploadTemplates.Width = 135;
            btnUploadTemplates.Click += btnUploadTemplates_Click;

            btnDownloadTemplates = MakeButton("↓ Download Templates", 510, pButtons, Color.FromArgb(245, 158, 11));
            btnDownloadTemplates.Width = 145;
            btnDownloadTemplates.Click += btnDownloadTemplates_Click;

            btnUsers = MakeButton("👥 ব্যবহারকারী", 660, pButtons, Color.FromArgb(168, 85, 247));
            btnUsers.Width = 130;
            btnUsers.Click += btnUsers_Click;

            this.Controls.Add(pButtons);

            // ── Device panel ─────────────────────────────────────────────────
            var lblDevices = new Label
            {
                Text      = "  Connected Devices",
                Dock      = DockStyle.Top,
                Height    = 28,
                BackColor = Color.FromArgb(30, 30, 46),
                ForeColor = Color.FromArgb(125, 211, 252),
                Font      = new Font("Segoe UI", 9, FontStyle.Bold),
                TextAlign = ContentAlignment.MiddleLeft
            };
            this.Controls.Add(lblDevices);

            panelDevices = new FlowLayoutPanel
            {
                Dock      = DockStyle.Top,
                Height    = 110,
                BackColor = Color.FromArgb(17, 17, 27),
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
                BackColor = Color.FromArgb(30, 30, 46),
                ForeColor = Color.FromArgb(125, 211, 252),
                Font      = new Font("Segoe UI", 9, FontStyle.Bold),
                TextAlign = ContentAlignment.MiddleLeft
            };
            this.Controls.Add(lblLogTitle);

            lstLog = new ListBox
            {
                Dock      = DockStyle.Fill,
                BackColor = Color.FromArgb(17, 17, 27),
                ForeColor = Color.FromArgb(200, 200, 220),
                Font      = new Font("Consolas", 8.5f),
                BorderStyle = BorderStyle.None
            };
            this.Controls.Add(lstLog);
        }

        private Label MakeStatLabel(string text, int x, Control parent)
        {
            var lbl = new Label
            {
                Text      = text,
                Location  = new Point(x, 14),
                ForeColor = Color.Gray,
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
                Location  = new Point(x, 14),
                ForeColor = color,
                Font      = new Font("Segoe UI", 9, FontStyle.Bold),
                AutoSize  = true
            };
            parent.Controls.Add(lbl);
            return lbl;
        }

        private Button MakeButton(string text, int x, Control parent, Color backColor)
        {
            var btn = new Button
            {
                Text      = text,
                Location  = new Point(x, 6),
                Size      = new Size(115, 28),
                BackColor = backColor,
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font      = new Font("Segoe UI", 8.5f)
            };
            btn.FlatAppearance.BorderSize = 0;
            parent.Controls.Add(btn);
            return btn;
        }

        protected override void Dispose(bool disposing)
        {
            if (disposing && components != null) components.Dispose();
            base.Dispose(disposing);
        }
    }
}
