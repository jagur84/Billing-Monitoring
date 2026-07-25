import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../services/formatters.dart';
import '../widgets/paginated_list_view.dart';

class PackagesScreen extends StatelessWidget {
  const PackagesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final client = context.read<SessionProvider>().client;

    return PaginatedListView(
      client: client,
      path: '/packages',
      emptyMessage: 'Belum ada paket.',
      itemBuilder: (context, item) {
        final active = item['is_active'] == true;
        return Card(
          margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: (active ? Colors.green : Colors.grey).withValues(alpha: 0.15),
              child: Icon(Icons.wifi, color: active ? Colors.green : Colors.grey),
            ),
            title: Text(item['name'] ?? '-'),
            subtitle: Text('${item['speed_mbps']} Mbps'),
            trailing: Text(formatRupiah(item['price']), style: const TextStyle(fontWeight: FontWeight.bold)),
          ),
        );
      },
    );
  }
}
