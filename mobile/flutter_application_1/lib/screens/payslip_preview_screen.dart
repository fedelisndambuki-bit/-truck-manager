import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:printing/printing.dart';
import '../services/api_client.dart';
import '../theme/app_colors.dart';

class PayslipPreviewScreen extends StatefulWidget {
  const PayslipPreviewScreen({
    super.key,
    required this.apiClient,
    required this.month,
  });

  final ApiClient apiClient;
  final String month;

  @override
  State<PayslipPreviewScreen> createState() => _PayslipPreviewScreenState();
}

class _PayslipPreviewScreenState extends State<PayslipPreviewScreen> {
  Uint8List? _pdfBytes;
  bool _loading = true;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadPdf();
  }

  Future<void> _loadPdf() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final bytes = await widget.apiClient.fetchPayslipPdfBytes(
        month: widget.month,
      );
      if (!mounted) {
        return;
      }
      setState(() => _pdfBytes = bytes);
    } on ApiException catch (e) {
      if (!mounted) {
        return;
      }
      setState(() => _error = e.message);
    } catch (_) {
      if (!mounted) {
        return;
      }
      setState(() => _error = 'Failed to preview payslip.');
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _download() async {
    final bytes = _pdfBytes;
    if (bytes == null || _saving) {
      return;
    }

    setState(() => _saving = true);
    try {
      final path = await widget.apiClient.savePayslipPdfBytes(
        month: widget.month,
        bytes: bytes,
        share: true,
      );
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('Payslip downloaded: $path')));
    } on ApiException catch (e) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (_) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Failed to download payslip.')),
      );
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Payslip Preview (${widget.month})'),
        actions: [
          IconButton(
            onPressed: _saving ? null : _download,
            icon: _saving
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.download_rounded),
            tooltip: 'Download',
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : (_error != null)
          ? Center(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Text(
                  _error!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.danger),
                ),
              ),
            )
          : PdfPreview(
              build: (format) async => _pdfBytes!,
              canChangePageFormat: false,
              canChangeOrientation: false,
              canDebug: false,
              allowSharing: false,
              allowPrinting: false,
            ),
    );
  }
}
