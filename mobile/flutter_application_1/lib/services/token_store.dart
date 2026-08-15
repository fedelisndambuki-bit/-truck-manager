import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TokenStore {
  static const _storage = FlutterSecureStorage();
  static const _key = 'driver_token';

  Future<void> save(String token) async {
    await _storage.write(key: _key, value: token);
  }

  Future<String?> read() async {
    try {
      return await _storage.read(key: _key);
    } catch (_) {
      await _storage.deleteAll();
      return null;
    }
  }

  Future<void> clear() async {
    await _storage.delete(key: _key);
  }
}
