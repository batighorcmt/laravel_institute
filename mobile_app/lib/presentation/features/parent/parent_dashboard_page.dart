import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../state/parent_state.dart';
import '../../routes/app_router.dart';
import '../../widgets/notice_details_modal.dart';

class ParentDashboardPage extends ConsumerStatefulWidget {
  const ParentDashboardPage({super.key});

  @override
  ConsumerState<ParentDashboardPage> createState() =>
      _ParentDashboardPageState();
}

class _ParentDashboardPageState extends ConsumerState<ParentDashboardPage>
    with RouteAware {
  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    routeObserver.subscribe(this, ModalRoute.of(context)!);
  }

  @override
  void dispose() {
    routeObserver.unsubscribe(this);
    super.dispose();
  }

  @override
  void didPopNext() {
    // Auto refresh when returning from any page
    ref.invalidate(parentChildrenProvider);
    ref.invalidate(parentHomeworkProvider);
    ref.invalidate(parentFeesProvider);
    ref.invalidate(parentStudentProfileProvider);
  }

  @override
  Widget build(BuildContext context) {
    final ref = this.ref; // Ensure ref is available in build
    final cs = Theme.of(context).colorScheme;
    final childrenAsync = ref.watch(parentChildrenProvider);
    final homeworkAsync = ref.watch(parentHomeworkProvider);
    final selectedStudentId = ref.watch(selectedStudentIdProvider);

    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(parentChildrenProvider);
        ref.invalidate(parentHomeworkProvider);
        ref.invalidate(parentFeesProvider);
        ref.invalidate(parentStudentProfileProvider);
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
                  final parentName =
                      profile['guardian_name_bn'] ??
                      profile['guardian_name_en'] ??
                      'অভিভাবক';
                  final studentName =
                      profile['name_bn'] ?? profile['name_en'] ?? 'শিক্ষার্থী';
                  final schoolName =
                      profile['school_name_bn'] ??
                      profile['school_name'] ??
                      'বিদ্যালয়ের নাম';

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
                        colors: [cs.primary, cs.primary.withValues(alpha: 0.8)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: cs.primary.withValues(alpha: 0.3),
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
                            const Icon(
                              Icons.waving_hand,
                              color: Colors.amberAccent,
                              size: 22,
                            ),
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
                        const SizedBox(height: 12),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'সন্তান: $studentName',
                                style: const TextStyle(
                                  fontSize: 14,
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const Divider(color: Colors.white24, height: 12),
                              Text(
                                'শ্রেণি: $className | শাখা: $sectionName | রোল: $rollNo',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'আইডি: $studentId | বিভাগ: $groupName',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            const Icon(
                              Icons.school,
                              color: Colors.white70,
                              size: 18,
                            ),
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
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Center(child: CircularProgressIndicator()),
                ),
                error: (err, _) => Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.red[50],
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Text(
                    'লোডিং ত্রুটি: $err',
                    style: const TextStyle(color: Colors.red),
                  ),
                ),
              );
            },
          ),
          const SizedBox(height: 20),

          const SizedBox(height: 10),
          // Latest Notices Section (Unread display showing total unread count)
          Consumer(
            builder: (context, ref, _) {
              final noticesAsync = ref.watch(parentNoticesProvider);
              return noticesAsync.when(
                data: (notices) {
                  final unreadNoticesList = notices
                      .where((n) => n['is_unread'] == true)
                      .toList();

                  if (unreadNoticesList.isEmpty) return const SizedBox.shrink();

                  final displayNotices = unreadNoticesList.take(2).toList();
                  final totalUnread = unreadNoticesList.length;

                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              const Icon(
                                Icons.error_outline,
                                color: Colors.red,
                                size: 20,
                              ),
                              const SizedBox(width: 8),
                              _SectionTitle(
                                title: 'সর্বশেষ নোটিশ ($totalUnreadটি অপঠিত)',
                                color: Colors.red[800],
                              ),
                            ],
                          ),
                          TextButton(
                            onPressed: () => context.push('/notice-board'),
                            child: const Text('সব দেখুন'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      ...displayNotices.map(
                        (n) => Card(
                          margin: const EdgeInsets.only(bottom: 8),
                          elevation: 2,
                          shadowColor: Colors.red.withValues(alpha: 0.2),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                            side: BorderSide(
                              color: Colors.red.withValues(alpha: 0.3),
                              width: 1,
                            ),
                          ),
                          child: ListTile(
                            visualDensity: VisualDensity.compact,
                            onTap: () => NoticeDetailsModal.show(
                              context,
                              ref,
                              n,
                              onRead: () {
                                ref.invalidate(parentNoticesProvider);
                              },
                            ),

                            leading: CircleAvatar(
                              radius: 18,
                              backgroundColor: Colors.red.withValues(
                                alpha: 0.12,
                              ),
                              child: const Icon(
                                Icons.campaign,
                                color: Colors.red,
                                size: 20,
                              ),
                            ),
                            title: Text(
                              n['title'] ?? 'N/A',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 13,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            subtitle: Text(
                              '${n['date']} | ${n['author']}',
                              style: const TextStyle(fontSize: 11),
                            ),
                            trailing: Container(
                              padding: const EdgeInsets.all(4),
                              decoration: BoxDecoration(
                                color: Colors.red.withValues(alpha: 0.1),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.arrow_forward_ios,
                                size: 10,
                                color: Colors.red,
                              ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                  );
                },
                loading: () => const SizedBox.shrink(),
                error: (_, _) => const SizedBox.shrink(),
              );
            },
          ),

          // Quick Navigation Row
          const _SectionTitle(title: 'দ্রুত অ্যাক্সেস'),

          const SizedBox(height: 10),
          SizedBox(
            height: 95,
            child: ListView(
              scrollDirection: Axis.horizontal,
              children: [
                _QuickAccessChip(
                  label: 'ফিস হিসাব',
                  icon: Icons.wallet,
                  color: Colors.pink,
                  onTap: () => context.push('/parent/fees'),
                ),
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
                  onTap: () => context.push('/notice-board'),
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

          // Fee Payment Alert Card
          Consumer(
            builder: (context, ref, _) {
              final feesAsync = ref.watch(parentFeesProvider);
              return feesAsync.when(
                data: (data) {
                  final dueFees = data['due_fees'] as List?;
                  if (dueFees == null || dueFees.isEmpty) {
                    return const SizedBox.shrink();
                  }

                  double totalDue = 0;
                  for (var f in dueFees) {
                    totalDue += (f['total_due'] as num).toDouble();
                  }

                  if (totalDue <= 0.01) return const SizedBox.shrink();

                  return Container(
                    margin: const EdgeInsets.only(bottom: 20, top: 5),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [Colors.red[400]!, Colors.red[700]!],
                      ),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.red.withValues(alpha: 0.3),
                          blurRadius: 8,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.2),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(
                            Icons.priority_high,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'বকেয়া ফিস',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 15,
                                ),
                              ),
                              Text(
                                'মোট বকেয়া: ৳${totalDue.toStringAsFixed(2)}',
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.9),
                                  fontSize: 13,
                                ),
                              ),
                            ],
                          ),
                        ),
                        FilledButton(
                          style: FilledButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: Colors.red[700],
                            padding: const EdgeInsets.symmetric(horizontal: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ),
                          onPressed: () => context.push('/parent/fees'),
                          child: const Text(
                            'পেমেন্ট করুন',
                            style: TextStyle(fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ),
                  );
                },
                loading: () => const SizedBox.shrink(),
                error: (err, _) => const SizedBox.shrink(),
              );
            },
          ),

          // Attendance Status Section
          const _SectionTitle(title: 'আজকের হাজিরার অবস্থা'),
          const SizedBox(height: 10),
          Consumer(
            builder: (context, ref, _) {
              final profileAsync = ref.watch(parentStudentProfileProvider);
              return profileAsync.when(
                data: (profile) {
                  final att =
                      profile['today_attendance'] as Map<String, dynamic>?;
                  if (att == null) return const SizedBox.shrink();

                  final classAtt = att['class'] as Map<String, dynamic>?;
                  final extras = (att['extra_classes'] as List?)
                      ?.cast<Map<String, dynamic>>();
                  final teams = (att['teams'] as List?)
                      ?.cast<Map<String, dynamic>>();

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
                        ...extras.map(
                          (ex) => _AttendanceStatusCard(
                            title: ex['name'] ?? 'এক্সট্রা ক্লাস',
                            status: ex['status'],
                            time: ex['time'],
                            icon: Icons.more_time,
                            color: Colors.orange,
                          ),
                        ),
                      if (teams != null)
                        ...teams.map(
                          (tm) => _AttendanceStatusCard(
                            title: tm['name'] ?? 'টিম হাজিরা',
                            status: tm['status'],
                            time: tm['time'],
                            icon: Icons.group_work_outlined,
                            color: Colors.indigo,
                          ),
                        ),
                    ],
                  );
                },
                loading: () => const _ShimmerBlock(height: 60),
                error: (_, _) => const SizedBox.shrink(),
              );
            },
          ),
          const SizedBox(height: 20),

          // Lesson Evaluation Section
          const _SectionTitle(title: 'আজকের লেসন ইভ্যালুয়েশন'),
          const SizedBox(height: 10),
          Consumer(
            builder: (context, ref, _) {
              final profileAsync = ref.watch(parentStudentProfileProvider);
              return profileAsync.when(
                data: (profile) {
                  final evals = (profile['today_evaluations'] as List?)
                      ?.cast<Map<String, dynamic>>();
                  if (evals == null || evals.isEmpty) {
                    return const _EmptyWidget(
                      message: 'আজকের কোনো লেসন ইভ্যালুয়েশন পাওয়া যায়নি',
                    );
                  }

                  return Column(
                    children: evals
                        .map(
                          (ev) => GestureDetector(
                            onTap: () => context.push('/parent/evaluations'),
                            child: _EvaluationStatusCard(
                              period: ev['period'],
                              subject: ev['subject'] ?? 'N/A',
                              status: ev['status'],
                              time: ev['time'],
                              notes: ev['notes'],
                            ),
                          ),
                        )
                        .toList(),
                  );
                },
                loading: () => const _ShimmerBlock(height: 80),
                error: (_, _) => const SizedBox.shrink(),
              );
            },
          ),
          const SizedBox(height: 20),

          const _SectionTitle(title: 'আজকের প্রদানকৃত হোমওয়ার্ক'),
          const SizedBox(height: 10),
          homeworkAsync.when(
            data: (items) {
              final todayStr = DateTime.now()
                  .toIso8601String()
                  .split('T')
                  .first;
              final todayItems = items
                  .where((e) => e['homework_date'] == todayStr)
                  .toList();

              if (todayItems.isEmpty) {
                return const _EmptyWidget(message: 'আজকের কোনো হোমওয়ার্ক নেই');
              }
              return Column(
                children: todayItems.map((e) {
                  return Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: Colors.purple.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Icon(
                          Icons.assignment,
                          color: Colors.purple,
                          size: 22,
                        ),
                      ),
                      title: Text(
                        e['title'] ?? 'Homework',
                        style: const TextStyle(fontWeight: FontWeight.w600),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      subtitle: Text(
                        'বিষয়: ${e['subject_name'] ?? 'N/A'} | জমা: ${e['submission_date'] ?? 'N/A'}',
                      ),
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
        final hour = dt.hour > 12
            ? dt.hour - 12
            : (dt.hour == 0 ? 12 : dt.hour);
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
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 24),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                  ),
                  if (formattedTime != null)
                    Text(
                      'সময়: $formattedTime',
                      style: TextStyle(color: Colors.grey[600], fontSize: 11),
                    ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: statusColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: statusColor.withValues(alpha: 0.2)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(statusIcon, color: statusColor, size: 14),
                  const SizedBox(width: 4),
                  Text(
                    statusText,
                    style: TextStyle(
                      color: statusColor,
                      fontWeight: FontWeight.bold,
                      fontSize: 11,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _EvaluationStatusCard extends StatelessWidget {
  final int? period;
  final String subject;
  final String? status;
  final String? time;
  final String? notes;

  const _EvaluationStatusCard({
    this.period,
    required this.subject,
    this.status,
    this.time,
    this.notes,
  });

  @override
  Widget build(BuildContext context) {
    String statusText = 'এখনো মূল্যায়ন হয়নি';
    Color statusColor = Colors.grey;
    IconData statusIcon = Icons.hourglass_empty;

    if (status == 'completed' || status == 'read') {
      statusText = 'পড়া হয়েছে';
      statusColor = Colors.green;
      statusIcon = Icons.check_circle;
    } else if (status == 'partial') {
      statusText = 'আংশিক হয়েছে';
      statusColor = Colors.orange;
      statusIcon = Icons.pending;
    } else if (status == 'not_done') {
      statusText = 'পড়া হয়নি';
      statusColor = Colors.red;
      statusIcon = Icons.cancel;
    } else if (status == 'absent') {
      statusText = 'অনুপস্থিত';
      statusColor = Colors.blueGrey;
      statusIcon = Icons.person_off;
    }

    String? formattedTime;
    if (time != null) {
      try {
        final dt = DateTime.parse(time!).toLocal();
        final hour = dt.hour > 12
            ? dt.hour - 12
            : (dt.hour == 0 ? 12 : dt.hour);
        final ampm = dt.hour >= 12 ? 'PM' : 'AM';
        final minute = dt.minute.toString().padLeft(2, '0');
        formattedTime = '$hour:$minute $ampm';
      } catch (_) {}
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.blue.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    'পিরিয়ড: ${period ?? 'N/A'}',
                    style: const TextStyle(
                      color: Colors.blue,
                      fontWeight: FontWeight.bold,
                      fontSize: 11,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    subject,
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: statusColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    children: [
                      Icon(statusIcon, color: statusColor, size: 14),
                      const SizedBox(width: 4),
                      Text(
                        statusText,
                        style: TextStyle(
                          color: statusColor,
                          fontWeight: FontWeight.bold,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (formattedTime != null || notes != null) ...[
              const Divider(height: 16),
              if (formattedTime != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.access_time,
                        size: 14,
                        color: Colors.grey,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        'মূল্যায়নের সময়: $formattedTime',
                        style: TextStyle(color: Colors.grey[600], fontSize: 11),
                      ),
                    ],
                  ),
                ),
              if (notes != null && notes!.isNotEmpty)
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.notes, size: 14, color: Colors.grey),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        'পাঠ বিষয়: $notes',
                        style: TextStyle(
                          color: Colors.grey[700],
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
            ],
          ],
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  final Color? color;
  const _SectionTitle({required this.title, this.color});
  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: color),
    );
  }
}

class _QuickAccessChip extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _QuickAccessChip({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

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
              width: 54,
              height: 54,
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(16),
              ),
              child: Icon(icon, color: color, size: 28),
            ),
            const SizedBox(height: 6),
            Text(
              label,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w500),
            ),
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
      decoration: BoxDecoration(
        color: Colors.grey[200],
        borderRadius: BorderRadius.circular(12),
      ),
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
        child: Center(
          child: Text(message, style: TextStyle(color: Colors.grey[600])),
        ),
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
      color: Colors.red.withValues(alpha: 0.05),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Text(
          'ত্রুটি: $message',
          style: const TextStyle(color: Colors.red),
        ),
      ),
    );
  }
}
