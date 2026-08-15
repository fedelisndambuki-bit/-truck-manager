import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class CredentialStore {
  static const _storage = FlutterSecureStorage();
  static const _emailKey = 'driver_login_email';
  static const _passwordKey = 'driver_login_password';

  Future<void> save({
    required String email,
    required String password,
  }) async {
    await _storage.write(key: _emailKey, value: email);
    await _storage.write(key: _passwordKey, value: password);
  }

  Future<Map<String, String>?> read() async {
    try {
      final email = await _storage.read(key: _emailKey);
      final password = await _storage.read(key: _passwordKey);
      if (email == null || email.isEmpty || password == null || password.isEmpty) {
        return null;
      }
      return {'email': email, 'password': password};
    } catch (_) {
      await _storage.deleteAll();
      return null;
    }
  }
}
