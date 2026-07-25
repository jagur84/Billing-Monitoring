import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/session_user.dart';
import '../services/api_client.dart';

enum SessionStatus { loading, needsBaseUrl, needsLogin, ready }

class SessionProvider extends ChangeNotifier {
  static const _kBaseUrl = 'base_url';
  static const _kToken = 'token';

  SessionStatus status = SessionStatus.loading;
  String? baseUrl;
  String? token;
  SessionUser? user;
  String appName = 'Modal Nekad';
  String? appLogoUrl;
  String? errorMessage;

  ApiClient get client => ApiClient(baseUrl: baseUrl ?? '', token: token);

  Future<void> bootstrap() async {
    final prefs = await SharedPreferences.getInstance();
    baseUrl = prefs.getString(_kBaseUrl);
    token = prefs.getString(_kToken);

    if (baseUrl == null || baseUrl!.isEmpty) {
      status = SessionStatus.needsBaseUrl;
      notifyListeners();
      return;
    }

    if (token == null || token!.isEmpty) {
      status = SessionStatus.needsLogin;
      notifyListeners();
      return;
    }

    await _fetchMe();
  }

  Future<bool> saveBaseUrl(String url) async {
    var normalized = url.trim();
    if (normalized.endsWith('/')) {
      normalized = normalized.substring(0, normalized.length - 1);
    }
    if (!normalized.startsWith('http://') && !normalized.startsWith('https://')) {
      normalized = 'https://$normalized';
    }

    // Verify the URL actually points at a reachable instance of this app
    // before persisting it, so a typo doesn't get silently saved.
    try {
      await ApiClient(baseUrl: normalized).get('/reports/revenue');
    } on ApiException catch (e) {
      // 401/403 means the server responded correctly (auth required) - that's fine.
      if (e.statusCode != 401 && e.statusCode != 403) {
        errorMessage = 'Tidak dapat terhubung ke server tersebut.';
        notifyListeners();
        return false;
      }
    } catch (_) {
      errorMessage = 'Tidak dapat terhubung ke server tersebut. Periksa alamat URL.';
      notifyListeners();
      return false;
    }

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_kBaseUrl, normalized);
    baseUrl = normalized;
    errorMessage = null;
    status = SessionStatus.needsLogin;
    notifyListeners();
    return true;
  }

  Future<bool> login(String email, String password) async {
    try {
      final result = await client.post('/login', body: {
        'email': email,
        'password': password,
        'device_name': 'android-mobile',
      });

      final prefs = await SharedPreferences.getInstance();
      token = result['token'];
      await prefs.setString(_kToken, token!);

      user = SessionUser.fromJson(result['user']);
      appName = result['app']?['name'] ?? appName;
      appLogoUrl = result['app']?['logo_url'];

      status = SessionStatus.ready;
      errorMessage = null;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      errorMessage = e.message;
      notifyListeners();
      return false;
    } catch (_) {
      errorMessage = 'Tidak dapat terhubung ke server.';
      notifyListeners();
      return false;
    }
  }

  Future<void> _fetchMe() async {
    try {
      final result = await client.get('/me');
      user = SessionUser.fromJson(result['user']);
      appName = result['app']?['name'] ?? appName;
      appLogoUrl = result['app']?['logo_url'];
      status = SessionStatus.ready;
    } on ApiException catch (e) {
      if (e.statusCode == 401) {
        await _clearToken();
        status = SessionStatus.needsLogin;
      } else {
        errorMessage = e.message;
        status = SessionStatus.needsLogin;
      }
    } catch (_) {
      errorMessage = 'Tidak dapat terhubung ke server.';
      status = SessionStatus.needsLogin;
    }
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await client.post('/logout');
    } catch (_) {
      // Ignore network errors on logout; clear local session regardless.
    }
    await _clearToken();
    user = null;
    status = SessionStatus.needsLogin;
    notifyListeners();
  }

  Future<void> changeServer() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_kBaseUrl);
    await _clearToken();
    baseUrl = null;
    user = null;
    errorMessage = null;
    status = SessionStatus.needsBaseUrl;
    notifyListeners();
  }

  Future<void> _clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_kToken);
    token = null;
  }
}
