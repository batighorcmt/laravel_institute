using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;

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
        private Button btnRefreshUsers = null!;
        private Button btnPushToDevice = null!;
        private Button btnPushAll = null!;
        private Label lblStatus = null!;
        private TextBox txtSearch = null!;
        private Label lblTitle = null!;
        private Label lblSubtitle = null!;

        public UsersEnrollmentForm(MainDashboard dashboard, AgentConfig config)
        {
            _dashboard = dashboard;
            _config    = config;
            InitForm();
        }

        private void InitForm()
        {
            // Form settings
            Text            = "Users & Enrollment — BATIGHOR SOFTWARE SYSTEMS LTD";
            Size            = new Size(900, 600);
            StartPosition   = FormStartPosition.CenterScreen;
            BackColor       = Color.FromArgb(15, 15, 25);
            ForeColor       = Color.White;
            Font            = new Font("Segoe UI", 9.5f);
            FormBorderStyle = FormBorderStyle.Sizable;

            // Title
            lblTitle = new Label
            {
                Text      = "ব্যবহারকারী ও এনরোলমেন্ট",
                Font      = new Font("Segoe UI", 16f, FontStyle.Bold),
                ForeColor = Color.FromArgb(125, 211, 252),
                Location  = new Point(16, 16),
                AutoSize  = true
            };
            lblSubtitle = new Label
            {
                Text      = "ক্লাউড থেকে ব্যবহারকারী আনুন এবং ডিভাইসে পাঠান",
                ForeColor = Color.FromArgb(148, 163, 184),
                Location  = new Point(18, 50),
                AutoSize  = true
            };

            // Search box
            txtSearch = new TextBox
            {
                PlaceholderText = "🔍 নাম বা Biometric ID দিয়ে খুঁজুন...",
                Location        = new Point(16, 80),
                Width           = 300,
                BackColor       = Color.FromArgb(30, 30, 50),
                ForeColor       = Color.White,
                BorderStyle     = BorderStyle.FixedSingle
            };
            txtSearch.TextChanged += (s, e) => FilterGrid();

            // Device dropdown
            var lblDevice = new Label
            {
                Text     = "টার্গেট ডিভাইস:",
                ForeColor = Color.FromArgb(148, 163, 184),
                Location  = new Point(330, 83),
                AutoSize  = true
            };
            cmbDevice = new ComboBox
            {
                Location     = new Point(440, 80),
                Width        = 220,
                DropDownStyle = ComboBoxStyle.DropDownList,
                BackColor    = Color.FromArgb(30, 30, 50),
                ForeColor    = Color.White,
                FlatStyle    = FlatStyle.Flat
            };
            // Populate devices
            foreach (var dev in _config.Devices)
                cmbDevice.Items.Add(dev.SerialNumber + " – " + dev.Name);
            if (cmbDevice.Items.Count > 0) cmbDevice.SelectedIndex = 0;

            // Buttons
            btnRefreshUsers = MakeButton("☁ ক্লাউড থেকে আনুন", Color.FromArgb(14, 165, 233));
            btnRefreshUsers.Location = new Point(16, 114);
            btnRefreshUsers.Click   += async (s, e) => await LoadUsersAsync();

            btnPushToDevice = MakeButton("→ ডিভাইসে পাঠান (নির্বাচিত)", Color.FromArgb(34, 197, 94));
            btnPushToDevice.Location = new Point(170, 114);
            btnPushToDevice.Click   += BtnPushSelected_Click;

            btnPushAll = MakeButton("→ সবাইকে পাঠান", Color.FromArgb(168, 85, 247));
            btnPushAll.Location = new Point(420, 114);
            btnPushAll.Click   += BtnPushAll_Click;

            // Status label
            lblStatus = new Label
            {
                Text      = "ক্লাউড থেকে ব্যবহারকারী লোড করুন",
                ForeColor = Color.FromArgb(148, 163, 184),
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
                BackgroundColor = Color.FromArgb(20, 20, 35),
                GridColor      = Color.FromArgb(50, 50, 70),
                ForeColor      = Color.White,
                DefaultCellStyle = { BackColor = Color.FromArgb(25, 25, 40), ForeColor = Color.White, SelectionBackColor = Color.FromArgb(14, 165, 233), SelectionForeColor = Color.White },
                ColumnHeadersDefaultCellStyle = { BackColor = Color.FromArgb(30, 30, 50), ForeColor = Color.White, Font = new Font("Segoe UI", 9f, FontStyle.Bold) },
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

        private Button MakeButton(string text, Color backColor)
        {
            return new Button
            {
                Text      = text,
                Width     = 148,
                Height    = 32,
                BackColor = backColor,
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Cursor    = Cursors.Hand,
                Font      = new Font("Segoe UI", 8.5f)
            };
        }

        private async Task LoadUsersAsync()
        {
            btnRefreshUsers.Enabled = false;
            lblStatus.Text = "⏳ ক্লাউড থেকে ব্যবহারকারী লোড হচ্ছে...";
            lblStatus.ForeColor = Color.FromArgb(251, 191, 36);

            _users = await _dashboard.LoadUsersFromCloudAsync();

            lblStatus.Text = $"✅ {_users.Count} জন ব্যবহারকারী লোড হয়েছে";
            lblStatus.ForeColor = Color.FromArgb(52, 211, 153);
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
                row.Cells["Status"].Style.ForeColor = Color.FromArgb(148, 163, 184);
            }
        }

        private void BtnPushSelected_Click(object? sender, EventArgs e)
        {
            if (cmbDevice.SelectedIndex < 0) { MessageBox.Show("একটি ডিভাইস বেছে নিন।"); return; }
            if (dgvUsers.SelectedRows.Count == 0) { MessageBox.Show("অন্তত একজন ব্যবহারকারী নির্বাচন করুন।"); return; }

            string serial = _config.Devices[cmbDevice.SelectedIndex].SerialNumber;
            int ok = 0, fail = 0;

            foreach (DataGridViewRow row in dgvUsers.SelectedRows)
            {
                if (row.DataBoundItem is UserRecord user)
                {
                    bool success = _dashboard.PushUserToDevice(serial, user);
                    row.Cells["Status"].Value     = success ? "✅ পাঠানো হয়েছে" : "❌ ব্যর্থ";
                    row.Cells["Status"].Style.ForeColor = success ? Color.FromArgb(52, 211, 153) : Color.FromArgb(248, 113, 113);
                    if (success) ok++; else fail++;
                }
            }
            lblStatus.Text = $"সম্পন্ন: {ok} সফল, {fail} ব্যর্থ";
        }

        private void BtnPushAll_Click(object? sender, EventArgs e)
        {
            if (cmbDevice.SelectedIndex < 0) { MessageBox.Show("একটি ডিভাইস বেছে নিন।"); return; }
            if (_users.Count == 0) { MessageBox.Show("প্রথমে ক্লাউড থেকে ব্যবহারকারী আনুন।"); return; }

            var confirm = MessageBox.Show(
                $"সকল {_users.Count} জন ব্যবহারকারীকে ডিভাইসে পাঠাবেন?",
                "নিশ্চিত করুন", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
            if (confirm != DialogResult.Yes) return;

            string serial = _config.Devices[cmbDevice.SelectedIndex].SerialNumber;
            int ok = 0, fail = 0;

            foreach (DataGridViewRow row in dgvUsers.Rows)
            {
                if (row.DataBoundItem is UserRecord user)
                {
                    bool success = _dashboard.PushUserToDevice(serial, user);
                    row.Cells["Status"].Value = success ? "✅ পাঠানো হয়েছে" : "❌ ব্যর্থ";
                    row.Cells["Status"].Style.ForeColor = success ? Color.FromArgb(52, 211, 153) : Color.FromArgb(248, 113, 113);
                    if (success) ok++; else fail++;
                }
            }
            lblStatus.Text = $"সম্পন্ন: {ok} সফল, {fail} ব্যর্থ";
            lblStatus.ForeColor = ok > 0 ? Color.FromArgb(52, 211, 153) : Color.FromArgb(248, 113, 113);
        }
    }
}
