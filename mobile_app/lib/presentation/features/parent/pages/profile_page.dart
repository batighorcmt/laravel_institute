import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../state/auth_state.dart';
import '../../../state/parent_state.dart';

class ProfilePage extends ConsumerWidget {
  const ProfilePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final parent = ref.watch(authProvider).value;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          // Profile Header
          Center(
            child: Column(
              children: [
                CircleAvatar(
                  radius: 56,
                  backgroundColor: Colors.blue.withOpacity(0.12),
                  backgroundImage: parent?.photoUrl != null
                      ? NetworkImage(parent!.photoUrl!)
                      : null,
                  child: parent?.photoUrl == null
                      ? const Icon(Icons.person, size: 60, color: Colors.blue)
                      : null,
                ),
                const SizedBox(height: 18),
                Text(
                  parent?.bnName ?? parent?.name ?? 'Guardian',
                  style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 6),
                Text(
                  parent?.mobile ?? 'N/A',
                  style: TextStyle(color: Colors.grey[600], fontSize: 16, letterSpacing: 1),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.blue.withOpacity(0.08),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    'Parent ID: ${parent?.id ?? 'N/A'}',
                    style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.w500, fontSize: 13),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 48),

          // Actions
          _buildActionItem(
             icon: Icons.edit_outlined,
             label: 'প্রোফাইল সম্পাদনা করুন',
             onTap: () {},
          ),
          _buildActionItem(
             icon: Icons.lock_open_outlined,
             label: 'পাসওয়ার্ড পরিবর্তন করুন',
             onTap: () {},
          ),
          _buildActionItem(
             icon: Icons.logout,
             label: 'লগআউট',
             color: Colors.red,
             onTap: () {
                // Logout logic here if wanted, or rely on shell
                ref.read(authProvider.notifier).logout();
             },
          ),
        ],
      ),
    );
  }

  Widget _buildActionItem({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    Color? color,
  }) {
    return Card(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Colors.grey[200]!),
      ),
      child: ListTile(
        leading: Icon(icon, color: color ?? Colors.blue[700]),
        title: Text(label, style: TextStyle(color: color, fontWeight: FontWeight.w500)),
        trailing: const Icon(Icons.chevron_right, size: 20),
        onTap: onTap,
      ),
    );
  }
}
