import 'package:flutter/material.dart';
import 'package:dropdown_search/dropdown_search.dart';
import '../../../core/network/dio_client.dart';
import 'lesson_evaluation_details_page.dart';
import 'dart:async';

class LessonEvaluationReportPage extends StatefulWidget {
  const LessonEvaluationReportPage({super.key});

  @override
  State<LessonEvaluationReportPage> createState() =>
      _LessonEvaluationReportPageState();
}

class _LessonEvaluationReportPageState
    extends State<LessonEvaluationReportPage> {
  DateTime? _selectedDate;
  int? _selectedClassId;
  int? _selectedSectionId;
  int? _selectedSubjectId;
  Map<String, dynamic>? _selectedTeacherObj;
  List<Map<String, dynamic>> _teachers = [];
  String? _statusFilter;
  List<Map<String, dynamic>> _classes = [];
  List<Map<String, dynamic>> _sections = [];
  List<Map<String, dynamic>> _subjects = [];
  void _addLog(String s) {
    // no-op: logging removed in production
  }

  List<dynamic> _extractList(dynamic respData) {
    if (respData == null) return [];
    if (respData is List) return respData;
    if (respData is Map) {
      // common API shape: { status: true, data: [...] } or { data: [...] }
      if (respData.containsKey('data')) {
        final d = respData['data'];
        if (d is List) return d;
      }
      // sometimes payload is wrapped under a key like 'classes'
      final firstList = respData.values.firstWhere(
        (v) => v is List,
        orElse: () => null,
      );
      if (firstList is List) return firstList;
    }
    return [];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lesson Evaluation Report')),
      body: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Date'),
              subtitle: Text(
                _selectedDate == null
                    ? 'Select a date'
                    : _selectedDate!.toLocal().toString().split(' ')[0],
              ),
              trailing: IconButton(
                icon: const Icon(Icons.calendar_today),
                onPressed: () async {
                  final d = await showDatePicker(
                    context: context,
                    initialDate: DateTime.now(),
                    firstDate: DateTime(2020),
                    lastDate: DateTime.now(),
                  );
                  if (d != null) setState(() => _selectedDate = d);
                },
              ),
            ),
            const SizedBox(height: 8),
            DropdownSearch<Map<String, dynamic>>(
              popupProps: PopupProps.menu(
                showSearchBox: true,
                searchFieldProps: TextFieldProps(
                  decoration: InputDecoration(labelText: 'Search teacher'),
                  style: const TextStyle(fontSize: 14, color: Colors.black),
                ),
                itemBuilder: (context, item, isSelected) => ListTile(
                  title: Text(
                    (item['name'] ?? '').toString(),
                    style: const TextStyle(fontSize: 14, color: Colors.black),
                  ),
                ),
              ),
              items: _teachers,
              itemAsString: (Map<String, dynamic>? m) =>
                  m == null ? '' : (m['name'] ?? '').toString(),
              dropdownDecoratorProps: DropDownDecoratorProps(
                dropdownSearchDecoration: const InputDecoration(
                  labelText: 'Teacher',
                ),
              ),
              dropdownBuilder: (context, selectedItem) => Text(
                selectedItem == null
                    ? ''
                    : (selectedItem['name'] ?? '').toString(),
                style: const TextStyle(fontSize: 14),
              ),
              selectedItem: _selectedTeacherObj,
              onChanged: (m) => setState(() {
                _selectedTeacherObj = m;
              }),
            ),
            const SizedBox(height: 8),
            DropdownButtonFormField<String>(
              decoration: const InputDecoration(labelText: 'Record status'),
              items: const [
                DropdownMenuItem(
                  value: '',
                  child: Text('Any', style: TextStyle(color: Colors.black)),
                ),
                DropdownMenuItem(
                  value: 'completed',
                  child: Text(
                    'Completed',
                    style: TextStyle(color: Colors.black),
                  ),
                ),
                DropdownMenuItem(
                  value: 'partial',
                  child: Text('Partial', style: TextStyle(color: Colors.black)),
                ),
                DropdownMenuItem(
                  value: 'not_done',
                  child: Text(
                    'Not done',
                    style: TextStyle(color: Colors.black),
                  ),
                ),
                DropdownMenuItem(
                  value: 'absent',
                  child: Text('Absent', style: TextStyle(color: Colors.black)),
                ),
              ],
              value: _statusFilter ?? '',
              style: const TextStyle(fontSize: 14),
              onChanged: (v) => setState(
                () => _statusFilter = (v == null || v.isEmpty) ? null : v,
              ),
            ),
            const SizedBox(height: 8),
            // Class/section/subject selectors
            if (_classes.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8.0),
                child: Text('No classes available'),
              )
            else
              DropdownButtonFormField<int>(
                decoration: const InputDecoration(labelText: 'Class'),
                dropdownColor: Colors.white,
                items:
                    (<Map<String, dynamic>>[
                              {'id': -1, 'name': 'All Classes'},
                            ] +
                            _classes)
                        .map(
                          (c) => DropdownMenuItem<int>(
                            value: c['id'] is int
                                ? c['id'] as int
                                : int.tryParse(c['id']?.toString() ?? ''),
                            child: Text(
                              (c['name'] ?? '').toString(),
                              style: const TextStyle(color: Colors.black),
                            ),
                          ),
                        )
                        .where((it) => it.value != null)
                        .toList(),
                value:
                    _selectedClassId ??
                    (_classes.isEmpty
                        ? null
                        : (_classes.any((c) => c['id'] == -1) ? -1 : null)),
                style: const TextStyle(fontSize: 14, color: Colors.black),
                onChanged: (v) async {
                  // sentinel -1 = All Classes -> treat as no specific class
                  final selectedIsAll = v != null && v == -1;
                  setState(() {
                    _selectedClassId = selectedIsAll ? null : v;
                    _selectedSectionId = null;
                    _selectedSubjectId = null;
                    _sections = [];
                    _subjects = [];
                  });
                  if (!selectedIsAll && v != null) await _fetchSections(v);
                  if (!selectedIsAll) await _fetchSubjects();
                },
              ),
            const SizedBox(height: 8),
            const SizedBox(height: 8),
            if (_sections.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8.0),
                child: Text('No sections available'),
              )
            else
              DropdownButtonFormField<int>(
                decoration: const InputDecoration(labelText: 'Section'),
                dropdownColor: Colors.white,
                items: _sections
                    .map(
                      (s) => DropdownMenuItem<int>(
                        value: s['id'] is int
                            ? s['id'] as int
                            : int.tryParse(s['id']?.toString() ?? ''),
                        child: Text(
                          (s['name'] ?? '').toString(),
                          style: const TextStyle(color: Colors.black),
                        ),
                      ),
                    )
                    .where((it) => it.value != null)
                    .toList(),
                value: _selectedSectionId,
                style: const TextStyle(fontSize: 14, color: Colors.black),
                onChanged: (v) async {
                  setState(() {
                    _selectedSectionId = v;
                    _selectedSubjectId = null;
                    _subjects = [];
                  });
                  await _fetchSubjects();
                },
              ),
            const SizedBox(height: 8),
            if (_subjects.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8.0),
                child: Text('No subjects available'),
              )
            else
              DropdownSearch<Map<String, dynamic>>(
                popupProps: PopupProps.menu(
                  showSearchBox: true,
                  searchFieldProps: TextFieldProps(
                    decoration: InputDecoration(labelText: 'Search subject'),
                    style: const TextStyle(fontSize: 14, color: Colors.black),
                  ),
                  itemBuilder: (context, item, isSelected) => ListTile(
                    title: Text(
                      (item['name'] ?? '').toString(),
                      style: const TextStyle(fontSize: 14, color: Colors.black),
                    ),
                  ),
                ),
                items: _subjects,
                itemAsString: (Map<String, dynamic>? m) =>
                    m == null ? '' : (m['name'] ?? '').toString(),
                dropdownDecoratorProps: DropDownDecoratorProps(
                  dropdownSearchDecoration: const InputDecoration(
                    labelText: 'Subject',
                  ),
                ),
                dropdownBuilder: (context, selectedItem) => Text(
                  selectedItem == null
                      ? ''
                      : (selectedItem['name'] ?? '').toString(),
                  style: const TextStyle(fontSize: 14),
                ),
                selectedItem: _subjects.firstWhere(
                  (s) =>
                      (s['id'] is int
                          ? s['id'] as int
                          : int.tryParse(s['id']?.toString() ?? '')) ==
                      _selectedSubjectId,
                  orElse: () => <String, dynamic>{},
                ),
                onChanged: (m) => setState(() {
                  if (m == null || m.isEmpty) {
                    _selectedSubjectId = null;
                  } else {
                    final id = m['id'] is int
                        ? m['id'] as int
                        : int.tryParse(m['id']?.toString() ?? '');
                    _selectedSubjectId = id;
                  }
                }),
              ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: _loading
                        ? null
                        : () async => await _fetchReport(),
                    child: _loading
                        ? const SizedBox(
                            height: 16,
                            width: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Generate Report'),
                  ),
                ),
                const SizedBox(width: 8),
                OutlinedButton(
                  onPressed: () {
                    setState(() {
                      _selectedDate = null;
                      _selectedClassId = null;
                      _selectedSectionId = null;
                      _selectedSubjectId = null;
                      _selectedTeacherObj = null;
                      _statusFilter = null;
                      _sections = [];
                      _subjects = [];
                      _items = [];
                      _error = null;
                    });
                  },
                  child: const Text('Reset'),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // (Removed duplicate classes dropdown) show sections/subjects above instead.
            const SizedBox(height: 8),
            Expanded(child: _buildOutput()),
          ],
        ),
      ),
    );
  }

  bool _loading = false;
  String? _error;
  List<dynamic> _items = [];

  Future<void> _fetchReport() async {
    setState(() {
      _loading = true;
      _error = null;
      _items = [];
    });
    try {
      final dio = DioClient().dio;
      final params = <String, dynamic>{};
      if (_selectedDate != null)
        params['date'] = _selectedDate!.toIso8601String().split('T')[0];
      if (_selectedClassId != null) params['class_id'] = _selectedClassId;
      if (_selectedSectionId != null) params['section_id'] = _selectedSectionId;
      if (_selectedSubjectId != null) params['subject_id'] = _selectedSubjectId;
      if (_statusFilter != null) params['status'] = _statusFilter;
      if (_selectedTeacherObj != null)
        params['teacher'] =
            _selectedTeacherObj!['id'] ?? _selectedTeacherObj!['name'];

      final resp = await dio.get(
        'principal/reports/lesson-evaluations',
        queryParameters: params,
      );
      _addLog('GET principal/reports/lesson-evaluations -> ${resp.statusCode}');
      _addLog(
        'Resp: ${resp.data is Map || resp.data is List ? resp.data.toString() : resp.data}',
      );
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        setState(() {
          _items = data;
        });
      } else {
        setState(() {
          _error = 'Server error: ${resp.statusCode}';
        });
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void initState() {
    super.initState();
    _loadInitialFilters();
  }

  Future<void> _loadInitialFilters() async {
    await _fetchClasses();
    await _fetchTeachers();
  }

  Future<void> _fetchClasses() async {
    try {
      final resp = await DioClient().dio.get(
        'principal/students/filters/classes',
      );
      _addLog('GET principal/students/filters/classes -> ${resp.statusCode}');
      _addLog('Resp: ${resp.data}');
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        setState(() {
          _classes = data
              .map((e) => {'id': e['id'], 'name': e['name']})
              .toList()
              .cast<Map<String, dynamic>>();
        });
      } else {
        _addLog('Classes endpoint returned status ${resp.statusCode}');
      }
      // If principal endpoint returned no classes (or is restricted),
      // try the debug endpoint which accepts a `school_id` query param.
      if (_classes.isEmpty) {
        try {
          final dbg = await DioClient().dio.get(
            'debug/classes',
            queryParameters: {'school_id': 1},
          );
          _addLog('GET debug/classes?school_id=1 -> ${dbg.statusCode}');
          _addLog('Debug Resp: ${dbg.data}');
          if (dbg.statusCode == 200) {
            final ddata = _extractList(dbg.data);
            setState(() {
              _classes = ddata
                  .map((e) => {'id': e['id'], 'name': e['name']})
                  .toList()
                  .cast<Map<String, dynamic>>();
            });
          }
        } catch (e) {
          _addLog('Exception fetching debug classes: $e');
        }
      }
    } catch (e, st) {
      _addLog('Exception fetching classes: $e');
      _addLog(st.toString());
    }
  }

  Future<void> _fetchSections(int classId) async {
    try {
      final resp = await DioClient().dio.get(
        'principal/students/filters/sections',
        queryParameters: {'class_id': classId},
      );
      _addLog(
        'GET principal/students/filters/sections?class_id=$classId -> ${resp.statusCode}',
      );
      _addLog('Resp: ${resp.data}');
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        setState(() {
          _sections = data
              .map((e) => {'id': e['id'], 'name': e['name']})
              .toList()
              .cast<Map<String, dynamic>>();
        });
      } else {
        _addLog('Sections endpoint returned status ${resp.statusCode}');
      }
      // Fallback to meta endpoint if empty
      if (_sections.isEmpty) {
        try {
          final meta = await DioClient().dio.get(
            'meta/sections',
            queryParameters: {'class_id': classId},
          );
          _addLog('GET meta/sections?class_id=$classId -> ${meta.statusCode}');
          if (meta.statusCode == 200) {
            final mdata = _extractList(meta.data);
            setState(() {
              _sections = mdata
                  .map((e) => {'id': e['id'], 'name': e['name']})
                  .toList()
                  .cast<Map<String, dynamic>>();
            });
          }
        } catch (e) {
          _addLog('Exception fetching meta sections: $e');
        }
      }
      // (no-op) additional debug/subject fallback removed -- next fallback tries debug/sections without class
      // Try debug sections without class filter if still empty
      if (_sections.isEmpty) {
        try {
          final dbg2 = await DioClient().dio.get(
            'debug/sections',
            queryParameters: {'school_id': 1},
          );
          _addLog('GET debug/sections?school_id=1 -> ${dbg2.statusCode}');
          if (dbg2.statusCode == 200) {
            final ddata2 = _extractList(dbg2.data);
            setState(() {
              _sections = ddata2
                  .map((e) => {'id': e['id'], 'name': e['name']})
                  .toList()
                  .cast<Map<String, dynamic>>();
            });
          }
        } catch (e) {
          _addLog('Exception fetching debug sections (no class): $e');
        }
      }
    } catch (e, st) {
      _addLog('Exception fetching sections: $e');
      _addLog(st.toString());
    }
  }

  Future<void> _fetchSubjects() async {
    try {
      // Only fetch subjects when a specific class is selected. If no class is
      // selected (or All Classes chosen), keep the subjects list empty so the
      // dropdown shows "No subjects available".
      if (_selectedClassId == null) {
        setState(() {
          _subjects = [];
        });
        return;
      }
      final params = <String, dynamic>{};
      if (_selectedClassId != null) params['class_id'] = _selectedClassId;
      if (_selectedSectionId != null) params['section_id'] = _selectedSectionId;
      final resp = await DioClient().dio.get(
        'principal/students/filters/subjects',
        queryParameters: params,
      );
      _addLog(
        'GET principal/students/filters/subjects?params=$params -> ${resp.statusCode}',
      );
      _addLog('Resp: ${resp.data}');
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        setState(() {
          _subjects = data
              .map((e) => {'id': e['id'], 'name': e['name']})
              .toList()
              .cast<Map<String, dynamic>>();
        });
      } else {
        _addLog('Subjects endpoint returned status ${resp.statusCode}');
      }
      // Fallback to meta endpoint
      if (_subjects.isEmpty) {
        try {
          final meta = await DioClient().dio.get(
            'meta/sections',
            queryParameters: params,
          );
          _addLog('GET meta/sections?params=$params -> ${meta.statusCode}');
          if (meta.statusCode == 200) {
            final mdata = _extractList(meta.data);
            setState(() {
              _subjects = mdata
                  .map((e) => {'id': e['id'], 'name': e['name']})
                  .toList()
                  .cast<Map<String, dynamic>>();
            });
          }
        } catch (e) {
          _addLog('Exception fetching meta subjects: $e');
        }
      }
      // Fallback to debug endpoint
      if (_subjects.isEmpty) {
        try {
          final dbg = await DioClient().dio.get(
            'debug/subjects',
            queryParameters: params..['school_id'] = 1,
          );
          _addLog('GET debug/subjects?params=$params -> ${dbg.statusCode}');
          if (dbg.statusCode == 200) {
            final ddata = _extractList(dbg.data);
            setState(() {
              _subjects = ddata
                  .map((e) => {'id': e['id'], 'name': e['name']})
                  .toList()
                  .cast<Map<String, dynamic>>();
            });
          }
        } catch (e) {
          _addLog('Exception fetching debug subjects: $e');
        }
      }
    } catch (e, st) {
      _addLog('Exception fetching subjects: $e');
      _addLog(st.toString());
    }
  }

  Future<void> _fetchTeachers() async {
    try {
      final resp = await DioClient().dio.get('meta/teachers');
      _addLog('GET meta/teachers -> ${resp.statusCode}');
      _addLog('Resp: ${resp.data}');
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        setState(() {
          _teachers = data
              .map((e) => {'id': e['id'], 'name': e['name']})
              .toList()
              .cast<Map<String, dynamic>>();
        });
      } else {
        _addLog('Teachers endpoint returned status ${resp.statusCode}');
      }
      // Fallback to debug endpoint if empty
      if (_teachers.isEmpty) {
        try {
          final dbg = await DioClient().dio.get(
            'debug/teachers',
            queryParameters: {'school_id': 1},
          );
          _addLog('GET debug/teachers?school_id=1 -> ${dbg.statusCode}');
          if (dbg.statusCode == 200) {
            final ddata = _extractList(dbg.data);
            setState(() {
              _teachers = ddata
                  .map((e) => {'id': e['id'], 'name': e['name']})
                  .toList()
                  .cast<Map<String, dynamic>>();
            });
          }
        } catch (e) {
          _addLog('Exception fetching debug teachers: $e');
        }
      }
    } catch (e, st) {
      _addLog('Exception fetching teachers: $e');
      _addLog(st.toString());
    }
  }

  Widget _buildOutput() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Text('Error: $_error'));
    if (_items.isEmpty) return const Center(child: Text('No results'));
    return ListView.separated(
      padding: const EdgeInsets.only(bottom: 24),
      itemCount: _items.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, i) {
        final it = _items[i] as Map<String, dynamic>;
        final stats = it['stats'] as Map<String, dynamic>?;
        return Card(
          child: ListTile(
            title: Text(
              '${it['evaluation_date'] ?? ''} • ${it['teacher_name'] ?? ''}',
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 6),
                Text(
                  '${it['class_name'] ?? ''}${it['section_name'] != null ? ' - ${it['section_name']}' : ''} • ${it['subject_name'] ?? ''}',
                ),
                const SizedBox(height: 6),
                if (stats != null)
                  Text(
                    'Total: ${stats['total'] ?? 0}  Completed: ${stats['completed'] ?? 0}  Partial: ${stats['partial'] ?? 0}  Not done: ${stats['not_done'] ?? 0}  Absent: ${stats['absent'] ?? 0}',
                  ),
                if (it['notes'] != null &&
                    (it['notes'] as String).isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(it['notes'] ?? ''),
                ],
              ],
            ),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => LessonEvaluationDetailsPage(report: it),
                ),
              );
            },
          ),
        );
      },
    );
  }
}
