import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/notice/notice_repository.dart';

final noticeRepositoryProvider = Provider((ref) => NoticeRepository());

// Fetching notices for current context (Principal/Teacher/Parent)
final noticesListProvider = FutureProvider<List<dynamic>>((ref) async {
  return ref.watch(noticeRepositoryProvider).getNotices();
});

// Notice Statistics (Principal)
final noticeStatsProvider = FutureProvider.family<Map<String, dynamic>, int>((ref, id) async {
  return ref.watch(noticeRepositoryProvider).getNoticeStats(id);
});

// Meta Data for Creation
final metaClassesProvider = FutureProvider<List<dynamic>>((ref) async {
  return ref.watch(noticeRepositoryProvider).getClasses();
});

final metaSectionsProvider = FutureProvider<List<dynamic>>((ref) async {
  return ref.watch(noticeRepositoryProvider).getSections();
});

final metaGroupsProvider = FutureProvider<List<dynamic>>((ref) async {
  return ref.watch(noticeRepositoryProvider).getGroups();
});

class StudentSearchNotifier extends Notifier<AsyncValue<List<dynamic>>> {
  @override
  AsyncValue<List<dynamic>> build() => const AsyncValue.data([]);

  Future<void> search(String query) async {
    if (query.length < 2) {
      state = const AsyncValue.data([]);
      return;
    }
    state = const AsyncValue.loading();
    try {
      final results = await ref.read(noticeRepositoryProvider).searchStudents(query);
      state = AsyncValue.data(results);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }
}

final studentSearchProvider = NotifierProvider<StudentSearchNotifier, AsyncValue<List<dynamic>>>(StudentSearchNotifier.new);
