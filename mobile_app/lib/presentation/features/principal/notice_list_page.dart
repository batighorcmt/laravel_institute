import 'package:flutter/material.dart';
import 'notice_create_page.dart';

class NoticeListPage extends StatelessWidget {
  const NoticeListPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notices')),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.of(
            context,
          ).push(MaterialPageRoute(builder: (_) => const NoticeCreatePage()));
        },
        child: const Icon(Icons.add),
      ),
      body: ListView(
        padding: const EdgeInsets.all(12),
        children: const [
          Card(
            child: ListTile(
              title: Text('Sample Notice 1'),
              subtitle: Text('Jan 01, 2026 — General announcement'),
            ),
          ),
          SizedBox(height: 8),
          Card(
            child: ListTile(
              title: Text('Sample Notice 2'),
              subtitle: Text('Jan 15, 2026 — Exam schedule'),
            ),
          ),
        ],
      ),
    );
  }
}
