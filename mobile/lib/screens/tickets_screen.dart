import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../widgets/paginated_list_view.dart';
import 'ticket_detail_screen.dart';

class TicketsScreen extends StatefulWidget {
  const TicketsScreen({super.key});

  @override
  State<TicketsScreen> createState() => _TicketsScreenState();
}

class _TicketsScreenState extends State<TicketsScreen> {
  String? _status;
  bool _mineOnly = false;
  Key _listKey = UniqueKey();

  static const _statuses = {
    null: 'Semua',
    'open': 'Terbuka',
    'in_progress': 'Diproses',
    'resolved': 'Selesai',
    'closed': 'Ditutup',
  };

  @override
  Widget build(BuildContext context) {
    final session = context.read<SessionProvider>();
    final client = session.client;
    final isTechnician = session.user?.roles.contains('technician') == true;

    return Column(
      children: [
        SizedBox(
          height: 48,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            children: [
              if (isTechnician)
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: const Text('Tiket Saya'),
                    selected: _mineOnly,
                    onSelected: (selected) => setState(() {
                      _mineOnly = selected;
                      _listKey = UniqueKey();
                    }),
                  ),
                ),
              ..._statuses.entries.map((entry) {
                final selected = _status == entry.key;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(entry.value),
                    selected: selected,
                    onSelected: (_) => setState(() {
                      _status = entry.key;
                      _listKey = UniqueKey();
                    }),
                  ),
                );
              }),
            ],
          ),
        ),
        Expanded(
          child: PaginatedListView(
            key: _listKey,
            client: client,
            path: '/tickets',
            extraQuery: {
              if (_status != null) 'status': _status,
              if (_mineOnly) 'mine': '1',
            },
            emptyMessage: 'Tidak ada tiket.',
            itemBuilder: (context, item) {
              final priority = item['priority'] ?? '-';
              return Card(
                margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: _priorityColor(priority).withValues(alpha: 0.15),
                    child: Icon(Icons.confirmation_number_outlined, color: _priorityColor(priority)),
                  ),
                  title: Text(item['subject'] ?? '-'),
                  subtitle: Text('${item['ticket_number']} • ${item['customer']?['name'] ?? '-'}'),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(priority, style: TextStyle(color: _priorityColor(priority), fontSize: 12)),
                      Text(item['status'] ?? '', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                    ],
                  ),
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => TicketDetailScreen(ticketId: item['id'])),
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Color _priorityColor(String priority) {
    switch (priority) {
      case 'high':
        return Colors.red;
      case 'medium':
        return Colors.orange;
      default:
        return Colors.blueGrey;
    }
  }
}
