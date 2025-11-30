import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/dio_client.dart';
import '../../../widgets/animated_tile.dart';
import '../../../widgets/rive_icon_registry.dart';
import '../../../widgets/app_snack.dart';
import '../../../theme/theme_mode_provider.dart';

class ParentDashboardPage extends ConsumerStatefulWidget {
  const ParentDashboardPage({super.key});

  @override
  ConsumerState<ParentDashboardPage> createState() =>
      _ParentDashboardPageState();
}

class _ParentDashboardPageState extends ConsumerState<ParentDashboardPage> {
  late final Dio _dio;
  late Future<List<dynamic>> _childrenFuture;
  late Future<List<dynamic>> _homeworkFuture;

  @override
  void initState() {
    super.initState();
    _dio = DioClient().dio;
    _childrenFuture = _fetchList('parent/children');
    _homeworkFuture = _fetchList('parent/homework');
  }

  Future<List<dynamic>> _fetchList(String path) async {
    final resp = await _dio.get(path);
    final data = resp.data;
    if (data is List) return data;
    if (data is Map<String, dynamic> && data['data'] is List) {
      return data['data'] as List<dynamic>;
    }
    return [];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Parent Dashboard'),
        actions: [
          IconButton(
            tooltip: 'Toggle theme',
            icon: const Icon(Icons.dark_mode_outlined),
            onPressed: () {
              ref.read(themeModeProvider.notifier).toggle();
            },
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: () async {
              await showAppSnack(context, message: 'Refreshing', success: true);
              setState(() {
                _childrenFuture = _fetchList('parent/children');
                _homeworkFuture = _fetchList('parent/homework');
              });
            },
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 1.2,
            children: [
              AnimatedTile(
                title: 'Children',
                icon: Icons.family_restroom_outlined,
                riveIcon: RiveIconRegistry.artboardFor('children'),
                onTap: () async {
                  await showAppSnack(
                    context,
                    message: 'Scroll for Children list',
                  );
                },
              ),
              AnimatedTile(
                title: 'Homework',
                icon: Icons.assignment_outlined,
                riveIcon: RiveIconRegistry.artboardFor('parent_homework'),
                onTap: () async {
                  await showAppSnack(
                    context,
                    message: 'Scroll for Homework list',
                  );
                },
              ),
            ],
          ),
          const SizedBox(height: 24),
          const Text(
            'Children',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          FutureBuilder<List<dynamic>>(
            future: _childrenFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Center(child: CircularProgressIndicator());
              }
              if (snapshot.hasError) {
                return Text('Error: ${snapshot.error}');
              }
              final items = snapshot.data ?? [];
              if (items.isEmpty) return const Text('No children');
              return Column(
                children: items.map((e) {
                  final m = e as Map<String, dynamic>? ?? {};
                  return ListTile(
                    title: Text(m['name']?.toString() ?? 'Student'),
                    subtitle: Text('ID: ${m['id'] ?? ''}'),
                  );
                }).toList(),
              );
            },
          ),
          const SizedBox(height: 24),
          const Text(
            'Homework',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          FutureBuilder<List<dynamic>>(
            future: _homeworkFuture,
            builder: (context, snapshot) {
              if (snapshot.connectionState != ConnectionState.done) {
                return const Center(child: CircularProgressIndicator());
              }
              if (snapshot.hasError) {
                return Text('Error: ${snapshot.error}');
              }
              final items = snapshot.data ?? [];
              if (items.isEmpty) return const Text('No homework');
              return Column(
                children: items.map((e) {
                  final m = e as Map<String, dynamic>? ?? {};
                  return ListTile(
                    title: Text(m['title']?.toString() ?? 'Homework'),
                    subtitle: Text(m['date']?.toString() ?? ''),
                  );
                }).toList(),
              );
            },
          ),
        ],
      ),
    );
  }
}
