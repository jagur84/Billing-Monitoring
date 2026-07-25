import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';

import 'providers/session_provider.dart';
import 'screens/home_shell.dart';
import 'screens/login_screen.dart';
import 'screens/server_setup_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID', null);
  runApp(const ModalNekadApp());
}

class ModalNekadApp extends StatelessWidget {
  const ModalNekadApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => SessionProvider()..bootstrap(),
      child: MaterialApp(
        title: 'Modal Nekad',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: Colors.indigo),
          useMaterial3: true,
        ),
        home: const _RootRouter(),
      ),
    );
  }
}

class _RootRouter extends StatelessWidget {
  const _RootRouter();

  @override
  Widget build(BuildContext context) {
    final session = context.watch<SessionProvider>();

    switch (session.status) {
      case SessionStatus.loading:
        return const Scaffold(body: Center(child: CircularProgressIndicator()));
      case SessionStatus.needsBaseUrl:
        return const ServerSetupScreen();
      case SessionStatus.needsLogin:
        return const LoginScreen();
      case SessionStatus.ready:
        return const HomeShell();
    }
  }
}
