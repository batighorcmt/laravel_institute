using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Runtime.InteropServices;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Builds the two tray-icon variants (server connected / disconnected) once,
    // by compositing a small check/cross badge onto the app's base icon. Cached
    // so callers just swap between two ready-made Icon instances on state change
    // instead of recompositing on every tick.
    // ─────────────────────────────────────────────────────────────────────────
    internal static class TrayIconComposer
    {
        [DllImport("user32.dll", SetLastError = true)]
        private static extern bool DestroyIcon(IntPtr handle);

        public static (Icon connected, Icon disconnected) BuildBadgedIcons(Icon baseIcon)
        {
            var connected = Compose(baseIcon, Color.FromArgb(52, 211, 153), drawCheck: true);
            var disconnected = Compose(baseIcon, Color.FromArgb(248, 113, 113), drawCheck: false);
            return (connected, disconnected);
        }

        private static Icon Compose(Icon baseIcon, Color badgeColor, bool drawCheck)
        {
            const int size = 32;
            using var bitmap = new Bitmap(size, size);
            using (var g = Graphics.FromImage(bitmap))
            {
                g.SmoothingMode = SmoothingMode.AntiAlias;
                g.DrawIcon(baseIcon, new Rectangle(0, 0, size, size));

                // Badge circle in the bottom-right corner.
                int badgeSize = 14;
                int x = size - badgeSize - 1;
                int y = size - badgeSize - 1;
                using (var badgeBrush = new SolidBrush(badgeColor))
                using (var borderPen = new Pen(Color.White, 1.5f))
                {
                    var rect = new Rectangle(x, y, badgeSize, badgeSize);
                    g.FillEllipse(badgeBrush, rect);
                    g.DrawEllipse(borderPen, rect);
                }

                using var markPen = new Pen(Color.White, 1.8f) { StartCap = LineCap.Round, EndCap = LineCap.Round };
                if (drawCheck)
                {
                    g.DrawLines(markPen, new[]
                    {
                        new Point(x + 3, y + 7),
                        new Point(x + 6, y + 10),
                        new Point(x + 11, y + 4)
                    });
                }
                else
                {
                    g.DrawLine(markPen, x + 4, y + 4, x + badgeSize - 4, y + badgeSize - 4);
                    g.DrawLine(markPen, x + badgeSize - 4, y + 4, x + 4, y + badgeSize - 4);
                }
            }

            IntPtr hIcon = bitmap.GetHicon();
            try
            {
                // Icon.FromHandle wraps the HICON but does not own/destroy it - clone
                // into a fully managed Icon, then release the native handle ourselves
                // so it isn't leaked (GetHicon()'s handle is never cleaned up by the GC).
                using var temp = Icon.FromHandle(hIcon);
                return (Icon)temp.Clone();
            }
            finally
            {
                DestroyIcon(hIcon);
            }
        }
    }
}
