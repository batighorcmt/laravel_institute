import 'package:dio/dio.dart';
import 'dart:convert';

class TeacherStudentsRepository {
  final Dio _dio;
  TeacherStudentsRepository(this._dio);
  List<Map<String, dynamic>>? _classMetaCache;
  Map<String, dynamic>?
  _classScopedCache; // key: classId -> {sections, groups, genders}

  Future<Map<String, dynamic>> fetchStudents({
    int page = 1,
    String? search,
    String? classId,
    String? sectionId,
    String? groupId,
    String? gender,
  }) async {
    final params = <String, dynamic>{'page': page};
    if (search != null && search.isNotEmpty) params['search'] = search;
    if (classId != null && classId.isNotEmpty) params['class_id'] = classId;
    if (sectionId != null && sectionId.isNotEmpty)
      params['section_id'] = sectionId;
    if (groupId != null && groupId.isNotEmpty) params['group_id'] = groupId;
    if (gender != null && gender.isNotEmpty) params['gender'] = gender;

    try {
      // Use relative path so DioClient base `/api/v1` isn't duplicated
      final res = await _dio.get('teacher/students', queryParameters: params);
      final data = res.data as Map<String, dynamic>;
      assert(() {
        try {
          // Pretty-print a small snapshot in debug mode
          final preview = {
            'meta': data['meta'],
            'data_sample':
                (data['data'] is List && (data['data'] as List).isNotEmpty)
                ? (data['data'] as List).first
                : null,
          };
          // ignore: avoid_print
          print(
            '[API teacher/students] =>\n${const JsonEncoder.withIndent('  ').convert(preview)}',
          );
        } catch (_) {}
        return true;
      }());
      return data;
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      final body = e.response?.data;
      throw Exception(
        'Students load failed (${status ?? 'n/a'}): ${body is Map ? body['message'] ?? body.toString() : body?.toString() ?? e.message}',
      );
    }
  }

  Future<Map<String, dynamic>> fetchStudentProfile(String studentId) async {
    try {
      final res = await _dio.get('teacher/students/$studentId');
      final data = res.data as Map<String, dynamic>;
      assert(() {
        try {
          // ignore: avoid_print
          print(
            '[API teacher/students/$studentId] =>\n${const JsonEncoder.withIndent('  ').convert(data)}',
          );
        } catch (_) {}
        return true;
      }());
      return data;
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      final body = e.response?.data;
      throw Exception(
        'Profile load failed (${status ?? 'n/a'}): ${body is Map ? body['message'] ?? body.toString() : body?.toString() ?? e.message}',
      );
    }
  }

  Future<void> _ensureClassMeta() async {
    if (_classMetaCache != null) return;
    try {
      final res = await _dio.get('teacher/students/meta');
      final data = res.data;
      final list = data is Map<String, dynamic> ? data['classes'] : null;
      if (list is List) {
        _classMetaCache = list.cast<Map<String, dynamic>>();
      } else {
        _classMetaCache = [];
      }
    } on DioException catch (e) {
      // If the teacher meta endpoint is forbidden (e.g., principal user),
      // try the principal classes endpoint which reads from DB.
      final status = e.response?.statusCode;
      if (status == 403) {
        try {
          final res = await _dio.get('principal/students/filters/classes');
          final data = res.data;
          List<Map<String, dynamic>> rows = [];
          if (data is List) {
            rows = data.cast<Map<String, dynamic>>();
          } else if (data is Map<String, dynamic> && data['classes'] is List) {
            rows = (data['classes'] as List).cast<Map<String, dynamic>>();
          }
          _classMetaCache = rows
              .map(
                (m) => {
                  'id': (m['id'])?.toString(),
                  'name': (m['name'])?.toString() ?? 'Class',
                  'numeric_value': m['numeric_value'],
                },
              )
              .where((m) => (m['id']?.isNotEmpty ?? false))
              .cast<Map<String, dynamic>>()
              .toList();

          // If the teacher-scoped meta returned too few classes (sometimes the
          // API only returns classes the teacher is assigned to), try the
          // principal DB-backed endpoint which returns all classes for the school.
          if ((_classMetaCache ?? []).length <= 1) {
            try {
              final res = await _dio.get('principal/students/filters/classes');
              final data = res.data;
              List<Map<String, dynamic>> rows = [];
              if (data is List) {
                rows = data.cast<Map<String, dynamic>>();
              } else if (data is Map<String, dynamic> &&
                  data['classes'] is List) {
                rows = (data['classes'] as List).cast<Map<String, dynamic>>();
              }
              final fromPrincipal = rows
                  .map(
                    (m) => {
                      'id': (m['id'])?.toString(),
                      'name': (m['name'])?.toString() ?? 'Class',
                      'numeric_value': m['numeric_value'],
                    },
                  )
                  .where((m) => (m['id']?.isNotEmpty ?? false))
                  .cast<Map<String, dynamic>>()
                  .toList();
              if (fromPrincipal.isNotEmpty) {
                _classMetaCache = fromPrincipal;
              }
            } catch (_) {
              // ignore
            }
          }
        } catch (_) {
          _classMetaCache = [];
        }
      } else {
        _classMetaCache = [];
      }
    } catch (_) {
      _classMetaCache = [];
    }

    // Do not fallback to attendance meta here — prefer the teacher meta
    // endpoint which reads classes from DB. If that endpoint is forbidden
    // for principals, we try the principal DB-backed endpoint in the
    // DioException handler above.
  }

