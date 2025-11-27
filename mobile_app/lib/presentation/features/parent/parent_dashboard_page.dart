import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';

class ParentDashboardPage extends StatefulWidget {
  const ParentDashboardPage({super.key});

  @override
  State<ParentDashboardPage> createState() => _ParentDashboardPageState();
}

class _ParentDashboardPageState extends State<ParentDashboardPage> {
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
      appBar: AppBar(title: const Text('Parent Dashboard')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
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
