import 'package:flutter/material.dart';

/// Shared color tokens for the lesson-evaluation screens (list, report,
/// mark, student history). Built on the app's real brand green (see
/// AppTheme.light()) plus a warm amber accent for stats/highlights, and the
/// status semantics already established by the mark page (green/orange/red/
/// grey = completed/partial/not_done/absent) — kept consistent everywhere
/// rather than introducing a new unrelated palette per screen.
class LeColors {
  LeColors._();

  static const Color brand = Color(0xFF00BF6D);
  static const Color brandDark = Color(0xFF049655);
  static const Color brandSoft = Color(0xFFE6F9F1);
  static const Color accent = Color(0xFFD97706);
  static const Color accentSoft = Color(0xFFFEF3E2);
  static const Color ink = Color(0xFF1A1D1F);
  static const Color muted = Color(0xFF6B7280);
  static const Color bg = Color(0xFFF6F8F7);

  static const Color completed = Color(0xFF16A34A);
  static const Color partial = Color(0xFFD97706);
  static const Color notDone = Color(0xFFDC2626);
  static const Color absent = Color(0xFF6B7280);
  // Neutral "total/expected" tone for stat pills (blue reads as informational,
  // distinct from the green/red completed-vs-remaining pair).
  static const Color total = Color(0xFF2563EB);

  static Color completedSoft = completed.withValues(alpha: 0.12);
  static Color partialSoft = partial.withValues(alpha: 0.12);
  static Color notDoneSoft = notDone.withValues(alpha: 0.12);
  static Color absentSoft = absent.withValues(alpha: 0.12);

  static const LinearGradient brandGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [brand, brandDark],
  );
}
