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
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [cs.primary, cs.primary.withOpacity(0.65)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: cs.primary.withOpacity(0.3),
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.waving_hand, color: Colors.amberAccent, size: 28),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'স্বাগতম, ${ref.watch(authProvider).asData?.value?.bnName ?? ref.watch(authProvider).asData?.value?.name ?? 'অভিভাবক'}!',
                        style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'আপনার সন্তানের শিক্ষা সংক্রান্ত সকল তথ্য এখানে পাবেন।',
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.9),
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Quick Navigation Row
          const _SectionTitle(title: 'দ্রুত অ্যাক্সেস'),
          const SizedBox(height: 10),
          SizedBox(
            height: 88,
            child: ListView(
              scrollDirection: Axis.horizontal,
              children: [
                _QuickAccessChip(
                  label: 'ক্লাস রুটিন',
                  icon: Icons.schedule,
                  color: Colors.orange,
                  onTap: () => context.go('/parent/routine'),
                ),
                _QuickAccessChip(
                  label: 'হাজিরা',
                  icon: Icons.check_circle_outline,
                  color: Colors.green,
                  onTap: () => context.go('/parent/attendance'),
                ),
                _QuickAccessChip(
                  label: 'হোমওয়ার্ক',
                  icon: Icons.assignment_outlined,
                  color: Colors.purple,
                  onTap: () => context.go('/parent/homework'),
                ),
                _QuickAccessChip(
                  label: 'নোটিস',
                  icon: Icons.campaign_outlined,
                  color: Colors.indigo,
                  onTap: () => context.go('/parent/notices'),
                ),
                _QuickAccessChip(
                  label: 'ছুটি',
                  icon: Icons.card_travel_outlined,
                  color: Colors.teal,
                  onTap: () => context.go('/parent/leaves'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Children Section
          const _SectionTitle(title: 'সন্তান সমূহ'),
          const SizedBox(height: 10),
          childrenAsync.when(
            data: (items) {
              if (items.isEmpty) return const _EmptyWidget(message: 'কোনো সন্তান যুক্ত নেই');
              return Column(
                children: items.map((e) {
                  final isSelected = e['id'] == selectedStudentId;
                  return Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    elevation: isSelected ? 4 : 1,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                      side: isSelected ? BorderSide(color: cs.primary, width: 2) : BorderSide.none,
                    ),
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: (isSelected ? cs.primary : Colors.grey).withOpacity(0.15),
                        child: Text(
                          (e['name']?[0] ?? 'S').toUpperCase(),
                          style: TextStyle(fontWeight: FontWeight.bold, color: isSelected ? cs.primary : Colors.grey),
                        ),
                      ),
                      title: Text(e['name'] ?? 'Student', style: const TextStyle(fontWeight: FontWeight.w600)),
                      subtitle: Text('শ্রেণি: ${e['class'] ?? 'N/A'} | শাখা: ${e['section'] ?? 'N/A'}'),
                      trailing: isSelected ? Icon(Icons.check_circle, color: cs.primary) : null,
                      onTap: () {
                         ref.read(selectedStudentIdProvider.notifier).update(e['id']);
                      },
                    ),
                  );
                }).toList(),
              );
            },
            loading: () => const _ShimmerBlock(height: 80),
            error: (err, _) => _ErrorWidget(message: err.toString()),
          ),
          const SizedBox(height: 24),

          // Recent Homework
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
                      onTap: () => context.go('/parent/homework'),
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

