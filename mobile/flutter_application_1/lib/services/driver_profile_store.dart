import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class DriverProfileStore {
  static const _storage = FlutterSecureStorage();
  static const _key = 'driver_profile_v1';

  Future<void> save(Map<String, dynamic> profile) async {
    await _storage.write(key: _key, value: jsonEncode(profile));
  }

  Future<Map<String, dynamic>?> read() async {
    final raw = await _storage.read(key: _key);
    if (raw == null || raw.isEmpty) {
      return null;
    }

    try {
      return Map<String, dynamic>.from(jsonDecode(raw) as Map);
    } catch (_) {
      return null;
    }
  }

  Future<void> clear() async {
    await _storage.delete(key: _key);
  }
}
