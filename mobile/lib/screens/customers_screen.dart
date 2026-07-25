import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../widgets/paginated_list_view.dart';
import 'customer_detail_screen.dart';

class CustomersScreen extends StatefulWidget {
  const CustomersScreen({super.key});

  @override
  State<CustomersScreen> createState() => _CustomersScreenState();
}

class _CustomersScreenState extends State<CustomersScreen> {
  final _searchController = TextEditingController();
  String _search = '';
  Key _listKey = UniqueKey();

  @override
  Widget build(BuildContext context) {
    final client = context.read<SessionProvider>().client;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: TextField(
            controller: _searchController,
            decoration: InputDecoration(
              hintText: 'Cari nama, kode, atau nomor HP...',
              prefixIcon: const Icon(Icons.search),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
              isDense: true,
            ),
            onSubmitted: (value) => setState(() {
              _search = value;
              _listKey = UniqueKey();
            }),
          ),
        ),
        Expanded(
          child: PaginatedListView(
            key: _listKey,
            client: client,
            path: '/customers',
            extraQuery: _search.isNotEmpty ? {'search': _search} : null,
            emptyMessage: 'Tidak ada pelanggan ditemukan.',
            itemBuilder: (context, item) {
              final status = item['status'] ?? '-';
              return Card(
                margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: _statusColor(status).withValues(alpha: 0.15),
                    child: Icon(Icons.person_outline, color: _statusColor(status)),
                  ),
                  title: Text(item['name'] ?? '-'),
                  subtitle: Text('${item['customer_code'] ?? ''} • ${item['package']?['name'] ?? 'Tanpa paket'}'),
                  trailing: Chip(
                    label: Text(status, style: const TextStyle(fontSize: 11)),
                    backgroundColor: _statusColor(status).withValues(alpha: 0.15),
                    padding: EdgeInsets.zero,
                  ),
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => CustomerDetailScreen(customerId: item['id'])),
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
      case 'active':
        return Colors.green;
      case 'isolated':
        return Colors.orange;
      case 'inactive':
        return Colors.grey;
      default:
        return Colors.blueGrey;
    }
  }
}
