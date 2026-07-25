import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../services/formatters.dart';
import '../widgets/paginated_list_view.dart';

class ExpensesScreen extends StatelessWidget {
  const ExpensesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final client = context.read<SessionProvider>().client;

    return PaginatedListView(
      client: client,
      path: '/expenses',
      emptyMessage: 'Belum ada pengeluaran.',
      itemBuilder: (context, item) {
        return Card(
          margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          child: ListTile(
            leading: const CircleAvatar(
              backgroundColor: Color(0x1AF44336),
              child: Icon(Icons.receipt_outlined, color: Colors.red),
            ),
            title: Text(item['description'] ?? item['category'] ?? '-'),
            subtitle: Text('${item['category'] ?? ''} • ${formatDate(item['expense_date'])}'),
            trailing: Text(formatRupiah(item['amount']), style: const TextStyle(fontWeight: FontWeight.bold)),
          ),
        );
      },
    );
  }
}
