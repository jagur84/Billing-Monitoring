import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../widgets/paginated_list_view.dart';

class InventoryScreen extends StatelessWidget {
  const InventoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final client = context.read<SessionProvider>().client;

    return PaginatedListView(
      client: client,
      path: '/inventory-items',
      emptyMessage: 'Belum ada barang inventaris.',
      itemBuilder: (context, item) {
        final qty = (item['stock_qty'] ?? 0) as num;
        final min = (item['min_stock'] ?? 0) as num;
        final low = qty <= min;
        return Card(
          margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: (low ? Colors.red : Colors.green).withValues(alpha: 0.15),
              child: Icon(Icons.inventory_2_outlined, color: low ? Colors.red : Colors.green),
            ),
            title: Text(item['name'] ?? '-'),
            subtitle: Text('${item['sku'] ?? ''} • ${item['category'] ?? ''}'),
            trailing: Text('$qty ${item['unit'] ?? ''}', style: TextStyle(fontWeight: FontWeight.bold, color: low ? Colors.red : null)),
          ),
        );
      },
    );
  }
}
