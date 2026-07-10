using System;
using System.Drawing;
using System.Windows.Forms;

namespace BiometricAgent
{
    public class NotificationAlertForm : Form
    {
        private Label lblMessage;
        private Label lblIcon;
        private Button btnClose;

        public NotificationAlertForm(string message)
        {
            this.Text = "Device Alert";
            this.StartPosition = FormStartPosition.Manual;
            this.FormBorderStyle = FormBorderStyle.None;
            this.TopMost = true; // Stays on top
            this.BackColor = Color.FromArgb(40, 42, 54); // Dark theme
            this.ForeColor = Color.White;
            this.ShowInTaskbar = false;

            lblIcon = new Label
            {
                Text = "⚠️",
                Font = new Font("Segoe UI Emoji", 20),
                Location = new Point(10, 15),
                AutoSize = true,
                ForeColor = Color.FromArgb(255, 184, 108) // Warning color
            };
            this.Controls.Add(lblIcon);

            lblMessage = new Label
            {
                Text = message,
                Font = new Font("Segoe UI", 10, FontStyle.Regular),
                Location = new Point(50, 15),
                AutoSize = true,
                MaximumSize = new Size(300, 0), // Allow wrapping
                TextAlign = ContentAlignment.TopLeft
            };
            this.Controls.Add(lblMessage);

            btnClose = new Button
            {
                Text = "✕",
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                Size = new Size(25, 25),
                FlatStyle = FlatStyle.Flat,
                ForeColor = Color.FromArgb(200, 200, 200),
                BackColor = Color.Transparent,
                Cursor = Cursors.Hand
            };
            btnClose.FlatAppearance.BorderSize = 0;
            btnClose.FlatAppearance.MouseOverBackColor = Color.FromArgb(255, 85, 85);
            btnClose.Click += (s, e) => this.Close();
            this.Controls.Add(btnClose);

            // Auto-size the form based on the label text
            int height = Math.Max(70, lblMessage.PreferredHeight + 30);
            this.Size = new Size(360, height);
            
            // Reposition button to top right
            btnClose.Location = new Point(this.Width - 30, 5);

            // Position at bottom right of the screen
            Rectangle workingArea = Screen.GetWorkingArea(this);
            this.Location = new Point(workingArea.Right - Size.Width - 20, workingArea.Bottom - Size.Height - 20);

            // Subtle border
            this.Paint += (s, e) => {
                using (var pen = new Pen(Color.FromArgb(255, 85, 85), 2))
                {
                    e.Graphics.DrawRectangle(pen, 0, 0, this.Width - 1, this.Height - 1);
                }
            };
        }
    }
}
