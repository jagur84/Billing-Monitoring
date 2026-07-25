import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/session_provider.dart';
import 'customers_screen.dart';
import 'dashboard_screen.dart';
import 'expenses_screen.dart';
import 'inventory_screen.dart';
import 'invoices_screen.dart';
import 'packages_screen.dart';
import 'payments_screen.dart';
import 'reports_screen.dart';
import 'tickets_screen.dart';

class _MenuItem {
  final String key;
  final String label;
  final IconData icon;
  final String? permission;
  final Widget Function() builder;

  const _MenuItem({
    required this.key,
    required this.label,
    required this.icon,
    required this.builder,
    this.permission,
  });
}

class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  String _activeKey = 'dashboard';

  static final List<_MenuItem> _allItems = [
    _MenuItem(key: 'dashboard', label: 'Dashboard', icon: Icons.dashboard_outlined, builder: () => const DashboardScreen()),
    _MenuItem(key: 'customers', label: 'Pelanggan', icon: Icons.people_outline, permission: 'customers', builder: () => const CustomersScreen()),
    _MenuItem(key: 'invoices', label: 'Tagihan', icon: Icons.receipt_long_outlined, permission: 'invoices', builder: () => const InvoicesScreen()),
    _MenuItem(key: 'payments', label: 'Pembayaran', icon: Icons.payments_outlined, permission: 'payments', builder: () => const PaymentsScreen()),
    _MenuItem(key: 'tickets', label: 'Tiket', icon: Icons.confirmation_number_outlined, permission: 'tickets', builder: () => const TicketsScreen()),
    _MenuItem(key: 'packages', label: 'Paket', icon: Icons.wifi, permission: 'packages', builder: () => const PackagesScreen()),
    _MenuItem(key: 'inventory', label: 'Inventaris', icon: Icons.inventory_2_outlined, permission: 'inventory', builder: () => const InventoryScreen()),
    _MenuItem(key: 'expenses', label: 'Pengeluaran & Operasional', icon: Icons.money_off, permission: 'expenses', builder: () => const ExpensesScreen()),
    _MenuItem(key: 'reports', label: 'Laporan Pendapatan', icon: Icons.bar_chart_outlined, permission: 'reports', builder: () => const ReportsScreen()),
  ];

  List<_MenuItem> _visibleItems(SessionProvider session) {
    final user = session.user;
    return _allItems.where((item) {
      if (item.permission == null) return true;
      if (user == null) return false;
      return user.isSuperAdmin || user.can(item.permission!);
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionProvider>();
    final items = _visibleItems(session);
    final active = items.firstWhere((i) => i.key == _activeKey, orElse: () => items.first);

    return Scaffold(
      appBar: AppBar(title: Text(active.label)),
      drawer: Drawer(
        child: SafeArea(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              DrawerHeader(
                decoration: const BoxDecoration(color: Colors.indigo),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    const Icon(Icons.account_circle, size: 48, color: Colors.white),
                    const SizedBox(height: 8),
                    Text(session.user?.name ?? '', style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                    Text(session.user?.email ?? '', style: const TextStyle(color: Colors.white70, fontSize: 12)),
                  ],
                ),
              ),
              Expanded(
                child: ListView(
                  padding: EdgeInsets.zero,
                  children: items.map((item) {
                    return ListTile(
                      leading: Icon(item.icon),
                      title: Text(item.label),
                      selected: item.key == active.key,
                      onTap: () {
                        setState(() => _activeKey = item.key);
                        Navigator.of(context).pop();
                      },
                    );
                  }).toList(),
                ),
              ),
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.logout, color: Colors.red),
                title: const Text('Keluar', style: TextStyle(color: Colors.red)),
                onTap: () => session.logout(),
              ),
            ],
          ),
        ),
      ),
      body: active.builder(),
    );
  }
}
