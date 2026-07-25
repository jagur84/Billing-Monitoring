import 'package:flutter/material.dart';

import '../services/api_client.dart';

/// Generic infinite-scroll list backed by a Laravel `paginate()` JSON response
/// (`{data: [...], current_page, last_page}`). Handles loading, error, empty,
/// pull-to-refresh, and "load more on scroll" states so each resource screen
/// only needs to supply the endpoint path and an item builder.
class PaginatedListView extends StatefulWidget {
  final ApiClient client;
  final String path;
  final Map<String, dynamic>? extraQuery;
  final Widget Function(BuildContext context, Map<String, dynamic> item) itemBuilder;
  final String emptyMessage;

  const PaginatedListView({
    super.key,
    required this.client,
    required this.path,
    required this.itemBuilder,
    this.extraQuery,
    this.emptyMessage = 'Belum ada data.',
  });

  @override
  State<PaginatedListView> createState() => _PaginatedListViewState();
}

class _PaginatedListViewState extends State<PaginatedListView> {
  final List<Map<String, dynamic>> _items = [];
  final ScrollController _scrollController = ScrollController();
  int _currentPage = 1;
  int _lastPage = 1;
  bool _loading = false;
  bool _initialLoadDone = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _load(reset: true);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >
            _scrollController.position.maxScrollExtent - 200 &&
        !_loading &&
        _currentPage < _lastPage) {
      _load();
    }
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading) return;
    setState(() {
      _loading = true;
      _error = null;
      if (reset) {
        _currentPage = 1;
      }
    });

    try {
      final query = {
        'page': reset ? 1 : _currentPage + 1,
        ...?widget.extraQuery,
      };
      final result = await widget.client.get(widget.path, query: query);

      setState(() {
        if (reset) _items.clear();
        _items.addAll(List<Map<String, dynamic>>.from(result['data'] ?? []));
        _currentPage = result['current_page'] ?? 1;
        _lastPage = result['last_page'] ?? 1;
        _loading = false;
        _initialLoadDone = true;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
        _initialLoadDone = true;
      });
    } catch (_) {
      setState(() {
        _error = 'Gagal memuat data. Periksa koneksi Anda.';
        _loading = false;
        _initialLoadDone = true;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_initialLoadDone && _loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null && _items.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(_error!, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              OutlinedButton(
                onPressed: () => _load(reset: true),
                child: const Text('Coba Lagi'),
              ),
            ],
          ),
        ),
      );
    }

    if (_items.isEmpty) {
      return Center(child: Text(widget.emptyMessage));
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.builder(
        controller: _scrollController,
        itemCount: _items.length + (_currentPage < _lastPage ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _items.length) {
            return const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            );
          }
          return widget.itemBuilder(context, _items[index]);
        },
      ),
    );
  }
}
