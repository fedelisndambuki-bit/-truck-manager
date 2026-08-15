import 'dart:io';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:signature/signature.dart';
import '../models/trip_assignment.dart';
import '../services/api_client.dart';
import '../services/receipt_ocr_service.dart';
import '../services/upload_queue_service.dart';
import '../theme/app_colors.dart';
import '../widgets/driver_header_menu.dart';

class TripDetailScreen extends StatefulWidget {
  const TripDetailScreen({
    super.key,
    required this.assignment,
    required this.apiClient,
  });

  final TripAssignment assignment;
  final ApiClient apiClient;

  @override
  State<TripDetailScreen> createState() => _TripDetailScreenState();
}

class _TripDetailScreenState extends State<TripDetailScreen> {
  final ImagePicker _imagePicker = ImagePicker();
  final ReceiptOcrService _ocrService = ReceiptOcrService();
  final UploadQueueService _queueService = UploadQueueService();

  bool _uploadingFuelReceipt = false;
  bool _uploadingPod = false;
  bool _retryingQueue = false;
  bool _loadingHistory = true;
  String? _historyError;

  int _queuedCount = 0;
  List<Map<String, dynamic>> _fuelHistory = [];
  List<Map<String, dynamic>> _podHistory = [];

  int? get _tripId => _extractTripId(widget.assignment.trip);

  @override
  void initState() {
    super.initState();
    _refreshAll();
  }

  Future<void> _refreshAll() async {
    await Future.wait([
      _loadQueueCount(),
      _loadHistory(),
    ]);
  }

  Future<void> _loadQueueCount() async {
    final count = await _queueService.queuedCount();
    if (mounted) {
      setState(() => _queuedCount = count);
    }
  }

  Future<void> _loadHistory() async {
    setState(() => _loadingHistory = true);
    try {
      final fuel = await widget.apiClient.fetchFuelReceipts(tripId: _tripId);
      final pods = await widget.apiClient.fetchPods(tripId: _tripId);
      if (mounted) {
        setState(() {
          _fuelHistory = fuel;
          _podHistory = pods;
          _historyError = null;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _historyError = e is ApiException ? e.message : 'Failed to load history.';
        });
      }
    } finally {
      if (mounted) {
        setState(() => _loadingHistory = false);
      }
    }
  }

  Future<void> _retryQueuedUploads({bool showSnackbar = true}) async {
    setState(() => _retryingQueue = true);
    try {
      final uploaded = await _queueService.retryQueuedUploads(widget.apiClient);
      await _refreshAll();
      if (showSnackbar && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Uploaded $uploaded item(s) from queue.')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _retryingQueue = false);
      }
    }
  }

