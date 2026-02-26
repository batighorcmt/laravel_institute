import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../state/parent_state.dart';

class AttendanceReportPage extends ConsumerWidget {
  const AttendanceReportPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final overallAsync = ref.watch(parentOverallAttendanceProvider);
    final monthlyAsync = ref.watch(parentAttendanceProvider);

    return Column(
      children: [
        overallAsync.when(
          data: (history) {
            final total = history.length;
            final present = history.where((e) => e['status']?.toString().toLowerCase() == 'present').length;
            final absent = history.where((e) => e['status']?.toString().toLowerCase() == 'absent').length;
            final late = history.where((e) => e['status']?.toString().toLowerCase() == 'late').length;
            final leave = history.where((e) => e['status']?.toString().toLowerCase() == 'leave').length;
            final percentage = total > 0 ? (present / total * 100).toStringAsFixed(1) : '0';

            return Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    children: [
                      _buildStatItem('মোট ক্লাস', total.toString(), Colors.blue),
                      const SizedBox(width: 8),
                      _buildStatItem('উপস্থিত', present.toString(), Colors.green),
                      const SizedBox(width: 8),
                      _buildStatItem('অনুপস্থিত', absent.toString(), Colors.red),
                      const SizedBox(width: 8),
                      _buildStatItem('ছুটি', leave.toString(), Colors.orange),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        children: [
                          _buildCircularProgress(percentage),
                          const SizedBox(width: 24),
                          Expanded(
                            child: Column(
                              children: [
                                _buildSummaryRow('উপস্থিত', present, Colors.green),
                                const Divider(),
                                _buildSummaryRow('অনুপস্থিত', absent, Colors.red),
                                const Divider(),
                                _buildSummaryRow('বিলম্ব', late, Colors.orange),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            );
          },
          loading: () => const Padding(padding: EdgeInsets.all(32), child: Center(child: CircularProgressIndicator())),
          error: (err, _) => Center(child: Text('ত্রুটি: $err')),
        ),
        
        Expanded(
          child: monthlyAsync.when(
            data: (attendance) {
              final mTotal = attendance.length;
              final mPresent = attendance.where((e) => e['status']?.toString().toLowerCase() == 'present').length;
              final mPercent = mTotal > 0 ? (mPresent / mTotal * 100).toStringAsFixed(1) : '0';

              return ListView(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'হাজিরা ইতিহাস',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                          ),
                          Text(
                            'এই মাসের উপস্থিতির হার: $mPercent%',
                            style: TextStyle(fontSize: 13, color: Colors.blue.shade700, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                      _buildMonthPicker(context, ref),
                    ],
                  ),
                  const SizedBox(height: 12),
                  if (attendance.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 32),
                      child: Center(child: Text('এই মাসে কোনো হাজিরা পাওয়া যায়নি')),
                    )
                  else
                    ...attendance.map((e) => _buildAttendanceItem(e)).toList(),
                  const SizedBox(height: 16),
                ],
              );
            },
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (err, _) => Center(child: Text('ত্রুটি: $err')),
          ),
        ),
      ],
    );
  }

