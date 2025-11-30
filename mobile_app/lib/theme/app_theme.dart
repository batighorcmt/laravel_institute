import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  static ThemeData light() {
    final base = ThemeData.light();
    const primary = Color(0xFF00BF6D);
    const secondary = Color(0xFF0FD68A);
    var colorScheme = ColorScheme.fromSeed(
      seedColor: primary,
      primary: primary,
      secondary: secondary,
      surface: const Color(0xFFFFFFFF),
      background: const Color(0xFFF5F7F9),
    );
    // Ensure readable dark foreground on light surfaces.
    colorScheme = colorScheme.copyWith(
      onSurface: const Color(0xFF1A1D1F),
      onPrimary: Colors.white,
      onSecondary: Colors.white,
      onBackground: const Color(0xFF1A1D1F),
    );

    final baseInter = GoogleFonts.interTextTheme(base.textTheme);
    final textTheme = baseInter
        .copyWith(
          headlineSmall: baseInter.headlineSmall?.copyWith(
            fontWeight: FontWeight.w700,
            fontSize: 28,
            color: const Color(0xFF111111),
          ),
          titleMedium: baseInter.titleMedium?.copyWith(
            fontWeight: FontWeight.w600,
            fontSize: 18,
            color: const Color(0xFF111111),
          ),
        )
        .apply(
          bodyColor: const Color(0xFF111111),
          displayColor: const Color(0xFF111111),
        );

    return base.copyWith(
      colorScheme: colorScheme,
      textTheme: textTheme,
      scaffoldBackgroundColor: colorScheme.background,
      iconTheme: const IconThemeData(color: Color(0xFF111111)),
      appBarTheme: AppBarTheme(
        backgroundColor: colorScheme.background,
        elevation: 0,
        foregroundColor: const Color(0xFF111111),
        iconTheme: const IconThemeData(color: Color(0xFF111111)),
        titleTextStyle: GoogleFonts.inter(
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: const Color(0xFF111111),
        ),
      ),
      cardTheme: CardThemeData(
        color: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        elevation: 2,
        shadowColor: Colors.black12,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primary,
          foregroundColor: Colors.white,
          shape: const StadiumBorder(),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          textStyle: GoogleFonts.inter(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        contentTextStyle: GoogleFonts.inter(color: Colors.white),
      ),
    );
  }

  static ThemeData dark() {
    final base = ThemeData.dark();
    const primary = Color(0xFF00BF6D);
    const secondary = Color(0xFF0FD68A);
    final colorScheme = ColorScheme.fromSeed(
      seedColor: primary,
      brightness: Brightness.dark,
      primary: primary,
      secondary: secondary,
      surface: const Color(0xFF1F2422),
      background: const Color(0xFF121614),
    );
    final textTheme = GoogleFonts.interTextTheme(base.textTheme).copyWith(
      headlineSmall: GoogleFonts.inter(
        fontWeight: FontWeight.w700,
        fontSize: 28,
        color: Colors.white,
      ),
      titleMedium: GoogleFonts.inter(
        fontWeight: FontWeight.w600,
        fontSize: 18,
        color: Colors.white,
      ),
      bodyMedium: GoogleFonts.inter(fontSize: 14, color: Colors.white70),
    );
    return base.copyWith(
      colorScheme: colorScheme,
      textTheme: textTheme,
      scaffoldBackgroundColor: colorScheme.background,
      iconTheme: const IconThemeData(color: Colors.white),
      appBarTheme: AppBarTheme(
        backgroundColor: colorScheme.background,
        elevation: 0,
        foregroundColor: colorScheme.primary,
        iconTheme: const IconThemeData(color: Colors.white),
        titleTextStyle: GoogleFonts.inter(
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
      ),
      cardTheme: CardThemeData(
        color: colorScheme.surface,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        elevation: 0,
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        contentTextStyle: GoogleFonts.inter(color: Colors.white),
        backgroundColor: const Color(0xFF2A2F2D),
      ),
    );
  }
}
