import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../services/formatters.dart';
import '../widgets/paginated_list_view.dart';
import 'invoice_detail_screen.dart';

class InvoicesScreen extends StatefulWidget {
  const InvoicesScreen({super.key});

  @override
  State<InvoicesScreen> createState() => _InvoicesScreenState();
}

class _InvoicesScreenState extends State<InvoicesScreen> {
  String? _status;
  Key _listKey = UniqueKey();

  static const _statuses = {
    null: 'Semua',
    'unpaid': 'Belum Bayar',
    'partial': 'Sebagian',
    'paid': 'Lunas',
    'overdue': 'Jatuh Tempo',
    'cancelled': 'Dibatalkan',
  };

  @override
  Widget build(BuildContext context) {
    final client = context.read<SessionProvider>().client;

    return Column(
      children: [
        SizedBox(
          height: 48,
          child: ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            children: _statuses.entries.map((entry) {
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
            }).toList(),
          ),
        ),
        Expanded(
          child: PaginatedListView(
            key: _listKey,
            client: client,
            path: '/invoices',
            extraQuery: _status != null ? {'status': _status} : null,
            emptyMessage: 'Tidak ada tagihan.',
            itemBuilder: (context, item) {
              final status = item['status'] ?? '-';
              return Card(
                margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: _statusColor(status).withValues(alpha: 0.15),
                    child: Icon(Icons.receipt_long_outlined, color: _statusColor(status)),
                  ),
                  title: Text(item['customer']?['name'] ?? '-'),
                  subtitle: Text('${item['invoice_number']} • jatuh tempo ${formatDate(item['due_date'])}'),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(formatRupiah(item['total_amount']), style: const TextStyle(fontWeight: FontWeight.bold)),
                      Text(status, style: TextStyle(fontSize: 11, color: _statusColor(status))),
                    ],
                  ),
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => InvoiceDetailScreen(invoiceId: item['id'])),
                  ),
                ),
              );
            },
          ),
        ),
      ],
    );
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'paid':
        return Colors.green;
      case 'partial':
        return Colors.blue;
      case 'overdue':
        return Colors.red;
      case 'cancelled':
        return Colors.grey;
      default:
        return Colors.orange;
    }
  }
}
