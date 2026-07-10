using System;
using System.Drawing;
using System.Windows.Forms;

namespace BiometricAgent
{
    public class NotificationAlertForm : Form
    {
        private Label lblMessage;
        private Label lblTitle;
        private Button btnClose;
        private Panel pTop;

        public NotificationAlertForm(string message)
        {
            this.Text = "Device Alert";
            this.StartPosition = FormStartPosition.Manual;
            this.FormBorderStyle = FormBorderStyle.None;
            this.TopMost = true;
            this.ShowInTaskbar = false;
            this.BackColor = Color.FromArgb(30, 32, 48);

            // ── Top bar ────────────────────────────────
            pTop = new Panel
            {
                Dock = DockStyle.Top,
                Height = 32,
                BackColor = Color.FromArgb(220, 38, 38)  // Red header
            };

            lblTitle = new Label
            {
                Text = "  ⚠  Device Alert",
                Dock = DockStyle.Fill,
                ForeColor = Color.White,
                Font = new Font("Segoe UI", 9, FontStyle.Bold),
                TextAlign = ContentAlignment.MiddleLeft
            };

            btnClose = new Button
            {
                Text = "✕",
                Dock = DockStyle.Right,
                Width = 32,
                FlatStyle = FlatStyle.Flat,
                ForeColor = Color.White,
                BackColor = Color.Transparent,
                Cursor = Cursors.Hand,
                Font = new Font("Segoe UI", 9, FontStyle.Bold)
            };
            btnClose.FlatAppearance.BorderSize = 0;
            btnClose.FlatAppearance.MouseOverBackColor = Color.FromArgb(185, 28, 28);
            btnClose.Click += (s, e) => this.Close();

            pTop.Controls.Add(lblTitle);
            pTop.Controls.Add(btnClose);

            // ── Message label ──────────────────────────
            lblMessage = new Label
            {
                Text = message,
                Font = new Font("Segoe UI", 9.5f, FontStyle.Regular),
                ForeColor = Color.FromArgb(230, 230, 250),
                Padding = new Padding(12, 10, 12, 10),
                AutoSize = false,
                TextAlign = ContentAlignment.TopLeft
            };

            // Calculate size
            int formWidth = 320;
            using (var g = Graphics.FromHwnd(IntPtr.Zero))
            {
                var sz = g.MeasureString(message, lblMessage.Font, formWidth - 24);
                lblMessage.Size = new Size(formWidth, (int)sz.Height + 24);
            }

            this.Width = formWidth;
            this.Height = 32 + lblMessage.Height + 4;

            lblMessage.Location = new Point(0, 32);
            lblMessage.Width = formWidth;

            // ── Positioning: bottom-right ──────────────
            Rectangle wa = Screen.GetWorkingArea(this);
            this.Location = new Point(wa.Right - this.Width - 16, wa.Bottom - this.Height - 16);

            this.Controls.Add(lblMessage);
            this.Controls.Add(pTop);

            // Subtle border via Paint
            this.Paint += (s, e) =>
            {
                using var pen = new Pen(Color.FromArgb(220, 38, 38), 1);
                e.Graphics.DrawRectangle(pen, 0, 0, this.Width - 1, this.Height - 1);
            };
        }
    }
}
