using System.Drawing;
using MaterialSkin;

namespace BiometricAgent
{
    // ─────────────────────────────────────────────────────────────────────────
    // Shared MaterialSkin theme setup + palette, so every form in the app looks
    // like one consistent product instead of a patchwork of hand-picked colors.
    // ─────────────────────────────────────────────────────────────────────────
    internal static class AppTheme
    {
        private static bool _applied;

        public static void Apply()
        {
            if (_applied) return;
            _applied = true;

            var manager = MaterialSkinManager.Instance;
            manager.Theme = MaterialSkinManager.Themes.DARK;
            manager.ColorScheme = new ColorScheme(
                MaterialSkin.Primary.Indigo700, MaterialSkin.Primary.Indigo900, MaterialSkin.Primary.Indigo500,
                MaterialSkin.Accent.Teal200, TextShade.WHITE);
        }

        // Palette kept in sync with the ColorScheme above, for the plain WinForms
        // controls (DataGridView, RichTextBox, NotifyIcon-composited icons, etc.)
        // that don't have a MaterialSkin equivalent, so they read as part of the
        // same design system rather than a different app bolted on.
        public static readonly Color Background = Color.FromArgb(18, 18, 26);
        public static readonly Color Surface = Color.FromArgb(28, 28, 40);
        public static readonly Color SurfaceAlt = Color.FromArgb(36, 36, 50);
        public static readonly Color Primary = Color.FromArgb(63, 81, 181);
        public static readonly Color Accent = Color.FromArgb(29, 233, 182);
        public static readonly Color TextPrimary = Color.FromArgb(240, 240, 245);
        public static readonly Color TextSecondary = Color.FromArgb(160, 160, 176);
        public static readonly Color Success = Color.FromArgb(52, 211, 153);
        public static readonly Color Danger = Color.FromArgb(248, 113, 113);
        public static readonly Color Warning = Color.FromArgb(251, 191, 36);
    }
}
