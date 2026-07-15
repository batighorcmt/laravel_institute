using System;
using System.Collections.Generic;
using System.Drawing;
using System.Windows.Forms;
using MaterialSkin.Controls;
using Newtonsoft.Json;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Settings Form – configure SaaS URL, token, and devices
    // ─────────────────────────────────────────────────────────────────────────
    public class SettingsForm : Form
    {
        public AgentConfig Config { get; private set; }

        /// <summary>True if devices were added/edited/removed via Manage Devices while
        /// this dialog was open, so the caller knows to reconnect/refresh devices.</summary>
        public bool DevicesChanged { get; private set; }

        private MaterialTextBox2 txtApiUrl = null!, txtSchoolCode = null!, txtToken = null!;
        private MaterialTextBox2 txtSyncInterval = null!;
        private MaterialCheckbox chkAutoStart = null!;

        public SettingsForm(AgentConfig config)
        {
            Config = config;
            AppTheme.Apply();
            BuildUI();
            LoadValues();
        }

        private void BuildUI()
        {
            Text          = "Agent Settings";
            Size          = new Size(600, 470);
            StartPosition = FormStartPosition.CenterParent;
            BackColor     = AppTheme.Background;
            ForeColor     = AppTheme.TextPrimary;
            Font          = new Font("Segoe UI", 9f);

            var lblTitle = new Label
            {
                Text = "⚙ Agent Settings",
                Location = new Point(20, 16),
                Font = new Font("Segoe UI", 13, FontStyle.Bold),
                ForeColor = AppTheme.TextPrimary,
                AutoSize = true
            };
            Controls.Add(lblTitle);

            var lblHint = new Label
            {
                Text = "Server connection settings. Devices are managed separately.",
                Location = new Point(20, 46),
                ForeColor = AppTheme.TextSecondary,
                AutoSize = true
            };
            Controls.Add(lblHint);

            int y = 80;
            txtApiUrl = AddTextField("SaaS API URL", 20, y, 540); y += 56;
            txtSchoolCode = AddTextField("School Code", 20, y, 260); y += 56;
            txtToken = AddTextField("Agent Token", 20, y, 540); y += 56;
            txtSyncInterval = AddTextField("Sync Interval (seconds)", 20, y, 180);

            chkAutoStart = new MaterialCheckbox
            {
                Text = "Auto-Start with Windows",
                Location = new Point(230, y + 14),
                AutoSize = true
            };
            Controls.Add(chkAutoStart);
            y += 60;

            var btnManageDevices = new MaterialButton
            {
                Text = "Manage Devices",
                Location = new Point(20, y),
                AutoSize = true,
                Height = 34,
                Type = MaterialButton.MaterialButtonType.Outlined,
                UseAccentColor = false,
                HighEmphasis = true
            };
            btnManageDevices.Click += (s, e) =>
            {
                using var frm = new DeviceManagementForm(Config);
                frm.ShowDialog(this);
                if (frm.DevicesChanged) DevicesChanged = true;
            };
            Controls.Add(btnManageDevices);
            y += 52;

            var btnSave = new MaterialButton
            {
                Text = "Save and Close",
                Location = new Point(20, y),
                AutoSize = true,
                Height = 36,
                Type = MaterialButton.MaterialButtonType.Contained,
                UseAccentColor = false,
                HighEmphasis = true,
                DialogResult = DialogResult.OK
            };
            btnSave.Click += BtnSave_Click;
            Controls.Add(btnSave);
        }

        private void LoadValues()
        {
            txtApiUrl.Text        = Config.SaasApiUrl;
            txtSchoolCode.Text    = Config.SchoolCode;
            txtToken.Text         = Config.AgentToken;
            txtSyncInterval.Text  = Config.SyncIntervalSeconds.ToString();
            chkAutoStart.Checked  = Config.AutoStart;
        }

        private void BtnSave_Click(object? sender, EventArgs e)
        {
            Config.SaasApiUrl         = txtApiUrl.Text.Trim();
            Config.SchoolCode         = txtSchoolCode.Text.Trim();
            Config.AgentToken         = txtToken.Text.Trim();
            Config.SyncIntervalSeconds = int.TryParse(txtSyncInterval.Text.Trim(), out int iv) ? iv : 60;
            Config.AutoStart          = chkAutoStart.Checked;
        }

        private MaterialTextBox2 AddTextField(string hint, int x, int y, int width)
        {
            var tb = new MaterialTextBox2
            {
                Hint = hint,
                Location = new Point(x, y),
                Width = width
            };
            Controls.Add(tb);
            return tb;
        }
    }
}
