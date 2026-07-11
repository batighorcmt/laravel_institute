using System;
using System.Collections.Generic;
using System.Drawing;
using System.Windows.Forms;
using Newtonsoft.Json;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Settings Form – configure SaaS URL, token, and devices
    // ─────────────────────────────────────────────────────────────────────────
    public class SettingsForm : Form
    {
        public AgentConfig Config { get; private set; }

        private TextBox txtApiUrl = null!, txtSchoolCode = null!, txtToken = null!;
        private TextBox txtSyncInterval = null!;
        private CheckBox chkAutoStart = null!;
        private DataGridView gridDevices = null!;

        public SettingsForm(AgentConfig config)
        {
            Config = config;
            BuildUI();
            LoadValues();
        }

        private void BuildUI()
        {
            Text          = "Agent Settings";
            Size          = new Size(600, 520);
            StartPosition = FormStartPosition.CenterParent;
            BackColor     = Color.FromArgb(17, 17, 27);
            ForeColor     = Color.White;
            Font          = new Font("Segoe UI", 9f);

            int y = 16;
            AddLabel("SaaS API URL:", 16, y);
            txtApiUrl = AddTextBox(150, y, 400); y += 36;

            AddLabel("School Code:", 16, y);
            txtSchoolCode = AddTextBox(150, y, 200); y += 36;

            AddLabel("Agent Token:", 16, y);
            txtToken = AddTextBox(150, y, 400); y += 36;

            AddLabel("Sync Interval (s):", 16, y);
            txtSyncInterval = AddTextBox(150, y, 80); 
            
            chkAutoStart = new CheckBox 
            { 
                Text = "Auto-Start with Windows", 
                Location = new Point(250, y + 2), 
                ForeColor = Color.White,
                AutoSize = true
            };
            Controls.Add(chkAutoStart);
            y += 36;

            var lblDevices = new Label
            {
                Text      = "Devices (Name | Serial | IP | Port | Location):",
                Location  = new Point(16, y),
                ForeColor = Color.FromArgb(125, 211, 252),
                AutoSize  = true
            };
            Controls.Add(lblDevices); y += 22;

            gridDevices = new DataGridView
            {
                Location          = new Point(16, y),
                Size              = new Size(550, 200),
                BackgroundColor   = Color.FromArgb(30, 30, 46),
                ForeColor         = Color.White,
                GridColor         = Color.FromArgb(60, 60, 80),
                DefaultCellStyle  = { BackColor = Color.FromArgb(30, 30, 46), ForeColor = Color.White },
                ColumnHeadersDefaultCellStyle = { BackColor = Color.FromArgb(99, 102, 241), ForeColor = Color.White },
                BorderStyle       = BorderStyle.None,
                AllowUserToAddRows = true
            };
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "Name",          HeaderText = "Name",          Width = 100 });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "SerialNumber",  HeaderText = "Serial",        Width = 90  });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "MachineNumber", HeaderText = "Device #",      Width = 70  });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "IpAddress",     HeaderText = "IP",            Width = 110 });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "Port",          HeaderText = "Port",          Width = 60  });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "Location",      HeaderText = "Location",      Width = 120 });
            Controls.Add(gridDevices);
            y += 210;

            var btnSave = new Button
            {
                Text      = "💾 Save & Close",
                Location  = new Point(16, y),
                Size      = new Size(140, 32),
                BackColor = Color.FromArgb(99, 102, 241),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                DialogResult = DialogResult.OK
            };
            btnSave.FlatAppearance.BorderSize = 0;
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

            foreach (var dev in Config.Devices)
            {
                gridDevices.Rows.Add(dev.Name, dev.SerialNumber, dev.MachineNumber.ToString(), dev.IpAddress, dev.Port.ToString(), dev.Location);
            }
        }

        private void BtnSave_Click(object? sender, EventArgs e)
        {
            Config.SaasApiUrl         = txtApiUrl.Text.Trim();
            Config.SchoolCode         = txtSchoolCode.Text.Trim();
            Config.AgentToken         = txtToken.Text.Trim();
            Config.SyncIntervalSeconds = int.TryParse(txtSyncInterval.Text.Trim(), out int iv) ? iv : 60;
            Config.AutoStart          = chkAutoStart.Checked;

            Config.Devices.Clear();
            foreach (DataGridViewRow row in gridDevices.Rows)
            {
                if (row.IsNewRow) continue;
                var name   = row.Cells["Name"].Value?.ToString() ?? "";
                var serial = row.Cells["SerialNumber"].Value?.ToString() ?? "";
                if (string.IsNullOrWhiteSpace(name) && string.IsNullOrWhiteSpace(serial)) continue;

                Config.Devices.Add(new DeviceConfig
                {
                    Name          = name,
                    SerialNumber  = serial,
                    MachineNumber = int.TryParse(row.Cells["MachineNumber"].Value?.ToString(), out int m) ? m : 1,
                    IpAddress     = row.Cells["IpAddress"].Value?.ToString() ?? "",
                    Port          = int.TryParse(row.Cells["Port"].Value?.ToString(), out int p) ? p : 4370,
                    Location      = row.Cells["Location"].Value?.ToString() ?? ""
                });
            }
        }

        private Label AddLabel(string text, int x, int y)
        {
            var lbl = new Label { Text = text, Location = new Point(x, y + 3), ForeColor = Color.Gray, AutoSize = true };
            Controls.Add(lbl);
            return lbl;
        }

        private TextBox AddTextBox(int x, int y, int width)
        {
            var tb = new TextBox
            {
                Location  = new Point(x, y),
                Width     = width,
                BackColor = Color.FromArgb(30, 30, 46),
                ForeColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle
            };
            Controls.Add(tb);
            return tb;
        }
    }
}
