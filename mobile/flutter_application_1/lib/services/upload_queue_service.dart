import 'dart:convert';
import 'dart:io';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_client.dart';

class UploadQueueService {
  static const String _queueKey = 'driver_upload_queue_v1';

  Future<void> enqueueFuelReceipt({
    required String filePath,
    int? tripId,
    String? vendor,
    double? litres,
    double? amount,
    double? mileage,
  }) async {
    final items = await _readQueue();
    items.add({
      'type': 'fuel_receipt',
      'file_path': filePath,
      'trip_id': tripId,
      'vendor': vendor,
      'litres': litres,
      'amount': amount,
      'mileage': mileage,
      'created_at': DateTime.now().toIso8601String(),
    });
    await _writeQueue(items);
  }

  Future<void> enqueuePod({
    required String filePath,
    required int tripId,
    String? notes,
    String? receiverName,
    String? receiverSignature,
    String? pickupDetails,
    String? dropoffDetails,
    String? documentOcrText,
  }) async {
    final items = await _readQueue();
    items.add({
      'type': 'pod',
      'file_path': filePath,
      'trip_id': tripId,
      'notes': notes,
      'receiver_name': receiverName,
      'receiver_signature': receiverSignature,
      'pickup_details': pickupDetails,
      'dropoff_details': dropoffDetails,
      'document_ocr_text': documentOcrText,
      'created_at': DateTime.now().toIso8601String(),
    });
    await _writeQueue(items);
  }

  Future<void> enqueueLocation({
    required double latitude,
    required double longitude,
    double? speed,
  }) async {
    final items = await _readQueue();
    items.add({
      'type': 'location',
      'latitude': latitude,
      'longitude': longitude,
      'speed': speed,
      'created_at': DateTime.now().toIso8601String(),
    });
    await _writeQueue(items);
  }

  Future<int> queuedCount() async {
    final items = await _readQueue();
    return items.length;
  }

  Future<int> retryQueuedUploads(ApiClient apiClient) async {
    final items = await _readQueue();
    if (items.isEmpty) {
      return 0;
    }

    final remaining = <Map<String, dynamic>>[];
    var successCount = 0;

    for (final item in items) {
      final filePath = item['file_path'] as String?;
      final type = item['type'] as String?;
      if (type != 'location' && (filePath == null || filePath.isEmpty || !File(filePath).existsSync())) {
        // Drop broken queue entries that reference missing files.
        continue;
      }

      try {
        if (type == 'fuel_receipt') {
          final uploadPath = filePath!;
          await apiClient.uploadFuelReceipt(
            receiptFile: File(uploadPath),
            tripId: _asInt(item['trip_id']),
            vendor: item['vendor'] as String?,
            litres: _asDouble(item['litres']),
            amount: _asDouble(item['amount']),
            mileage: _asDouble(item['mileage']),
          );
          successCount++;
          continue;
        }

        if (type == 'pod') {
          final uploadPath = filePath!;
          final tripId = _asInt(item['trip_id']);
          if (tripId == null) {
            continue;
          }

          await apiClient.uploadPod(
            podFile: File(uploadPath),
            tripId: tripId,
            notes: item['notes'] as String?,
            receiverName: item['receiver_name'] as String?,
            receiverSignature: item['receiver_signature'] as String?,
            pickupDetails: item['pickup_details'] as String?,
            dropoffDetails: item['dropoff_details'] as String?,
            documentOcrText: item['document_ocr_text'] as String?,
          );
          successCount++;
          continue;
        }

        if (type == 'location') {
          final latitude = _asDouble(item['latitude']);
          final longitude = _asDouble(item['longitude']);
          if (latitude == null || longitude == null) {
            continue;
          }

          await apiClient.sendLocation(
            latitude: latitude,
            longitude: longitude,
            speed: _asDouble(item['speed']),
          );
          successCount++;
          continue;
        }

        remaining.add(item);
      } on ApiException catch (e) {
        // Keep for later only on retryable transport errors.
        if (e.statusCode == null) {
          remaining.add(item);
        }
      } catch (_) {
        remaining.add(item);
      }
    }

    await _writeQueue(remaining);
    return successCount;
  }

  Future<List<Map<String, dynamic>>> _readQueue() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_queueKey);
    if (raw == null || raw.isEmpty) {
      return <Map<String, dynamic>>[];
    }

    try {
      final decoded = jsonDecode(raw) as List<dynamic>;
      return decoded
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    } catch (_) {
      return <Map<String, dynamic>>[];
    }
  }

  Future<void> _writeQueue(List<Map<String, dynamic>> items) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_queueKey, jsonEncode(items));
  }

  int? _asInt(dynamic value) {
    if (value is int) {
      return value;
    }
    if (value is String) {
      return int.tryParse(value);
    }
    return null;
  }

  double? _asDouble(dynamic value) {
    if (value is double) {
      return value;
    }
    if (value is int) {
      return value.toDouble();
    }
    if (value is String) {
      return double.tryParse(value);
    }
    return null;
  }
}
