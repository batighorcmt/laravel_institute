import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../state/parent_state.dart';

class TeacherListPage extends ConsumerWidget {
  const TeacherListPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final teachersAsync = ref.watch(parentTeachersProvider);

    return teachersAsync.when(
      data: (teachers) {
        if (teachers.isEmpty) {
          return const Center(child: Text('কোনো শিক্ষক পাওয়া যায়নি'));
        }

        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: teachers.length,
          itemBuilder: (context, index) {
            final teacher = teachers[index];
            final name = teacher['name']?.toString() ?? 'N/A';
            final designation = teacher['designation']?.toString() ?? 'Teacher';
            final photo = teacher['photo']?.toString();
            final phone = teacher['phone']?.toString();
            final email = teacher['email']?.toString();

            return Card(
              margin: const EdgeInsets.only(bottom: 12),
              child: ExpansionTile(
                leading: CircleAvatar(
                  backgroundColor: Colors.blue.withOpacity(0.2),
                  backgroundImage: photo != null ? NetworkImage(photo) : null,
                  child: photo == null
                      ? Text(
                          name.isNotEmpty ? name[0] : 'T',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            color: Colors.blue,
                          ),
                        )
                      : null,
                ),
                title: Text(
                  name,
                  style: const TextStyle(fontWeight: FontWeight.bold),
                ),
                subtitle: Text(designation),
                children: [
                  Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 12,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildInfoRow(
                          icon: Icons.work,
                          label: 'পদবী',
                          value: designation,
                        ),
                        const SizedBox(height: 8),
                        if (phone != null && phone.isNotEmpty)
                          _buildInfoRow(
                            icon: Icons.phone,
                            label: 'ফোন',
                            value: phone,
                            isClickable: true,
                          ),
                        const SizedBox(height: 8),
                        if (email != null && email.isNotEmpty)
                          _buildInfoRow(
                            icon: Icons.email,
                            label: 'ইমেইল',
                            value: email,
                            isClickable: true,
                          ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: (phone != null && phone.isNotEmpty)
                                    ? () async {
                                        final uri = Uri.parse('tel:$phone');
                                        if (await canLaunchUrl(uri)) {
                                          await launchUrl(uri);
                                        }
                                      }
                                    : null,
                                icon: const Icon(Icons.phone, size: 18),
                                label: const Text('কল করুন'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.green,
                                  foregroundColor: Colors.white,
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: ElevatedButton.icon(
                                onPressed: (email != null && email.isNotEmpty)
                                    ? () async {
                                        final uri = Uri.parse('mailto:$email');
                                        if (await canLaunchUrl(uri)) {
                                          await launchUrl(uri);
                                        }
                                      }
                                    : null,
                                icon: const Icon(Icons.email, size: 18),
                                label: const Text('ইমেইল'),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.blue,
                                  foregroundColor: Colors.white,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (err, stack) => Center(child: Text('ত্রুটি: $err')),
    );
  }

  Widget _buildInfoRow({
    required IconData icon,
    required String label,
    required String value,
    bool isClickable = false,
  }) {
    return Row(
      children: [
        Icon(icon, size: 20, color: Colors.blue),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(fontSize: 12, color: Colors.grey),
              ),
              Text(
                value,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: isClickable ? Colors.blue : Colors.black,
                  decoration: isClickable ? TextDecoration.underline : null,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
