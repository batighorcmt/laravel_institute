import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../state/parent_state.dart';

class AttendanceReportPage extends ConsumerWidget {
  const AttendanceReportPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final attendanceAsync = ref.watch(parentAttendanceProvider);

    return attendanceAsync.when(
      data: (attendance) {
        final total = attendance.length;
        final present = attendance.where((e) => e['status']?.toString().toLowerCase() == 'present').length;
        final absent = attendance.where((e) => e['status']?.toString().toLowerCase() == 'absent').length;
        final late = attendance.where((e) => e['status']?.toString().toLowerCase() == 'late').length;
        final percentage = total > 0 ? (present / total * 100).toStringAsFixed(1) : '0';

        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Summary Cards
              Row(
                children: [
                  Expanded(child: _buildStatCard('মোট ক্লাস', total.toString(), Colors.blue)),
                  const SizedBox(width: 12),
                  Expanded(child: _buildStatCard('উপস্থিত', present.toString(), Colors.green)),
                  const SizedBox(width: 12),
                  Expanded(child: _buildStatCard('অনুপস্থিত', absent.toString(), Colors.red)),
                ],
              ),
              const SizedBox(height: 24),

              // Attendance Percentage
              const Text(
                'উপস্থিতির হার',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Row(
                    children: [
                      SizedBox(
                        width: 80,
                        height: 80,
                        child: CustomPaint(
                          painter: CircleProgressPainter(double.tryParse(percentage) ?? 0),
                          child: Center(
                            child: Text(
                              '$percentage%',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ),
                      ),
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
              const SizedBox(height: 24),

              // Attendance History
              const Text(
                'হাজিরা ইতিহাস',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              ...attendance.map((e) => _buildAttendanceItem(e)).toList(),
            ],
          ),
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (err, _) => Center(child: Text('ত্রুটি: $err')),
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
