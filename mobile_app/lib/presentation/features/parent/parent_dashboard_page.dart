import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../state/parent_state.dart';
import '../../state/auth_state.dart';

class ParentDashboardPage extends ConsumerWidget {
  const ParentDashboardPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cs = Theme.of(context).colorScheme;
    final childrenAsync = ref.watch(parentChildrenProvider);
    final homeworkAsync = ref.watch(parentHomeworkProvider);
    final selectedStudentId = ref.watch(selectedStudentIdProvider);

    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(parentChildrenProvider);
        ref.invalidate(parentHomeworkProvider);
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Welcome Banner
          Consumer(
            builder: (context, ref, _) {
              final profileAsync = ref.watch(parentStudentProfileProvider);
              return profileAsync.when(
                data: (profile) {
                  final parentName = profile['guardian_name_bn'] ?? profile['guardian_name_en'] ?? 'অভিভাবক';
                  final studentName = profile['name_bn'] ?? profile['name_en'] ?? 'শিক্ষার্থী';
                  final schoolName = profile['school_name_bn'] ?? profile['school_name'] ?? 'বিদ্যালয়ের নাম';
                  
                  final studentId = profile['student_id'] ?? 'N/A';
                  final className = profile['class'] ?? 'N/A';
                  final sectionName = profile['section'] ?? 'N/A';
                  final rollNo = profile['roll']?.toString() ?? 'N/A';
                  final groupName = profile['group'] ?? 'N/A';

                  return Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [cs.primary, cs.primary.withOpacity(0.8)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: cs.primary.withOpacity(0.3),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.waving_hand, color: Colors.amberAccent, size: 22),
                            const SizedBox(width: 8),
                            Text(
                              'স্বাগতম, $parentName',
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                        Text(
                          'সন্তান: $studentName',
                          style: const TextStyle(
                            fontSize: 14,
                            color: Colors.white,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'শ্রেণি: $className | শাখা: $sectionName | রোল: $rollNo',
                                style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w500),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'আইডি: $studentId | বিভাগ: $groupName',
                                style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w500),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            const Icon(Icons.school, color: Colors.white70, size: 18),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text(
                                schoolName,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 14,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  );
                },
                loading: () => Container(
                  height: 140,
                  decoration: BoxDecoration(color: Colors.grey[200], borderRadius: BorderRadius.circular(16)),
                  child: const Center(child: CircularProgressIndicator()),
                ),
                error: (err, _) => Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(color: Colors.red[50], borderRadius: BorderRadius.circular(16)),
                  child: Text('লোডিং ত্রুটি: $err', style: const TextStyle(color: Colors.red)),
                ),
              );
            },
          ),
          const SizedBox(height: 20),

