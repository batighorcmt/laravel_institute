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
    // Users & Enrollment Form
    // Allows pulling users from the cloud and pushing them to a connected device
    // ─────────────────────────────────────────────────────────────────────────
    public class UsersEnrollmentForm : Form
    {
        private readonly MainDashboard _dashboard;
        private readonly AgentConfig _config;
        private List<UserRecord> _users = new();

        // Controls
        private DataGridView dgvUsers = null!;
        private ComboBox cmbDevice = null!;
        private MaterialButton btnRefreshUsers = null!;
        private MaterialButton btnPushToDevice = null!;
        private MaterialButton btnPushAll = null!;
        private Label lblStatus = null!;
        private TextBox txtSearch = null!;
        private Label lblTitle = null!;
        private Label lblSubtitle = null!;

        public UsersEnrollmentForm(MainDashboard dashboard, AgentConfig config)
        {
            _dashboard = dashboard;
            _config    = config;
            AppTheme.Apply();
            InitForm();
        }

        private void InitForm()
        {
            // Form settings
            Text            = "Users & Enrollment — BATIGHOR SOFTWARE SYSTEMS LTD";
            Size            = new Size(900, 600);
            StartPosition   = FormStartPosition.CenterScreen;
            BackColor       = AppTheme.Background;
            ForeColor       = AppTheme.TextPrimary;
            Font            = new Font("Segoe UI", 9.5f);
            FormBorderStyle = FormBorderStyle.Sizable;

            // Title
            lblTitle = new Label
            {
                Text      = "ব্যবহারকারী ও এনরোলমেন্ট",
                Font      = new Font("Segoe UI", 16f, FontStyle.Bold),
                ForeColor = AppTheme.TextPrimary,
                Location  = new Point(16, 16),
                AutoSize  = true
            };
            lblSubtitle = new Label
            {
                Text      = "ক্লাউড থেকে ব্যবহারকারী আনুন এবং ডিভাইসে পাঠান",
                ForeColor = AppTheme.TextSecondary,
                Location  = new Point(18, 50),
                AutoSize  = true
            };

            // Search box
            txtSearch = new TextBox
            {
                PlaceholderText = "🔍 নাম বা Biometric ID দিয়ে খুঁজুন...",
                Location        = new Point(16, 80),
                Width           = 300,
                BackColor       = AppTheme.Surface,
                ForeColor       = AppTheme.TextPrimary,
                BorderStyle     = BorderStyle.FixedSingle
            };
            txtSearch.TextChanged += (s, e) => FilterGrid();

            // Device dropdown
            var lblDevice = new Label
            {
                Text     = "টার্গেট ডিভাইস:",
                ForeColor = AppTheme.TextSecondary,
                Location  = new Point(330, 83),
                AutoSize  = true
            };
            cmbDevice = new ComboBox
            {
                Location     = new Point(440, 80),
                Width        = 220,
                DropDownStyle = ComboBoxStyle.DropDownList,
                BackColor    = AppTheme.Surface,
                ForeColor    = AppTheme.TextPrimary,
                FlatStyle    = FlatStyle.Flat
            };
            // Populate devices
            foreach (var dev in _config.Devices)
                cmbDevice.Items.Add(dev.SerialNumber + " – " + dev.Name);
            if (cmbDevice.Items.Count > 0) cmbDevice.SelectedIndex = 0;

            // Buttons
            btnRefreshUsers = MakeButton("ক্লাউড থেকে আনুন");
            btnRefreshUsers.Location = new Point(16, 112);
            btnRefreshUsers.Click   += async (s, e) => await LoadUsersAsync();

            btnPushToDevice = MakeButton("ডিভাইসে পাঠান (নির্বাচিত)");
            btnPushToDevice.Location = new Point(btnRefreshUsers.Right + 8, 112);
            btnPushToDevice.Click   += BtnPushSelected_Click;

            btnPushAll = MakeButton("সবাইকে পাঠান");
            btnPushAll.Location = new Point(btnPushToDevice.Right + 8, 112);
            btnPushAll.Click   += BtnPushAll_Click;

            // Status label
            lblStatus = new Label
            {
                Text      = "ক্লাউড থেকে ব্যবহারকারী লোড করুন",
                ForeColor = AppTheme.TextSecondary,
                Location  = new Point(16, 153),
                AutoSize  = true
            };

            // Grid
            dgvUsers = new DataGridView
            {
                Location       = new Point(16, 175),
                Width          = this.ClientSize.Width - 32,
                Height         = this.ClientSize.Height - 195,
                Anchor         = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right | AnchorStyles.Bottom,
                BackgroundColor = AppTheme.Surface,
                GridColor      = AppTheme.SurfaceAlt,
                ForeColor      = AppTheme.TextPrimary,
                DefaultCellStyle = { BackColor = AppTheme.Surface, ForeColor = AppTheme.TextPrimary, SelectionBackColor = AppTheme.Primary, SelectionForeColor = Color.White },
                ColumnHeadersDefaultCellStyle = { BackColor = AppTheme.Primary, ForeColor = Color.White, Font = new Font("Segoe UI", 9f, FontStyle.Bold) },
                RowHeadersVisible    = false,
                AllowUserToAddRows   = false,
                AllowUserToDeleteRows = false,
                ReadOnly             = true,
                SelectionMode        = DataGridViewSelectionMode.FullRowSelect,
                AutoSizeColumnsMode  = DataGridViewAutoSizeColumnsMode.Fill,
                BorderStyle          = BorderStyle.None,
                MultiSelect          = true
            };
            dgvUsers.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = "BiometricId", HeaderText = "Biometric ID", FillWeight = 15 });
            dgvUsers.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = "Name",        HeaderText = "নাম",          FillWeight = 50 });
            dgvUsers.Columns.Add(new DataGridViewTextBoxColumn { DataPropertyName = "Role",        HeaderText = "ভূমিকা",       FillWeight = 15 });
            dgvUsers.Columns.Add(new DataGridViewTextBoxColumn { Name = "Status",                   HeaderText = "ডিভাইস স্ট্যাটাস", FillWeight = 20 });

            // Add controls
            Controls.AddRange(new Control[] {
                lblTitle, lblSubtitle, txtSearch,
                lblDevice, cmbDevice,
                btnRefreshUsers, btnPushToDevice, btnPushAll,
                lblStatus, dgvUsers
            });

            Resize += (s, e) => { if (dgvUsers != null) dgvUsers.Width = this.ClientSize.Width - 32; };
        }

        private MaterialButton MakeButton(string text)
        {
            return new MaterialButton
            {
                Text      = text,
                AutoSize  = true,
                Height    = 32,
                Type      = MaterialButton.MaterialButtonType.Contained,
                UseAccentColor = false,
                HighEmphasis = true,
                Cursor    = Cursors.Hand
            };
        }

        private async Task LoadUsersAsync()
        {
            btnRefreshUsers.Enabled = false;
            lblStatus.Text = "⏳ ক্লাউড থেকে ব্যবহারকারী লোড হচ্ছে...";
            lblStatus.ForeColor = AppTheme.Warning;

            _users = await _dashboard.LoadUsersFromCloudAsync();

            lblStatus.Text = $"✅ {_users.Count} জন ব্যবহারকারী লোড হয়েছে";
            lblStatus.ForeColor = AppTheme.Success;
            btnRefreshUsers.Enabled = true;

            FilterGrid();
        }

        private void FilterGrid()
        {
            var search = txtSearch.Text.Trim().ToLower();
            var filtered = string.IsNullOrEmpty(search)
                ? _users
                : _users.Where(u => u.Name.ToLower().Contains(search) || u.BiometricId.Contains(search)).ToList();

            dgvUsers.DataSource = null;
            dgvUsers.DataSource = filtered;
            // Set status column
            foreach (DataGridViewRow row in dgvUsers.Rows)
            {
                row.Cells["Status"].Value = "—";
                row.Cells["Status"].Style.ForeColor = AppTheme.TextSecondary;
            }
        }

        private async void BtnPushSelected_Click(object? sender, EventArgs e)
        {
            if (cmbDevice.SelectedIndex < 0) { MessageBox.Show("একটি ডিভাইস বেছে নিন।"); return; }
            if (dgvUsers.SelectedRows.Count == 0) { MessageBox.Show("অন্তত একজন ব্যবহারকারী নির্বাচন করুন।"); return; }

            var users = dgvUsers.SelectedRows
                .OfType<DataGridViewRow>()
                .Where(row => row.DataBoundItem is UserRecord)
                .Select(row => (row, (UserRecord)row.DataBoundItem!))
                .ToList();

            if (users.Count == 0)
                return;

            await PushUsersToDeviceAsync(users);
        }

        private async void BtnPushAll_Click(object? sender, EventArgs e)
        {
            if (cmbDevice.SelectedIndex < 0) { MessageBox.Show("একটি ডিভাইস বেছে নিন।"); return; }
            if (_users.Count == 0) { MessageBox.Show("প্রথমে ক্লাউড থেকে ব্যবহারকারী আনুন।"); return; }

            var confirm = MessageBox.Show(
                $"সকল {_users.Count} জন ব্যবহারকারীকে ডিভাইসে পাঠাবেন?",
                "নিশ্চিত করুন", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (confirm != DialogResult.Yes) return;

            var users = dgvUsers.Rows
                .OfType<DataGridViewRow>()
                .Where(row => row.DataBoundItem is UserRecord)
                .Select(row => (row, (UserRecord)row.DataBoundItem!))
                .ToList();

            if (users.Count == 0)
                return;

            await PushUsersToDeviceAsync(users);
        }

        private async Task PushUsersToDeviceAsync(List<(DataGridViewRow row, UserRecord user)> items)
        {
            btnPushToDevice.Enabled = false;
            btnPushAll.Enabled = false;
            lblStatus.Text = "⏳ ডিভাইসে পাঠানো হচ্ছে...";
            lblStatus.ForeColor = AppTheme.Warning;

            var device = _config.Devices[cmbDevice.SelectedIndex];
            string deviceKey = string.IsNullOrEmpty(device.SerialNumber)
                ? device.IpAddress
                : device.SerialNumber;

            var results = await Task.Run(() =>
            {
                var list = new List<(DataGridViewRow row, UserRecord user, bool success)>();
                foreach (var item in items)
                {
                    bool success = _dashboard.PushUserToDevice(deviceKey, item.user);
                    list.Add((item.row, item.user, success));
                }
                return list;
            });

            int ok = 0, fail = 0;
            foreach (var item in results)
            {
                item.row.Cells["Status"].Value = item.success ? "✅ পাঠানো হয়েছে" : "❌ ব্যর্থ";
                item.row.Cells["Status"].Style.ForeColor = item.success ? AppTheme.Success : AppTheme.Danger;
                if (item.success) ok++; else fail++;
            }

            lblStatus.Text = $"সম্পন্ন: {ok} সফল, {fail} ব্যর্থ";
            lblStatus.ForeColor = ok > 0 ? AppTheme.Success : AppTheme.Danger;
            btnPushToDevice.Enabled = true;
            btnPushAll.Enabled = true;
        }
    }
}
