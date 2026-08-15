import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart' as http_parser;
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_base_url_store.dart';
import 'driver_profile_store.dart';
import 'token_store.dart';

import 'package:device_info_plus/device_info_plus.dart';
import 'package:open_filex/open_filex.dart';
import 'package:permission_handler/permission_handler.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({required this.tokenStore});

  final TokenStore tokenStore;
  final DriverProfileStore _profileStore = DriverProfileStore();
  final ApiBaseUrlStore _baseUrlStore = ApiBaseUrlStore();
  static const String _defaultOrigin = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://192.168.4.102:8001',
  );
  String? _cachedOrigin;
  String? _cachedBaseUrl;

  Future<String> getConfiguredOrigin() async {
    if (_cachedOrigin != null && _cachedOrigin!.isNotEmpty) {
      return _cachedOrigin!;
    }
    final stored = await _baseUrlStore.read();
    if (stored != null && stored.trim().isNotEmpty) {
      _cachedOrigin = stored.trim();
      return _cachedOrigin!;
    }
    _cachedOrigin = _defaultOrigin;
    return _cachedOrigin!;
  }

  Future<void> setConfiguredOrigin(String origin) async {
    final value = origin.trim().isEmpty ? _defaultOrigin : origin.trim();
    await _baseUrlStore.save(value);
    _cachedOrigin = value;
    _cachedBaseUrl = _buildBaseUrlFromOrigin(value);
  }

  Future<String> _resolveBaseUrl() async {
    if (_cachedBaseUrl != null && _cachedBaseUrl!.isNotEmpty) {
      return _cachedBaseUrl!;
    }
    final origin = await getConfiguredOrigin();
    _cachedBaseUrl = _buildBaseUrlFromOrigin(origin);
    return _cachedBaseUrl!;
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    final baseUrl = await _resolveBaseUrl();
    http.Response response;

    try {
      response = await http
          .post(
            Uri.parse('$baseUrl/login'),
            headers: {'Accept': 'application/json'},
            body: {'email': email, 'password': password},
          )
          .timeout(const Duration(seconds: 30));
    } on SocketException {
      throw ApiException(
        'Cannot reach server at $baseUrl. Check network and API URL.',
      );
    }

    if (response.statusCode != 200) {
      final message = _extractMessage(response) ?? 'Login failed.';
      throw ApiException(message, statusCode: response.statusCode);
    }

    final payload = jsonDecode(response.body) as Map<String, dynamic>;
    final token = payload['token'] as String?;
    if (token == null || token.isEmpty) {
      throw ApiException('Login response did not include a token.');
    }

    await tokenStore.save(token);
    final profile = payload['profile'];
    if (profile is Map<String, dynamic>) {
      await _profileStore.save(profile);
    } else if (profile is Map) {
      await _profileStore.save(Map<String, dynamic>.from(profile));
    }
    return payload;
  }

  Future<void> logout() async {
    try {
      await _post('/logout');
    } catch (e) {
      // Ignore server-side logout failures when signing out locally.
    } finally {
      await tokenStore.clear();
      await _profileStore.clear();
    }
  }

  Future<List<dynamic>> fetchTrips() async {
    try {
      final payload = await _get('/trips');
      final data = payload['data'] as List<dynamic>;
      await _cacheTrips(data);
      return data;
    } on ApiException catch (e) {
      if (e.statusCode == null) {
        final cached = await _readCachedTrips();
        if (cached.isNotEmpty) {
          return cached;
        }
      }
      rethrow;
    }
  }

  Future<Map<String, dynamic>> fetchPerformance() async {
    return _get('/performance');
  }

  Future<Map<String, dynamic>> fetchAiFeedback() async {
    return _get('/ai-feedback');
  }

  Future<void> acceptTrip(int assignmentId) async {
    await _post('/assignments/$assignmentId/accept');
  }

  Future<void> declineTrip(int assignmentId, String reason) async {
    await _post('/assignments/$assignmentId/decline', body: {'reason': reason});
  }

  Future<Map<String, dynamic>> fetchAlerts() async {
    return _get('/alerts');
  }

  Future<Map<String, dynamic>> fetchPaymentSummary() async {
    final payload = await _get('/payments/summary');
    return payload['data'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> fetchDriverProfile() async {
    final payload = await _get('/profile');
    final data = payload['data'];
    final map = data is Map
        ? Map<String, dynamic>.from(data)
        : <String, dynamic>{};

    final profile = map['profile'];
    if (profile is Map<String, dynamic>) {
      await _profileStore.save(profile);
    } else if (profile is Map) {
      await _profileStore.save(Map<String, dynamic>.from(profile));
    }

    return map;
  }

  Future<List<Map<String, dynamic>>> fetchChatMessages() async {
    final payload = await _get('/chat/messages');
    final data = payload['data'] as List<dynamic>? ?? const [];
    return data
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  Future<int> fetchChatUnreadCount() async {
    final payload = await _get('/chat/unread-count');
    final data = payload['data'] as Map<String, dynamic>? ?? const {};
    final raw = data['unread_count'];
    if (raw is int) {
      return raw;
    }
    return int.tryParse('$raw') ?? 0;
  }

  Future<Map<String, dynamic>> requestWithdrawal(double amount) async {
    final baseUrl = await _resolveBaseUrl();
    final token = await tokenStore.read();
    
    final response = await http.post(
      Uri.parse('$baseUrl/payments/withdraw'),
      headers: {
        ..._headers(token),
        'Content-Type': 'application/json',
      },
      body: jsonEncode({'amount': amount}),
    ).timeout(const Duration(seconds: 30));

    if (response.statusCode >= 400) {
      final message = _extractMessage(response) ?? 'Withdrawal request failed.';
      throw ApiException(message, statusCode: response.statusCode);
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<void> sendChatMessage(String message) async {
    await _post('/chat/messages', body: {'message': message});
  }

  Future<void> editChatMessage({
    required int messageId,
    required String message,
  }) async {
    await _post(
      '/chat/messages/$messageId/edit',
      body: {'message': message},
    );
  }

  Future<void> deleteChatMessage({
    required int messageId,
    required bool deleteForEveryone,
  }) async {
    await _post(
      '/chat/messages/$messageId/delete',
      body: {'scope': deleteForEveryone ? 'everyone' : 'me'},
    );
  }

  Future<void> sendChatVoiceNote(File voiceNoteFile, {String? message}) async {
    await _sendChatMultipart(
      voiceNoteFile: voiceNoteFile,
      message: message,
    );
  }

  Future<void> sendChatAttachment(File attachmentFile, {String? message}) async {
    await _sendChatMultipart(
      attachmentFile: attachmentFile,
      message: message,
    );
  }

  Future<void> _sendChatMultipart({
    File? voiceNoteFile,
    File? attachmentFile,
    String? message,
  }) async {
    final token = await tokenStore.read();
    final baseUrl = await _resolveBaseUrl();
    final uri = Uri.parse('$baseUrl/chat/messages');

    final request = http.MultipartRequest('POST', uri)..headers.addAll(_headers(token));

    if (voiceNoteFile != null) {
      final extension = voiceNoteFile.path.split('.').last.toLowerCase();
      final contentType = switch (extension) {
        'm4a' => 'audio/mp4',
        'aac' => 'audio/aac',
        'amr' => 'audio/amr',
        'mp3' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'wav' => 'audio/wav',
        'webm' => 'audio/webm',
        _ => 'audio/mp4',
      };
      request.files.add(
        await http.MultipartFile.fromPath(
          'voice_note',
          voiceNoteFile.path,
          contentType: http_parser.MediaType.parse(contentType),
        ),
      );
    }
    if (attachmentFile != null) {
      request.files.add(
        await http.MultipartFile.fromPath('attachment', attachmentFile.path),
      );
    }

    if (message != null && message.trim().isNotEmpty) {
      request.fields['message'] = message.trim();
    }

    http.Response response;
    try {
      final streamed = await request.send().timeout(const Duration(seconds: 30));
      response = await http.Response.fromStream(streamed);
    } on SocketException {
      throw ApiException('Cannot reach server. Check your internet connection.');
    }

    if (response.statusCode >= 400) {
      final msg = _extractMessage(response) ?? 'Upload failed.';
      throw ApiException(msg, statusCode: response.statusCode);
    }
  }

  Future<String> downloadPayslipPdf({required String month}) async {
    final bytes = await fetchPayslipPdfBytes(month: month);
    return savePayslipPdfBytes(month: month, bytes: bytes, share: true);
  }

  Future<Uint8List> fetchPayslipPdfBytes({required String month}) async {
    final baseUrl = await _resolveBaseUrl();
    final token = await tokenStore.read();
    final response = await http
        .get(
          Uri.parse('$baseUrl/payments/payslip?month=$month'),
          headers: _headers(token),
        )
        .timeout(const Duration(seconds: 30));

    if (response.statusCode >= 400) {
      final message =
          _extractMessage(response) ?? 'Failed to download payslip.';
      throw ApiException(message, statusCode: response.statusCode);
    }

    return response.bodyBytes;
  }

  Future<String> savePayslipPdfBytes({
    required String month,
    required Uint8List bytes,
    bool share = false,
  }) async {
    // 1. Handle Permissions
    if (Platform.isAndroid) {
      final androidInfo = await DeviceInfoPlugin().androidInfo;
      if (androidInfo.version.sdkInt <= 32) {
        final status = await Permission.storage.request();
        if (!status.isGranted) {
          throw ApiException('Storage permission is required to save the PDF.');
        }
      }
    }

    // 2. Find Best Directory (Downloads preferred on Android)
    Directory? directory;
    if (Platform.isAndroid) {
      directory = Directory('/storage/emulated/0/Download');
      if (!await directory.exists()) {
        directory = await getExternalStorageDirectory();
      }
    } else {
      directory = await getApplicationDocumentsDirectory();
    }

    if (directory == null) {
      throw ApiException('Could not find a valid storage directory.');
    }

    final filePath = '${directory.path}/payslip-$month.pdf';
    final file = File(filePath);
    await file.writeAsBytes(bytes, flush: true);

    if (share) {
      await Share.shareXFiles(
        [XFile(file.path)],
        text: 'Payslip for $month',
      );
    } else {
      // If not sharing, try to open the file immediately
      await OpenFilex.open(file.path);
    }

    return filePath;
  }

  Future<void> sendLocation({
    required double latitude,
    required double longitude,
    double? speed,
  }) async {
    await _post(
      '/locations',
      body: {
        'latitude': latitude.toString(),
        'longitude': longitude.toString(),
        if (speed != null) 'speed': speed.toString(),
      },
    );
  }

  Future<List<Map<String, dynamic>>> fetchFuelReceipts({int? tripId}) async {
    final query = tripId == null ? '' : '?trip_id=$tripId';
    final payload = await _get('/fuel-receipts$query');
    final data = payload['data'] as List<dynamic>? ?? const [];
    return data
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Future<List<Map<String, dynamic>>> fetchPods({int? tripId}) async {
    final query = tripId == null ? '' : '?trip_id=$tripId';
    final payload = await _get('/pods$query');
    final data = payload['data'] as List<dynamic>? ?? const [];
    return data
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Future<void> uploadFuelReceipt({
    required File receiptFile,
    int? tripId,
    String? vendor,
    double? litres,
    double? amount,
    double? mileage,
  }) async {
    final token = await tokenStore.read();
    final baseUrl = await _resolveBaseUrl();
    final uri = Uri.parse('$baseUrl/fuel-receipts');

    final request = http.MultipartRequest('POST', uri)
      ..headers.addAll(_headers(token))
      ..files.add(
        await http.MultipartFile.fromPath('receipt', receiptFile.path),
      );

    if (tripId != null) {
      request.fields['trip_id'] = tripId.toString();
    }
    if (vendor != null && vendor.trim().isNotEmpty) {
      request.fields['vendor'] = vendor.trim();
    }
    if (litres != null) {
      request.fields['litres'] = litres.toString();
    }
    if (amount != null) {
      request.fields['amount'] = amount.toString();
    }
    if (mileage != null) {
      request.fields['mileage'] = mileage.toString();
    }

    http.Response response;
    try {
      final streamed = await request.send().timeout(
        const Duration(seconds: 30),
      );
      response = await http.Response.fromStream(streamed);
    } on SocketException {
      throw ApiException('Network unavailable. Receipt saved for retry.');
    } on HttpException {
      throw ApiException('Server connection failed. Receipt saved for retry.');
    }

    if (response.statusCode >= 400) {
      final message =
          _extractMessage(response) ?? 'Fuel receipt upload failed.';
      throw ApiException(message, statusCode: response.statusCode);
    }
  }

  Future<void> uploadPod({
    required File podFile,
    required int tripId,
    String? notes,
    String? receiverName,
    String? receiverSignature,
    String? pickupDetails,
    String? dropoffDetails,
    String? documentOcrText,
  }) async {
    final token = await tokenStore.read();
    final baseUrl = await _resolveBaseUrl();
    final uri = Uri.parse('$baseUrl/pods');

    final request = http.MultipartRequest('POST', uri)
      ..headers.addAll(_headers(token))
      ..fields['trip_id'] = tripId.toString()
      ..files.add(await http.MultipartFile.fromPath('image', podFile.path));

    if (notes != null && notes.trim().isNotEmpty) {
      request.fields['notes'] = notes.trim();
    }
    if (receiverName != null && receiverName.trim().isNotEmpty) {
      request.fields['receiver_name'] = receiverName.trim();
    }
    if (receiverSignature != null && receiverSignature.trim().isNotEmpty) {
      request.fields['receiver_signature'] = receiverSignature.trim();
    }
    if (pickupDetails != null && pickupDetails.trim().isNotEmpty) {
      request.fields['pickup_details'] = pickupDetails.trim();
    }
    if (dropoffDetails != null && dropoffDetails.trim().isNotEmpty) {
      request.fields['dropoff_details'] = dropoffDetails.trim();
    }
    if (documentOcrText != null && documentOcrText.trim().isNotEmpty) {
      request.fields['document_ocr_text'] = documentOcrText.trim();
    }

    http.Response response;
    try {
      final streamed = await request.send().timeout(
        const Duration(seconds: 30),
      );
      response = await http.Response.fromStream(streamed);
    } on SocketException {
      throw ApiException('Network unavailable. POD saved for retry.');
    } on HttpException {
      throw ApiException('Server connection failed. POD saved for retry.');
    }

    if (response.statusCode >= 400) {
      final message = _extractMessage(response) ?? 'POD upload failed.';
      throw ApiException(message, statusCode: response.statusCode);
    }
  }

  Future<void> submitPreTripInspection({
    int? tripId,
    required bool brakes,
    required bool lights,
    required bool tires,
    required bool loadSecured,
    String? notes,
  }) async {
    await _post('/pre-trip-inspections', body: {
      if (tripId != null) 'trip_id': tripId.toString(),
      'brakes': brakes ? '1' : '0',
      'lights': lights ? '1' : '0',
      'tires': tires ? '1' : '0',
      'load_secured': loadSecured ? '1' : '0',
      if (notes != null && notes.trim().isNotEmpty) 'notes': notes.trim(),
    });
  }

  Future<Map<String, dynamic>?> fetchLatestPreTripInspection() async {
    final payload = await _get('/pre-trip-inspections/latest');
    final data = payload['data'];
    if (data is Map) {
      return Map<String, dynamic>.from(data);
    }
    return null;
  }

  Future<void> triggerSos({
    int? tripId,
    String? message,
    double? latitude,
    double? longitude,
  }) async {
    await _post('/emergency/sos', body: {
      if (tripId != null) 'trip_id': tripId.toString(),
      if (message != null && message.trim().isNotEmpty) 'message': message.trim(),
      if (latitude != null) 'latitude': latitude.toString(),
      if (longitude != null) 'longitude': longitude.toString(),
    });
  }

  Future<void> reportIncident({
    int? tripId,
    required String type,
    String? severity,
    String? description,
    File? photo,
    double? latitude,
    double? longitude,
  }) async {
    final token = await tokenStore.read();
    final baseUrl = await _resolveBaseUrl();
    final uri = Uri.parse('$baseUrl/incidents');

    final request = http.MultipartRequest('POST', uri)
      ..headers.addAll(_headers(token))
      ..fields['type'] = type;

    if (tripId != null) {
      request.fields['trip_id'] = tripId.toString();
    }
    if (severity != null && severity.trim().isNotEmpty) {
      request.fields['severity'] = severity.trim();
    }
    if (description != null && description.trim().isNotEmpty) {
      request.fields['description'] = description.trim();
    }
    if (latitude != null) {
      request.fields['latitude'] = latitude.toString();
    }
    if (longitude != null) {
      request.fields['longitude'] = longitude.toString();
    }
    if (photo != null && await photo.exists()) {
      request.files.add(await http.MultipartFile.fromPath('photo', photo.path));
    }

    http.Response response;
    try {
      final streamed = await request.send().timeout(const Duration(seconds: 30));
      response = await http.Response.fromStream(streamed);
    } on SocketException {
      throw ApiException('Cannot reach server. Check your internet connection.');
    }

    if (response.statusCode >= 400) {
      final msg = _extractMessage(response) ?? 'Incident report failed.';
      throw ApiException(msg, statusCode: response.statusCode);
    }
  }

  Future<Map<String, dynamic>> fetchTyreData() async {
    return _get('/tyres');
  }

  Future<void> installTyre({
    required int tyreId,
    required int truckId,
    required String position,
  }) async {
    await _post('/tyres/install', body: {
      'tyre_id': tyreId.toString(),
      'truck_id': truckId.toString(),
      'position': position,
    });
  }

  Future<void> removeTyre({required int tyreId}) async {
    await _post('/tyres/remove', body: {
      'tyre_id': tyreId.toString(),
    });
  }

  Future<void> discardTyre({required int tyreId}) async {
    await _post('/tyres/discard', body: {
      'tyre_id': tyreId.toString(),
    });
  }

  Future<void> triggerSOS({
    int? tripId,
    String? message,
    double? latitude,
    double? longitude,
  }) async {
    await _post('/emergency/sos', body: {
      if (tripId != null) 'trip_id': tripId.toString(),
      if (message != null && message.trim().isNotEmpty) 'message': message.trim(),
      if (latitude != null) 'latitude': latitude.toString(),
      if (longitude != null) 'longitude': longitude.toString(),
    });
  }

  Future<Map<String, dynamic>> _get(String path) async {
    final baseUrl = await _resolveBaseUrl();
    final token = await tokenStore.read();
    print('ApiClient GET: $baseUrl$path');
    http.Response response;
    try {
      response = await http
          .get(Uri.parse('$baseUrl$path'), headers: _headers(token))
          .timeout(const Duration(seconds: 30));
      print('ApiClient Response [${response.statusCode}]');
    } on SocketException {
      print('ApiClient SocketException: $baseUrl$path');
      throw ApiException(
        'Cannot reach server. Check your internet connection.',
      );
    } on TimeoutException {
      print('ApiClient TimeoutException: $baseUrl$path');
      throw ApiException(
        'Connection timed out. The server is taking too long to respond.',
      );
    }

    if (response.statusCode >= 400) {
      final message = _extractMessage(response) ?? 'Request failed.';
      throw ApiException(message, statusCode: response.statusCode);
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<void> _post(String path, {Map<String, String>? body}) async {
    final baseUrl = await _resolveBaseUrl();
    final token = await tokenStore.read();
    http.Response response;
    try {
      response = await http
          .post(
            Uri.parse('$baseUrl$path'),
            headers: _headers(token),
            body: body,
          )
          .timeout(const Duration(seconds: 30));
    } on SocketException {
      throw ApiException(
        'Cannot reach server. Check your internet connection.',
      );
    } on TimeoutException {
      throw ApiException(
        'Connection timed out. The server is taking too long to respond.',
      );
    }

    if (response.statusCode >= 400) {
      final message = _extractMessage(response) ?? 'Request failed.';
      throw ApiException(message, statusCode: response.statusCode);
    }
  }

  Map<String, String> _headers(String? token) {
    return {
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  String? _extractMessage(http.Response response) {
    try {
      final payload = jsonDecode(response.body);
      if (payload is Map<String, dynamic>) {
        final message = payload['message'];
        if (message is String && message.trim().isNotEmpty) {
          return message.trim();
        }
      }
    } catch (_) {
      // Ignore malformed/non-json response bodies.
    }
    return null;
  }

  static String _buildBaseUrlFromOrigin(String configuredOrigin) {
    final origin = configuredOrigin.trim().replaceAll(RegExp(r'\/+$'), '');

    if (origin.endsWith('/api/driver')) {
      return origin;
    }

    if (origin.endsWith('/api')) {
      return '$origin/driver';
    }

    return '$origin/api/driver';
  }

  static bool _isLocalOnlyOrigin(String originValue) {
    final uri = Uri.tryParse(originValue.trim());
    final host = (uri?.host ?? '').toLowerCase();
    return host == '127.0.0.1' || host == 'localhost' || host == '10.0.2.2';
  }

  Future<void> _cacheTrips(List<dynamic> data) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('driver_cached_trips_v1', jsonEncode(data));
  }

  Future<List<dynamic>> _readCachedTrips() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('driver_cached_trips_v1');
    if (raw == null || raw.isEmpty) {
      return const [];
    }
    try {
      final decoded = jsonDecode(raw);
      if (decoded is List) {
        return decoded;
      }
    } catch (_) {}
    return const [];
  }
}
