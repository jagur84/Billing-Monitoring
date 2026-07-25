import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import '../services/api_client.dart';
import '../services/formatters.dart';
import '../widgets/stat_card.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic>? _payload;
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
      final result = await client.get('/dashboard');
      setState(() {
        _payload = result;
        _loading = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (_) {
      setState(() {
        _error = 'Gagal memuat dashboard. Periksa koneksi Anda.';
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(_error!, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              OutlinedButton(onPressed: _load, child: const Text('Coba Lagi')),
            ],
          ),
        ),
      );
    }

    final type = _payload?['type'] as String? ?? 'admin';
    final data = Map<String, dynamic>.from(_payload?['data'] ?? {});

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (type == 'technician') ..._technicianWidgets(data),
          if (type == 'finance') ..._financialWidgets(data),
          if (type == 'admin') ...[
            ..._financialWidgets(data),
            const SizedBox(height: 16),
            ..._customerWidgets(data),
          ],
        ],
      ),
    );
  }

  List<Widget> _financialWidgets(Map<String, dynamic> data) {
    return [
      GridView.count(
        crossAxisCount: 2,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 2.0,
        children: [
          StatCard(
            label: 'Pendapatan Bulan Ini',
            value: formatRupiah(data['monthlyRevenue']),
            icon: Icons.trending_up,
            color: Colors.teal,
          ),
          StatCard(
            label: 'Pengeluaran Bulan Ini',
            value: formatRupiah(data['monthlyExpense']),
            icon: Icons.trending_down,
            color: Colors.red,
          ),
          StatCard(
            label: 'Saldo Kas',
            value: formatRupiah(data['cashBalance']),
            icon: Icons.account_balance_wallet_outlined,
            color: Colors.indigo,
          ),
          StatCard(
            label: 'Total Tunggakan',
            value: formatRupiah(data['totalArrears']),
            icon: Icons.warning_amber_outlined,
            color: Colors.orange,
          ),
          StatCard(
            label: 'Sudah Bayar Bulan Ini',
            value: '${data['paidThisMonthCount'] ?? 0}',
            icon: Icons.check_circle_outline,
            color: Colors.green,
          ),
          StatCard(
            label: 'Belum Bayar Bulan Ini',
            value: '${data['unpaidThisMonthCount'] ?? 0}',
            icon: Icons.pending_outlined,
            color: Colors.amber,
          ),
        ],
      ),
      const SizedBox(height: 20),
      const Text('10 Pembayaran Terakhir', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      ..._recentPayments(data),
      const SizedBox(height: 20),
      const Text('Tagihan Belum Dibayar', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      ..._unpaidInvoices(data),
    ];
  }

  List<Widget> _recentPayments(Map<String, dynamic> data) {
    final payments = List<Map<String, dynamic>>.from(data['recentPayments'] ?? []);
    if (payments.isEmpty) {
      return [const Text('Belum ada pembayaran.', style: TextStyle(color: Colors.grey))];
    }
    return payments.map((p) {
      final invoice = p['invoice'];
      final customerName = invoice?['customer']?['name'] ?? '-';
      return Card(
        margin: const EdgeInsets.only(bottom: 8),
        child: ListTile(
          leading: const Icon(Icons.payments_outlined, color: Colors.green),
          title: Text(customerName),
          subtitle: Text(invoice?['invoice_number'] ?? ''),
          trailing: Text(formatRupiah(p['amount']), style: const TextStyle(fontWeight: FontWeight.bold)),
        ),
      );
    }).toList();
  }

  List<Widget> _unpaidInvoices(Map<String, dynamic> data) {
    final invoices = List<Map<String, dynamic>>.from(data['unpaidInvoices'] ?? []);
    if (invoices.isEmpty) {
      return [const Text('Tidak ada tagihan yang belum dibayar.', style: TextStyle(color: Colors.grey))];
    }
    return invoices.map((inv) {
      final customerName = inv['customer']?['name'] ?? '-';
      return Card(
        margin: const EdgeInsets.only(bottom: 8),
        child: ListTile(
          leading: const Icon(Icons.receipt_long_outlined, color: Colors.orange),
          title: Text(customerName),
          subtitle: Text('${inv['invoice_number']} • jatuh tempo ${formatDate(inv['due_date'])}'),
          trailing: Text(formatRupiah(inv['remaining_amount']), style: const TextStyle(fontWeight: FontWeight.bold)),
        ),
      );
    }).toList();
  }

  List<Widget> _customerWidgets(Map<String, dynamic> data) {
    return [
      const Text('Pelanggan', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      GridView.count(
        crossAxisCount: 2,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 2.0,
        children: [
          StatCard(
            label: 'Total Pelanggan',
            value: '${data['totalCustomers'] ?? 0}',
            icon: Icons.people_outline,
            color: Colors.indigo,
          ),
          StatCard(
            label: 'Pelanggan Aktif',
            value: '${data['activeCustomers'] ?? 0}',
            icon: Icons.person_outline,
            color: Colors.green,
          ),
          StatCard(
            label: 'Pelanggan Baru Bulan Ini',
            value: '${data['newCustomersThisMonth'] ?? 0}',
            icon: Icons.person_add_alt_outlined,
            color: Colors.blue,
          ),
          StatCard(
            label: 'Tagihan Overdue',
            value: '${data['overdueInvoices'] ?? 0}',
            icon: Icons.error_outline,
            color: Colors.red,
          ),
        ],
      ),
    ];
  }

  List<Widget> _technicianWidgets(Map<String, dynamic> data) {
    final tickets = List<Map<String, dynamic>>.from(data['myTickets'] ?? []);
    final lowStock = List<Map<String, dynamic>>.from(data['lowStockItems'] ?? []);

    return [
      GridView.count(
        crossAxisCount: 2,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 2.0,
        children: [
          StatCard(
            label: 'Tiket Saya Terbuka',
            value: '${data['myOpenCount'] ?? 0}',
            icon: Icons.assignment_outlined,
            color: Colors.indigo,
          ),
          StatCard(
            label: 'Prioritas Tinggi',
            value: '${data['myHighPriorityCount'] ?? 0}',
            icon: Icons.priority_high,
            color: Colors.red,
          ),
          StatCard(
            label: 'Selesai Bulan Ini',
            value: '${data['resolvedThisMonth'] ?? 0}',
            icon: Icons.check_circle_outline,
            color: Colors.green,
          ),
          StatCard(
            label: 'Total Tiket Terbuka',
            value: '${data['openTicketsTotal'] ?? 0}',
            icon: Icons.confirmation_number_outlined,
            color: Colors.orange,
          ),
        ],
      ),
      const SizedBox(height: 20),
      const Text('Tiket Saya', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      if (tickets.isEmpty)
        const Text('Tidak ada tiket yang ditugaskan.', style: TextStyle(color: Colors.grey))
      else
        ...tickets.map((t) => Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                leading: Icon(Icons.build_outlined,
                    color: t['priority'] == 'high' ? Colors.red : Colors.indigo),
                title: Text(t['subject'] ?? ''),
                subtitle: Text(t['customer']?['name'] ?? '-'),
                trailing: Text(t['priority'] ?? ''),
              ),
            )),
      const SizedBox(height: 20),
      const Text('Stok Menipis', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      const SizedBox(height: 8),
      if (lowStock.isEmpty)
        const Text('Tidak ada stok yang menipis.', style: TextStyle(color: Colors.grey))
      else
        ...lowStock.map((item) => Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                leading: const Icon(Icons.inventory_2_outlined, color: Colors.orange),
                title: Text(item['name'] ?? ''),
                trailing: Text('${item['stock_qty']} / min ${item['min_stock']}'),
              ),
            )),
    ];
  }
}
