import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../state/auth_state.dart';
import '../../state/parent_state.dart';

/// Sidebar items for parent navigation.
class _NavItem {
  final String label;
  final IconData icon;
  final String path;
  const _NavItem({required this.label, required this.icon, required this.path});
}

const _navItems = [
  _NavItem(label: 'ড্যাসবোর্ড', icon: Icons.dashboard_outlined, path: '/parent/dashboard'),
  _NavItem(label: 'প্রোফাইল', icon: Icons.person_outline, path: '/parent/profile'),
  _NavItem(label: 'আমার সন্তান', icon: Icons.child_care_outlined, path: '/parent/my-child'),
  _NavItem(label: 'পঠিত বিষয়', icon: Icons.book_outlined, path: '/parent/subjects'),
  _NavItem(label: 'ক্লাস রুটিন', icon: Icons.schedule_outlined, path: '/parent/routine'),
  _NavItem(label: 'হোমওয়ার্ক', icon: Icons.assignment_outlined, path: '/parent/homework'),
  _NavItem(label: 'হাজিরা রিপোর্ট', icon: Icons.check_circle_outline, path: '/parent/attendance'),
  _NavItem(label: 'লেসন ইভ্যালুয়েশন', icon: Icons.star_outline, path: '/parent/evaluations'),
  _NavItem(label: 'ছুটির আবেদন', icon: Icons.card_travel_outlined, path: '/parent/leaves'),
  _NavItem(label: 'নোটিস বোর্ড', icon: Icons.campaign_outlined, path: '/parent/notices'),
  _NavItem(label: 'শিক্ষক তালিকা', icon: Icons.groups_outlined, path: '/parent/teachers'),
  _NavItem(label: 'মতামত/অভিযোগ', icon: Icons.feedback_outlined, path: '/parent/feedback'),
];

class ParentShellPage extends ConsumerWidget {
  final Widget child;
  const ParentShellPage({super.key, required this.child});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final currentPath = GoRouterState.of(context).matchedLocation;
    final profile = ref.watch(authProvider).value;
    final studentProfile = ref.watch(parentStudentProfileProvider).value;
    final parentName = studentProfile?['guardian_name_bn'] ?? studentProfile?['guardian_name_en'] ?? 'অভিভাবক';
    final cs = Theme.of(context).colorScheme;

    final currentItem = _navItems.firstWhere(
      (item) => currentPath == item.path,
      orElse: () => _navItems.first,
    );

    return Scaffold(
      appBar: AppBar(
        title: Text(currentItem.label),
        elevation: 1,
        actions: [
          // Notification
          IconButton(
            tooltip: 'নোটিফিকেশন',
            icon: Stack(
              children: [
                const Icon(Icons.notifications_outlined, size: 26),
                Positioned(
                  right: 0,
                  top: 0,
                  child: Container(
                    width: 10,
                    height: 10,
                    decoration: BoxDecoration(
                      color: Colors.red,
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 1.5),
                    ),
                  ),
                ),
              ],
            ),
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('কোনো নতুন নোটিফিকেশন নেই')),
              );
            },
          ),
          // Reload
          IconButton(
            tooltip: 'রিলোড',
            icon: const Icon(Icons.refresh, size: 26),
            onPressed: () {
              // Invalidate all parent-related providers
              ref.invalidate(parentChildrenProvider);
              ref.invalidate(parentHomeworkProvider);
              ref.invalidate(parentRoutineProvider);
              ref.invalidate(parentAttendanceProvider);
              ref.invalidate(parentEvaluationsProvider);
              ref.invalidate(parentLeavesProvider);
              ref.invalidate(parentNoticesProvider);
              ref.invalidate(parentTeachersProvider);
              ref.invalidate(parentSubjectsProvider);
              ref.invalidate(parentFeedbackProvider);
              
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('উপাত্ত রিফ্রেশ করা হচ্ছে...')),
              );
            },
          ),
          // Logout
          IconButton(
            tooltip: 'লগআউট',
            icon: const Icon(Icons.logout, size: 26, color: Colors.red),
            onPressed: () {
              showDialog(
                context: context,
                builder: (ctx) => AlertDialog(
                  title: const Text('লগআউট'),
                  content: const Text('আপনি কি লগআউট করতে চান?'),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(ctx),
                      child: const Text('না'),
                    ),
                    FilledButton(
                      style: FilledButton.styleFrom(backgroundColor: Colors.red),
                      onPressed: () {
                        Navigator.pop(ctx);
                        ref.read(authProvider.notifier).logout();
                        context.go('/login');
                      },
                      child: const Text('হ্যাঁ, লগআউট'),
                    ),
                  ],
                ),
              );
            },
          ),
          const SizedBox(width: 4),
        ],
      ),
      drawer: Drawer(
        child: Column(
          children: [
            // Drawer Header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(20, 48, 20, 20),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [cs.primary, cs.primary.withOpacity(0.7)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CircleAvatar(
                    radius: 32,
                    backgroundColor: Colors.white.withOpacity(0.3),
                    backgroundImage: profile?.photoUrl != null
                        ? NetworkImage(profile!.photoUrl!)
                        : null,
                    child: profile?.photoUrl == null
                        ? Text(
                            parentName.isNotEmpty ? parentName[0].toUpperCase() : 'A',
                            style: const TextStyle(
                              fontSize: 28,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                          )
                        : null,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    parentName,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'অভিভাবক',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.8),
                      fontSize: 13,
                    ),
                  ),
                ],
              ),
            ),
            // Nav Items
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(vertical: 8),
                itemCount: _navItems.length,
                itemBuilder: (context, index) {
                  final item = _navItems[index];
                  final isSelected = currentPath == item.path;
                  return Container(
                    margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 0.5),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(12),
                      color: isSelected ? cs.primary.withOpacity(0.12) : null,
                    ),
                    child: ListTile(
                      dense: true,
                      leading: Icon(
                        item.icon,
                        color: isSelected ? cs.primary : Colors.grey[700],
                        size: 22,
                      ),
                      title: Text(
                        item.label,
                        style: TextStyle(
                          fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                          color: isSelected ? cs.primary : null,
                          fontSize: 14,
                        ),
                      ),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      onTap: () {
                        Navigator.pop(context); // close drawer
                        if (!isSelected) {
                          context.go(item.path);
                        }
                      },
                    ),
                  );
                },
              ),
            ),
            // Footer
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Text(
                'Batighor EIMS',
                style: TextStyle(
                  color: Colors.grey[500],
                  fontSize: 12,
                ),
              ),
            ),
          ],
        ),
      ),
      body: child,
    );
  }
}
