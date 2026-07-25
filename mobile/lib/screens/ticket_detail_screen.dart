import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../services/api_client.dart';
import '../services/formatters.dart';

class TicketDetailScreen extends StatefulWidget {
  final int ticketId;

  const TicketDetailScreen({super.key, required this.ticketId});

  @override
  State<TicketDetailScreen> createState() => _TicketDetailScreenState();
}

class _TicketDetailScreenState extends State<TicketDetailScreen> {
  Map<String, dynamic>? _ticket;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final client = context.read<SessionProvider>().client;
      final result = await client.get('/tickets/${widget.ticketId}');
      setState(() {
        _ticket = result;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (_) {
      setState(() {
        _error = 'Gagal memuat data tiket.';
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_ticket?['ticket_number'] ?? 'Detail Tiket')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : _buildBody(),
    );
  }

  Widget _buildBody() {
    final t = _ticket!;
    final logs = List<Map<String, dynamic>>.from(t['logs'] ?? []);

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(t['subject'] ?? '', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          _infoTile('Pelanggan', t['customer']?['name']),
          _infoTile('Kategori', t['category']),
          _infoTile('Prioritas', t['priority']),
          _infoTile('Status', t['status']),
          _infoTile('Ditugaskan Ke', t['assignee']?['name']),
          _infoTile('Selesai Pada', formatDate(t['resolved_at'])),
          const Divider(height: 32),
          const Text('Deskripsi', style: TextStyle(fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text(t['description'] ?? '-'),
          const SizedBox(height: 20),
          const Text('Log Aktivitas', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 8),
          if (logs.isEmpty)
            const Text('Belum ada log.', style: TextStyle(color: Colors.grey))
          else
            ...logs.map((log) => Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    title: Text(log['note'] ?? log['message'] ?? '-'),
                    subtitle: Text(formatDate(log['created_at'])),
                  ),
                )),
        ],
      ),
    );
  }

  Widget _infoTile(String label, dynamic value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(label, style: const TextStyle(color: Colors.grey)),
          ),
          Expanded(child: Text((value ?? '-').toString(), style: const TextStyle(fontWeight: FontWeight.w500))),
        ],
      ),
    );
  }
}
