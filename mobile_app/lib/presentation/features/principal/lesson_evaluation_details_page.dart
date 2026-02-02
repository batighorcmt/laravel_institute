import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import 'package:dio/dio.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'principal_student_profile_page.dart';

class LessonEvaluationDetailsPage extends StatefulWidget {
  final Map<String, dynamic> report;
  const LessonEvaluationDetailsPage({super.key, required this.report});

  @override
  State<LessonEvaluationDetailsPage> createState() =>
      _LessonEvaluationDetailsPageState();
}

class _LessonEvaluationDetailsPageState
    extends State<LessonEvaluationDetailsPage> {
  List<dynamic> _students = [];
  String? _statusFilter;
  bool _loading = false;
  String? _error;
  Map<String, dynamic>? _remoteEval;

  @override
  void initState() {
    super.initState();
    _loadLocalStudentsOrFetch();
  }

  void _loadLocalStudentsOrFetch() async {
    // Try to extract students from the provided report object using common keys.
    final it = widget.report;
    final candidates = <dynamic>[];
    if (it['students'] is List) candidates.addAll(it['students']);
    if (it['items'] is List) candidates.addAll(it['items']);
    if (it['students_list'] is List) candidates.addAll(it['students_list']);
    if (it['students_data'] is List) candidates.addAll(it['students_data']);
    if (it['rows'] is List) candidates.addAll(it['rows']);

    if (candidates.isNotEmpty) {
      setState(() {
        _students = candidates;
      });
      return;
    }

    // If there were no students embedded, attempt to fetch details using a few
    // commonly used endpoints. These calls are best-effort and will silently
    // fail if the backend doesn't support them.
    if (it['id'] == null && it['evaluation_id'] == null) return;

    final id = it['id'] ?? it['evaluation_id'];
    setState(() => _loading = true);
    try {
      final dio = DioClient().dio;
      // First try a direct details endpoint
      final resp = await dio.get('principal/reports/lesson-evaluations/$id');
      // Prefer structured evaluation payload when available
      if (resp.data is Map && resp.data['evaluation'] is Map) {
        final eval = resp.data['evaluation'] as Map<String, dynamic>;
        _remoteEval = eval;
        final recs = eval['records'] is List
            ? eval['records'] as List<dynamic>
            : [];
        if (recs.isNotEmpty) {
          setState(() {
            _students = recs;
            _error = null;
          });
          return;
        }
      }
      final extracted1 = _extractStudentsFrom(resp.data);
      if (extracted1.isNotEmpty) {
        setState(() {
          _students = extracted1;
          _error = null;
        });
        return;
      }

      // fallback: try an endpoint that accepts query params
      final params = <String, dynamic>{};
      if (it['evaluation_date'] != null) params['date'] = it['evaluation_date'];
      if (it['class_id'] != null) params['class_id'] = it['class_id'];
      if (it['section_id'] != null) params['section_id'] = it['section_id'];
      if (it['subject_id'] != null) params['subject_id'] = it['subject_id'];
      if (it['teacher_id'] != null) params['teacher_id'] = it['teacher_id'];

      final resp2 = await dio.get(
        'principal/reports/lesson-evaluations/details',
        queryParameters: params,
      );
      if (resp2.data is Map && resp2.data['records'] is List) {
        final recs = resp2.data['records'] as List<dynamic>;
        if (recs.isNotEmpty) {
          setState(() {
            _students = recs;
            _error = null;
          });
          return;
        }
      }
      final extracted2 = _extractStudentsFrom(resp2.data);
      if (extracted2.isNotEmpty) {
        setState(() {
          _students = extracted2;
          _error = null;
        });
        return;
      }

      // Final fallback: some endpoints (teacher facing) provide the student
      // list via the teacher form endpoint. Try that with available params.
      final tp = <String, dynamic>{};
      if (it['class_id'] != null) tp['class_id'] = it['class_id'];
      if (it['section_id'] != null) tp['section_id'] = it['section_id'];
      if (it['subject_id'] != null) tp['subject_id'] = it['subject_id'];
      if (it['evaluation_date'] != null) tp['date'] = it['evaluation_date'];
      // try a few common names/locations for routine entry id
      if (it['routine_entry_id'] != null)
        tp['routine_entry_id'] = it['routine_entry_id'];
      else if (it['routine_id'] != null)
        tp['routine_entry_id'] = it['routine_id'];
      else if (it['routine'] is Map && it['routine']['id'] != null)
        tp['routine_entry_id'] = it['routine']['id'];
      else if (it['routine_entry'] is Map && it['routine_entry']['id'] != null)
        tp['routine_entry_id'] = it['routine_entry']['id'];
      else if (it['routineEntryId'] != null)
        tp['routine_entry_id'] = it['routineEntryId'];

      if (tp.isNotEmpty) {
        try {
          final resp3 = await dio.get(
            'teacher/lesson-evaluations/form',
            queryParameters: tp,
          );
          if (resp3.statusCode == 200) {
            final extracted3 = _extractStudentsFrom(resp3.data);
            if (extracted3.isNotEmpty) {
              setState(() {
                _students = extracted3;
                _error = null;
              });
              return;
            }
          }
        } catch (_) {
          // ignore teacher-form fallback errors
        }
      }
    } on DioException catch (e) {
      // If the endpoint doesn't exist or returns 404, treat as "no details" rather
      // than an error to display. Only surface other errors.
      final code = e.response?.statusCode;
      if (code == 404) {
        setState(() {
          _error = null;
        });
      } else {
        setState(() => _error = e.toString());
      }
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _getName(dynamic s) {
    if (s is Map)
      return (s['name'] ?? s['student_name'] ?? s['full_name'] ?? '')
          .toString();
    return s.toString();
  }

  String _getStatus(dynamic s) {
    if (s is Map)
      return (s['status'] ?? s['attendance_status'] ?? s['status_name'] ?? '')
          .toString();
    return '';
  }

  String _getRoll(dynamic s) {
    if (s is Map)
      return (s['roll_no'] ??
              s['roll'] ??
              s['admission_no'] ??
              s['seat_no'] ??
              '')
          .toString();
    return '';
  }

  String _statusLabelLocalized(String raw) {
    final l = raw.toLowerCase();
    if (l.contains('complete')) return 'পড়া হয়েছে';
    if (l.contains('partial')) return 'আংশিক হয়েছে';
    if (l.contains('absent')) return 'অনুপস্থিত';
    if (l.contains('not') || l.contains('not_done') || l.contains('not done'))
      return 'পড়া হয়নি';
    return raw;
  }

  Color _statusColorFromRaw(String raw, BuildContext context) {
    final l = raw.toLowerCase();
    final scheme = Theme.of(context).colorScheme;
    if (l.contains('not') || l.contains('not_done') || l.contains('not done'))
      return Colors.red; // পড়া হয়নি -> red
    if (l.contains('absent'))
      return Colors.blueGrey; // অনুপস্থিত -> distinct color
    if (l.contains('complete')) return Colors.green;
    if (l.contains('partial')) return Colors.orange;
    return scheme.onSurface.withOpacity(0.7);
  }

  Future<void> _callPhone(String? number) async {
    if (number == null || number.toString().trim().isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('No phone number')));
      return;
    }
    final uri = Uri(scheme: 'tel', path: number.toString());
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri);
      } else {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Cannot place call')));
      }
    } catch (_) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Call failed')));
    }
  }

  void _showStudentModal(Map<String, dynamic> student) {
    final raw = student['raw'] ?? student;
    String photo = (student['photo'] ?? '')?.toString() ?? '';
    String name = (student['name'] ?? '')?.toString() ?? '';
    String father = '';
    String mother = '';
    String phone = '';

    dynamic _findValue(dynamic node, List<String> keys) {
      bool _hasVal(dynamic v) => v != null && v.toString().trim().isNotEmpty;

      dynamic _search(dynamic cur) {
        if (cur is Map) {
          for (final k in keys) {
            if (_hasVal(cur[k])) return cur[k];
          }
          // search nested maps
          for (final v in cur.values) {
            final r = _search(v);
            if (_hasVal(r)) return r;
          }
        } else if (cur is List) {
          for (final e in cur) {
            final r = _search(e);
            if (_hasVal(r)) return r;
          }
        }
        return null;
      }

      return _search(node);
    }

    if (raw != null) {
      father =
          (_findValue(raw, [
                    'father_name',
                    'father',
                    'guardian_father',
                    'parent_father',
                  ]) ??
                  '')
              .toString();
      mother =
          (_findValue(raw, [
                    'mother_name',
                    'mother',
                    'guardian_mother',
                    'parent_mother',
                  ]) ??
                  '')
              .toString();
      phone =
          (_findValue(raw, [
                    'guardian_phone',
                    'guardian_mobile',
                    'guardian_number',
                    'guardian_contact',
                    'phone',
                    'mobile',
                  ]) ??
                  '')
              .toString();
      if (photo.isEmpty)
        photo =
            (_findValue(raw, [
                      'photo_url',
                      'photo',
                      'image',
                      'avatar',
                      'profile_photo',
                    ]) ??
                    '')
                .toString();
      if (name.isEmpty)
        name = (_findValue(raw, ['name', 'full_name', 'student_name']) ?? '')
            .toString();
    }

    final studentId =
        (_findValue(student, ['id', 'student_id', 'user_id']) ??
                _findValue(raw, ['id', 'student_id', 'user_id']))
            ?.toString();

    showDialog(
      context: context,
      barrierDismissible: true,
      builder: (ctx) {
        return AlertDialog(
          contentPadding: const EdgeInsets.fromLTRB(20, 20, 20, 12),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (photo.isNotEmpty)
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: CachedNetworkImage(
                    imageUrl: photo,
                    width: 100,
                    height: 100,
                    fit: BoxFit.cover,
                    placeholder: (c, u) => Container(
                      width: 100,
                      height: 100,
                      color: Colors.grey.shade200,
                    ),
                    errorWidget: (c, u, e) => Container(
                      width: 100,
                      height: 100,
                      color: Colors.grey.shade200,
                    ),
                  ),
                )
              else
                Container(
                  width: 100,
                  height: 100,
                  color: Colors.grey.shade200,
                  child: const Icon(Icons.person, size: 48),
                ),
              const SizedBox(height: 12),
              Text(
                name,
                style: Theme.of(
                  context,
                ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 8),
              if (father.isNotEmpty)
                Text(
                  'পিতা: $father',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              if (mother.isNotEmpty)
                Text(
                  'মাতা: $mother',
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  ElevatedButton.icon(
                    icon: const Icon(Icons.call),
                    label: const Text('কল'),
                    onPressed: phone.isNotEmpty
                        ? () {
                            Navigator.of(ctx).pop();
                            _callPhone(phone);
                          }
                        : null,
                  ),
                  OutlinedButton(
                    child: const Text('প্রোফাইল'),
                    onPressed: studentId != null
                        ? () {
                            Navigator.of(ctx).pop();
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => PrincipalStudentProfilePage(
                                  studentId: studentId,
                                  initialData: raw,
                                ),
                              ),
                            );
                          }
                        : null,
                  ),
                  TextButton(
                    child: const Text('বন্ধ'),
                    onPressed: () => Navigator.of(ctx).pop(),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  String? _getPhotoUrl(dynamic s) {
    if (s is Map) {
      final keys = [
        'photo',
        'image',
        'avatar',
        'profile_photo',
        'student_photo',
        'photo_url',
      ];
      for (final k in keys) {
        if (s[k] != null && s[k].toString().isNotEmpty) return s[k].toString();
      }
    }
    return null;
  }

  List<Map<String, dynamic>> _normalizedStudents() {
    final out = <Map<String, dynamic>>[];
    final statusMap = _buildStatusMap();
    for (final s in _students) {
      if (s == null) continue;
      if (s is Map) {
        // If this is a record-like object it might contain a nested
        // `student` map. Prefer nested student info for name/roll/photo.
        final person = (s['student'] is Map) ? s['student'] as Map : s;

        int? id;
        try {
          final rawId =
              person['id'] ??
              person['student_id'] ??
              s['student_id'] ??
              s['id'] ??
              person['user_id'];
          if (rawId is num) id = rawId.toInt();
          if (rawId is String) id = int.tryParse(rawId);
        } catch (_) {}

        var name = _getName(person);
        final roll = _getRoll(person);
        // If the record only contains `student_id` without nested student
        // details, show a fallback label so the table still displays usefully.
        if ((name == null || name.toString().trim().isEmpty) && id != null) {
          name = 'Student #${id.toString()}';
        }

        // Status can live on the record (`s`) or on the nested `person`.
        var status = '';
        if (s['status'] != null)
          status = s['status'].toString();
        else if (s['record_status'] != null)
          status = s['record_status'].toString();
        else if (person['status'] != null)
          status = person['status'].toString();
        else if (s['evaluation_status'] != null)
          status = s['evaluation_status'].toString();

        // fallback to report-level status map
        if ((status == null || status.toString().isEmpty) &&
            statusMap.isNotEmpty) {
          final tryKeys = <String>[];
          if (id != null) tryKeys.add(id.toString());
          if (s['student_id'] != null) tryKeys.add(s['student_id'].toString());
          if (roll.isNotEmpty) tryKeys.add(roll);
          if (name.isNotEmpty) tryKeys.add(name.toLowerCase());
          for (final k in tryKeys) {
            if (statusMap.containsKey(k)) {
              status = statusMap[k] ?? '';
              break;
            }
          }
        }
        status = status?.toLowerCase() ?? '';
        final photo = _getPhotoUrl(person);
        out.add({
          'id': id,
          'name': name,
          'roll': roll,
          'status': status,
          'photo': photo,
          'raw': s,
        });
      } else {
        // simple scalar (string) entry
        out.add({
          'id': null,
          'name': s.toString(),
          'roll': '',
          'status': '',
          'photo': null,
          'raw': s,
        });
      }
    }
    return out;
  }

  Map<String, String> _buildStatusMap() {
    final map = <String, String>{};
    final it = widget.report;
    void addFromList(dynamic list) {
      if (list is! List) return;
      for (final e in list) {
        if (e is Map) {
          String? id;
          if (e['id'] != null)
            id = e['id'].toString();
          else if (e['student_id'] != null)
            id = e['student_id'].toString();
          String? roll;
          if (e['roll_no'] != null)
            roll = e['roll_no'].toString();
          else if (e['roll'] != null)
            roll = e['roll'].toString();
          final name = (e['name'] ?? e['student_name'] ?? '').toString();
          String? status;
          if (e['status'] != null)
            status = e['status'].toString();
          else if (e['evaluation_status'] != null)
            status = e['evaluation_status'].toString();
          else if (e['attendance_status'] != null)
            status = e['attendance_status'].toString();
          // also accept record-style fields
          else if (e['record_status'] != null)
            status = e['record_status'].toString();
          if (status != null) {
            status = status.toLowerCase();
            if (id != null) map[id] = status;
            if (roll != null && roll.isNotEmpty) map[roll] = status;
            if (name.isNotEmpty) map[name.toLowerCase()] = status;
          }
        }
      }
    }

    // Common places in report to find per-student statuses
    addFromList(it['items']);
    addFromList(it['students']);
    addFromList(it['rows']);
    addFromList(it['records']); // lesson_evaluation_records
    if (it['data'] is Map) addFromList(it['data']['students']);
    // also check nested arrays inside items and evaluation objects
    if (it['items'] is List) {
      for (final v in it['items']) {
        if (v is Map) {
          addFromList(v['students']);
          addFromList(v['items']);
          addFromList(v['records']);
        }
      }
    }
    if (it['evaluation'] is Map) {
      addFromList((it['evaluation'] as Map)['records']);
    }
    return map;
  }

  List<dynamic> _extractStudentsFrom(dynamic d) {
    final out = <dynamic>[];
    if (d == null) return out;
    if (d is List) return List<dynamic>.from(d);
    if (d is Map) {
      // direct keys
      if (d['students'] is List) out.addAll(d['students']);
      if (d['items'] is List && out.isEmpty) out.addAll(d['items']);
      if (d['records'] is List && out.isEmpty) out.addAll(d['records']);
      if (d['data'] is Map) {
        final dd = d['data'] as Map;
        if (dd['students'] is List) out.addAll(dd['students']);
        if (dd['items'] is List && out.isEmpty) out.addAll(dd['items']);
        if (dd['records'] is List && out.isEmpty) out.addAll(dd['records']);
      }
      // support evaluation -> records shape
      if (d['evaluation'] is Map) {
        final ev = d['evaluation'] as Map;
        if (ev['records'] is List && out.isEmpty) out.addAll(ev['records']);
        if (ev['students'] is List && out.isEmpty) out.addAll(ev['students']);
      }
      // search one level deep for common keys
      for (final v in d.values) {
        if (v is Map) {
          if (v['students'] is List) out.addAll(v['students']);
          if (v['items'] is List && out.isEmpty) out.addAll(v['items']);
          if (v['records'] is List && out.isEmpty) out.addAll(v['records']);
        }
      }
    }
    return out;
  }

  List<Map<String, dynamic>> _normalizeList(List<dynamic> list) {
    final prev = _students;
    try {
      _students = list;
      return _normalizedStudents();
    } finally {
      _students = prev;
    }
  }

  List<Map<String, dynamic>> _extractGroups() {
    final it = widget.report;
    final groups = <Map<String, dynamic>>[];

    // Common keys that may contain grouped evaluations
    final candidateKeys = [
      'evaluations',
      'groups',
      'class_sections',
      'sections',
      'class_rows',
      'rows',
      'items',
    ];

    for (final key in candidateKeys) {
      if (it[key] is List) {
        for (final g in (it[key] as List)) {
          if (g is Map) {
            final students = <dynamic>[];
            if (g['students'] is List) students.addAll(g['students']);
            if (g['items'] is List) students.addAll(g['items']);
            if (g['rows'] is List) students.addAll(g['rows']);
            if (students.isNotEmpty) {
              groups.add({
                'class_name':
                    g['class_name'] ?? g['class'] ?? it['class_name'] ?? '',
                'section_name':
                    g['section_name'] ??
                    g['section'] ??
                    g['section_title'] ??
                    it['section_name'] ??
                    '',
                'students': students,
              });
            }
          }
        }
        if (groups.isNotEmpty) return groups;
      }
    }

    // Nothing grouped found
    return groups;
  }

  @override
  Widget build(BuildContext context) {
    final remote = _remoteEval;
    final title =
        '${remote?['evaluation_date'] ?? widget.report['evaluation_date'] ?? ''} • Details';
    final stats =
        (remote?['stats'] ?? widget.report['stats']) as Map<String, dynamic>?;
    final className =
        (remote?['class_name'] ?? widget.report['class_name'] ?? '').toString();
    final sectionName =
        (remote?['section_name'] ?? widget.report['section_name'] ?? '')
            .toString();
    final subjectName =
        (remote?['subject_name'] ?? widget.report['subject_name'] ?? '')
            .toString();
    final teacherName =
        (remote?['teacher'] is Map
            ? (remote!['teacher']['name'] ?? '')
            : remote?['teacher_name']) ??
        widget.report['teacher_name'] ??
        '';
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          children: [
            // Top compact metadata: class / section / subject / teacher
            Card(
              color: Colors.grey.shade50,
              elevation: 0,
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 10,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Wrap(
                      spacing: 12,
                      runSpacing: 6,
                      children: [
                        if (className.isNotEmpty)
                          Text(
                            className,
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        if (sectionName.isNotEmpty)
                          Text(sectionName, style: TextStyle(fontSize: 13)),
                        if (subjectName.isNotEmpty)
                          Text(subjectName, style: TextStyle(fontSize: 13)),
                        if (teacherName.toString().trim().isNotEmpty)
                          Text(
                            teacherName.toString(),
                            style: TextStyle(fontSize: 13),
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    if (stats != null)
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.green.shade50,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        padding: const EdgeInsets.symmetric(
                          vertical: 10,
                          horizontal: 8,
                        ),
                        child: Column(
                          children: [
                            Row(
                              children: const [
                                Expanded(child: Center(child: Text('Total'))),
                                Expanded(child: Center(child: Text('Done'))),
                                Expanded(child: Center(child: Text('Partial'))),
                                Expanded(
                                  child: Center(child: Text('Not Done')),
                                ),
                                Expanded(child: Center(child: Text('Absent'))),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Row(
                              children: [
                                Expanded(
                                  child: Center(
                                    child: Text(
                                      '${stats['total'] ?? 0}',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: Center(
                                    child: Text(
                                      '${stats['completed'] ?? 0}',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: Center(
                                    child: Text(
                                      '${stats['partial'] ?? 0}',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: Center(
                                    child: Text(
                                      '${stats['not_done'] ?? 0}',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: Center(
                                    child: Text(
                                      '${stats['absent'] ?? 0}',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                      ),
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
              ),
            ),
            const SizedBox(height: 8),
            // Status filter
            Container(
              margin: const EdgeInsets.only(bottom: 8),
              child: DropdownButtonFormField<String>(
                value: _statusFilter ?? '',
                decoration: InputDecoration(
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 8,
                  ),
                  filled: true,
                  fillColor: Theme.of(context).cardColor,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide.none,
                  ),
                ),
                items: const [
                  DropdownMenuItem(value: '', child: Text('সকল স্ট্যাটাস')),
                  DropdownMenuItem(
                    value: 'complete',
                    child: Text('পড়া হয়েছে'),
                  ),
                  DropdownMenuItem(
                    value: 'partial',
                    child: Text('আংশিক হয়েছে'),
                  ),
                  DropdownMenuItem(value: 'not', child: Text('পড়া হয়নি')),
                  DropdownMenuItem(value: 'absent', child: Text('অনুপস্থিত')),
                ],
                onChanged: (v) =>
                    setState(() => _statusFilter = (v ?? '').toString()),
              ),
            ),
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _error != null
                  ? Center(child: Text('Error: $_error'))
                  : _students.isEmpty
                  ? (() {
                      final groups = _extractGroups();
                      if (groups.isNotEmpty) {
                        return ListView.separated(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          itemCount: groups.length,
                          separatorBuilder: (_, __) =>
                              const SizedBox(height: 8),
                          itemBuilder: (ctx, idx) {
                            final g = groups[idx];
                            final title =
                                '${g['class_name'] ?? ''} ${g['section_name'] ?? ''}'
                                    .trim();
                            return Card(
                              child: Padding(
                                padding: const EdgeInsets.all(8.0),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    if (title.isNotEmpty)
                                      Padding(
                                        padding: const EdgeInsets.only(
                                          bottom: 8.0,
                                        ),
                                        child: Text(
                                          title,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                      ),
                                    _buildStudentsTableForList(
                                      g['students'] as List<dynamic>,
                                    ),
                                  ],
                                ),
                              ),
                            );
                          },
                        );
                      }
                      return const SizedBox.shrink();
                    })()
                  : _buildStudentsTable(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStudentsTable() {
    final normalized = _normalizedStudents();
    if (normalized.isEmpty)
      return const Center(child: Text('No students to show'));

    return ListView.separated(
      padding: EdgeInsets.zero,
      itemCount: normalized.length,
      separatorBuilder: (_, __) => const Divider(height: 1),
      itemBuilder: (ctx, i) {
        final s = normalized[i];
        final st = (s['status'] ?? '').toString();
        if (_statusFilter != null &&
            _statusFilter!.isNotEmpty &&
            !st.contains(_statusFilter!)) {
          return const SizedBox.shrink();
        }
        final roll = (s['roll'] ?? '').toString();
        final photo = (s['photo'] ?? '') as String?;

        Widget leading;
        if (photo != null && photo.isNotEmpty) {
          leading = ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: CachedNetworkImage(
              imageUrl: photo,
              width: 44,
              height: 44,
              fit: BoxFit.cover,
              placeholder: (c, u) => Container(
                width: 44,
                height: 44,
                color: Colors.grey.shade200,
                child: const Icon(Icons.person, color: Colors.grey, size: 20),
              ),
              errorWidget: (c, u, e) => Container(
                width: 44,
                height: 44,
                color: Colors.grey.shade200,
                child: const Icon(Icons.person, color: Colors.grey, size: 20),
              ),
            ),
          );
        } else {
          leading = Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: Colors.grey.shade200,
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.person, color: Colors.grey, size: 20),
          );
        }

        final statusLabel = _statusLabelLocalized(st);
        final statusColor = _statusColorFromRaw(st, context);

        return ListTile(
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 12,
            vertical: 6,
          ),
          leading: leading,
          title: InkWell(
            onTap: () => _showStudentModal(s),
            child: Text(
              (s['name'] ?? '').toString(),
              style: Theme.of(
                context,
              ).textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.w600),
            ),
          ),
          subtitle: Text(
            roll.isEmpty ? '' : 'রোল: $roll',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          trailing: Chip(
            label: Text(
              statusLabel,
              style: TextStyle(
                color: statusColor,
                fontWeight: FontWeight.w700,
                fontSize: 12,
              ),
            ),
            backgroundColor: statusColor.withOpacity(0.12),
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
          ),
        );
      },
    );
  }

  Widget _buildStudentsTableForList(List<dynamic> list) {
    final normalized = _normalizeList(list);
    if (normalized.isEmpty)
      return const Center(child: Text('No students to show'));

    return ListView.separated(
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      itemCount: normalized.length,
      separatorBuilder: (_, __) => const Divider(height: 1),
      itemBuilder: (ctx, i) {
        final s = normalized[i];
        final st = (s['status'] ?? '').toString();
        if (_statusFilter != null &&
            _statusFilter!.isNotEmpty &&
            !st.contains(_statusFilter!)) {
          return const SizedBox.shrink();
        }
        final roll = (s['roll'] ?? '').toString();
        final photo = (s['photo'] ?? '') as String?;

        Widget leading;
        if (photo != null && photo.isNotEmpty) {
          leading = ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: CachedNetworkImage(
              imageUrl: photo,
              width: 44,
              height: 44,
              fit: BoxFit.cover,
              placeholder: (c, u) => Container(
                width: 44,
                height: 44,
                color: Colors.grey.shade200,
                child: const Icon(Icons.person, color: Colors.grey, size: 20),
              ),
              errorWidget: (c, u, e) => Container(
                width: 44,
                height: 44,
                color: Colors.grey.shade200,
                child: const Icon(Icons.person, color: Colors.grey, size: 20),
              ),
            ),
          );
        } else {
          leading = Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: Colors.grey.shade200,
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.person, color: Colors.grey, size: 20),
          );
        }

        final statusLabel = _statusLabelLocalized(st);
        final statusColor = _statusColorFromRaw(st, context);

        return ListTile(
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 12,
            vertical: 6,
          ),
          leading: leading,
          title: InkWell(
            onTap: () => _showStudentModal(s),
            child: Text(
              (s['name'] ?? '').toString(),
              style: Theme.of(
                context,
              ).textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.w600),
            ),
          ),
          subtitle: Text(
            roll.isEmpty ? '' : 'রোল: $roll',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          trailing: Chip(
            label: Text(
              statusLabel,
              style: TextStyle(
                color: statusColor,
                fontWeight: FontWeight.w700,
                fontSize: 12,
              ),
            ),
            backgroundColor: statusColor.withOpacity(0.12),
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
          ),
        );
      },
    );
  }
}