          // Quick Navigation Row
          const _SectionTitle(title: 'দ্রুত অ্যাক্সেস'),
          const SizedBox(height: 10),
          SizedBox(
            height: 95,
            child: ListView(
              scrollDirection: Axis.horizontal,
              children: [
                _QuickAccessChip(
                  label: 'ক্লাস রুটিন',
                  icon: Icons.schedule,
                  color: Colors.orange,
                  onTap: () => context.push('/parent/routine'),
                ),
                _QuickAccessChip(
                  label: 'হাজিরা',
                  icon: Icons.check_circle_outline,
                  color: Colors.green,
                  onTap: () => context.push('/parent/attendance'),
                ),
                _QuickAccessChip(
                  label: 'মূল্যায়ন',
                  icon: Icons.assessment_outlined,
                  color: Colors.blue,
                  onTap: () => context.push('/parent/evaluations'),
                ),
                _QuickAccessChip(
                  label: 'হোমওয়ার্ক',
                  icon: Icons.assignment_outlined,
                  color: Colors.purple,
                  onTap: () => context.push('/parent/homework'),
                ),
                _QuickAccessChip(
                  label: 'নোটিস',
                  icon: Icons.campaign_outlined,
                  color: Colors.indigo,
                  onTap: () => context.push('/parent/notices'),
                ),
                _QuickAccessChip(
                  label: 'ছুটি',
                  icon: Icons.card_travel_outlined,
                  color: Colors.teal,
                  onTap: () => context.push('/parent/leaves'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),

          // Attendance Status Section
          const _SectionTitle(title: 'আজকের হাজিরার অবস্থা'),
          const SizedBox(height: 10),
          Consumer(
            builder: (context, ref, _) {
              final profileAsync = ref.watch(parentStudentProfileProvider);
              return profileAsync.when(
                data: (profile) {
                  final att = profile['today_attendance'] as Map<String, dynamic>?;
                  if (att == null) return const SizedBox.shrink();

                  final classAtt = att['class'] as Map<String, dynamic>?;
                  final extras = (att['extra_classes'] as List?)?.cast<Map<String, dynamic>>();
                  final teams = (att['teams'] as List?)?.cast<Map<String, dynamic>>();

                  return Column(
                    children: [
                      _AttendanceStatusCard(
                        title: 'শ্রেণি হাজিরা',
                        status: classAtt?['status'],
                        time: classAtt?['updated_at'],
                        icon: Icons.class_outlined,
                        color: Colors.green,
                      ),
                      if (extras != null)
                        ...extras.map((ex) => _AttendanceStatusCard(
                          title: ex['name'] ?? 'এক্সট্রা ক্লাস',
                          status: ex['status'],
                          time: ex['time'],
                          icon: Icons.more_time,
                          color: Colors.orange,
                        )),
                      if (teams != null)
                        ...teams.map((tm) => _AttendanceStatusCard(
                          title: tm['name'] ?? 'টিম হাজিরা',
                          status: tm['status'],
                          time: tm['time'],
                          icon: Icons.group_work_outlined,
                          color: Colors.indigo,
                        )),
                    ],
                  );
                },
                loading: () => const _ShimmerBlock(height: 60),
                error: (_, __) => const SizedBox.shrink(),
              );
            },
          ),
          const SizedBox(height: 20),

          const _SectionTitle(title: 'সাম্প্রতিক হোমওয়ার্ক'),
          const SizedBox(height: 10),
          homeworkAsync.when(
            data: (items) {
              if (items.isEmpty) return const _EmptyWidget(message: 'কোনো সাম্প্রতিক হোমওয়ার্ক নেই');
              return Column(
                children: items.take(3).map((e) {
                  return Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: Container(
                        width: 40, height: 40,
                        decoration: BoxDecoration(color: Colors.purple.withOpacity(0.12), borderRadius: BorderRadius.circular(10)),
                        child: const Icon(Icons.assignment, color: Colors.purple, size: 22),
                      ),
                      title: Text(e['title'] ?? 'Homework', style: const TextStyle(fontWeight: FontWeight.w600), maxLines: 1, overflow: TextOverflow.ellipsis),
                      subtitle: Text('বিষয়: ${e['subject_name'] ?? 'N/A'} | জমা: ${e['submission_date'] ?? 'N/A'}'),
                      onTap: () => context.push('/parent/homework'),
                    ),
                  );
                }).toList(),
              );
            },
            loading: () => const _ShimmerBlock(height: 80),
            error: (err, _) => _ErrorWidget(message: err.toString()),
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }
}

class _AttendanceStatusCard extends StatelessWidget {
  final String title;
  final String? status;
  final String? time;
  final IconData icon;
  final Color color;

  const _AttendanceStatusCard({
    required this.title,
    this.status,
    this.time,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    String statusText = 'এখনো নেয়া হয়নি';
    Color statusColor = Colors.grey;
    IconData statusIcon = Icons.hourglass_empty;

    if (status == 'present') {
      statusText = 'উপস্থিত';
      statusColor = Colors.green;
      statusIcon = Icons.check_circle;
    } else if (status == 'absent') {
      statusText = 'অনুপস্থিত';
      statusColor = Colors.red;
      statusIcon = Icons.cancel;
    } else if (status == 'late') {
      statusText = 'বিলম্বিত';
      statusColor = Colors.orange;
      statusIcon = Icons.access_time;
    }

    String? formattedTime;
    if (time != null) {
      try {
        final dt = DateTime.parse(time!).toLocal();
        final hour = dt.hour > 12 ? dt.hour - 12 : (dt.hour == 0 ? 12 : dt.hour);
        final ampm = dt.hour >= 12 ? 'PM' : 'AM';
        final minute = dt.minute.toString().padLeft(2, '0');
        formattedTime = '$hour:$minute $ampm';
      } catch (_) {}
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: color.withOpacity(0.1), shape: BoxShape.circle),
              child: Icon(icon, color: color, size: 24),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  if (formattedTime != null)
                    Text('সময়: $formattedTime', style: TextStyle(color: Colors.grey[600], fontSize: 11)),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(color: statusColor.withOpacity(0.1), borderRadius: BorderRadius.circular(20), border: Border.all(color: statusColor.withOpacity(0.2))),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(statusIcon, color: statusColor, size: 14),
                  const SizedBox(width: 4),
                  Text(statusText, style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 11)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  const _SectionTitle({required this.title});
  @override
  Widget build(BuildContext context) {
    return Text(title, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold));
  }
}

class _QuickAccessChip extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _QuickAccessChip({required this.label, required this.icon, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 12),
      child: GestureDetector(
        onTap: onTap,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 54, height: 54,
              decoration: BoxDecoration(color: color.withOpacity(0.12), borderRadius: BorderRadius.circular(16)),
              child: Icon(icon, color: color, size: 28),
            ),
            const SizedBox(height: 6),
            Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w500)),
          ],
        ),
      ),
    );
  }
}

class _ShimmerBlock extends StatelessWidget {
  final double height;
  const _ShimmerBlock({required this.height});
  @override
  Widget build(BuildContext context) {
    return Container(
      height: height,
      decoration: BoxDecoration(color: Colors.grey[200], borderRadius: BorderRadius.circular(12)),
      child: const Center(child: CircularProgressIndicator()),
    );
  }
}

class _EmptyWidget extends StatelessWidget {
  final String message;
  const _EmptyWidget({required this.message});
  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Center(child: Text(message, style: TextStyle(color: Colors.grey[600]))),
      ),
    );
  }
}

class _ErrorWidget extends StatelessWidget {
  final String message;
  const _ErrorWidget({required this.message});
  @override
  Widget build(BuildContext context) {
    return Card(
      color: Colors.red.withOpacity(0.05),
      child: Padding(padding: const EdgeInsets.all(16), child: Text('ত্রুটি: $message', style: const TextStyle(color: Colors.red))),
    );
  }
}

