import 'package:flutter/material.dart';

/// Shared UI pieces for the principal's monthly attendance reports
/// (teacher / class / extra-class) so all three tabs look consistent.

const List<String> kBnMonths = [
  'জানুয়ারি',
  'ফেব্রুয়ারি',
  'মার্চ',
  'এপ্রিল',
  'মে',
  'জুন',
  'জুলাই',
  'আগস্ট',
  'সেপ্টেম্বর',
  'অক্টোবর',
  'নভেম্বর',
  'ডিসেম্বর',
];

int toInt(dynamic v) {
  if (v == null) return 0;
  if (v is num) return v.toInt();
  return int.tryParse(v.toString()) ?? 0;
}

double? toPct(dynamic v) {
  if (v == null) return null;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString());
}

/// Header row showing the selected month and year, plus a button to pick another month.
class MonthPickerRow extends StatelessWidget {
  final int year;
  final int month;
  final VoidCallback onPick;
  final VoidCallback onRefresh;
  const MonthPickerRow({
    super.key,
    required this.year,
    required this.month,
    required this.onPick,
    required this.onRefresh,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 4),
      child: Row(
        children: [
          Expanded(
            child: Text(
              'মাস: ${kBnMonths[month - 1]} $year',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
          IconButton(
            tooltip: 'মাস পরিবর্তন করুন',
            icon: const Icon(Icons.calendar_month_outlined),
            onPressed: onPick,
          ),
          IconButton(
            tooltip: 'Refresh',
            icon: const Icon(Icons.refresh),
            onPressed: onRefresh,
          ),
        ],
      ),
    );
  }
}

/// Shows a bottom sheet with a year dropdown + a 12-month grid to pick from.
Future<({int year, int month})?> pickYearMonth(
  BuildContext context, {
  required int initialYear,
  required int initialMonth,
}) async {
  int selYear = initialYear;
  final now = DateTime.now();
  return showModalBottomSheet<({int year, int month})>(
    context: context,
    isScrollControlled: true,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
    ),
    builder: (ctx) {
      return StatefulBuilder(
        builder: (ctx, setSheetState) {
          return Padding(
            padding: EdgeInsets.only(
              left: 16,
              right: 16,
              top: 16,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 16,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.chevron_left),
                      onPressed: () => setSheetState(() => selYear -= 1),
                    ),
                    Text(
                      '$selYear',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.chevron_right),
                      onPressed: selYear >= now.year
                          ? null
                          : () => setSheetState(() => selYear += 1),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                GridView.count(
                  crossAxisCount: 3,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  mainAxisSpacing: 8,
                  crossAxisSpacing: 8,
                  childAspectRatio: 2.2,
                  children: List.generate(12, (i) {
                    final m = i + 1;
                    final disabled = selYear == now.year && m > now.month;
                    return OutlinedButton(
                      onPressed: disabled
                          ? null
                          : () => Navigator.of(
                              ctx,
                            ).pop((year: selYear, month: m)),
                      child: Text(kBnMonths[i]),
                    );
                  }),
                ),
                const SizedBox(height: 8),
              ],
            ),
          );
        },
      );
    },
  );
}

/// Small colored circle with rank number — gold/silver/bronze for top 3.
class RankBadge extends StatelessWidget {
  final int rank;
  const RankBadge({super.key, required this.rank});

  @override
  Widget build(BuildContext context) {
    Color bg;
    Color fg = Colors.white;
    switch (rank) {
      case 1:
        bg = const Color(0xFFD4AF37); // gold
        break;
      case 2:
        bg = const Color(0xFFA0A5AA); // silver
        break;
      case 3:
        bg = const Color(0xFFB08D57); // bronze
        break;
      default:
        bg = Colors.grey[300]!;
        fg = Colors.black87;
    }
    return CircleAvatar(
      radius: 15,
      backgroundColor: bg,
      child: Text(
        '$rank',
        style: TextStyle(color: fg, fontWeight: FontWeight.bold, fontSize: 12),
      ),
    );
  }
}

/// A single stat in the summary card's grid (label above value).
class MonthlyStatTile extends StatelessWidget {
  final String label;
  final String value;
  final Color? color;
  const MonthlyStatTile({
    super.key,
    required this.label,
    required this.value,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: TextStyle(fontSize: 11.5, color: Colors.grey[700])),
        const SizedBox(height: 2),
        Text(
          value,
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
      ],
    );
  }
}

/// The summary card shown at the top of every monthly tab: working days,
/// days attendance taken, and an overall percentage with a progress bar.
class MonthlySummaryCard extends StatelessWidget {
  final int workingDays;
  final int totalDaysInMonth;
  final int daysAttendanceTaken;
  final double? overallPercentage;
  final List<Widget> extraTiles;
  const MonthlySummaryCard({
    super.key,
    required this.workingDays,
    required this.totalDaysInMonth,
    required this.daysAttendanceTaken,
    required this.overallPercentage,
    this.extraTiles = const [],
  });

  Color _pctColor(double? pct) {
    if (pct == null) return Colors.grey;
    if (pct >= 80) return Colors.green;
    if (pct >= 60) return Colors.orange;
    return Colors.red;
  }

  @override
  Widget build(BuildContext context) {
    final pct = overallPercentage;
    return Card(
      margin: const EdgeInsets.fromLTRB(12, 8, 12, 12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'মাসিক সারসংক্ষেপ',
              style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 20,
              runSpacing: 12,
              children: [
                MonthlyStatTile(
                  label: 'কর্মদিবস',
                  value: '$workingDays / $totalDaysInMonth',
                ),
                MonthlyStatTile(
                  label: 'হাজিরা গৃহীত হয়েছে',
                  value: '$daysAttendanceTaken দিন',
                  color: Colors.indigo,
                ),
                ...extraTiles,
              ],
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: pct == null ? 0 : (pct / 100).clamp(0.0, 1.0),
                      minHeight: 8,
                      backgroundColor: Colors.grey[200],
                      color: _pctColor(pct),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Text(
                  pct == null ? '—' : '${pct.toStringAsFixed(1)}%',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: _pctColor(pct),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 2),
            Text(
              'সার্বিক উপস্থিতির হার',
              style: TextStyle(fontSize: 11.5, color: Colors.grey[600]),
            ),
          ],
        ),
      ),
    );
  }
}

/// Empty-state used when a month has no attendance/enrollment data yet.
class MonthlyEmptyState extends StatelessWidget {
  final String message;
  const MonthlyEmptyState({super.key, this.message = 'কোনও তথ্য নেই'});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 60),
      child: Center(
        child: Column(
          children: [
            Icon(Icons.inbox_outlined, size: 42, color: Colors.grey[400]),
            const SizedBox(height: 8),
            Text(message, style: TextStyle(color: Colors.grey[600])),
          ],
        ),
      ),
    );
  }
}
