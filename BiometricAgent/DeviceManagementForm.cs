using System;
using System.Drawing;
using System.Threading.Tasks;
using System.Windows.Forms;
using MaterialSkin.Controls;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Device Management — dedicated CRUD interface for biometric devices,
    // split out of SettingsForm (which now only holds API/connection settings).
    // ─────────────────────────────────────────────────────────────────────────
    public class DeviceManagementForm : Form
    {
        private readonly AgentConfig _config;

        private DataGridView gridDevices = null!;
        private MaterialButton btnAdd = null!;
        private MaterialButton btnDelete = null!;
        private MaterialButton btnTestConnection = null!;
        private MaterialButton btnSave = null!;
        private Label lblStatus = null!;

        /// <summary>True if the caller should reconnect/refresh devices after this dialog closes.</summary>
        public bool DevicesChanged { get; private set; }

        public DeviceManagementForm(AgentConfig config)
        {
            _config = config;
            AppTheme.Apply();
            BuildUI();
            LoadValues();
        }

        private void BuildUI()
        {
            Text          = "Device Management";
            Size          = new Size(760, 520);
            StartPosition = FormStartPosition.CenterParent;
            BackColor     = AppTheme.Background;
            ForeColor     = AppTheme.TextPrimary;
            Font          = new Font("Segoe UI", 9f);
            MinimumSize   = new Size(640, 420);

            var lblTitle = new Label
            {
                Text      = "🖥 Biometric Devices",
                Location  = new Point(16, 14),
                Font      = new Font("Segoe UI", 13, FontStyle.Bold),
                ForeColor = AppTheme.TextPrimary,
                AutoSize  = true
            };
            Controls.Add(lblTitle);

            var lblHint = new Label
            {
                Text      = "Add, edit or remove biometric devices. Server/API connection settings are configured separately under Settings.",
                Location  = new Point(16, 44),
                ForeColor = AppTheme.TextSecondary,
                AutoSize  = true
            };
            Controls.Add(lblHint);

            gridDevices = new DataGridView
            {
                Location          = new Point(16, 72),
                Size              = new Size(710, 300),
                Anchor            = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right | AnchorStyles.Bottom,
                BackgroundColor   = AppTheme.Surface,
                ForeColor         = AppTheme.TextPrimary,
                GridColor         = AppTheme.SurfaceAlt,
                DefaultCellStyle  = { BackColor = AppTheme.Surface, ForeColor = AppTheme.TextPrimary, SelectionBackColor = AppTheme.Primary },
                ColumnHeadersDefaultCellStyle = { BackColor = AppTheme.Primary, ForeColor = Color.White, Font = new Font("Segoe UI", 9, FontStyle.Bold) },
                BorderStyle       = BorderStyle.None,
                AllowUserToAddRows = false,
                AllowUserToDeleteRows = false,
                RowHeadersVisible = false,
                SelectionMode     = DataGridViewSelectionMode.FullRowSelect,
                MultiSelect       = false
            };
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "Name",          HeaderText = "Name",          Width = 120 });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "SerialNumber",  HeaderText = "Serial",        Width = 110 });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "MachineNumber", HeaderText = "Device #",      Width = 70  });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "IpAddress",     HeaderText = "IP Address",    Width = 120 });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "Port",          HeaderText = "Port",          Width = 60  });
            gridDevices.Columns.Add(new DataGridViewTextBoxColumn { Name = "Location",      HeaderText = "Location",      Width = 140 });
            Controls.Add(gridDevices);

            btnAdd = MakeButton("Add Device");
            btnAdd.Location = new Point(16, 385);
            btnAdd.Click += (s, e) =>
            {
                int idx = gridDevices.Rows.Add("New Device", "", "1", "", "4370", "");
                gridDevices.CurrentCell = gridDevices.Rows[idx].Cells[0];
                gridDevices.BeginEdit(true);
            };
            Controls.Add(btnAdd);

            btnDelete = MakeButton("Delete Selected");
            btnDelete.Location = new Point(btnAdd.Right + 8, 385);
            btnDelete.Click += (s, e) =>
            {
                if (gridDevices.CurrentRow != null && !gridDevices.CurrentRow.IsNewRow)
                    gridDevices.Rows.Remove(gridDevices.CurrentRow);
            };
            Controls.Add(btnDelete);

            btnTestConnection = MakeButton("Test Connection");
            btnTestConnection.Location = new Point(btnDelete.Right + 8, 385);
            btnTestConnection.Click += async (s, e) => await TestSelectedConnectionAsync();
            Controls.Add(btnTestConnection);

            lblStatus = new Label
            {
                Text      = "",
                Location  = new Point(16, 424),
                Width     = 710,
                ForeColor = AppTheme.TextSecondary,
                AutoSize  = false,
                Anchor    = AnchorStyles.Bottom | AnchorStyles.Left | AnchorStyles.Right
            };
            Controls.Add(lblStatus);

            btnSave = new MaterialButton
            {
                Text = "Save and Close",
                Location = new Point(560, 446),
                AutoSize = true,
                Height = 36,
                Type = MaterialButton.MaterialButtonType.Contained,
                UseAccentColor = false,
                HighEmphasis = true,
                Anchor = AnchorStyles.Bottom | AnchorStyles.Right,
                DialogResult = DialogResult.OK
            };
            btnSave.Click += BtnSave_Click;
            Controls.Add(btnSave);
        }

        private MaterialButton MakeButton(string text)
        {
            return new MaterialButton
            {
                Text = text,
                AutoSize = true,
                Height = 32,
                Type = MaterialButton.MaterialButtonType.Outlined,
                UseAccentColor = false,
                HighEmphasis = true,
                Anchor = AnchorStyles.Bottom | AnchorStyles.Left
            };
        }

        private void LoadValues()
        {
            foreach (var dev in _config.Devices)
            {
                gridDevices.Rows.Add(dev.Name, dev.SerialNumber, dev.MachineNumber.ToString(), dev.IpAddress, dev.Port.ToString(), dev.Location);
            }
        }

        private async Task TestSelectedConnectionAsync()
        {
            var row = gridDevices.CurrentRow;
            if (row == null || row.IsNewRow)
            {
                lblStatus.Text = "Select a device row first.";
                lblStatus.ForeColor = AppTheme.Warning;
                return;
            }

            string ip = row.Cells["IpAddress"].Value?.ToString() ?? "";
            int port = int.TryParse(row.Cells["Port"].Value?.ToString(), out int p) ? p : 4370;
            int machineNumber = int.TryParse(row.Cells["MachineNumber"].Value?.ToString(), out int m) ? m : 1;

            if (string.IsNullOrWhiteSpace(ip))
            {
                lblStatus.Text = "Enter an IP address first.";
                lblStatus.ForeColor = AppTheme.Warning;
                return;
            }

            btnTestConnection.Enabled = false;
            lblStatus.Text = $"⏳ Connecting to {ip}:{port}...";
            lblStatus.ForeColor = AppTheme.Warning;

            var (ok, serial, error) = await Task.Run(() =>
            {
                var adapter = BiometricAdapterFactory.Create("zkteco");
                bool connected = adapter.Connect(ip, port, machineNumber);
                string sn = connected ? adapter.GetSerialNumber() : "";
                string err = connected ? "" : adapter.LastError;
                adapter.Disconnect();
                return (connected, sn, err);
            });

            if (ok)
            {
                lblStatus.Text = $"✅ Connected. Serial: {(string.IsNullOrWhiteSpace(serial) ? "(unknown)" : serial)}";
                lblStatus.ForeColor = AppTheme.Success;
                if (!string.IsNullOrWhiteSpace(serial))
                    row.Cells["SerialNumber"].Value = serial;
            }
            else
            {
                lblStatus.Text = $"❌ Connection failed: {error}";
                lblStatus.ForeColor = AppTheme.Danger;
            }

            btnTestConnection.Enabled = true;
        }

        private void BtnSave_Click(object? sender, EventArgs e)
        {
            gridDevices.EndEdit();

            _config.Devices.Clear();
            foreach (DataGridViewRow row in gridDevices.Rows)
            {
                if (row.IsNewRow) continue;
                var name = row.Cells["Name"].Value?.ToString() ?? "";
                var ip   = row.Cells["IpAddress"].Value?.ToString() ?? "";
                if (string.IsNullOrWhiteSpace(name) && string.IsNullOrWhiteSpace(ip)) continue;

                _config.Devices.Add(new DeviceConfig
                {
                    Name          = name,
                    SerialNumber  = row.Cells["SerialNumber"].Value?.ToString() ?? "",
                    MachineNumber = int.TryParse(row.Cells["MachineNumber"].Value?.ToString(), out int m) ? m : 1,
                    IpAddress     = ip,
                    Port          = int.TryParse(row.Cells["Port"].Value?.ToString(), out int p) ? p : 4370,
                    Location      = row.Cells["Location"].Value?.ToString() ?? ""
                });
            }

            _config.Save();
            DevicesChanged = true;
        }
    }
}
