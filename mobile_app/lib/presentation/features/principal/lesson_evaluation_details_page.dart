import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import 'package:dio/dio.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:url_launcher/url_launcher.dart';
import 'principal_student_profile_page.dart';
import 'lesson_evaluation_student_profile_page.dart';

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
      if (it['routine_entry_id'] != null) {
        tp['routine_entry_id'] = it['routine_entry_id'];
      } else if (it['routine_id'] != null) {
        tp['routine_entry_id'] = it['routine_id'];
      } else if (it['routine'] is Map && it['routine']['id'] != null) {
        tp['routine_entry_id'] = it['routine']['id'];
      } else if (it['routine_entry'] is Map &&
          it['routine_entry']['id'] != null) {
        tp['routine_entry_id'] = it['routine_entry']['id'];
      } else if (it['routineEntryId'] != null) {
        tp['routine_entry_id'] = it['routineEntryId'];
      }

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
    if (s is Map) {
      return (s['name'] ?? s['student_name'] ?? s['full_name'] ?? '')
          .toString();
    }
    return s.toString();
  }

  String _getRoll(dynamic s) {
    if (s is Map) {
      return (s['roll_no'] ??
              s['roll'] ??
              s['admission_no'] ??
              s['seat_no'] ??
              '')
          .toString();
    }
    return '';
  }

  String _statusLabelLocalized(String raw) {
    final l = raw.toLowerCase();
    if (l.contains('complete')) return 'পড়া হয়েছে';
    if (l.contains('partial')) return 'আংশিক হয়েছে';
    if (l.contains('absent')) return 'অনুপস্থিত';
    if (l.contains('not') || l.contains('not_done') || l.contains('not done')) {
      return 'পড়া হয়নি';
    }
    return raw;
  }

  Color _statusColorFromRaw(String raw, BuildContext context) {
    final l = raw.toLowerCase();
    final scheme = Theme.of(context).colorScheme;
    if (l.contains('not') || l.contains('not_done') || l.contains('not done')) {
      return Colors.red; // পড়া হয়নি -> red
    }
    if (l.contains('absent')) {
      return Colors.blueGrey; // অনুপস্থিত -> distinct color
    }
    if (l.contains('complete')) return Colors.green;
    if (l.contains('partial')) return Colors.orange;
    return scheme.onSurface.withValues(alpha: 0.7);
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
        if (!mounted) return;
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Cannot place call')));
      }
    } catch (_) {
      if (!mounted) return;
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

    dynamic findValue(dynamic node, List<String> keys) {
      bool hasVal(dynamic v) => v != null && v.toString().trim().isNotEmpty;

      dynamic search(dynamic cur) {
        if (cur is Map) {
          for (final k in keys) {
            if (hasVal(cur[k])) return cur[k];
          }
          // search nested maps
          for (final v in cur.values) {
            final r = search(v);
            if (hasVal(r)) return r;
          }
        } else if (cur is List) {
          for (final e in cur) {
            final r = search(e);
            if (hasVal(r)) return r;
          }
        }
        return null;
      }

      return search(node);
    }

    if (raw != null) {
      father =
          (findValue(raw, [
                    'father_name',
                    'father',
                    'guardian_father',
                    'parent_father',
                  ]) ??
                  '')
              .toString();
      mother =
          (findValue(raw, [
                    'mother_name',
                    'mother',
                    'guardian_mother',
                    'parent_mother',
                  ]) ??
                  '')
              .toString();
      phone =
          (findValue(raw, [
                    'guardian_phone',
                    'guardian_mobile',
                    'guardian_number',
                    'guardian_contact',
                    'phone',
                    'mobile',
                  ]) ??
                  '')
              .toString();
      if (photo.isEmpty) {
        photo =
            (findValue(raw, [
                      'photo_url',
                      'photo',
                      'image',
                      'avatar',
                      'profile_photo',
                    ]) ??
                    '')
                .toString();
      }
      if (name.isEmpty) {
        name = (findValue(raw, ['name', 'full_name', 'student_name']) ?? '')
            .toString();
      }
    }

    final studentId =
        (findValue(student, ['id', 'student_id', 'user_id']) ??
                findValue(raw, ['id', 'student_id', 'user_id']))
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
                    onPressed: studentId != null
                        ? () {
                            Navigator.of(ctx).pop();
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => PrincipalStudentDetailView(
                                  studentId: studentId,
                                  initialData: raw,
                                ),
                              ),
                            );
                          }
                        : null,
                    child: const Text('প্রোফাইল'),
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
          // Prefer `student_id` over `id`: when there is no nested `student`
          // map, `person` is the same object as the record `s`, whose `id`
          // is the lesson_evaluation_records.id, not the student's id.
          final rawId =
              person['student_id'] ??
              s['student_id'] ??
              person['id'] ??
              s['id'] ??
              person['user_id'];
          if (rawId is num) id = rawId.toInt();
          if (rawId is String) id = int.tryParse(rawId);
        } catch (_) {}

        var name = _getName(person);
        final roll = _getRoll(person);
        // If the record only contains `student_id` without nested student
        // details, show a fallback label so the table still displays usefully.
        if ((name.toString().trim().isEmpty) && id != null) {
          name = 'Student #${id.toString()}';
        }

        // Status can live on the record (`s`) or on the nested `person`.
        var status = '';
        if (s['status'] != null) {
          status = s['status'].toString();
        } else if (s['record_status'] != null) {
          status = s['record_status'].toString();
        } else if (person['status'] != null) {
          status = person['status'].toString();
        } else if (s['evaluation_status'] != null) {
          status = s['evaluation_status'].toString();
        }

        // fallback to report-level status map
        if ((status.toString().isEmpty) && statusMap.isNotEmpty) {
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
        status = status.toLowerCase();
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
          if (e['id'] != null) {
            id = e['id'].toString();
          } else if (e['student_id'] != null) {
            id = e['student_id'].toString();
          }
          String? roll;
          if (e['roll_no'] != null) {
            roll = e['roll_no'].toString();
          } else if (e['roll'] != null) {
            roll = e['roll'].toString();
          }
          final name = (e['name'] ?? e['student_name'] ?? '').toString();
          String? status;
          if (e['status'] != null) {
            status = e['status'].toString();
          } else if (e['evaluation_status'] != null) {
            status = e['evaluation_status'].toString();
          } else if (e['attendance_status'] != null) {
            status = e['attendance_status'].toString();
            // also accept record-style fields
          } else if (e['record_status'] != null) {
            status = e['record_status'].toString();
          }
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

  static const Color _brand = Color(0xFF00BF6D);
  static const Color _brandDark = Color(0xFF049655);
  static const Color _bg = Color(0xFFF5F7F9);
  static const Color _ink = Color(0xFF1A1D1F);
  static const Color _muted = Color(0xFF6B7280);

  static const List<Map<String, String>> _statusOptions = [
    {'value': '', 'label': 'সকল'},
    {'value': 'complete', 'label': 'সম্পন্ন'},
    {'value': 'partial', 'label': 'আংশিক'},
    {'value': 'not', 'label': 'হয়নি'},
    {'value': 'absent', 'label': 'অনুপস্থিত'},
  ];

  @override
  Widget build(BuildContext context) {
    final remote = _remoteEval;
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
    final periodNumber =
        (remote?['period_number'] ?? widget.report['period_number'])
            ?.toString();
    final startTime = (remote?['start_time'] ?? widget.report['start_time'])
        ?.toString();
    final endTime = (remote?['end_time'] ?? widget.report['end_time'])
        ?.toString();
    final timeLabel = (startTime != null && startTime.isNotEmpty)
        ? (endTime != null && endTime.isNotEmpty
              ? '$startTime - $endTime'
              : startTime)
        : null;
    final submittedAt =
        (remote?['submitted_at'] ?? widget.report['submitted_at'])?.toString();
    final todayTopic = (remote?['notes'] ?? widget.report['notes'])?.toString();
    final nextTopic =
        (remote?['next_topic'] ?? widget.report['next_topic'])?.toString();

    return Scaffold(
      backgroundColor: _bg,
      appBar: AppBar(
        title: Text(
          '$className${sectionName.isNotEmpty ? ' - $sectionName' : ''}',
        ),
        backgroundColor: _brand,
        foregroundColor: Colors.white,
      ),
      body: _loading && _students.isEmpty
          ? const Center(child: CircularProgressIndicator(color: _brand))
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
              children: [
                _buildInfoCard(
                  subjectName: subjectName,
                  teacherName: teacherName.toString(),
                  periodNumber: periodNumber,
                  timeLabel: timeLabel,
                  submittedAt: submittedAt,
                  todayTopic: todayTopic,
                  nextTopic: nextTopic,
                ),
                const SizedBox(height: 14),
                if (stats != null) _buildStatsGrid(stats),
                const SizedBox(height: 14),
                _buildStatusFilter(),
                const SizedBox(height: 10),
                _buildStudentsSection(),
              ],
            ),
    );
  }

  Widget _buildInfoCard({
    required String subjectName,
    required String teacherName,
    String? periodNumber,
    String? timeLabel,
    String? submittedAt,
    String? todayTopic,
    String? nextTopic,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [_brand, _brandDark],
        ),
        borderRadius: BorderRadius.circular(18),
        boxShadow: const [
          BoxShadow(
            color: Color(0x22000000),
            blurRadius: 14,
            offset: Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (subjectName.isNotEmpty)
            Text(
              subjectName,
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w700,
                fontSize: 17,
              ),
            ),
          const SizedBox(height: 12),
          _infoLine(
            icon: Icons.person_outline,
            text: teacherName.isNotEmpty ? teacherName : 'শিক্ষক অজানা',
          ),
          if (periodNumber != null || timeLabel != null) ...[
            const SizedBox(height: 6),
            _infoLine(
              icon: Icons.schedule,
              text: [
                if (periodNumber != null) 'পিরিয়ড $periodNumber',
                if (timeLabel != null) timeLabel,
              ].join(' • '),
            ),
          ],
          if (submittedAt != null && submittedAt.isNotEmpty) ...[
            const SizedBox(height: 6),
            _infoLine(
              icon: Icons.check_circle_outline,
              text: 'জমা দেওয়া হয়েছে: $submittedAt',
            ),
          ],
          if (todayTopic != null && todayTopic.isNotEmpty) ...[
            const SizedBox(height: 10),
            _infoLine(
              icon: Icons.menu_book_outlined,
              text: 'আজকের পাঠ্য বিষয়: $todayTopic',
            ),
          ],
          if (nextTopic != null && nextTopic.isNotEmpty) ...[
            const SizedBox(height: 6),
            _infoLine(
              icon: Icons.arrow_forward_outlined,
              text: 'আগামীর পাঠ্য বিষয়: $nextTopic',
            ),
          ],
        ],
      ),
    );
  }

  Widget _infoLine({required IconData icon, required String text}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 15, color: Colors.white.withValues(alpha: 0.9)),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.95),
              fontSize: 13,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildStatsGrid(Map<String, dynamic> stats) {
    final entries = [
      ('মোট', stats['total'], _ink),
      ('সম্পন্ন', stats['completed'], const Color(0xFF16A34A)),
      ('আংশিক', stats['partial'], const Color(0xFFD97706)),
      ('হয়নি', stats['not_done'], const Color(0xFFDC2626)),
      ('অনুপস্থিত', stats['absent'], const Color(0xFF64748B)),
    ];
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0F000000),
            blurRadius: 10,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: entries.map((e) {
          final (label, value, color) = e;
          return Expanded(
            child: Column(
              children: [
                Text(
                  '${value ?? 0}',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w800,
                    color: color,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  label,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 11, color: _muted),
                ),
              ],
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildStatusFilter() {
    return SizedBox(
      height: 36,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: _statusOptions.length,
        separatorBuilder: (_, _) => const SizedBox(width: 8),
        itemBuilder: (context, i) {
          final opt = _statusOptions[i];
          final selected = (_statusFilter ?? '') == opt['value'];
          return ChoiceChip(
            label: Text(opt['label']!),
            selected: selected,
            onSelected: (_) => setState(() => _statusFilter = opt['value']),
            selectedColor: _brand,
            backgroundColor: Colors.white,
            labelStyle: TextStyle(
              color: selected ? Colors.white : _ink,
              fontWeight: FontWeight.w600,
              fontSize: 12,
            ),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(20),
              side: BorderSide(color: selected ? _brand : Colors.grey.shade300),
            ),
            showCheckmark: false,
          );
        },
      ),
    );
  }

  Widget _buildStudentsSection() {
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.only(top: 40),
          child: Text('ত্রুটি: $_error'),
        ),
      );
    }
    if (_students.isEmpty) {
      final groups = _extractGroups();
      if (groups.isNotEmpty) {
        return Column(
          children: groups.map((g) {
            final title = '${g['class_name'] ?? ''} ${g['section_name'] ?? ''}'
                .trim();
            return Container(
              margin: const EdgeInsets.only(bottom: 10),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: const [
                  BoxShadow(
                    color: Color(0x0F000000),
                    blurRadius: 10,
                    offset: Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (title.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8.0, left: 4),
                      child: Text(
                        title,
                        style: const TextStyle(fontWeight: FontWeight.w700),
                      ),
                    ),
                  _buildStudentsList(
                    _normalizeList(g['students'] as List<dynamic>),
                  ),
                ],
              ),
            );
          }).toList(),
        );
      }
      return const Padding(
        padding: EdgeInsets.only(top: 40),
        child: Center(child: Text('কোনো শিক্ষার্থী পাওয়া যায়নি')),
      );
    }
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0F000000),
            blurRadius: 10,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: _buildStudentsList(_normalizedStudents()),
    );
  }

  Widget _buildStudentsList(List<Map<String, dynamic>> normalized) {
    final filtered = normalized.where((s) {
      final st = (s['status'] ?? '').toString();
      if (_statusFilter != null &&
          _statusFilter!.isNotEmpty &&
          !st.contains(_statusFilter!)) {
        return false;
      }
      return true;
    }).toList();

    if (filtered.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 32),
        child: Center(
          child: Text(
            'এই ফিল্টারে কোনো শিক্ষার্থী নেই',
            style: TextStyle(color: _muted),
          ),
        ),
      );
    }

    return ListView.separated(
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      padding: const EdgeInsets.symmetric(vertical: 4),
      itemCount: filtered.length,
      separatorBuilder: (_, _) => const Divider(height: 1, indent: 68),
      itemBuilder: (ctx, i) {
        final s = filtered[i];
        final st = (s['status'] ?? '').toString();
        final roll = (s['roll'] ?? '').toString();
        final photo = (s['photo'] ?? '') as String?;
        final statusLabel = _statusLabelLocalized(st);
        final statusColor = _statusColorFromRaw(st, context);

        Widget leading;
        if (photo != null && photo.isNotEmpty) {
          leading = ClipRRect(
            borderRadius: BorderRadius.circular(10),
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
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.person, color: Colors.grey, size: 20),
          );
        }

        return ListTile(
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 12,
            vertical: 4,
          ),
          leading: InkWell(
            onTap: () => _showStudentModal(s),
            child: leading,
          ),
          title: InkWell(
            onTap: () {
              final id = s['id'];
              if (id is int) {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => LessonEvaluationStudentProfilePage(
                      studentId: id,
                      studentName: (s['name'] ?? '').toString(),
                    ),
                  ),
                );
              } else {
                _showStudentModal(s);
              }
            },
            child: Text(
              (s['name'] ?? '').toString(),
              style: const TextStyle(fontWeight: FontWeight.w600, color: _ink),
            ),
          ),
          subtitle: roll.isEmpty
              ? null
              : Text(
                  'রোল: $roll',
                  style: const TextStyle(fontSize: 12, color: _muted),
                ),
          trailing: Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              statusLabel,
              style: TextStyle(
                color: statusColor,
                fontWeight: FontWeight.w700,
                fontSize: 11,
              ),
            ),
          ),
        );
      },
    );
  }
}
