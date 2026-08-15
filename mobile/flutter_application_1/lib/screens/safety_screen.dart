import 'dart:io';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_client.dart';
import '../theme/app_colors.dart';
import '../widgets/driver_header_menu.dart';

class SafetyScreen extends StatefulWidget {
  const SafetyScreen({super.key, required this.apiClient});

  final ApiClient apiClient;

  @override
  State<SafetyScreen> createState() => _SafetyScreenState();
}

class _SafetyScreenState extends State<SafetyScreen> {
  final _inspectionForm = GlobalKey<FormState>();
  final _inspectionNotes = TextEditingController();
  final _incidentDescription = TextEditingController();
  final _incidentType = TextEditingController(text: 'Accident');
  bool _brakes = false;
  bool _lights = false;
  bool _tires = false;
  bool _loadSecured = false;
  bool _submittingInspection = false;
  bool _triggeringSos = false;
  bool _reportingIncident = false;
  File? _incidentPhoto;
  Map<String, dynamic>? _latestInspection;

  @override
  void initState() {
    super.initState();
    _loadLatestInspection();
  }

  @override
  void dispose() {
    _inspectionNotes.dispose();
    _incidentDescription.dispose();
    _incidentType.dispose();
    super.dispose();
  }

  Future<void> _loadLatestInspection() async {
    try {
      final latest = await widget.apiClient.fetchLatestPreTripInspection();
      if (!mounted) return;
      setState(() => _latestInspection = latest);
    } catch (_) {}
  }

  Future<void> _submitInspection() async {
    if (!(_inspectionForm.currentState?.validate() ?? false)) {
      return;
    }
    setState(() => _submittingInspection = true);
    try {
      await widget.apiClient.submitPreTripInspection(
        brakes: _brakes,
        lights: _lights,
        tires: _tires,
        loadSecured: _loadSecured,
        notes: _inspectionNotes.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pre-trip inspection submitted.')),
      );
      await _loadLatestInspection();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _submittingInspection = false);
    }
  }

  Future<Position?> _safePosition() async {
    try {
      final enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) return null;
      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
        return null;
      }
      return await Geolocator.getCurrentPosition();
    } catch (_) {
      return null;
    }
  }

  Future<void> _triggerSos() async {
    setState(() => _triggeringSos = true);
    try {
      final position = await _safePosition();
      await widget.apiClient.triggerSos(
        message: 'SOS from driver mobile app.',
        latitude: position?.latitude,
        longitude: position?.longitude,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('SOS sent to control room.')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _triggeringSos = false);
    }
  }

  Future<void> _pickIncidentPhoto() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.camera, imageQuality: 80);
    if (picked == null) return;
    setState(() => _incidentPhoto = File(picked.path));
  }

  Future<void> _reportIncident() async {
    setState(() => _reportingIncident = true);
    try {
      final position = await _safePosition();
      await widget.apiClient.reportIncident(
        type: _incidentType.text.trim().isEmpty ? 'Accident' : _incidentType.text.trim(),
        severity: 'high',
        description: _incidentDescription.text.trim(),
        photo: _incidentPhoto,
        latitude: position?.latitude,
        longitude: position?.longitude,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Accident report submitted.')),
      );
      setState(() {
        _incidentPhoto = null;
        _incidentDescription.clear();
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _reportingIncident = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Safety & Inspection'),
        actions: const [DriverHeaderMenu()],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            color: AppColors.darkSlateGrey,
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Emergency',
                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 16),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Use SOS for immediate control room assistance with live location sharing.',
                    style: TextStyle(color: Colors.white70),
                  ),
                  const SizedBox(height: 10),
                  ElevatedButton.icon(
                    onPressed: _triggeringSos ? null : _triggerSos,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red.shade700,
                      foregroundColor: Colors.white,
                    ),
                    icon: const Icon(Icons.sos),
                    label: const Text('SOS'),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Form(
                key: _inspectionForm,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Pre-Trip Inspection Form',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                    ),
                    CheckboxListTile(
                      contentPadding: EdgeInsets.zero,
                      value: _brakes,
                      onChanged: (v) => setState(() => _brakes = v ?? false),
                      title: const Text('Brakes'),
                    ),
                    CheckboxListTile(
                      contentPadding: EdgeInsets.zero,
                      value: _lights,
                      onChanged: (v) => setState(() => _lights = v ?? false),
                      title: const Text('Lights'),
                    ),
                    CheckboxListTile(
                      contentPadding: EdgeInsets.zero,
                      value: _tires,
                      onChanged: (v) => setState(() => _tires = v ?? false),
                      title: const Text('Tires'),
                    ),
                    CheckboxListTile(
                      contentPadding: EdgeInsets.zero,
                      value: _loadSecured,
                      onChanged: (v) => setState(() => _loadSecured = v ?? false),
                      title: const Text('Load secured'),
                    ),
                    TextFormField(
                      controller: _inspectionNotes,
                      maxLines: 2,
                      decoration: const InputDecoration(labelText: 'Notes (optional)'),
                    ),
                    const SizedBox(height: 10),
                    ElevatedButton.icon(
                      onPressed: _submittingInspection ? null : _submitInspection,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.darkSlateGrey,
                        foregroundColor: Colors.white,
                        visualDensity: VisualDensity.compact,
                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                      ),
                      icon: const Icon(Icons.assignment_turned_in_outlined),
                      label: const Text('Submit to Admin'),
                    ),
                    if (_latestInspection != null) ...[
                      const SizedBox(height: 8),
                      Text(
                        'Last submitted: ${_latestInspection!['submitted_at'] ?? '-'}',
                        style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Accident Reporting',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _incidentType,
                    decoration: const InputDecoration(labelText: 'Incident Type'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _incidentDescription,
                    decoration: const InputDecoration(labelText: 'Description'),
                    maxLines: 3,
                  ),
                  const SizedBox(height: 16),
                  if (_incidentPhoto != null)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.file(
                          _incidentPhoto!,
                          height: 150,
                          width: double.infinity,
                          fit: BoxFit.cover,
                        ),
                      ),
                    ),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _pickIncidentPhoto,
                          icon: const Icon(Icons.camera_alt_outlined),
                          label: Text(_incidentPhoto == null ? 'Take Photo' : 'Retake Photo'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: ElevatedButton.icon(
                          onPressed: _reportingIncident ? null : _reportIncident,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.darkSlateGrey,
                            foregroundColor: Colors.white,
                          ),
                          icon: _reportingIncident 
                            ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : const Icon(Icons.report_problem_outlined),
                          label: const Text('Submit Report'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
