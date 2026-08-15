import 'dart:io';
import 'package:google_mlkit_text_recognition/google_mlkit_text_recognition.dart';

class ReceiptOcrResult {
  const ReceiptOcrResult({
    this.vendor,
    this.litres,
    this.amount,
    this.capturedAt,
    this.rawText,
  });

  final String? vendor;
  final double? litres;
  final double? amount;
  final DateTime? capturedAt;
  final String? rawText;
}

class ReceiptOcrService {
  Future<ReceiptOcrResult> extract(File imageFile) async {
    final recognizer = TextRecognizer(script: TextRecognitionScript.latin);

    try {
      final input = InputImage.fromFile(imageFile);
      final recognized = await recognizer.processImage(input);
      final lines = recognized.text
          .split(RegExp(r'[\r\n]+'))
          .map((e) => e.trim())
          .where((e) => e.isNotEmpty)
          .toList();

      final vendor = _detectVendor(lines);
      final litres = _detectLitres(recognized.text);
      final amount = _detectAmount(recognized.text);
      final capturedAt = _detectDate(recognized.text);

      return ReceiptOcrResult(
        vendor: vendor,
        litres: litres,
        amount: amount,
        capturedAt: capturedAt,
        rawText: recognized.text.trim().isEmpty ? null : recognized.text.trim(),
      );
    } finally {
      await recognizer.close();
    }
  }

  String? _detectVendor(List<String> lines) {
    if (lines.isEmpty) {
      return null;
    }

    // Most receipts have vendor/store name near the first lines.
    for (final line in lines.take(5)) {
      if (line.length >= 3 && RegExp(r'[A-Za-z]').hasMatch(line)) {
        return line.length > 60 ? line.substring(0, 60) : line;
      }
    }
    return null;
  }

  double? _detectLitres(String text) {
    final litresRegex = RegExp(r'(\d{1,4}(?:[.,]\d{1,3})?)\s*(L|litre|litres)', caseSensitive: false);
    final match = litresRegex.firstMatch(text);
    if (match == null) {
      return null;
    }
    return _toDouble(match.group(1));
  }

  double? _detectAmount(String text) {
    final normalized = text.replaceAll(',', '.');
    final moneyRegex = RegExp(r'(total|amount|kes|ksh|usd)?\s*[:\-]?\s*(\d{2,7}(?:\.\d{1,2})?)', caseSensitive: false);
    final matches = moneyRegex.allMatches(normalized).toList();
    if (matches.isEmpty) {
      return null;
    }

    // Use the highest positive candidate, usually grand total.
    double? best;
    for (final m in matches) {
      final value = _toDouble(m.group(2));
      if (value == null || value <= 0) {
        continue;
      }
      if (best == null || value > best) {
        best = value;
      }
    }

    return best;
  }

  DateTime? _detectDate(String text) {
    final yyyyMmDd = RegExp(r'(\d{4})[-/](\d{1,2})[-/](\d{1,2})');
    final ddMmYyyy = RegExp(r'(\d{1,2})[-/](\d{1,2})[-/](\d{4})');

    final m1 = yyyyMmDd.firstMatch(text);
    if (m1 != null) {
      return _safeDate(
        int.tryParse(m1.group(1) ?? ''),
        int.tryParse(m1.group(2) ?? ''),
        int.tryParse(m1.group(3) ?? ''),
      );
    }

    final m2 = ddMmYyyy.firstMatch(text);
    if (m2 != null) {
      return _safeDate(
        int.tryParse(m2.group(3) ?? ''),
        int.tryParse(m2.group(2) ?? ''),
        int.tryParse(m2.group(1) ?? ''),
      );
    }

    return null;
  }

  DateTime? _safeDate(int? year, int? month, int? day) {
    if (year == null || month == null || day == null) {
      return null;
    }

    if (year < 2000 || year > 2100 || month < 1 || month > 12 || day < 1 || day > 31) {
      return null;
    }

    try {
      return DateTime(year, month, day);
    } catch (_) {
      return null;
    }
  }

  double? _toDouble(String? value) {
    if (value == null || value.isEmpty) {
      return null;
    }
    return double.tryParse(value.replaceAll(',', '.'));
  }
}
