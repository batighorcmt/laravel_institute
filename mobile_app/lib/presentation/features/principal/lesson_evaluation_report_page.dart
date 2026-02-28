import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dropdown_search/dropdown_search.dart';
import '../../../core/network/dio_client.dart';
import '../../state/auth_state.dart';
import 'lesson_evaluation_details_page.dart';
import 'dart:async';

class LessonEvaluationReportPage extends ConsumerStatefulWidget {
  const LessonEvaluationReportPage({super.key});

  @override
  ConsumerState<LessonEvaluationReportPage> createState() =>
      _LessonEvaluationReportPageState();
}

class _LessonEvaluationReportPageState
    extends ConsumerState<LessonEvaluationReportPage> {
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

  int? _getSchoolId() {
    final userState = ref.read(authProvider);
    if (userState is AsyncData && userState.value != null) {
      final user = userState.value!;
      // Try to find school_id from roles
      for (final role in user.roles) {
        if (role.schoolId != null) {
          return role.schoolId;
        }
      }
    }
    return null;
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
            // Compact Row 1: Date and Status
            Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () async {
                      final d = await showDatePicker(
                        context: context,
                        initialDate: DateTime.now(),
                        firstDate: DateTime(2020),
                        lastDate: DateTime.now(),
                      );
                      if (d != null) setState(() => _selectedDate = d);
                    },
                    child: InputDecorator(
                      decoration: const InputDecoration(
                        labelText: 'Date',
                        isDense: true,
                        contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                        border: OutlineInputBorder(),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            _selectedDate == null
                                ? 'Select Date'
                                : _selectedDate!.toLocal().toString().split(' ')[0],
                            style: const TextStyle(fontSize: 13),
                          ),
                          const Icon(Icons.calendar_today, size: 16),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: DropdownButtonFormField<String>(
                    decoration: const InputDecoration(
                      labelText: 'Status',
                      isDense: true,
                      contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                      border: OutlineInputBorder(),
                    ),
                    items: const [
                      DropdownMenuItem(value: '', child: Text('Any', style: TextStyle(fontSize: 13))),
                      DropdownMenuItem(value: 'completed', child: Text('Completed', style: TextStyle(fontSize: 13))),
                      DropdownMenuItem(value: 'partial', child: Text('Partial', style: TextStyle(fontSize: 13))),
                      DropdownMenuItem(value: 'not_done', child: Text('Not done', style: TextStyle(fontSize: 13))),
                      DropdownMenuItem(value: 'absent', child: Text('Absent', style: TextStyle(fontSize: 13))),
                    ],
                    value: _statusFilter ?? '',
                    style: const TextStyle(fontSize: 13, color: Colors.black),
                    onChanged: (v) => setState(() => _statusFilter = (v == null || v.isEmpty) ? null : v),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Compact Row 2: Class and Section
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<int>(
                    decoration: const InputDecoration(
                      labelText: 'Class',
                      isDense: true,
                      contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                      border: OutlineInputBorder(),
                    ),
                    dropdownColor: Colors.white,
                    items: (<Map<String, dynamic>>[{'id': -1, 'name': 'All'}] + _classes)
                        .map((c) => DropdownMenuItem<int>(
                              value: c['id'] is int ? c['id'] as int : int.tryParse(c['id']?.toString() ?? ''),
                              child: Text((c['name'] ?? '').toString(), style: const TextStyle(fontSize: 13)),
                            ))
                        .where((it) => it.value != null)
                        .toList(),
                    value: _selectedClassId ?? (_classes.isEmpty ? null : (_classes.any((c) => c['id'] == -1) ? -1 : null)),
                    onChanged: (v) async {
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
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: DropdownButtonFormField<int>(
                    decoration: const InputDecoration(
                      labelText: 'Section',
                      isDense: true,
                      contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                      border: OutlineInputBorder(),
                    ),
                    dropdownColor: Colors.white,
                    items: _sections
                        .map((s) => DropdownMenuItem<int>(
                              value: s['id'] is int ? s['id'] as int : int.tryParse(s['id']?.toString() ?? ''),
                              child: Text((s['name'] ?? '').toString(), style: const TextStyle(fontSize: 13)),
                            ))
                        .where((it) => it.value != null)
                        .toList(),
                    value: _selectedSectionId,
                    onChanged: (v) async {
                      setState(() {
                        _selectedSectionId = v;
                        _selectedSubjectId = null;
                        _subjects = [];
                      });
                      await _fetchSubjects();
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Row 3: Teacher
            DropdownSearch<Map<String, dynamic>>(
              popupProps: PopupProps.menu(
                showSearchBox: true,
                searchFieldProps: TextFieldProps(
                  decoration: const InputDecoration(labelText: 'Search teacher', isDense: true),
                  style: const TextStyle(fontSize: 13, color: Colors.black),
                ),
                itemBuilder: (context, item, isSelected) => ListTile(
                  dense: true,
                  title: Text((item['name'] ?? '').toString(), style: const TextStyle(fontSize: 13)),
                  subtitle: item['designation'] != null ? Text(item['designation'].toString(), style: const TextStyle(fontSize: 11)) : null,
                ),
              ),
              items: _teachers,
              itemAsString: (Map<String, dynamic>? m) =>
                  m == null ? '' : (m['name'] ?? '').toString(),
              dropdownDecoratorProps: DropDownDecoratorProps(
                dropdownSearchDecoration: const InputDecoration(
                  labelText: 'Teacher',
                  isDense: true,
                  contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  border: OutlineInputBorder(),
                ),
              ),
              dropdownBuilder: (context, selectedItem) => Text(
                selectedItem == null ? 'Select Teacher' : (selectedItem['name'] ?? '').toString(),
                style: const TextStyle(fontSize: 13),
              ),
              selectedItem: _selectedTeacherObj,
              onChanged: (m) {
                setState(() {
                  if (m == null || m['id'] == -1) {
                    _selectedTeacherObj = null;
                  } else {
                    _selectedTeacherObj = m;
                  }
                });
              },
            ),
            const SizedBox(height: 8),

            // Row 4: Subject
            DropdownSearch<Map<String, dynamic>>(
              popupProps: PopupProps.menu(
                showSearchBox: true,
                searchFieldProps: TextFieldProps(
                  decoration: const InputDecoration(labelText: 'Search subject', isDense: true),
                  style: const TextStyle(fontSize: 13, color: Colors.black),
                ),
                itemBuilder: (context, item, isSelected) => ListTile(
                  dense: true,
                  title: Text((item['name'] ?? '').toString(), style: const TextStyle(fontSize: 13)),
                ),
              ),
              items: _subjects,
              itemAsString: (Map<String, dynamic>? m) =>
                  m == null ? '' : (m['name'] ?? '').toString(),
              dropdownDecoratorProps: DropDownDecoratorProps(
                dropdownSearchDecoration: const InputDecoration(
                  labelText: 'Subject',
                  isDense: true,
                  contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  border: OutlineInputBorder(),
                ),
              ),
              dropdownBuilder: (context, selectedItem) => Text(
                selectedItem == null ? 'Select Subject' : (selectedItem['name'] ?? '').toString(),
                style: const TextStyle(fontSize: 13),
              ),
              selectedItem: _subjects.firstWhere(
                (s) => (s['id'] is int ? s['id'] as int : int.tryParse(s['id']?.toString() ?? '')) == _selectedSubjectId,
                orElse: () => _selectedClassId == null ? {'id': -1, 'name': 'All Subjects'} : {'id': -1, 'name': 'Any Subject'},
              ),
              onChanged: (m) => setState(() {
                if (m == null || m.isEmpty || m['id'] == -1) {
                  _selectedSubjectId = null;
                } else {
                  _selectedSubjectId = m['id'] is int ? m['id'] as int : int.tryParse(m['id']?.toString() ?? '');
                }
              }),
            ),
            const SizedBox(height: 12),

            // Row 5: Actions
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10)),
                    onPressed: _loading
                        ? null
                        : () async => await _fetchReport(),
                    child: _loading
                        ? const SizedBox(
                            height: 16,
                            width: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Generate Report', style: TextStyle(fontSize: 13)),
                  ),
                ),
                const SizedBox(width: 8),
                OutlinedButton(
                  style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 10)),
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
                  child: const Text('Reset', style: TextStyle(fontSize: 13)),
                ),
              ],
            ),
            const SizedBox(height: 12),
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
    final schoolId = _getSchoolId();
    setState(() {
      _loading = true;
      _error = null;
      _items = [];
    });
    try {
      final dio = DioClient().dio;
      final params = <String, dynamic>{};
      
      if (schoolId != null) params['school_id'] = schoolId;

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
    // Use addPostFrameCallback to ensure provider is ready
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadInitialFilters();
    });
  }

  Future<void> _loadInitialFilters() async {
    await _fetchClasses();
    await _fetchTeachers();
  }

  Future<void> _fetchClasses() async {
    final schoolId = _getSchoolId();
    try {
      final params = <String, dynamic>{};
      if (schoolId != null) params['school_id'] = schoolId;
      
      final resp = await DioClient().dio.get(
        'principal/students/filters/classes',
        queryParameters: params,
      );
      _addLog('GET principal/students/filters/classes -> ${resp.statusCode}');
      _addLog('Resp: ${resp.data}');
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        final rawData = data.map((e) => {
          'id': e['id'], 
          'name': e['name'],
          'numeric_value': e['numeric_value']
        }).toList();
        
        // Numerical sort for classes
        rawData.sort((a, b) {
          final an = int.tryParse(a['numeric_value']?.toString() ?? '');
          final bn = int.tryParse(b['numeric_value']?.toString() ?? '');
          if (an != null && bn != null && an != bn) return an.compareTo(bn);
          return a['name'].toString().compareTo(b['name'].toString());
        });

        setState(() {
          _classes = rawData.cast<Map<String, dynamic>>();
        });
      } else {
        _addLog('Classes endpoint returned status ${resp.statusCode}');
      }
      
      if (_classes.isEmpty && schoolId != null) {
        try {
          final dbg = await DioClient().dio.get(
            'debug/classes',
            queryParameters: {'school_id': schoolId},
          );
          _addLog('GET debug/classes?school_id=$schoolId -> ${dbg.statusCode}');
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
    final schoolId = _getSchoolId();
    try {
      final params = {'class_id': classId};
      if (schoolId != null) params['school_id'] = schoolId;

      final resp = await DioClient().dio.get(
        'principal/students/filters/sections',
        queryParameters: params,
      );
      _addLog(
        'GET principal/students/filters/sections?class_id=$classId -> ${resp.statusCode}',
      );
      _addLog('Resp: ${resp.data}');
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        final rawData = data.map((e) => {'id': e['id'], 'name': e['name']}).toList();
        
        // Natural sort for sections
        rawData.sort((a, b) {
          final s1 = a['name'].toString();
          final s2 = b['name'].toString();
          final n1 = int.tryParse(s1);
          final n2 = int.tryParse(s2);
          if (n1 != null && n2 != null) return n1.compareTo(n2);
          return s1.compareTo(s2);
        });

        setState(() {
          _sections = rawData.cast<Map<String, dynamic>>();
        });
      } else {
        _addLog('Sections endpoint returned status ${resp.statusCode}');
      }
      // Fallback to meta endpoint if empty
      if (_sections.isEmpty) {
        try {
          final meta = await DioClient().dio.get(
            'meta/sections',
            // meta/sections often filters by class_id globally but passing school_id is safer
             queryParameters: params,
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
      
      if (_sections.isEmpty && schoolId != null) {
        try {
          final dbg2 = await DioClient().dio.get(
            'debug/sections',
            queryParameters: {'school_id': schoolId},
          );
          _addLog('GET debug/sections?school_id=$schoolId -> ${dbg2.statusCode}');
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
    final schoolId = _getSchoolId();
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
      if (schoolId != null) params['school_id'] = schoolId;

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
          _subjects = <Map<String, dynamic>>[
                {'id': -1, 'name': _selectedClassId == null ? 'All Subjects' : 'Any Subject'}
              ] +
              data
                  .map((e) => <String, dynamic>{'id': e['id'], 'name': e['name']})
                  .toList();
        });
      } else {
        _addLog('Subjects endpoint returned status ${resp.statusCode}');
      }
      // Fallback to meta endpoint
      if (_subjects.isEmpty) {
        try {
          final meta = await DioClient().dio.get(
            'meta/subjects',
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
      if (_subjects.isEmpty && schoolId != null) {
        try {
          final dbg = await DioClient().dio.get(
            'debug/subjects',
            queryParameters: params..['school_id'] = schoolId,
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
    final schoolId = _getSchoolId();
    try {
      final params = <String, dynamic>{};
      if (schoolId != null) params['school_id'] = schoolId;

      final resp = await DioClient().dio.get(
        'meta/teachers',
        queryParameters: params,
      );
      _addLog('GET meta/teachers -> ${resp.statusCode}');
      _addLog('Resp: ${resp.data}');
      if (resp.statusCode == 200) {
        final data = _extractList(resp.data);
        setState(() {
          _teachers = <Map<String, dynamic>>[
            {'id': -1, 'name': 'All Teachers', 'designation': ''}
          ] +
              data
                  .map((e) => <String, dynamic>{
                        'id': e['id'],
                        'name': e['name'],
                        'designation': e['designation']
                      })
                  .toList();
        });
      } else {
        _addLog('Teachers endpoint returned status ${resp.statusCode}');
      }
      
      if (_teachers.isEmpty && schoolId != null) {
        try {
          final dbg = await DioClient().dio.get(
            'debug/teachers',
            queryParameters: {'school_id': schoolId},
          );
          _addLog('GET debug/teachers?school_id=$schoolId -> ${dbg.statusCode}');
          if (dbg.statusCode == 200) {
            final ddata = _extractList(dbg.data);
            setState(() {
              _teachers = ddata
                  .map((e) => {
                        'id': e['id'], 
                        'name': e['name'],
                        'designation': e['designation'] ?? 'Teacher'
                       })
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
