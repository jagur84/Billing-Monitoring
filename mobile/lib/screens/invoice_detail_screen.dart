import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../services/api_client.dart';
import '../services/formatters.dart';

class InvoiceDetailScreen extends StatefulWidget {
  final int invoiceId;

  const InvoiceDetailScreen({super.key, required this.invoiceId});

  @override
  State<InvoiceDetailScreen> createState() => _InvoiceDetailScreenState();
}

class _InvoiceDetailScreenState extends State<InvoiceDetailScreen> {
  Map<String, dynamic>? _invoice;
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
      final result = await client.get('/invoices/${widget.invoiceId}');
      setState(() {
        _invoice = result;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (_) {
      setState(() {
        _error = 'Gagal memuat data tagihan.';
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(_invoice?['invoice_number'] ?? 'Detail Tagihan')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : _buildBody(),
    );
  }

  Widget _buildBody() {
    final inv = _invoice!;
    final payments = List<Map<String, dynamic>>.from(inv['payments'] ?? []);

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _infoTile('Pelanggan', inv['customer']?['name']),
          _infoTile('Paket', inv['package_name_snapshot']),
          _infoTile('Periode', '${inv['period_month']}/${inv['period_year']}'),
          _infoTile('Jatuh Tempo', formatDate(inv['due_date'])),
          _infoTile('Status', inv['status']),
          const Divider(height: 32),
          _infoTile('Jumlah', formatRupiah(inv['amount'])),
          _infoTile('Pajak', formatRupiah(inv['tax_amount'])),
          _infoTile('Diskon', formatRupiah(inv['discount_amount'])),
          _infoTile('Total Tagihan', formatRupiah(inv['total_amount'])),
          _infoTile('Sudah Dibayar', formatRupiah(inv['total_paid'])),
          _infoTile('Sisa Pembayaran', formatRupiah(inv['remaining_amount'])),
          const SizedBox(height: 20),
          const Text('Riwayat Pembayaran', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 8),
          if (payments.isEmpty)
            const Text('Belum ada pembayaran.', style: TextStyle(color: Colors.grey))
          else
            ...payments.map((p) => Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    leading: Icon(
                      p['status'] == 'paid' ? Icons.check_circle_outline : Icons.hourglass_empty,
                      color: p['status'] == 'paid' ? Colors.green : Colors.orange,
                    ),
                    title: Text(formatRupiah(p['amount'])),
                    subtitle: Text('${p['gateway_payment_method'] ?? p['gateway'] ?? '-'} • ${formatDate(p['paid_at'])}'),
                    trailing: Text(p['status'] ?? ''),
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
