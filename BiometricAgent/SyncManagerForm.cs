using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Text.RegularExpressions;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace BiometricAgent
{
    public class SyncManagerForm : Form
    {
        private readonly AgentConfig _config;
        private readonly CloudSyncManager _cloud;
        private readonly Dictionary<string, IBiometricAdapter> _adapters;
        private readonly int _schoolId;

        private ComboBox cmbDevices;
        private Button btnStep1;
        private Button btnStep2;
        private Button btnStep3;
        private RichTextBox rtfLog;
        private Button btnClose;

        public SyncManagerForm(AgentConfig config, CloudSyncManager cloud, Dictionary<string, IBiometricAdapter> adapters, int schoolId)
        {
            _config = config;
            _cloud = cloud;
            _adapters = adapters;
            _schoolId = schoolId;

            InitializeUI();
            PopulateDevices();
        }

        private void InitializeUI()
        {
            this.Text = "Biometric Sync Manager";
            this.Size = new Size(600, 500);
            this.StartPosition = FormStartPosition.CenterParent;
            this.BackColor = Color.FromArgb(30, 30, 46);
            this.ForeColor = Color.White;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;

            Label lblTitle = new Label
            {
                Text = "Biometric Template Workflow",
                Font = new Font("Segoe UI", 14, FontStyle.Bold),
                Location = new Point(20, 20),
                AutoSize = true
            };
            Controls.Add(lblTitle);

            Label lblSelect = new Label
            {
                Text = "Enrollment Device:",
                Location = new Point(20, 70),
                AutoSize = true
            };
            Controls.Add(lblSelect);

            cmbDevices = new ComboBox
            {
                Location = new Point(140, 67),
                Width = 200,
                DropDownStyle = ComboBoxStyle.DropDownList
            };
            Controls.Add(cmbDevices);

            btnStep1 = CreateButton("Step 1: Send Users to Device", 20, 110, Color.FromArgb(59, 130, 246));
            btnStep1.Click += async (s, e) => await Step1_SendUsersAsync();
            Controls.Add(btnStep1);

            btnStep2 = CreateButton("Step 2: Upload Templates to Web", 20, 160, Color.FromArgb(139, 92, 246));
            btnStep2.Click += async (s, e) => await Step2_UploadTemplatesAsync();
            Controls.Add(btnStep2);

            btnStep3 = CreateButton("Step 3: Distribute to All Devices", 20, 210, Color.FromArgb(16, 185, 129));
            btnStep3.Click += async (s, e) => await Step3_DistributeTemplatesAsync();
            Controls.Add(btnStep3);

            rtfLog = new RichTextBox
            {
                Location = new Point(20, 260),
                Width = 540,
                Height = 130,
                BackColor = Color.FromArgb(40, 42, 54),
                ForeColor = Color.LightGray,
                ReadOnly = true,
                BorderStyle = BorderStyle.None
            };
            Controls.Add(rtfLog);

            btnClose = CreateButton("Close", 460, 410, Color.FromArgb(107, 114, 128));
            btnClose.Width = 100;
            btnClose.Click += (s, e) => this.Close();
            Controls.Add(btnClose);
        }

        private Button CreateButton(string text, int x, int y, Color color)
        {
            return new Button
            {
                Text = text,
                Location = new Point(x, y),
                Width = 250,
                Height = 35,
                FlatStyle = FlatStyle.Flat,
                BackColor = color,
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 9, FontStyle.Bold),
                Cursor = Cursors.Hand
            };
        }

        private void PopulateDevices()
        {
            foreach (var dev in _config.Devices)
            {
                if (!string.IsNullOrWhiteSpace(dev.IpAddress))
                {
                    cmbDevices.Items.Add(new DeviceItem { Name = dev.Name, IpAddress = dev.IpAddress, Serial = dev.SerialNumber });
                }
            }
            if (cmbDevices.Items.Count > 0)
                cmbDevices.SelectedIndex = 0;
        }

        private void Log(string msg)
        {
            if (rtfLog.InvokeRequired)
            {
                rtfLog.Invoke(new Action(() => Log(msg)));
                return;
            }
            rtfLog.AppendText($"[{DateTime.Now:HH:mm:ss}] {msg}\n");
            rtfLog.SelectionStart = rtfLog.Text.Length;
            rtfLog.ScrollToCaret();
        }

        private IBiometricAdapter? GetAdapter(string ipAddress)
        {
            if (_adapters.TryGetValue(ipAddress, out var adapter) && adapter.IsConnected)
                return adapter;
            return null;
        }

        private async Task Step1_SendUsersAsync()
        {
            if (cmbDevices.SelectedItem is not DeviceItem item) return;
            var adapter = GetAdapter(item.IpAddress);
            if (adapter == null)
            {
                Log($"❌ {item.Name} is offline.");
                return;
            }

            btnStep1.Enabled = false;
            Log($"⬇️ Fetching users from web...");
            try
            {
                var users = await _cloud.GetUsersAsync(_schoolId);
                if (users.Count == 0)
                {
                    Log("⚠️ No users found on web.");
                    return;
                }

                Log($"📤 Pushing {users.Count} users to {item.Name}...");
                int successCount = 0;
                await Task.Run(() =>
                {
                    foreach (var user in users)
                    {
                        var biometricId = user.BiometricId ?? string.Empty;
                        if (!string.IsNullOrWhiteSpace(_config.SchoolCode) &&
                            biometricId.StartsWith(_config.SchoolCode, StringComparison.OrdinalIgnoreCase))
                        {
                            biometricId = biometricId.Substring(_config.SchoolCode.Length);
                        }
                        biometricId = Regex.Replace(biometricId, "^\\D+", "");

                        var tmpl = new BiometricTemplate
                        {
                            BiometricId = biometricId,
                            Name = user.Name,
                            Privilege = user.Role == "Teacher" ? 0 : 0,
                            FingerIndex = 0,
                            TemplateData = ""
                        };
                        if (adapter.UploadTemplate(tmpl)) successCount++;
                    }
                });

                Log($"✅ Successfully sent {successCount}/{users.Count} users to {item.Name}.");
                Log($"👉 Now physically enroll fingerprints on {item.Name}.");
            }
            catch (Exception ex)
            {
                Log($"❌ Error: {ex.Message}");
            }
            finally
            {
                btnStep1.Enabled = true;
            }
        }

        private async Task Step2_UploadTemplatesAsync()
        {
            if (cmbDevices.SelectedItem is not DeviceItem item) return;
            var adapter = GetAdapter(item.IpAddress);
            if (adapter == null)
            {
                Log($"❌ {item.Name} is offline.");
                return;
            }

            btnStep2.Enabled = false;
            Log($"📥 Reading templates from {item.Name}...");
            try
            {
                var templates = await Task.Run(() => adapter.ReadAllTemplates());
                if (templates.Count == 0)
                {
                    Log("⚠️ No templates found on device.");
                    return;
                }

                Log($"⬆️ Uploading {templates.Count} templates to web...");
                bool ok = await _cloud.SyncTemplatesUpAsync(_schoolId, item.Serial, templates);
                if (ok)
                    Log($"✅ Successfully uploaded templates to web DB.");
                else
                    Log($"❌ Failed to upload templates.");
            }
            catch (Exception ex)
            {
                Log($"❌ Error: {ex.Message}");
            }
            finally
            {
                btnStep2.Enabled = true;
            }
        }

        private async Task Step3_DistributeTemplatesAsync()
        {
            btnStep3.Enabled = false;
            Log($"⬇️ Downloading templates from web...");
            try
            {
                string dummySerial = _config.Devices.FirstOrDefault()?.SerialNumber ?? "0";
                var templates = await _cloud.SyncTemplatesDownAsync(_schoolId, dummySerial);
                
                if (templates.Count == 0)
                {
                    Log("⚠️ No templates found on web DB.");
                    return;
                }

                foreach (var dev in _config.Devices)
                {
                    var adapter = GetAdapter(dev.IpAddress);
                    if (adapter == null)
                    {
                        Log($"⚠️ Skipping {dev.Name} (Offline)");
                        continue;
                    }

                    Log($"📤 Distributing {templates.Count} templates to {dev.Name}...");
                    int successCount = 0;
                    await Task.Run(() =>
                    {
                        foreach (var t in templates)
                        {
                            // Ensure BiometricId is normalized for device (strip school code prefix)
                            var biometricId = t.BiometricId ?? string.Empty;
                            if (!string.IsNullOrWhiteSpace(_config.SchoolCode) &&
                                biometricId.StartsWith(_config.SchoolCode, StringComparison.OrdinalIgnoreCase))
                            {
                                biometricId = biometricId.Substring(_config.SchoolCode.Length);
                            }
                            biometricId = Regex.Replace(biometricId, "^\\D+", "");
                            t.BiometricId = biometricId;

                            if (adapter.UploadTemplate(t)) successCount++;
                        }
                    });
                    Log($"✅ {successCount}/{templates.Count} distributed to {dev.Name}.");
                }
            }
            catch (Exception ex)
            {
                Log($"❌ Error: {ex.Message}");
            }
            finally
            {
                btnStep3.Enabled = true;
            }
        }

        private class DeviceItem
        {
            public string Name { get; set; } = "";
            public string IpAddress { get; set; } = "";
            public string Serial { get; set; } = "";
            public override string ToString() => $"{Name} ({IpAddress})";
        }
    }
}
