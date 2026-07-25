import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../services/formatters.dart';
import '../widgets/paginated_list_view.dart';

class PaymentsScreen extends StatelessWidget {
  const PaymentsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final client = context.read<SessionProvider>().client;

    return PaginatedListView(
      client: client,
      path: '/payments',
      emptyMessage: 'Belum ada pembayaran.',
      itemBuilder: (context, item) {
        final invoice = item['invoice'];
        final status = item['status'] ?? '-';
        return Card(
          margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          child: ListTile(
            leading: Icon(
              status == 'paid' ? Icons.check_circle_outline : Icons.hourglass_empty,
              color: status == 'paid' ? Colors.green : Colors.orange,
            ),
            title: Text(invoice?['customer']?['name'] ?? '-'),
            subtitle: Text('${invoice?['invoice_number'] ?? ''} • ${item['gateway_payment_method'] ?? item['gateway'] ?? '-'}'),
            trailing: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(formatRupiah(item['amount']), style: const TextStyle(fontWeight: FontWeight.bold)),
                Text(formatDate(item['paid_at']), style: const TextStyle(fontSize: 11, color: Colors.grey)),
              ],
            ),
          ),
        );
      },
    );
  }
}
