using System;
using System.Drawing;
using System.Windows.Forms;

namespace BiometricAgent
{
    public class NotificationAlertForm : Form
    {
        private Label lblMessage;
        private Button btnClose;

        public NotificationAlertForm(string message)
        {
            this.Text = "Device Alert";
            this.Size = new Size(350, 120);
            this.StartPosition = FormStartPosition.Manual;
            
            // Position at top right of the screen
            Rectangle workingArea = Screen.GetWorkingArea(this);
            this.Location = new Point(workingArea.Right - Size.Width - 20, 20);

            this.FormBorderStyle = FormBorderStyle.None;
            this.TopMost = true; // Stays on top
            this.BackColor = Color.FromArgb(220, 38, 38); // Red background
            this.ForeColor = Color.White;

            lblMessage = new Label
            {
                Text = message,
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                Location = new Point(15, 30),
                AutoSize = false,
                Size = new Size(290, 60),
                TextAlign = ContentAlignment.MiddleLeft
            };

            btnClose = new Button
            {
                Text = "X",
                Font = new Font("Segoe UI", 10, FontStyle.Bold),
                Size = new Size(30, 30),
                Location = new Point(310, 5),
                FlatStyle = FlatStyle.Flat,
                ForeColor = Color.White,
                BackColor = Color.Transparent,
                Cursor = Cursors.Hand
            };
            btnClose.FlatAppearance.BorderSize = 0;
            btnClose.Click += (s, e) => this.Close();

            this.Controls.Add(lblMessage);
            this.Controls.Add(btnClose);
        }
    }
}
