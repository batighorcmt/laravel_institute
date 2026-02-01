import 'package:flutter/material.dart';
import '../../../core/network/dio_client.dart';
import 'package:dio/dio.dart';

class LessonEvaluationApiTestPage extends StatefulWidget {
  const LessonEvaluationApiTestPage({super.key});

  @override
  State<LessonEvaluationApiTestPage> createState() =>
      _LessonEvaluationApiTestPageState();
}

class _LessonEvaluationApiTestPageState
    extends State<LessonEvaluationApiTestPage> {
  final _idCtrl = TextEditingController();
  String _output = '';
  bool _loading = false;

  Future<void> _fetchById() async {
    final id = _idCtrl.text.trim();
    if (id.isEmpty) return;
    setState(() {
      _loading = true;
      _output = '';
    });
    try {
      final dio = DioClient().dio;
      final resp = await dio.get('principal/reports/lesson-evaluations/$id');
      setState(() {
        _output = resp.data.toString();
      });
    } on DioException catch (e) {
      setState(() {
        _output =
            'Error: ${e.response?.statusCode} ${e.message} ${e.response?.data}';
      });
    } catch (e) {
      setState(() {
        _output = 'Error: $e';
      });
    } finally {
      setState(() {
        _loading = false;
      });
    }
  }

  Future<void> _fetchDetails() async {
    setState(() {
      _loading = true;
      _output = '';
    });
    try {
      final dio = DioClient().dio;
      final resp = await dio.get(
        'principal/reports/lesson-evaluations/details',
      );
      setState(() {
        _output = resp.data.toString();
      });
    } on DioException catch (e) {
      setState(() {
        _output =
            'Error: ${e.response?.statusCode} ${e.message} ${e.response?.data}';
      });
    } catch (e) {
      setState(() {
        _output = 'Error: $e';
      });
    } finally {
      setState(() {
        _loading = false;
      });
    }
  }

  @override
  void dispose() {
    _idCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lesson Evaluation API Test')),
      body: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextField(
              controller: _idCtrl,
              decoration: const InputDecoration(
                labelText: 'Evaluation ID',
                hintText: 'e.g. 6',
              ),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: _loading ? null : _fetchById,
                    child: _loading
                        ? const SizedBox(
                            height: 16,
                            width: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text('Fetch by ID'),
                  ),
                ),
                const SizedBox(width: 8),
                ElevatedButton(
                  onPressed: _loading ? null : _fetchDetails,
                  child: const Text('Fetch Details'),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Expanded(
              child: SingleChildScrollView(
                child: SelectableText(
                  _output.isEmpty ? 'No data yet' : _output,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
