import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../services/api_client.dart';
import '../services/formatters.dart';

class CustomerDetailScreen extends StatefulWidget {
  final int customerId;

  const CustomerDetailScreen({super.key, required this.customerId});

  @override
  State<CustomerDetailScreen> createState() => _CustomerDetailScreenState();
}

class _CustomerDetailScreenState extends State<CustomerDetailScreen> {
  Map<String, dynamic>? _customer;
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
      final result = await client.get('/customers/${widget.customerId}');
      setState(() {
        _customer = result;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (_) {
      setState(() {
        _error = 'Gagal memuat data pelanggan.';
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_customer?['name'] ?? 'Detail Pelanggan')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : _buildBody(),
    );
  }

  Widget _buildBody() {
    final c = _customer!;
    final invoices = List<Map<String, dynamic>>.from(c['invoices'] ?? []);

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _infoTile('Kode Pelanggan', c['customer_code']),
          _infoTile('Email', c['email']),
          _infoTile('Telepon', c['phone']),
          _infoTile('Alamat', c['address']),
          _infoTile('Paket', c['package']?['name']),
          _infoTile('Username PPPoE', c['pppoe_username']),
          _infoTile('IP Address', c['ip_address']),
          _infoTile('Status', c['status']),
          _infoTile('Tanggal Instalasi', formatDate(c['installation_date'])),
          const SizedBox(height: 20),
          const Text('Tagihan Terakhir', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 8),
          if (invoices.isEmpty)
            const Text('Belum ada tagihan.', style: TextStyle(color: Colors.grey))
          else
            ...invoices.map((inv) => Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    title: Text(inv['invoice_number'] ?? ''),
                    subtitle: Text('Periode ${inv['period_month']}/${inv['period_year']} • ${inv['status']}'),
                    trailing: Text(formatRupiah(inv['total_amount'])),
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
