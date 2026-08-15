import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiBaseUrlStore {
  static const _storage = FlutterSecureStorage();
  static const _key = 'driver_api_base_url';

  Future<void> save(String origin) async {
    await _storage.write(key: _key, value: origin);
  }

  Future<String?> read() async {
    try {
      return await _storage.read(key: _key);
    } catch (_) {
      await _storage.deleteAll();
      return null;
    }
  }
}
