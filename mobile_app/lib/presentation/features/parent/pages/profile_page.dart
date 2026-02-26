import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import '../../../state/auth_state.dart';
import '../../../state/parent_state.dart';

class ProfilePage extends ConsumerWidget {
  const ProfilePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final parent = ref.watch(authProvider).value;
    final studentProfile = ref.watch(parentStudentProfileProvider).value;
    final displayName = studentProfile?['guardian_name_bn'] ?? studentProfile?['guardian_name_en'] ?? parent?.bnName ?? parent?.name ?? 'অভিভাবক';

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          // Profile Header
          Center(
            child: Column(
              children: [
                Stack(
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
                    Positioned(
                      bottom: 0,
                      right: 0,
                      child: GestureDetector(
                        onTap: () => _pickAndUploadImage(context, ref),
                        child: CircleAvatar(
                          radius: 18,
                          backgroundColor: Colors.blue,
                          child: const Icon(Icons.camera_alt, size: 18, color: Colors.white),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                Text(
                  displayName,
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
             icon: Icons.camera_alt_outlined,
             label: 'প্রোফাইল ছবি পরিবর্তন করুন',
             onTap: () => _pickAndUploadImage(context, ref),
          ),
          _buildActionItem(
             icon: Icons.lock_open_outlined,
             label: 'পাসওয়ার্ড পরিবর্তন করুন',
             onTap: () => _showChangePasswordDialog(context, ref),
          ),
          _buildActionItem(
             icon: Icons.logout,
             label: 'লগআউট',
             color: Colors.red,
             onTap: () {
                ref.read(authProvider.notifier).logout();
             },
          ),
        ],
      ),
    );
  }

  Future<void> _pickAndUploadImage(BuildContext context, WidgetRef ref) async {
    final picker = ImagePicker();
    final image = await picker.pickImage(source: ImageSource.gallery, imageQuality: 70);
    
    if (image != null) {
      try {
        await ref.read(parentRepositoryProvider).updatePhoto(image.path);
        ref.invalidate(authProvider); // Refresh to show new photo
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('প্রোফাইল ছবি সফলভাবে পরিবর্তন হয়েছে')));
        }
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('ত্রুটি: $e')));
        }
      }
    }
  }

  void _showChangePasswordDialog(BuildContext context, WidgetRef ref) {
    final currentController = TextEditingController();
    final newController = TextEditingController();
    final confirmController = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('পাসওয়ার্ড পরিবর্তন'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: currentController,
                decoration: const InputDecoration(labelText: 'বর্তমান পাসওয়ার্ড'),
                obscureText: true,
              ),
              TextField(
                controller: newController,
                decoration: const InputDecoration(labelText: 'নতুন পাসওয়ার্ড'),
                obscureText: true,
              ),
              TextField(
                controller: confirmController,
                decoration: const InputDecoration(labelText: 'পাসওয়ার্ড নিশ্চিত করুন'),
                obscureText: true,
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('বাতিল')),
          FilledButton(
            onPressed: () async {
              if (newController.text != confirmController.text) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('নতুন পাসওয়ার্ড দুটি মিলছে না')));
                return;
              }
              try {
                await ref.read(parentRepositoryProvider).changePassword(
                  currentController.text,
                  newController.text,
                  confirmController.text,
                );
                if (context.mounted) {
                  Navigator.pop(ctx);
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('পাসওয়ার্ড সফলভাবে পরিবর্তিত হয়েছে')));
                }
              } catch (e) {
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('ত্রুটি: $e')));
                }
              }
            },
            child: const Text('পরিবর্তন করুন'),
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
