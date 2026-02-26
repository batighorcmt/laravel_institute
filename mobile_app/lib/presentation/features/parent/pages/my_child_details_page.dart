import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../state/parent_state.dart';

class MyChildDetailsPage extends ConsumerWidget {
  const MyChildDetailsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final profileAsync = ref.watch(parentStudentProfileProvider);

    return profileAsync.when(
      data: (profile) => _ProfileContent(profile: profile),
      loading: () => const Center(child: CircularProgressIndicator()),
      error: (err, _) => Center(child: Text('ত্রুটি: $err')),
    );
  }
}

class _ProfileContent extends StatelessWidget {
  final Map<String, dynamic> profile;
  const _ProfileContent({required this.profile});

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;

    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Section
          Container(
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
                  color: cs.primary.withOpacity(0.2),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 40,
                  backgroundColor: Colors.white,
                  backgroundImage: profile['photo_url'] != null
                      ? NetworkImage(profile['photo_url'])
                      : null,
                  child: profile['photo_url'] == null
                      ? Icon(Icons.person, size: 40, color: cs.primary)
                      : null,
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        profile['name_bn'] ?? profile['name'] ?? 'N/A',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      if (profile['name_en'] != null)
                        Text(
                          profile['name_en'],
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.9),
                            fontSize: 14,
                          ),
                        ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        children: [
                          _Badge(label: 'ID: ${profile['student_id']}'),
                          _Badge(label: 'রোল: ${profile['roll'] ?? 'N/A'}'),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Academic Info
          _SectionHeader(icon: Icons.school_outlined, title: 'একাডেমিক তথ্য'),
          _InfoCard(
            items: [
              _InfoItem(label: 'শ্রেণি', value: profile['class'] ?? 'N/A'),
              _InfoItem(label: 'শাখা', value: profile['section'] ?? 'N/A'),
              _InfoItem(label: 'বিভাগ', value: profile['group'] ?? 'N/A'),
              _InfoItem(label: 'শিক্ষাবর্ষ', value: profile['academic_year'] ?? 'N/A'),
            ],
          ),
          const SizedBox(height: 24),

          // Personal Info
          _SectionHeader(icon: Icons.person_outline, title: 'ব্যক্তিগত তথ্য'),
          _InfoCard(
            items: [
              _InfoItem(label: 'জন্ম তারিখ', value: profile['date_of_birth'] ?? 'N/A'),
              _InfoItem(label: 'লিঙ্গ', value: profile['gender'] == 'male' ? 'ছেলে' : 'মেয়ে'),
              _InfoItem(label: 'রক্তের গ্রুপ', value: profile['blood_group'] ?? 'N/A'),
              _InfoItem(label: 'ধর্ম', value: profile['religion'] ?? 'N/A'),
            ],
          ),
          const SizedBox(height: 24),

          // Guardian Info
          _SectionHeader(icon: Icons.family_restroom_outlined, title: 'অভিভাবকের তথ্য'),
          _InfoCard(
            items: [
              _InfoItem(
                label: 'পিতার নাম (BN)',
                value: profile['father_name_bn'] ?? profile['guardians']?['father_name_bn'] ?? 'N/A',
                isBold: true,
              ),
              _InfoItem(
                label: 'পিতার নাম (EN)',
                value: profile['father_name'] ?? profile['guardians']?['father_name'] ?? 'N/A',
              ),
              _InfoItem(
                label: 'মাতার নাম (BN)',
                value: profile['mother_name_bn'] ?? profile['guardians']?['mother_name_bn'] ?? 'N/A',
                isBold: true,
              ),
              _InfoItem(
                label: 'মাতার নাম (EN)',
                value: profile['mother_name'] ?? profile['guardians']?['mother_name'] ?? 'N/A',
              ),
              _InfoItem(label: 'অভিভাবক', value: profile['guardian_name'] ?? 'N/A'),
              _InfoItem(label: 'মোবাইল', value: profile['guardian_phone'] ?? 'N/A'),
            ],
          ),
          const SizedBox(height: 24),

          // Address Info
          _SectionHeader(icon: Icons.location_on_outlined, title: 'ঠিকানা'),
          _InfoCard(
            items: [
              _InfoItem(label: 'বর্তমান ঠিকানা', value: profile['present_address_bn'] ?? profile['present_address'] ?? 'N/A'),
              _InfoItem(label: 'স্থায়ী ঠিকানা', value: profile['permanent_address_bn'] ?? profile['permanent_address'] ?? 'N/A'),
            ],
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final String title;
  const _SectionHeader({required this.icon, required this.title});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12, left: 4),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Theme.of(context).primaryColor),
          const SizedBox(width: 8),
          Text(
            title,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              letterSpacing: 0.5,
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final List<_InfoItem> items;
  const _InfoCard({required this.items});

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: Colors.grey.withOpacity(0.2)),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        child: Column(
          children: items,
        ),
      ),
    );
  }
}

class _InfoItem extends StatelessWidget {
  final String label;
  final String value;
  final bool isBold;

  const _InfoItem({
    required this.label,
    required this.value,
    this.isBold = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: TextStyle(
                color: Colors.grey[600],
                fontSize: 13,
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Text(
              value,
              style: TextStyle(
                fontWeight: isBold ? FontWeight.bold : FontWeight.w500,
                fontSize: 14,
                color: Colors.black87,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  final String label;
  const _Badge({required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.2),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 12,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }
}