  Future<List<Map<String, dynamic>>> fetchClasses() async {
    await _ensureClassMeta();
    final meta = _classMetaCache ?? [];
    // Deduplicate by class_id
    final seen = <String>{};
    final out = <Map<String, dynamic>>[];
    for (final item in meta) {
      final cid = (item['id'])?.toString();
      final cname = (item['name'])?.toString();
      if (cid != null && cid.isNotEmpty && !seen.contains(cid)) {
        seen.add(cid);
        out.add({'id': cid, 'name': cname ?? 'Class'});
      }
    }
    // Order by numeric_value when provided, else by name
    out.sort((a, b) {
      final ai = meta.firstWhere(
        (m) => m['id'].toString() == a['id'],
        orElse: () => const {},
      );
      final bi = meta.firstWhere(
        (m) => m['id'].toString() == b['id'],
        orElse: () => const {},
      );
      final an = (ai['numeric_value'] as num?)?.toInt();
      final bn = (bi['numeric_value'] as num?)?.toInt();
      if (an != null && bn != null && an != bn) return an.compareTo(bn);
      return (a['name'] as String).compareTo(b['name'] as String);
    });
    return out;
  }

  Future<List<Map<String, dynamic>>> fetchSections({String? classId}) async {
    if (classId == null || classId.isEmpty) return [];
    _classScopedCache ??= {};
    if (_classScopedCache![classId] == null) {
      try {
        final res = await _dio.get(
          'teacher/students/meta',
          queryParameters: {'class_id': classId},
        );
        _classScopedCache![classId] = res.data is Map<String, dynamic>
            ? res.data
            : {};
      } on DioException catch (e) {
        final status = e.response?.statusCode;
        if (status == 403) {
          // Principal user: call principal sections/groups endpoints
          try {
            final resSec = await _dio.get(
              'principal/students/filters/sections',
              queryParameters: {'class_id': classId},
            );
            final resGrp = await _dio.get(
              'principal/students/filters/groups',
              queryParameters: {'class_id': classId},
            );
            final sections = resSec.data is List ? resSec.data : [];
            final groups = resGrp.data is List ? resGrp.data : [];
            _classScopedCache![classId] = {
              'sections': sections,
              'groups': groups,
              'genders': [],
            };
          } catch (_) {
            _classScopedCache![classId] = {};
          }
        } else {
          // Fallback: try attendance meta and extract sections for the class
          try {
            final res2 = await _dio.get(
              'teacher/students-attendance/class/meta',
            );
            final data2 = res2.data;
            final list = data2 is List
                ? data2.cast<Map<String, dynamic>>()
                : (data2 is Map<String, dynamic> && data2['data'] is List)
                ? (data2['data'] as List).cast<Map<String, dynamic>>()
                : <Map<String, dynamic>>[];
            final match = list.firstWhere(
              (m) =>
                  (m['class_id']?.toString() ?? m['id']?.toString()) == classId,
              orElse: () => const {},
            );
            _classScopedCache![classId] = {
              'sections': (match['sections'] as List?) ?? [],
              'groups': [],
              'genders': [],
            };
          } catch (_) {
            _classScopedCache![classId] = {};
          }
        }
      } catch (_) {
        _classScopedCache![classId] = {};
      }
    }
    final sections = ((_classScopedCache![classId]['sections']) as List? ?? [])
        .cast<Map<String, dynamic>>()
        .map((e) => {'id': e['id']?.toString(), 'name': e['name']?.toString()})
        .toList();
    sections.sort(
      (a, b) => (a['name'] as String).compareTo(b['name'] as String),
    );
    return sections;
  }

  Future<List<Map<String, dynamic>>> fetchGroups() async {
    // When a class is selected we call fetchGroupsForClass instead
    return [];
  }

  Future<List<Map<String, dynamic>>> fetchGroupsForClass(String classId) async {
    if (classId.isEmpty) return [];
    _classScopedCache ??= {};
    if (_classScopedCache![classId] == null) {
      try {
        final res = await _dio.get(
          'teacher/students/meta',
          queryParameters: {'class_id': classId},
        );
        _classScopedCache![classId] = res.data is Map<String, dynamic>
            ? res.data
            : {};
      } catch (_) {
        _classScopedCache![classId] = {};
      }
    }
    final groups = ((_classScopedCache![classId]['groups']) as List? ?? [])
        .cast<Map<String, dynamic>>()
        .map((e) => {'id': e['id']?.toString(), 'name': e['name']?.toString()})
        .toList();
    groups.sort((a, b) => (a['name'] as String).compareTo(b['name'] as String));
    return groups;
  }

  Future<List<String>> fetchGendersForClass(String classId) async {
    if (classId.isEmpty) return [];
    _classScopedCache ??= {};
    if (_classScopedCache![classId] == null) {
      try {
        final res = await _dio.get(
          'teacher/students/meta',
          queryParameters: {'class_id': classId},
        );
        _classScopedCache![classId] = res.data is Map<String, dynamic>
            ? res.data
            : {};
      } catch (_) {
        _classScopedCache![classId] = {};
      }
    }
    final genders = ((_classScopedCache![classId]['genders']) as List? ?? [])
        .map((e) => e?.toString() ?? '')
        .where((e) => e.isNotEmpty)
        .toList();
    genders.sort();
    return genders;
  }
}