  Widget _buildStatItem(String title, String value, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withOpacity(0.2)),
        ),
        child: Column(
          children: [
            Text(title, style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text(
              value,
              style: TextStyle(color: color, fontSize: 18, fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCircularProgress(String percentage) {
    return SizedBox(
      width: 70,
      height: 70,
      child: CustomPaint(
        painter: CircleProgressPainter(double.tryParse(percentage) ?? 0),
        child: Center(
          child: Text(
            '$percentage%',
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
          ),
        ),
      ),
    );
  }

  Widget _buildMonthPicker(BuildContext context, WidgetRef ref) {
    return InkWell(
      onTap: () => _selectMonth(context, ref),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: Colors.blue.withOpacity(0.1),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.blue.withOpacity(0.3)),
        ),
        child: Row(
          children: [
            const Icon(Icons.calendar_month, size: 16, color: Colors.blue),
            const SizedBox(width: 4),
            Text(
              _formatMonthYear(ref.watch(attendanceMonthYearProvider)),
              style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _selectMonth(BuildContext context, WidgetRef ref) async {
    final current = ref.read(attendanceMonthYearProvider);
    final months = [
      'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
      'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'
    ];
    
    showDialog(
      context: context,
      builder: (context) {
        int tempYear = current.year;
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('মাস ও বছর নির্বাচন করুন'),
              content: SizedBox(
                width: double.maxFinite,
                height: 300,
                child: Column(
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        IconButton(
                          icon: const Icon(Icons.arrow_back_ios, size: 16),
                          onPressed: () => setDialogState(() => tempYear--),
                        ),
                        Text('$tempYear', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        IconButton(
                          icon: const Icon(Icons.arrow_forward_ios, size: 16),
                          onPressed: () => setDialogState(() => tempYear++),
                        ),
                      ],
                    ),
                    const Divider(),
                    Expanded(
                      child: GridView.builder(
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 3,
                          childAspectRatio: 2,
                        ),
                        itemCount: 12,
                        itemBuilder: (context, index) {
                          final isSelected = current.month == index + 1 && current.year == tempYear;
                          return InkWell(
                            onTap: () {
                              ref.read(attendanceMonthYearProvider.notifier).state = DateTime(tempYear, index + 1);
                              Navigator.pop(context);
                            },
                            child: Container(
                              alignment: Alignment.center,
                              margin: const EdgeInsets.all(4),
                              decoration: BoxDecoration(
                                color: isSelected ? Colors.blue : null,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                months[index],
                                style: TextStyle(
                                  color: isSelected ? Colors.white : Colors.black,
                                  fontSize: 13,
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ),
                  ],
                ),
              ),
            );
          }
        );
      },
    );
  }

  Widget _buildStatCard(String title, String value, Color color) {
    return Card(
      elevation: 0,
      color: color.withOpacity(0.1),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: color.withOpacity(0.2)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            Text(title, style: TextStyle(color: color, fontSize: 12)),
            const SizedBox(height: 4),
            Text(
              value,
              style: TextStyle(color: color, fontSize: 20, fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryRow(String label, int value, Color color) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Colors.grey)),
        Text(value.toString(), style: TextStyle(color: color, fontWeight: FontWeight.bold)),
      ],
    );
  }

  Widget _buildAttendanceItem(dynamic record) {
    final status = record['status']?.toString().toLowerCase() ?? 'absent';
    Color color = Colors.red;
    String text = 'অনুপস্থিত';
    IconData icon = Icons.cancel;

    if (status == 'present') {
      color = Colors.green;
      text = 'উপস্থিত';
      icon = Icons.check_circle;
    } else if (status == 'late') {
      color = Colors.orange;
      text = 'বিলম্ব';
      icon = Icons.access_time;
    } else if (status == 'leave') {
      color = Colors.blue;
      text = 'ছুটি';
      icon = Icons.event_note;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Icon(icon, color: color),
        title: Text(record['date']?.toString() ?? 'N/A', style: const TextStyle(fontWeight: FontWeight.bold)),
        trailing: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
          decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
          child: Text(text, style: TextStyle(color: color, fontSize: 12, fontWeight: FontWeight.bold)),
        ),
      ),
    );
  }

  String _formatMonthYear(DateTime dt) {
    final months = [
      'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
      'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'
    ];
    return '${months[dt.month - 1]} ${dt.year}';
  }
}

class CircleProgressPainter extends CustomPainter {
  final double progress;
  CircleProgressPainter(this.progress);

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.grey[200]!
      ..strokeWidth = 8
      ..style = PaintingStyle.stroke;
    
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;
    canvas.drawCircle(center, radius, paint);

    final progressPaint = Paint()
      ..color = Colors.green
      ..strokeWidth = 8
      ..style = PaintingStyle.stroke
      ..strokeCap = StrokeCap.round;

    final sweepAngle = (progress / 100) * 2 * 3.14159;
    canvas.drawArc(Rect.fromCircle(center: center, radius: radius), -1.5708, sweepAngle, false, progressPaint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}