  Future<void> _handleUploadFuel() async {
    final source = await _chooseImageSource();
    if (source == null) return;

    final picked = await _imagePicker.pickImage(source: source, imageQuality: 80, maxWidth: 1920);
    if (picked == null) return;

    setState(() => _uploadingFuelReceipt = true);
    final imageFile = File(picked.path);
    
    ReceiptOcrResult ocrResult = const ReceiptOcrResult();
    try { ocrResult = await _ocrService.extract(imageFile); } catch (_) {}

    final metadata = await _collectFuelMetadata(ocrResult);
    if (metadata == null) {
      setState(() => _uploadingFuelReceipt = false);
      return;
    }

    try {
      await widget.apiClient.uploadFuelReceipt(
        receiptFile: imageFile,
        tripId: _tripId,
        vendor: metadata.vendor,
        litres: metadata.litres,
        amount: metadata.amount,
        mileage: metadata.mileage,
      );
      await _refreshAll();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Fuel receipt uploaded.')));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Upload failed. Queued for retry.')));
      await _queueService.enqueueFuelReceipt(
        filePath: imageFile.path,
        tripId: _tripId,
        vendor: metadata.vendor,
        litres: metadata.litres,
        amount: metadata.amount,
        mileage: metadata.mileage,
      );
      _loadQueueCount();
    } finally {
      if (mounted) setState(() => _uploadingFuelReceipt = false);
    }
  }

  Future<void> _handleUploadPod() async {
    if (_tripId == null) return;
    final source = await _chooseImageSource();
    if (source == null) return;

    final picked = await _imagePicker.pickImage(source: source, imageQuality: 80, maxWidth: 1920);
    if (picked == null) return;

    ReceiptOcrResult podOcr = const ReceiptOcrResult();
    try { podOcr = await _ocrService.extract(File(picked.path)); } catch (_) {}

    final podMetadata = await _collectPodMetadata(podOcr);
    if (podMetadata == null) return;

    setState(() => _uploadingPod = true);
    final imageFile = File(picked.path);

    try {
      await widget.apiClient.uploadPod(
        podFile: imageFile,
        tripId: _tripId!,
        notes: podMetadata.notes,
        receiverName: podMetadata.receiverName,
        receiverSignature: podMetadata.receiverSignature,
        pickupDetails: podMetadata.pickupDetails,
        dropoffDetails: podMetadata.dropoffDetails,
        documentOcrText: podMetadata.documentOcrText,
      );
      await _refreshAll();
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('POD uploaded.')));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Upload failed. Queued for retry.')));
      await _queueService.enqueuePod(
        filePath: imageFile.path,
        tripId: _tripId!,
        notes: podMetadata.notes,
        receiverName: podMetadata.receiverName,
        receiverSignature: podMetadata.receiverSignature,
        pickupDetails: podMetadata.pickupDetails,
        dropoffDetails: podMetadata.dropoffDetails,
        documentOcrText: podMetadata.documentOcrText,
      );
      _loadQueueCount();
    } finally {
      if (mounted) setState(() => _uploadingPod = false);
    }
  }

  Future<void> _handleAccept() async {
    try {
      await widget.apiClient.acceptTrip(widget.assignment.assignmentId);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Trip accepted.')));
        Navigator.pop(context, true); 
      }
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }

  Future<void> _handleDecline() async {
    final reasonController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    final shouldDecline = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.report_problem, color: Colors.red),
            SizedBox(width: 10),
            Text('Decline Trip'),
          ],
        ),
        content: Form(
          key: formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Please provide a reason for declining.', style: TextStyle(fontSize: 13, color: Colors.grey)),
              const SizedBox(height: 16),
              TextFormField(
                controller: reasonController,
                decoration: InputDecoration(
                  labelText: 'Decline Reason',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: AppColors.darkSlateGrey, width: 2),
                  ),
                ),
                validator: (v) => v == null || v.trim().isEmpty ? 'Please provide a reason' : null,
                maxLines: 3,
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel', style: TextStyle(color: Colors.grey))),
          ElevatedButton(
            onPressed: () {
              if (formKey.currentState?.validate() ?? false) {
                Navigator.pop(context, true);
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.darkSlateGrey,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Confirm Decline'),
          ),
        ],
      ),
    );

    if (shouldDecline == true) {
      try {
        await widget.apiClient.declineTrip(widget.assignment.assignmentId, reasonController.text.trim());
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Trip declined.')));
          Navigator.pop(context, true);
        }
      } catch (e) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final trip = widget.assignment.trip;
    final origin = trip['route_from'] ?? '-';
    final destination = trip['route_to'] ?? '-';
    final status = widget.assignment.status.toUpperCase();
    
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text('Trip Detail'),
        actions: const [DriverHeaderMenu()],
      ),
      body: RefreshIndicator(
        onRefresh: _refreshAll,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(color: AppColors.darkSlateGrey, borderRadius: BorderRadius.circular(20)),
                  child: Text(status, style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                ),
                Text('ID: #${trip['id'] ?? '??'}', style: const TextStyle(color: Colors.grey, fontWeight: FontWeight.bold, fontSize: 12)),
              ],
            ),
            const SizedBox(height: 20),
            _buildRouteVisual(origin, destination),
            const SizedBox(height: 24),
            
            if (status == 'PENDING') ...[
              Row(
                children: [
                  Expanded(child: ElevatedButton(
                    onPressed: _handleAccept,
                    style: ElevatedButton.styleFrom(backgroundColor: AppColors.darkSlateGrey, foregroundColor: Colors.white, padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                    child: const Text('Accept Trip', style: TextStyle(fontWeight: FontWeight.bold)),
                  )),
                  const SizedBox(width: 12),
                  Expanded(child: OutlinedButton(
                    onPressed: _handleDecline,
                    style: OutlinedButton.styleFrom(foregroundColor: Colors.red, side: const BorderSide(color: Colors.red), padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                    child: const Text('Decline', style: TextStyle(fontWeight: FontWeight.bold)),
                  )),
                ],
              ),
              const SizedBox(height: 24),
            ],

            GridView.count(
              crossAxisCount: 2, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: 2.8,
              children: [
                _infoBox(Icons.inventory_2, 'Cargo Type', trip['cargo_type'] ?? 'N/A'),
                _infoBox(Icons.scale, 'Weight', '${trip['cargo_weight'] ?? '0'} T'),
                _infoBox(Icons.schedule, 'Departure', _formatTime(trip['departure_time'])),
                _infoBox(Icons.timer, 'Expected', _formatTime(trip['expected_arrival'])),
              ],
            ),
            const SizedBox(height: 24),
            if (trip['delay_prediction'] != null) _buildDelayPrediction(trip['delay_prediction'] as Map),
            const SizedBox(height: 24),

            Text('Quick Actions', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            _actionButton(icon: Icons.location_on, label: 'Update Live Location', onPressed: () async {
              await widget.apiClient.sendLocation(latitude: 0, longitude: 0, speed: 0);
              if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Location shared.')));
            }),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: _actionButton(icon: Icons.upload_file, label: 'Upload POD', color: Colors.blueGrey.shade800, onPressed: _uploadingPod ? null : _handleUploadPod, loading: _uploadingPod)),
              const SizedBox(width: 10),
              Expanded(child: _actionButton(icon: Icons.local_gas_station, label: 'Fuel Receipt', color: Colors.blueGrey.shade800, onPressed: _uploadingFuelReceipt ? null : _handleUploadFuel, loading: _uploadingFuelReceipt)),
            ]),
            if (_queuedCount > 0) Padding(padding: const EdgeInsets.only(top: 16), child: _buildQueueStatus()),
            const SizedBox(height: 32),
            _buildHistoryList('Fuel Receipt History', _fuelHistory, Icons.receipt_long),
            const SizedBox(height: 20),
            _buildHistoryList('POD History', _podHistory, Icons.inventory_2_outlined),
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }

  Widget _buildRouteVisual(String from, String to) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), border: Border.all(color: const Color(0xFFE2E8F0))),
      child: Column(children: [
        _routePoint(Icons.location_on, Colors.blue, 'Pickup Location', from),
        Padding(padding: const EdgeInsets.only(left: 11, top: 4, bottom: 4), child: Align(alignment: Alignment.centerLeft, child: Container(width: 2, height: 30, color: const Color(0xFFE2E8F0)))),
        _routePoint(Icons.flag, Colors.redAccent, 'Delivery Destination', to),
      ]),
    );
  }

  Widget _routePoint(IconData icon, Color color, String label, String value) {
    return Row(children: [
      Icon(icon, color: color, size: 24),
      const SizedBox(width: 16),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label, style: const TextStyle(fontSize: 10, color: Colors.grey, fontWeight: FontWeight.bold)),
        Text(value, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF1E293B))),
      ])),
    ]);
  }

  Widget _infoBox(IconData icon, String label, String value) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFE2E8F0))),
      child: Row(children: [
        Icon(icon, size: 18, color: AppColors.darkSlateGrey),
        const SizedBox(width: 10),
        Expanded(child: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(label, style: const TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold)),
          Text(value, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold), overflow: TextOverflow.ellipsis),
        ])),
      ]),
    );
  }

  Widget _buildDelayPrediction(Map data) {
    final risk = data['risk'] ?? 'low';
    final message = data['message'] ?? '-';
    final color = risk == 'high' ? Colors.red : (risk == 'medium' ? Colors.orange : Colors.green);
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: color.withOpacity(0.08), borderRadius: BorderRadius.circular(16), border: Border.all(color: color.withOpacity(0.3))),
      child: Row(children: [
        Icon(Icons.auto_graph, color: color),
        const SizedBox(width: 12),
        Expanded(child: Text(message, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 13))),
      ]),
    );
  }

  Widget _actionButton({required IconData icon, required String label, Color? color, VoidCallback? onPressed, bool loading = false}) {
    return SizedBox(height: 50, width: double.infinity, child: ElevatedButton.icon(
      onPressed: onPressed,
      style: ElevatedButton.styleFrom(backgroundColor: color ?? AppColors.darkSlateGrey, foregroundColor: Colors.white, elevation: 0, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
      icon: loading ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Icon(icon, size: 20),
      label: Text(label, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
    ));
  }

  Widget _buildQueueStatus() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Colors.amber.shade50, borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.amber.shade200)),
      child: Row(children: [
        const Icon(Icons.cloud_off, color: Colors.amber, size: 20),
        const SizedBox(width: 12),
        Expanded(child: Text('$_queuedCount items pending sync', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
        TextButton(onPressed: _retryingQueue ? null : () => _retryQueuedUploads(), child: const Text('SYNC NOW')),
      ]),
    );
  }

  Widget _buildHistoryList(String title, List<Map<String, dynamic>> items, IconData icon) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
      const SizedBox(height: 10),
      if (_loadingHistory) const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()))
      else if (items.isEmpty) Container(width: double.infinity, padding: const EdgeInsets.all(20), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFE2E8F0))), child: const Text('No records found.', style: TextStyle(color: Colors.grey, fontSize: 12)))
      else ...items.take(5).map((item) => _historyItem(item, icon)),
    ]);
  }

  Widget _historyItem(Map<String, dynamic> item, IconData icon) {
    final status = (item['status'] as String? ?? 'pending').toLowerCase();
    final color = status == 'approved' ? Colors.green : (status == 'failed' ? Colors.red : Colors.orange);
    return Container(
      margin: const EdgeInsets.only(bottom: 8), padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0xFFE2E8F0))),
      child: Row(children: [
        CircleAvatar(backgroundColor: AppColors.darkSlateGrey.withOpacity(0.1), radius: 18, child: Icon(icon, size: 18, color: AppColors.darkSlateGrey)),
        const SizedBox(width: 12),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Submission #${item['id']}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
          Text(item['created_at']?.toString().split('T').first ?? '', style: const TextStyle(fontSize: 10, color: Colors.grey)),
        ])),
        Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4), decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(8)), child: Text(status.toUpperCase(), style: TextStyle(color: color, fontSize: 9, fontWeight: FontWeight.bold))),
      ]),
    );
  }

  Future<ImageSource?> _chooseImageSource() async {
    return showModalBottomSheet<ImageSource>(context: context, builder: (context) => SafeArea(child: Column(mainAxisSize: MainAxisSize.min, children: [
      ListTile(leading: const Icon(Icons.camera_alt), title: const Text('Take Photo'), onTap: () => Navigator.pop(context, ImageSource.camera)),
      ListTile(leading: const Icon(Icons.photo_library), title: const Text('Choose from Gallery'), onTap: () => Navigator.pop(context, ImageSource.gallery)),
    ])));
  }

  Future<_FuelMetadata?> _collectFuelMetadata(ReceiptOcrResult ocrResult) async {
    final v = TextEditingController(text: ocrResult.vendor); final l = TextEditingController(text: ocrResult.litres?.toString()); final a = TextEditingController(text: ocrResult.amount?.toString()); final m = TextEditingController();
    return showDialog<_FuelMetadata>(context: context, builder: (context) => AlertDialog(title: const Text('Fuel Details'), content: SingleChildScrollView(child: Column(mainAxisSize: MainAxisSize.min, children: [
      TextField(controller: v, decoration: const InputDecoration(labelText: 'Vendor')),
      TextField(controller: l, decoration: const InputDecoration(labelText: 'Litres'), keyboardType: TextInputType.number),
      TextField(controller: a, decoration: const InputDecoration(labelText: 'Amount'), keyboardType: TextInputType.number),
      TextField(controller: m, decoration: const InputDecoration(labelText: 'Current Mileage'), keyboardType: TextInputType.number),
    ])), actions: [
      TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
      ElevatedButton(onPressed: () => Navigator.pop(context, _FuelMetadata(vendor: v.text, litres: double.tryParse(l.text), amount: double.tryParse(a.text), mileage: double.tryParse(m.text))), child: const Text('Upload')),
    ]));
  }

  Future<_PodMetadata?> _collectPodMetadata(ReceiptOcrResult ocrResult) async {
    final r = TextEditingController();
    final n = TextEditingController();
    final SignatureController sigController = SignatureController(
      penStrokeWidth: 3,
      penColor: Colors.black,
      exportBackgroundColor: Colors.white,
    );

    return showModalBottomSheet<_PodMetadata>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        height: MediaQuery.of(context).size.height * 0.85,
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
        ),
        child: Column(
          children: [
            Container(
              margin: const EdgeInsets.only(top: 12, bottom: 8),
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'POD Details',
                      style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 24),
                    TextField(
                      controller: r,
                      decoration: InputDecoration(
                        labelText: 'Receiver Name',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        prefixIcon: const Icon(Icons.person_outline),
                      ),
                    ),
                    const SizedBox(height: 20),
                    TextField(
                      controller: n,
                      maxLines: 2,
                      decoration: InputDecoration(
                        labelText: 'Notes',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        prefixIcon: const Icon(Icons.notes),
                      ),
                    ),
                    const SizedBox(height: 24),
                    const Text(
                      'Receiver Signature',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Colors.blueGrey),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      decoration: BoxDecoration(
                        border: Border.all(color: Colors.grey.shade300),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Signature(
                          controller: sigController,
                          height: 200,
                          backgroundColor: Colors.grey.shade50,
                        ),
                      ),
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        TextButton.icon(
                          onPressed: () => sigController.clear(),
                          icon: const Icon(Icons.clear, size: 18),
                          label: const Text('Clear Signature'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 32),
                    SizedBox(
                      width: double.infinity,
                      height: 56,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          elevation: 0,
                        ),
                        onPressed: () async {
                          String? signatureBase64;
                          if (sigController.isNotEmpty) {
                            final export = await sigController.toPngBytes();
                            if (export != null) {
                              signatureBase64 = 'data:image/png;base64,${base64Encode(export)}';
                            }
                          }
                          
                          if (mounted) {
                            Navigator.pop(context, _PodMetadata(
                              receiverName: r.text,
                              receiverSignature: signatureBase64,
                              notes: n.text,
                              pickupDetails: widget.assignment.trip['route_from'],
                              dropoffDetails: widget.assignment.trip['route_to'],
                              documentOcrText: ocrResult.rawText
                            ));
                          }
                        },
                        child: const Text('Confirm & Upload', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: TextButton(
                        onPressed: () => Navigator.pop(context),
                        child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatTime(dynamic val) {
    if (val == null) return 'N/A';
    try { final d = DateTime.parse(val.toString()); return '${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}'; } catch (_) { return val.toString(); }
  }

  int? _extractTripId(Map<String, dynamic> trip) {
    final raw = trip['id']; if (raw is int) return raw; if (raw is String) return int.tryParse(raw); return null;
  }
}

class _FuelMetadata {
  const _FuelMetadata({this.vendor, this.litres, this.amount, this.mileage});
  final String? vendor; final double? litres; final double? amount; final double? mileage;
}

class _PodMetadata {
  const _PodMetadata({this.notes, this.receiverName, this.receiverSignature, this.pickupDetails, this.dropoffDetails, this.documentOcrText});
  final String? notes; final String? receiverName; final String? receiverSignature; final String? pickupDetails; final String? dropoffDetails; final String? documentOcrText;
}
